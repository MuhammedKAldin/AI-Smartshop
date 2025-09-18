@extends('layouts.app')

@section('content')
<div x-data="cart()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-indigo-600">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('home') }}" class="ml-1 text-gray-700 hover:text-indigo-600 md:ml-2">Products</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-gray-500 md:ml-2" x-text="product.name"></span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Product Details -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
            <!-- Product Images -->
            <div class="space-y-4">
                <div class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-lg overflow-hidden">
                    <img :src="product.image" 
                         :alt="product.name"
                         class="w-full h-96 object-cover">
                </div>
                <div class="grid grid-cols-4 gap-4">
                    <template x-for="(image, index) in product.images" :key="index">
                        <div class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-indigo-500">
                            <img :src="image" 
                                 :alt="product.name"
                                 class="w-full h-20 object-cover"
                                 @click="product.image = image">
                        </div>
                    </template>
                </div>
            </div>

            <!-- Product Info -->
            <div class="space-y-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2" x-text="product.name"></h1>
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="flex items-center">
                            <template x-for="star in 5" :key="star">
                                <svg class="w-5 h-5" :class="star <= product.rating ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </template>
                            <span class="ml-2 text-sm text-gray-600" x-text="product.rating + ' (' + product.reviews + ' reviews)'"></span>
                        </div>
                    </div>
                </div>

                <div class="text-3xl font-bold text-indigo-600" x-text="'$' + product.price"></div>

                <div class="text-gray-700" x-text="product.description"></div>

                <!-- Stock Status -->
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-700">Availability:</span>
                    <span :class="product.inStock ? 'text-green-600' : 'text-red-600'" 
                          class="text-sm font-medium"
                          x-text="product.inStock ? 'In Stock' : 'Out of Stock'"></span>
                </div>

                <!-- Quantity Selector -->
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-gray-700">Quantity:</span>
                    <div class="flex items-center border border-gray-300 rounded-lg">
                        <button @click="quantity = Math.max(1, quantity - 1)"
                                class="px-3 py-2 text-gray-600 hover:text-gray-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </button>
                        <input type="number" 
                               x-model="quantity"
                               min="1"
                               class="w-16 text-center border-0 focus:ring-0"
                               readonly>
                        <button @click="quantity = quantity + 1"
                                class="px-3 py-2 text-gray-600 hover:text-gray-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Add to Cart Button -->
                <div class="space-y-4">
                    <button @click="addToCartWithQuantity()"
                            :disabled="!product.inStock"
                            :class="product.inStock ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-400 cursor-not-allowed'"
                            class="w-full text-white px-6 py-3 rounded-lg font-semibold transition duration-300">
                        <span x-text="product.inStock ? 'Add to Cart' : 'Out of Stock'"></span>
                    </button>
                    
                    <button class="w-full border border-indigo-600 text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-indigo-50 transition duration-300">
                        Add to Wishlist
                    </button>
                </div>

                <!-- Product Features -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Features</h3>
                    <ul class="space-y-2">
                        <template x-for="feature in product.features" :key="feature">
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span x-text="feature"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        <!-- AI Recommendations Section -->
        <div class="border-t pt-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Recommended for You</h2>
                <p class="text-lg text-gray-600">AI-powered suggestions based on this product</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <template x-for="recommendation in recommendations" :key="recommendation.id">
                    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition duration-300 overflow-hidden group cursor-pointer"
                         @click="window.location.href = '/products/' + recommendation.id">
                        <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                            <img :src="recommendation.image" 
                                 :alt="recommendation.name"
                                 class="w-full h-48 object-cover group-hover:scale-105 transition duration-300">
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-indigo-600 transition duration-300" x-text="recommendation.name"></h3>
                            <p class="text-gray-600 text-sm mb-3" x-text="recommendation.description"></p>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-indigo-600" x-text="'$' + recommendation.price"></span>
                                <button @click.stop="addToCart(recommendation)"
                                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-300">
                                    🛒 Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Loading state for recommendations -->
            <div x-show="loadingRecommendations" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-600">Loading recommendations...</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cart', () => ({
        items: [],
        cartCount: 0,
        quantity: 1,
        product: {},
        recommendations: [],
        loadingRecommendations: false,
        
        async init() {
            await this.loadCart();
            this.loadProduct();
            this.loadRecommendations();
            
            // Track product view after product is loaded
            setTimeout(() => {
                if (this.product && this.product.id) {
                    this.trackUserInteraction(this.product.id, 'view');
                }
            }, 100);
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
        
        loadProduct() {
            // Use product from database passed from controller
            this.product = @json($product);
            
            // Create additional images array for the product gallery
            this.product.images = [
                this.product.image,
                'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=600',
                'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=600',
                'https://images.unsplash.com/photo-1572536147248-ac59a8abfa4b?w=600'
            ];
            
            // Set default features if not available
            if (!this.product.features) {
                this.product.features = [
                    'High Quality Materials',
                    'Premium Design',
                    'Excellent Performance',
                    'Great Value for Money'
                ];
            }
            
            // Ensure inStock is boolean
            this.product.inStock = this.product.in_stock || true;
            this.product.reviews = this.product.reviews_count || 0;
        },
        
        async loadRecommendations() {
            this.loadingRecommendations = true;
            try {
                // Call AI recommendation endpoint
                const response = await fetch('/ai/recommendations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        user_id: {{ auth()->id() ?? 'null' }},
                        product_id: this.product.id,
                        cart_items: this.items,
                        context: 'product_view'
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.recommendations = data.recommendations || [];
                } else {
                    // Fallback to mock recommendations
                    this.recommendations = [
                        {
                            id: 2,
                            name: 'Smart Watch',
                            description: 'Advanced smartwatch with health monitoring',
                            price: 299.99,
                            image: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400'
                        },
                        {
                            id: 3,
                            name: 'Bluetooth Speaker',
                            description: 'Portable speaker with excellent sound quality',
                            price: 79.99,
                            image: 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400'
                        },
                        {
                            id: 4,
                            name: 'Phone Case',
                            description: 'Protective case for your smartphone',
                            price: 29.99,
                            image: 'https://images.unsplash.com/photo-1556656793-08538906a9f8?w=400'
                        },
                        {
                            id: 5,
                            name: 'Charging Cable',
                            description: 'Fast charging cable for all devices',
                            price: 19.99,
                            image: 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=400'
                        }
                    ];
                }
            } catch (error) {
                console.error('Error loading recommendations:', error);
                // Fallback to mock recommendations
                this.recommendations = [
                    {
                        id: 2,
                        name: 'Smart Watch',
                        description: 'Advanced smartwatch with health monitoring',
                        price: 299.99,
                        image: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400'
                    }
                ];
            } finally {
                this.loadingRecommendations = false;
            }
        },
        
        async addToCartWithQuantity() {
            try {
                const response = await fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        product_id: this.product.id,
                        quantity: this.quantity
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    this.items = data.cart_items;
                    this.updateCartCount();
                    
                    // Dispatch cart updated event for layout
                    window.dispatchEvent(new CustomEvent('cartUpdated'));
                    
                    this.showNotification('Product added to cart!');
                } else {
                    throw new Error('Failed to add to cart');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                this.showNotification('❌ Failed to add product to cart');
            }
        },
        
        async addToCart(product) {
            try {
                const response = await fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        product_id: product.id,
                        quantity: 1
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    this.items = data.cart_items;
                    this.updateCartCount();
                    
                    // Dispatch cart updated event for layout
                    window.dispatchEvent(new CustomEvent('cartUpdated'));
                    
                    this.showNotification('Product added to cart!');
                } else {
                    throw new Error('Failed to add to cart');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                this.showNotification('❌ Failed to add product to cart');
            }
        },
        
        saveCart() {
            localStorage.setItem('cart', JSON.stringify(this.items));
        },
        
        updateCartCount() {
            this.cartCount = this.items.reduce((total, item) => total + item.quantity, 0);
        },
        
        showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        },
        
        async trackUserInteraction(productId, interactionType) {
            const userId = {{ auth()->id() ?? 'null' }};
            if (!userId) return; // Don't track for non-logged-in users
            
            try {
                await fetch('/ai/track-interaction', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        product_id: productId,
                        interaction_type: interactionType
                    })
                });
            } catch (error) {
                console.error('Failed to track interaction:', error);
            }
        }
    }));
});
</script>
@endsection
