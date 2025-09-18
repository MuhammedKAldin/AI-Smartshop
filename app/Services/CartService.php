<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
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
     * @param int $productId
     * @param int $quantity
     * @return array
     */
    public function addToCart($productId, $quantity = 1)
    {
        $product = Product::findOrFail($productId);
        
        if (Auth::check()) {
            return $this->addToUserCart(Auth::id(), $product, $quantity);
        } else {
            return $this->addToGuestCart($product, $quantity);
        }
    }

    /**
     * Update cart item quantity for current user.
     * 
     * @param int $productId
     * @param int $quantity
     * @return array
     */
    public function updateCartItem($productId, $quantity)
    {
        if (Auth::check()) {
            return $this->updateUserCartItem(Auth::id(), $productId, $quantity);
        } else {
            return $this->updateGuestCartItem($productId, $quantity);
        }
    }

    /**
     * Remove item from cart for current user.
     * 
     * @param int $productId
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
     * @param int $userId
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
                    'quantity' => $existingItem->quantity + $item['quantity']
                ]);
            } else {
                $userCart->cartItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
            }
        }

        // Clear guest cart after sync
        $this->clearGuestCart();
    }

    /**
     * Get user cart items from database.
     * 
     * @param int $userId
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
                    'price' => $item->price,
                    'image' => $item->product->image,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->price * $item->quantity
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
     * @param int $userId
     * @param Product $product
     * @param int $quantity
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
                'quantity' => $existingItem->quantity + $quantity
            ]);
        } else {
            $cart->cartItems()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price
            ]);
        }

        return $this->getUserCartItems($userId);
    }

    /**
     * Add item to guest cart in session.
     * 
     * @param Product $product
     * @param int $quantity
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
                'price' => $product->price,
                'image' => $product->image,
                'quantity' => $quantity
            ];
        }

        Session::put('cart', $cart);
        return $cart;
    }

    /**
     * Update user cart item quantity.
     * 
     * @param int $userId
     * @param int $productId
     * @param int $quantity
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
     * @param int $productId
     * @param int $quantity
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
     * @param int $userId
     * @param int $productId
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
     * @param int $productId
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
     * @param int $userId
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
     * @param int $userId
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
}
