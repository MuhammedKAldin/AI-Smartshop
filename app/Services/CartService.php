<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\StockReservation;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CartService
{
    /**
     * Get cart items for current user (logged-in or guest).
     *
     * @return array
     */
    public function getCartItems()
    {
        if (Auth::check()) {
            return $this->getUserCartItems(Auth::id());
        } else {
            return $this->getGuestCartItems();
        }
    }

    /**
     * Add item to cart for current user.
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @return array
     *
     * @throws Exception
     */
    public function addToCart($productId, $quantity = 1)
    {
        try {
            DB::beginTransaction();

            // Get product with lock to prevent race conditions
            $product = Product::lockForUpdate()->findOrFail($productId);

            // Check if product is in stock
            if (! $product->in_stock || $product->stock <= 0) {
                throw new Exception('Product is out of stock');
            }

            // Check if requested quantity exceeds available stock
            if ($quantity > $product->stock) {
                throw new Exception("Only {$product->stock} items available in stock");
            }

            if (Auth::check()) {
                $result = $this->addToUserCart(Auth::id(), $product, $quantity);
            } else {
                $result = $this->addToGuestCart($product, $quantity);
            }

            DB::commit();

            return $result;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add to cart failed', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update cart item quantity for current user.
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @return array
     *
     * @throws Exception
     */
    public function updateCartItem($productId, $quantity)
    {
        try {
            DB::beginTransaction();

            // Get product with lock to prevent race conditions
            $product = Product::lockForUpdate()->findOrFail($productId);

            // Check if product is still in stock (for non-zero quantities)
            if ($quantity > 0) {
                if (! $product->in_stock || $product->stock <= 0) {
                    throw new Exception('Product is out of stock');
                }

                if ($quantity > $product->stock) {
                    throw new Exception("Only {$product->stock} items available in stock");
                }
            }

            if (Auth::check()) {
                $result = $this->updateUserCartItem(Auth::id(), $productId, $quantity);
            } else {
                $result = $this->updateGuestCartItem($productId, $quantity);
            }

            DB::commit();

            return $result;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update cart item failed', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Remove item from cart for current user.
     *
     * @param  int  $productId
     * @return array
     */
    public function removeFromCart($productId)
    {
        if (Auth::check()) {
            return $this->removeFromUserCart(Auth::id(), $productId);
        } else {
            return $this->removeFromGuestCart($productId);
        }
    }

    /**
     * Clear cart for current user.
     *
     * @return array
     */
    public function clearCart()
    {
        if (Auth::check()) {
            return $this->clearUserCart(Auth::id());
        } else {
            return $this->clearGuestCart();
        }
    }

    /**
     * Sync guest cart to user cart when user logs in.
     *
     * @param  int  $userId
     * @return void
     */
    public function syncGuestCartToUser($userId)
    {
        $guestCart = $this->getGuestCartItems();

        if (empty($guestCart)) {
            return;
        }

        $userCart = $this->getOrCreateUserCart($userId);

        foreach ($guestCart as $item) {
            $existingItem = $userCart->cartItems()
                ->where('product_id', $item['product_id'])
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $item['quantity'],
                ]);
            } else {
                $userCart->cartItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        }

        // Clear guest cart after sync
        $this->clearGuestCart();
    }

    /**
     * Get user cart items from database.
     *
     * @param  int  $userId
     * @return array
     */
    private function getUserCartItems($userId)
    {
        $cart = $this->getOrCreateUserCart($userId);

        return $cart->cartItems()
            ->with('product')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'description' => $item->product->description,
                    'price' => $item->price,
                    'image' => $item->product->image,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->price * $item->quantity,
                ];
            })
            ->toArray();
    }

    /**
     * Get guest cart items from session.
     *
     * @return array
     */
    private function getGuestCartItems()
    {
        return Session::get('cart', []);
    }

    /**
     * Add item to user cart in database.
     *
     * @param  int  $userId
     * @param  Product  $product
     * @param  int  $quantity
     * @return array
     */
    private function addToUserCart($userId, $product, $quantity)
    {
        $cart = $this->getOrCreateUserCart($userId);

        $existingItem = $cart->cartItems()
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
            ]);
        } else {
            $cart->cartItems()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);
        }

        return $this->getUserCartItems($userId);
    }

    /**
     * Add item to guest cart in session.
     *
     * @param  Product  $product
     * @param  int  $quantity
     * @return array
     */
    private function addToGuestCart($product, $quantity)
    {
        $cart = Session::get('cart', []);

        $existingIndex = collect($cart)->search(function ($item) use ($product) {
            return $item['id'] === $product->id;
        });

        if ($existingIndex !== false) {
            $cart[$existingIndex]['quantity'] += $quantity;
        } else {
            $cart[] = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'image' => $product->image,
                'quantity' => $quantity,
            ];
        }

        Session::put('cart', $cart);

        return $cart;
    }

    /**
     * Update user cart item quantity.
     *
     * @param  int  $userId
     * @param  int  $productId
     * @param  int  $quantity
     * @return array
     */
    private function updateUserCartItem($userId, $productId, $quantity)
    {
        $cart = $this->getOrCreateUserCart($userId);

        if ($quantity <= 0) {
            $cart->cartItems()->where('product_id', $productId)->delete();
        } else {
            $cart->cartItems()
                ->where('product_id', $productId)
                ->update(['quantity' => $quantity]);
        }

        return $this->getUserCartItems($userId);
    }

    /**
     * Update guest cart item quantity.
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @return array
     */
    private function updateGuestCartItem($productId, $quantity)
    {
        $cart = Session::get('cart', []);

        $cart = collect($cart)->map(function ($item) use ($productId, $quantity) {
            if ($item['id'] === $productId) {
                $item['quantity'] = $quantity;
            }

            return $item;
        })->filter(function ($item) {
            return $item['quantity'] > 0;
        })->values()->toArray();

        Session::put('cart', $cart);

        return $cart;
    }

    /**
     * Remove item from user cart.
     *
     * @param  int  $userId
     * @param  int  $productId
     * @return array
     */
    private function removeFromUserCart($userId, $productId)
    {
        $cart = $this->getOrCreateUserCart($userId);
        $cart->cartItems()->where('product_id', $productId)->delete();

        return $this->getUserCartItems($userId);
    }

    /**
     * Remove item from guest cart.
     *
     * @param  int  $productId
     * @return array
     */
    private function removeFromGuestCart($productId)
    {
        $cart = Session::get('cart', []);
        $cart = collect($cart)->reject(function ($item) use ($productId) {
            return $item['id'] === $productId;
        })->values()->toArray();

        Session::put('cart', $cart);

        return $cart;
    }

    /**
     * Clear user cart.
     *
     * @param  int  $userId
     * @return array
     */
    private function clearUserCart($userId)
    {
        $cart = $this->getOrCreateUserCart($userId);
        $cart->cartItems()->delete();

        return [];
    }

    /**
     * Clear guest cart.
     *
     * @return array
     */
    private function clearGuestCart()
    {
        Session::forget('cart');

        return [];
    }

    /**
     * Get or create user cart.
     *
     * @param  int  $userId
     * @return Cart
     */
    private function getOrCreateUserCart($userId)
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Get cart total for current user.
     *
     * @return float
     */
    public function getCartTotal()
    {
        $items = $this->getCartItems();

        return collect($items)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    /**
     * Get cart item count for current user.
     *
     * @return int
     */
    public function getCartItemCount()
    {
        $items = $this->getCartItems();

        return collect($items)->sum('quantity');
    }

    /**
     * Validate cart items for checkout (check stock availability with reservations).
     *
     * @return array
     */
    public function validateCartForCheckout()
    {
        $cartItems = $this->getCartItems();
        $outOfStockItems = [];
        $insufficientStockItems = [];

        foreach ($cartItems as $item) {
            $product = Product::find($item['id']);

            if (! $product) {
                $outOfStockItems[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'reason' => 'Product not found',
                ];

                continue;
            }

            // Calculate available stock (total - reserved)
            $reservedQuantity = StockReservation::where('product_id', $item['id'])
                ->where('status', 'active')
                ->where('reserved_until', '>', now())
                ->sum('quantity');

            $availableStock = $product->stock - $reservedQuantity;

            if (! $product->in_stock || $availableStock <= 0) {
                $outOfStockItems[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'reason' => 'Out of stock',
                    'available' => $availableStock,
                ];
            } elseif ($item['quantity'] > $availableStock) {
                $insufficientStockItems[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => $availableStock,
                ];
            }
        }

        return [
            'valid' => empty($outOfStockItems) && empty($insufficientStockItems),
            'out_of_stock' => $outOfStockItems,
            'insufficient_stock' => $insufficientStockItems,
        ];
    }

    /**
     * Remove out of stock items from cart.
     *
     * @param  array  $outOfStockItems
     * @return void
     */
    public function removeOutOfStockItems($outOfStockItems)
    {
        foreach ($outOfStockItems as $item) {
            $this->removeFromCart($item['id']);
        }
    }

    /**
     * Validate cart stock (alias for validateCartForCheckout).
     *
     * @param  array  $cartItems
     * @return array
     */
    public function validateCartStock($cartItems)
    {
        return $this->validateCartForCheckout($cartItems);
    }
}
