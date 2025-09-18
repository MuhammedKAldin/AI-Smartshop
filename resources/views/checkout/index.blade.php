@extends('layouts.app')

@section('content')
<div x-data="checkout()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
            <p class="mt-2 text-gray-600">Complete your order securely</p>
        </div>

        <div x-show="items.length > 0">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Checkout Form -->
                <div class="lg:col-span-2">
                    <form @submit.prevent="processPayment()">
                        <!-- Shipping Information -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">Shipping Information</h2>
                            </div>
                            
                            <div class="p-6 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                        <input type="text" 
                                               x-model="shippingInfo.firstName"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                               required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                        <input type="text" 
                                               x-model="shippingInfo.lastName"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                               required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" 
                                           x-model="shippingInfo.email"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                           required>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input type="tel" 
                                           x-model="shippingInfo.phone"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                           required>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <input type="text" 
                                           x-model="shippingInfo.address"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                           required>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                        <input type="text" 
                                               x-model="shippingInfo.city"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                               required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                        <input type="text" 
                                               x-model="shippingInfo.state"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                               required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>
                                        <input type="text" 
                                               x-model="shippingInfo.zipCode"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">Payment Information</h2>
                            </div>
                            
                            <div class="p-6">
                                <!-- Stripe Elements will be mounted here -->
                                <div id="card-element" class="mb-4">
                                    <!-- Stripe Elements will create form elements here -->
                                </div>
                                
                                <!-- Display form errors -->
                                <div id="card-errors" role="alert" class="text-red-600 text-sm mb-4"></div>
                                
                                <!-- Place Order Button -->
                                <button type="submit" 
                                        :disabled="processing"
                                        :class="processing ? 'bg-gray-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700'"
                                        class="w-full text-white py-3 px-4 rounded-lg font-semibold transition duration-300">
                                    <span x-show="!processing">Place Order - $<span x-text="getGrandTotal().toFixed(2)"></span></span>
                                    <span x-show="processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 sticky top-24">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Order Summary</h2>
                        </div>
                        
                        <div class="p-6">
                            <!-- Cart Items -->
                            <div class="space-y-4 mb-6">
                                <template x-for="item in items" :key="item.id">
                                    <div class="flex items-center space-x-3">
                                        <img :src="item.image" 
                                             :alt="item.name"
                                             class="w-12 h-12 object-cover rounded">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="item.name"></p>
                                            <p class="text-sm text-gray-500">Qty: <span x-text="item.quantity"></span></p>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900" x-text="'$' + (item.price * item.quantity).toFixed(2)"></p>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Order Totals -->
                            <div class="space-y-2 border-t pt-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span x-text="'$' + getTotal().toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Shipping</span>
                                    <span x-text="getTotal() > 50 ? 'Free' : '$9.99'"></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Tax</span>
                                    <span x-text="'$' + (getTotal() * 0.08).toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between text-lg font-semibold border-t pt-2">
                                    <span>Total</span>
                                    <span x-text="'$' + getGrandTotal().toFixed(2)"></span>
                                </div>
                            </div>
                            
                            <!-- Security Notice -->
                            <div class="mt-6 text-center">
                                <div class="flex items-center justify-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Secure payment with Stripe
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
                <p class="text-gray-600 mb-8">You need to add items to your cart before checkout.</p>
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
    Alpine.data('checkout', () => ({
        items: [],
        cartCount: 0,
        processing: false,
        orderToken: null,
        stripe: null,
        elements: null,
        cardElement: null,
        shippingInfo: {
            firstName: '',
            lastName: '',
            email: '',
            phone: '',
            address: '',
            city: '',
            state: '',
            zipCode: ''
        },
        
        async init() {
            await this.loadCart();
            this.initializeStripe();
            
            // Generate order token for idempotency
            this.orderToken = this.generateOrderToken();
            
            // Validate stock before proceeding
            await this.validateCartStock();
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
        
        async validateCartStock() {
            try {
                const response = await fetch('/cart/validate-stock');
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.has_out_of_stock) {
                        // Show SweetAlert for out of stock items
                        const itemNames = data.out_of_stock_items.map(item => item.name).join(', ');
                        
                        const result = await Swal.fire({
                            icon: 'warning',
                            title: 'Items Out of Stock',
                            html: `The following items are no longer available:<br><strong>${itemNames}</strong><br><br>These items will be removed from your cart.`,
                            confirmButtonText: 'Continue to Cart',
                            confirmButtonColor: '#f59e0b',
                            showCancelButton: true,
                            cancelButtonText: 'Stay on Checkout',
                            cancelButtonColor: '#6b7280'
                        });
                        
                        if (result.isConfirmed) {
                            // Remove out of stock items and redirect to cart
                            await this.removeOutOfStockItems();
                            window.location.href = '/cart';
                        } else {
                            // Remove out of stock items but stay on checkout
                            await this.removeOutOfStockItems();
                        }
                    }
                }
            } catch (error) {
                console.error('Error validating cart stock:', error);
            }
        },
        
        async removeOutOfStockItems() {
            try {
                const response = await fetch('/cart/remove-out-of-stock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.items = data.cart_items;
                    this.updateCartCount();
                    
                    // Dispatch cart updated event for layout
                    window.dispatchEvent(new CustomEvent('cartUpdated'));
                }
            } catch (error) {
                console.error('Error removing out of stock items:', error);
            }
        },
        
        async initializeStripe() {
            // Initialize Stripe
            this.stripe = Stripe('{{ config("stripe.pk") }}');
            this.elements = this.stripe.elements();
            
            // Create card element
            this.cardElement = this.elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#424770',
                        '::placeholder': {
                            color: '#aab7c4',
                        },
                    },
                },
            });
            
            // Mount the card element
            this.cardElement.mount('#card-element');
            
            // Handle real-time validation errors from the card Element
            this.cardElement.on('change', (event) => {
                const displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });
        },
        
        generateOrderToken() {
            const data = {
                cart_items: this.items,
                total_amount: this.getGrandTotal(),
                timestamp: new Date().toISOString().slice(0, 16) // Round to minute
            };
            return 'order_' + btoa(JSON.stringify(data)).replace(/[^a-zA-Z0-9]/g, '').substring(0, 32);
        },
        
        async processPayment() {
            if (this.processing) return;
            
            this.processing = true;
            
            // Disable the submit button to prevent double-clicking
            const submitButton = document.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Processing...';
            }
            
            try {
                // Create payment method
                const {error, paymentMethod} = await this.stripe.createPaymentMethod({
                    type: 'card',
                    card: this.cardElement,
                    billing_details: {
                        name: `${this.shippingInfo.firstName} ${this.shippingInfo.lastName}`,
                        email: this.shippingInfo.email,
                        phone: this.shippingInfo.phone,
                        address: {
                            line1: this.shippingInfo.address,
                            city: this.shippingInfo.city,
                            state: this.shippingInfo.state,
                            postal_code: this.shippingInfo.zipCode,
                        },
                    },
                });
                
                if (error) {
                    throw error;
                }
                
                // Debug: Log the request data
                console.log('Sending checkout request:', {
                    shipping_info: this.shippingInfo,
                    cart_items: this.items,
                    total_amount: this.getGrandTotal()
                });
                
                // Create checkout session
                const response = await fetch('/stripe/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        payment_method_id: paymentMethod.id,
                        shipping_info: this.shippingInfo,
                        cart_items: this.items,
                        total_amount: this.getGrandTotal(),
                        order_token: this.orderToken
                    })
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned invalid response. Please try again.');
                }
                
                const session = await response.json();
                
                if (!response.ok) {
                    const errorData = await response.json();
                    
                    // Handle stock validation error
                    if (errorData.error_type === 'stock_issue') {
                        const itemNames = errorData.out_of_stock_items.map(item => item.name).join(', ');
                        
                        await Swal.fire({
                            icon: 'warning',
                            title: 'Items Out of Stock',
                            html: `The following items are no longer available:<br><strong>${itemNames}</strong><br><br>You will be redirected to your cart to update your order.`,
                            confirmButtonText: 'Go to Cart',
                            confirmButtonColor: '#f59e0b'
                        });
                        
                        // Redirect to cart page
                        window.location.href = '/cart';
                        return;
                    }
                    
                    throw new Error(errorData.error || 'Payment processing failed');
                }
                
                if (session.error) {
                    throw new Error(session.error);
                }
                
                // Redirect to Stripe Checkout
                if (session.url) {
                    window.location.href = session.url;
                } else {
                    // Handle successful payment
                    this.clearCart();
                    this.showNotification('Payment successful! Order confirmed.');
                    // Redirect to success page
                    setTimeout(() => {
                        window.location.href = '/stripe/success';
                    }, 2000);
                }
                
            } catch (error) {
                console.error('Payment error:', error);
                
                // Show error with SweetAlert2
                await Swal.fire({
                    icon: 'error',
                    title: 'Payment Failed',
                    text: error.message || 'An error occurred during payment processing. Please try again.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444'
                });
                
                // Stay on checkout page for user to retry
            } finally {
                this.processing = false;
                
                // Re-enable the submit button
                const submitButton = document.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Place Order';
                }
            }
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
                localStorage.removeItem('cart');
                this.items = [];
                this.updateCartCount();
            }
        },
        
        updateCartCount() {
            this.cartCount = this.items.reduce((total, item) => total + item.quantity, 0);
        },
        
        showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                type === 'error' ? 'bg-red-500 text-white' : 'bg-green-500 text-white'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }));
});
</script>
@endsection
