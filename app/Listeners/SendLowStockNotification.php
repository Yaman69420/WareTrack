<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\StockMovementRegistered;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Queued listener die admins verwittigt zodra een product onder zijn minimum zakt.
 *
 * ShouldQueue is hier essentieel: de check en het mailen draaien op een queue-worker,
 * zodat het request van de magazijnier nooit hoeft te wachten op SMTP-verkeer.
 */
class SendLowStockNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Aparte 'notifications'-queue: mailverkeer wordt zo los van de default-queue
     * verwerkt, en een trage mailserver blokkeert geen andere jobs.
     */
    public string $queue = 'notifications';

    /**
     * Maximaal drie pogingen: een tijdelijke mailserver-hapering krijgt herkansingen,
     * een structureel probleem belandt na drie keer in de failed_jobs-tabel.
     */
    public int $tries = 3;

    /**
     * Controleert na elke mutatie of het product onder zijn minimum zit en mailt dan.
     *
     * fresh() haalt het product opnieuw uit de databank: het model in het event werd
     * geserialiseerd op het moment van dispatchen en kan verouderd zijn tegen de tijd
     * dat de worker dit verwerkt. Zo rekenen we altijd met de stand ná de transactie.
     */
    public function handle(StockMovementRegistered $event): void
    {
        $product = $event->product->fresh(['stock', 'category']);

        // min_stock 0 betekent: geen bewaking gewenst voor dit product.
        if ($product->min_stock <= 0) {
            return;
        }

        // Voorraad op of boven het minimum: niets te melden.
        if (! $product->isBelowMinStock()) {
            return;
        }

        // Throttle: maximaal één melding per product per 24 uur, anders spamt elke
        // volgende uitgifte de admins opnieuw. Cache::add is atomair (zet alleen als de
        // sleutel nog niet bestaat), dus parallelle workers passeren nooit allebei.
        $cacheKey = "low_stock_alert:{$product->id}";

        if (! Cache::add($cacheKey, true, now()->addHours(24))) {
            return;
        }

        $admins = User::where('role', UserRole::Admin)->get();

        foreach ($admins as $admin) {
            // Eén falende mail mag de melding voor de andere admins niet tegenhouden:
            // log de fout en ga verder met de volgende ontvanger.
            try {
                $admin->notify(new LowStockAlert($product));
            } catch (\Throwable $e) {
                Log::error('Failed to send low-stock notification', [
                    'product_id' => $product->id,
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Low-stock notification dispatched', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'current_stock' => $product->totalStock(),
            'min_stock' => $product->min_stock,
            'admins_notified' => $admins->count(),
        ]);
    }
}
