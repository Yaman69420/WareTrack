<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Register incoming stock for a product at a location.
     */
    public function registerIncoming(
        Product $product,
        Location $location,
        int $quantity,
        User $user,
        ?string $reference = null,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($product, $location, $quantity, $user, $reference, $notes) {
            $stock = Stock::lockForUpdate()->firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $location->id],
                ['quantity' => 0]
            );

            $stock->increment('quantity', $quantity);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'user_id' => $user->id,
                'type' => StockMovementType::Incoming,
                'quantity' => $quantity,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            Log::info('Stock incoming registered', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity' => $quantity,
            ]);

            return $movement;
        });
    }

    /**
     * Register outgoing stock for a product at a location.
     *
     * @throws InsufficientStockException
     */
    public function registerOutgoing(
        Product $product,
        Location $location,
        int $quantity,
        User $user,
        ?string $reference = null,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($product, $location, $quantity, $user, $reference, $notes) {
            $stock = Stock::lockForUpdate()
                ->where('product_id', $product->id)
                ->where('location_id', $location->id)
                ->first();

            $available = $stock?->quantity ?? 0;

            if ($available < $quantity) {
                Log::warning('Insufficient stock attempt blocked', [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'requested' => $quantity,
                    'available' => $available,
                ]);

                throw new InsufficientStockException($quantity, $available);
            }

            $stock->decrement('quantity', $quantity);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'user_id' => $user->id,
                'type' => StockMovementType::Outgoing,
                'quantity' => -$quantity,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            Log::info('Stock outgoing registered', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity' => $quantity,
            ]);

            return $movement;
        });
    }

    /**
     * Transfer stock from one location to another.
     *
     * @throws InsufficientStockException
     */
    public function transfer(
        Product $product,
        Location $from,
        Location $to,
        int $quantity,
        User $user,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('From and to locations must be different.');
        }

        return DB::transaction(function () use ($product, $from, $to, $quantity, $user, $notes) {
            $fromStock = Stock::lockForUpdate()
                ->where('product_id', $product->id)
                ->where('location_id', $from->id)
                ->first();

            $available = $fromStock?->quantity ?? 0;

            if ($available < $quantity) {
                Log::warning('Insufficient stock for transfer', [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'from_location_id' => $from->id,
                    'requested' => $quantity,
                    'available' => $available,
                ]);

                throw new InsufficientStockException($quantity, $available);
            }

            $fromStock->decrement('quantity', $quantity);

            $toStock = Stock::lockForUpdate()->firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $to->id],
                ['quantity' => 0]
            );

            $toStock->increment('quantity', $quantity);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'user_id' => $user->id,
                'type' => StockMovementType::Transfer,
                'quantity' => $quantity,
                'notes' => $notes,
            ]);

            Log::info('Stock transfer registered', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'quantity' => $quantity,
            ]);

            return $movement;
        });
    }

    /**
     * Adjust stock to an absolute quantity (correction).
     */
    public function adjust(
        Product $product,
        Location $location,
        int $newQuantity,
        User $user,
        ?string $notes = null,
    ): StockMovement {
        if ($newQuantity < 0) {
            throw new \InvalidArgumentException('New quantity cannot be negative.');
        }

        return DB::transaction(function () use ($product, $location, $newQuantity, $user, $notes) {
            $stock = Stock::lockForUpdate()->firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $location->id],
                ['quantity' => 0]
            );

            $diff = $newQuantity - $stock->quantity;
            $stock->update(['quantity' => $newQuantity]);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'user_id' => $user->id,
                'type' => StockMovementType::Correction,
                'quantity' => $diff,
                'notes' => $notes,
            ]);

            Log::info('Stock correction registered', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'location_id' => $location->id,
                'old_quantity' => $stock->quantity + ($diff * -1),
                'new_quantity' => $newQuantity,
                'diff' => $diff,
            ]);

            return $movement;
        });
    }

    public function getCurrentStock(Product $product, Location $location): int
    {
        return Stock::where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->value('quantity') ?? 0;
    }

    public function getTotalStockForProduct(Product $product): int
    {
        return Stock::where('product_id', $product->id)->sum('quantity');
    }
}
