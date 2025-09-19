@extends('layouts.app')

@section('content')
<div x-data="cart()" x-init="init()">
    @if(session('payment_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: '🎉 Payment Successful!',
                    html: `
                        <div class="text-center">
                            <p class="text-lg mb-4">{{ session("success_message", "Your order has been processed successfully. Thank you for your purchase!") }}</p>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                                <p class="text-green-800 font-semibold">Order Confirmed</p>
                                <p class="text-green-600">You will receive an email confirmation shortly.</p>
                            </div>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'Continue Shopping',
                    confirmButtonColor: '#10b981',
                    cancelButtonText: 'View Orders',
                    showCancelButton: true,
                    cancelButtonColor: '#6b7280',
                    width: '500px',
                    padding: '2rem',
                    backdrop: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showCloseButton: false,
                    customClass: {
                        popup: 'swal2-popup-large',
                        title: 'swal2-title-large',
                        content: 'swal2-content-large'
                    }
                }).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel) {
                        // Redirect to orders page if it exists, otherwise stay on cart
                        window.location.href = '/orders';
                    }
                });
            });
        </script>
        
        <style>
            .swal2-popup-large {
                font-size: 1.1rem !important;
            }
            .swal2-title-large {
                font-size: 2rem !important;
                margin-bottom: 1rem !important;
            }
            .swal2-content-large {
                font-size: 1.1rem !important;
            }
        </style>
    @endif
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
            <p class="mt-2 text-gray-600">Review your items and proceed to checkout</p>
        </div>

        <!-- Cart Content -->
        <div x-show="items.length > 0">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Cart Items</h2>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            <template x-for="item in items" :key="item.id">
                                <div class="p-6">
                                    <div class="flex items-center space-x-4">
                                        <!-- Product Image -->
                                        <div class="flex-shrink-0">
                                            <img :src="item.image" 
                                                 :alt="item.name"
                                                 class="w-20 h-20 object-cover rounded-lg">
                                        </div>
                                        
                                        <!-- Product Details -->
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-medium text-gray-900" x-text="item.name"></h3>
                                            <p class="text-sm text-gray-500" x-text="item.description"></p>
                                            <p class="text-lg font-semibold text-indigo-600 mt-1" x-text="'$' + item.price"></p>
                                        </div>
                                        
                                        <!-- Quantity Controls -->
                                        <div class="flex items-center space-x-2">
                                            <button @click="updateQuantity(item.id, item.quantity - 1)"
                                                    class="p-1 rounded-full hover:bg-gray-100">
                                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                </svg>
                                            </button>
                                            <span class="w-8 text-center font-medium" x-text="item.quantity"></span>
                                            <button @click="updateQuantity(item.id, item.quantity + 1)"
                                                    class="p-1 rounded-full hover:bg-gray-100">
                                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <!-- Item Total -->
                                        <div class="text-right">
                                            <p class="text-lg font-semibold text-gray-900" x-text="'$' + (item.price * item.quantity).toFixed(2)"></p>
                                        </div>
                                        
                                        <!-- Remove Button -->
                                        <button @click="removeFromCart(item.id)"
                                                class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-full">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Continue Shopping -->
                    <div class="mt-6">
                        <a href="{{ route('home') }}" 
                           class="inline-flex items-center text-indigo-600 hover:text-indigo-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Continue Shopping
                        </a>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 sticky top-24">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Order Summary</h2>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <!-- Subtotal -->
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium" x-text="'$' + getTotal().toFixed(2)"></span>
                            </div>
                            
                            <!-- Shipping -->
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium" x-text="getTotal() > 50 ? 'Free' : '$9.99'"></span>
                            </div>
                            
                            <!-- Tax -->
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax</span>
                                <span class="font-medium" x-text="'$' + (getTotal() * 0.08).toFixed(2)"></span>
                            </div>
                            
                            <div class="border-t pt-4">
                                <div class="flex justify-between text-lg font-semibold">
                                    <span>Total</span>
                                    <span x-text="'$' + getGrandTotal().toFixed(2)"></span>
                                </div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <a href="{{ route('checkout.index') }}" 
                               class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300 text-center block">
                                Proceed to Checkout
                            </a>
                            
                            <!-- Security Notice -->
                            <div class="text-center">
                                <div class="flex items-center justify-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Secure checkout with Stripe
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Empty Cart State -->
        <div x-show="items.length === 0" class="text-center py-16">
            <div class="max-w-md mx-auto">
                <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Your cart is empty</h2>
                <p class="text-gray-600 mb-8">Looks like you haven't added any items to your cart yet.</p>
                <a href="{{ route('home') }}" 
                   class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300">
                    Start Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cart', () => ({
        items: [],
        cartCount: 0,
        
        async init() {
            await this.loadCart();

            // Clear cart if redirected from successful payment
            @if(session('payment_success'))
                await this.clearCart();
                @if(session('order_number'))
                    this.showNotification('Order Number: {{ session('order_number') }}');
                @endif
            @endif
            
            // Check for stock error messages
            @if(session('error'))
                this.showStockError('{{ session('error') }}');
            @endif
            
            @if(session('out_of_stock_items'))
                const outOfStockItems = @json(session('out_of_stock_items'));
                const itemNames = outOfStockItems.map(item => item.name).join(', ');
                this.showStockError(`The following items were removed from your cart: ${itemNames}`);
            @endif
        },
        
        async loadCart() {
            try {
                const response = await fetch('/cart/data');
                if (response.ok) {
                    const data = await response.json();
                    this.items = data.cart_items;
                } else {
                    // Fallback to localStorage for guests
                    const cart = localStorage.getItem('cart');
                    if (cart) {
                        this.items = JSON.parse(cart);
                    }
                }
                this.updateCartCount();
            } catch (error) {
                console.error('Error loading cart:', error);
                // Fallback to localStorage
                const cart = localStorage.getItem('cart');
                if (cart) {
                    this.items = JSON.parse(cart);
                }
                this.updateCartCount();
            }
        },
        
    async updateQuantity(productId, quantity) {
        try {
            const response = await fetch('/cart/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.items = data.cart_items;
                this.updateCartCount();
                
                // Dispatch cart updated event for layout
                window.dispatchEvent(new CustomEvent('cartUpdated'));
            } else {
                // Handle stock issues
                if (data.error_type === 'stock_issue') {
                    this.showStockError(data.error);
                    // Remove the item from cart if out of stock
                    if (quantity > 0) {
                        this.removeFromCart(productId);
                    }
                } else {
                    throw new Error(data.error || 'Failed to update quantity');
                }
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
            // Fallback to local update
            const item = this.items.find(item => item.id === productId);
            if (item) {
                if (quantity <= 0) {
                    this.removeFromCart(productId);
                } else {
                    item.quantity = quantity;
                    this.saveCart();
                    this.updateCartCount();
                }
            }
        }
    },
        
        async removeFromCart(productId) {
            try {
                const response = await fetch('/cart/remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    this.items = data.cart_items;
                    this.updateCartCount();
                    
                    // Dispatch cart updated event for layout
                    window.dispatchEvent(new CustomEvent('cartUpdated'));
                    
                    this.showNotification('Item removed from cart');
                } else {
                    throw new Error('Failed to remove item');
                }
            } catch (error) {
                console.error('Error removing item:', error);
                // Fallback to local removal
                this.items = this.items.filter(item => item.id !== productId);
                this.saveCart();
                this.updateCartCount();
                this.showNotification('Item removed from cart');
            }
        },
        
        saveCart() {
            localStorage.setItem('cart', JSON.stringify(this.items));
        },
        
        updateCartCount() {
            this.cartCount = this.items.reduce((total, item) => total + item.quantity, 0);
        },
        
        getTotal() {
            return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
        },
        
        getGrandTotal() {
            const subtotal = this.getTotal();
            const shipping = subtotal > 50 ? 0 : 9.99;
            const tax = subtotal * 0.08;
            return subtotal + shipping + tax;
        },
        
        async clearCart() {
            try {
                const response = await fetch('/cart/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    this.items = [];
                    this.updateCartCount();
                    
                    // Dispatch cart updated event for layout
                    window.dispatchEvent(new CustomEvent('cartUpdated'));
                } else {
                    throw new Error('Failed to clear cart');
                }
            } catch (error) {
                console.error('Error clearing cart:', error);
                // Fallback to local clear
                this.items = [];
                this.saveCart();
                this.updateCartCount();
            }
        },
        
        showStockError(message) {
            Swal.fire({
                title: 'Item Out of Stock',
                text: message,
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444'
            });
        },
        
        showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }));
});
</script>
@endsection
