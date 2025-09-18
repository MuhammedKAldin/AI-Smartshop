<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create an order with idempotency protection.
     * 
     * @param array $orderData
     * @param string $orderToken
     * @return Order
     * @throws \Exception
     */
    public function createOrder(array $orderData, string $orderToken): Order
    {
        return DB::transaction(function () use ($orderData, $orderToken) {
            // Check if order already exists with this token
            $existingOrder = Order::where('order_token', $orderToken)->first();
            
            if ($existingOrder) {
                Log::info('Order already exists with token', [
                    'order_token' => $orderToken,
                    'existing_order_id' => $existingOrder->id
                ]);
                return $existingOrder;
            }
            
            // Create the order
            $order = Order::create([
                'user_id' => $orderData['user_id'],
                'order_number' => $this->generateOrderNumber(),
                'order_token' => $orderToken,
                'status' => $orderData['status'] ?? 'pending',
                'subtotal' => $orderData['subtotal'],
                'tax' => $orderData['tax'] ?? 0,
                'shipping' => $orderData['shipping'] ?? 0,
                'total' => $orderData['total'],
                'stripe_session_id' => $orderData['stripe_session_id'] ?? null,
                'shipping_address' => $orderData['shipping_address'] ?? null,
                'billing_address' => $orderData['billing_address'] ?? null,
            ]);
            
            // Create order items
            foreach ($orderData['items'] as $item) {
                $this->createOrderItem($order, $item);
            }
            
            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_token' => $orderToken,
                'user_id' => $order->user_id
            ]);
            
            return $order;
        });
    }
    
    /**
     * Create an order item and update product stock.
     * 
     * @param Order $order
     * @param array $itemData
     * @return OrderItem
     * @throws \Exception
     */
    private function createOrderItem(Order $order, array $itemData): OrderItem
    {
        $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);
        
        // Check stock availability
        if (!$product->in_stock || $product->stock < $itemData['quantity']) {
            throw new \Exception("Product {$product->name} is out of stock");
        }
        
        // Create order item
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $itemData['product_id'],
            'quantity' => $itemData['quantity'],
            'price' => $itemData['price'],
        ]);
        
        // Update product stock
        $product->decrement('stock', $itemData['quantity']);
        
        // Mark as out of stock if needed
        if ($product->stock <= 0) {
            $product->update(['in_stock' => false]);
        }
        
        return $orderItem;
    }
    
    /**
     * Generate a unique order number.
     * 
     * @return string
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());
        
        return $orderNumber;
    }
    
    /**
     * Generate a unique order token for client-side idempotency.
     * 
     * @param array $orderData
     * @return string
     */
    public function generateOrderToken(array $orderData): string
    {
        $data = [
            'user_id' => $orderData['user_id'],
            'cart_items' => $orderData['items'],
            'total_amount' => $orderData['total'],
            'timestamp' => now()->format('Y-m-d H:i') // Round to minute for idempotency
        ];
        
        return 'order_' . hash('sha256', json_encode($data));
    }
    
    /**
     * Update order status.
     * 
     * @param string $orderToken
     * @param string $status
     * @return Order|null
     */
    public function updateOrderStatus(string $orderToken, string $status): ?Order
    {
        $order = Order::where('order_token', $orderToken)->first();
        
        if ($order) {
            $order->update(['status' => $status]);
            Log::info('Order status updated', [
                'order_id' => $order->id,
                'order_token' => $orderToken,
                'new_status' => $status
            ]);
        }
        
        return $order;
    }
}
