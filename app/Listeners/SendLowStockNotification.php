<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\StockMovementRegistered;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendLowStockNotification
{
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

        // Throttle: send at most once per 24 h per product
        $cacheKey = "low_stock_alert:{$product->id}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addHours(24));

        // Notify every admin
        $admins = User::where('role', UserRole::Admin)->get();

        foreach ($admins as $admin) {
            try {
                $admin->notify(new LowStockAlert($product));
            } catch (\Throwable $e) {
                Log::error('Failed to send low-stock notification', [
                    'product_id' => $product->id,
                    'admin_id'   => $admin->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::info('Low-stock notification dispatched', [
            'product_id'    => $product->id,
            'product_name'  => $product->name,
            'current_stock' => $product->totalStock(),
            'min_stock'     => $product->min_stock,
            'admins_notified' => $admins->count(),
        ]);
    }
}
