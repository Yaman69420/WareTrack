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

class SendLowStockNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The queue the listener should be pushed to.
     */
    public string $queue = 'notifications';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Handle the event.
     *
     * Re-fetches stock fresh from DB so we always use the post-transaction
     * quantity (the event's product may still be in-memory from before the update).
     */
    public function handle(StockMovementRegistered $event): void
    {
        $product = $event->product->fresh(['stock', 'category']);

        // Skip if no minimum is configured
        if ($product->min_stock <= 0) {
            return;
        }

        // Skip if stock is at or above minimum
        if (! $product->isBelowMinStock()) {
            return;
        }

        // Throttle: send at most once per 24 h per product.
        // Cache::add is atomic, so parallel workers can't both pass the check.
        $cacheKey = "low_stock_alert:{$product->id}";

        if (! Cache::add($cacheKey, true, now()->addHours(24))) {
            return;
        }

        // Notify every admin
        $admins = User::where('role', UserRole::Admin)->get();

        foreach ($admins as $admin) {
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
