<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\StockReservation;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockReservationService
{
    /**
     * Reserve stock for a product during checkout.
     *
     * @throws Exception
     */
    public function reserveStock(int $productId, int $quantity, string $orderToken, ?int $userId = null, ?string $sessionId = null): ?StockReservation
    {
        return DB::transaction(function () use ($productId, $quantity, $orderToken, $userId, $sessionId) {
            // Lock the product for update to prevent race conditions
            $product = Product::lockForUpdate()->findOrFail($productId);

            // Calculate available stock (total stock - active reservations)
            $reservedQuantity = $this->getReservedQuantity($productId);
            $availableStock = $product->stock - $reservedQuantity;

            // Check if enough stock is available
            if ($availableStock < $quantity) {
                Log::warning('Insufficient stock for reservation', [
                    'product_id' => $productId,
                    'requested_quantity' => $quantity,
                    'available_stock' => $availableStock,
                    'total_stock' => $product->stock,
                    'reserved_quantity' => $reservedQuantity,
                ]);

                throw new Exception("Only {$availableStock} items available in stock");
            }

            // Check if product is still in stock
            if (! $product->in_stock) {
                throw new Exception('Product is out of stock');
            }

            // Create reservation
            $reservation = StockReservation::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'quantity' => $quantity,
                'reserved_until' => now()->addMinutes(15), // 15 minutes reservation
                'status' => 'active',
                'order_token' => $orderToken,
            ]);

            Log::info('Stock reserved successfully', [
                'reservation_id' => $reservation->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'order_token' => $orderToken,
            ]);

            return $reservation;
        });
    }

    /**
     * Reserve stock for multiple products (cart checkout).
     *
     * @throws Exception
     */
    public function reserveCartStock(array $cartItems, string $orderToken, ?int $userId = null, ?string $sessionId = null): array
    {
        $reservations = [];

        try {
            DB::beginTransaction();

            // Reserve stock for each item
            foreach ($cartItems as $item) {
                $reservation = $this->reserveStock(
                    $item['id'],
                    $item['quantity'],
                    $orderToken,
                    $userId,
                    $sessionId
                );

                $reservations[] = $reservation;
            }

            DB::commit();

            return $reservations;

        } catch (Exception $e) {
            DB::rollBack();

            // Cancel any reservations that were created
            $this->cancelReservations($reservations);

            throw $e;
        }
    }

    /**
     * Confirm stock reservation (order created successfully).
     */
    public function confirmReservation(string $orderToken): void
    {
        $reservations = StockReservation::where('order_token', $orderToken)
            ->where('status', 'active')
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->markAsConfirmed();

            // Update product stock
            $product = $reservation->product;
            $product->decrement('stock', $reservation->quantity);

            // Mark as out of stock if needed
            if ($product->stock <= 0) {
                $product->update(['in_stock' => false]);
            }
        }

        Log::info('Stock reservations confirmed', [
            'order_token' => $orderToken,
            'reservations_count' => $reservations->count(),
        ]);
    }

    /**
     * Cancel stock reservations.
     */
    public function cancelReservations(array $reservations): void
    {
        foreach ($reservations as $reservation) {
            // Handle both objects and arrays
            if ($reservation instanceof StockReservation && $reservation->status === 'active') {
                $reservation->markAsCancelled();
            } elseif (is_array($reservation) && isset($reservation['status']) && $reservation['status'] === 'active') {
                // If it's an array, find the model and update it
                $reservationModel = StockReservation::find($reservation['id']);
                if ($reservationModel) {
                    $reservationModel->markAsCancelled();
                }
            }
        }
    }

    /**
     * Cancel reservation by order token.
     */
    public function cancelReservationByToken(string $orderToken): void
    {
        $reservations = StockReservation::where('order_token', $orderToken)
            ->where('status', 'active')
            ->get();

        $this->cancelReservations($reservations->all());
    }

    /**
     * Get total reserved quantity for a product (only unconfirmed reservations).
     */
    public function getReservedQuantity(int $productId): int
    {
        return StockReservation::where('product_id', $productId)
            ->where('status', 'active') // Only active reservations
            ->where('reserved_until', '>', now()) // Not expired
            ->sum('quantity');
    }

    /**
     * Get available stock for a product (total - reserved).
     */
    public function getAvailableStock(int $productId): int
    {
        $product = Product::find($productId);
        if (! $product) {
            return 0;
        }

        $reservedQuantity = $this->getReservedQuantity($productId);

        return max(0, $product->stock - $reservedQuantity);
    }

    /**
     * Clean up expired reservations.
     *
     * @return int Number of expired reservations cleaned up
     */
    public function cleanupExpiredReservations(): int
    {
        $expiredReservations = StockReservation::expired()
            ->where('status', 'active')
            ->get();

        $count = $expiredReservations->count();

        foreach ($expiredReservations as $reservation) {
            $reservation->markAsExpired();
        }

        Log::info('Expired reservations cleaned up', [
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Check if cart items can be reserved.
     */
    public function validateCartReservation(array $cartItems): array
    {
        $unavailableItems = [];

        foreach ($cartItems as $item) {
            $availableStock = $this->getAvailableStock($item['id']);

            if ($availableStock < $item['quantity']) {
                $unavailableItems[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => $availableStock,
                ];
            }
        }

        return [
            'can_reserve' => empty($unavailableItems),
            'unavailable_items' => $unavailableItems,
        ];
    }
}
