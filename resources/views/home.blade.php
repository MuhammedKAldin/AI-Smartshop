@extends('layouts.app')

@section('content')
    <div x-data="cart()" x-init="init()">
        <!-- Hero Section -->
        <section class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
            <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="mb-6 text-4xl font-bold md:text-6xl">
                        🛍️ SmartShop - AI-Powered E-Commerce
                    </h1>
                    <p class="mb-8 text-xl text-indigo-100 md:text-2xl">
                        Discover amazing products with AI-powered recommendations
                    </p>
                    <a href="#products"
                        class="inline-block rounded-lg bg-white px-8 py-3 text-lg font-semibold text-indigo-600 transition duration-300 hover:bg-gray-100">
                        🚀 Start Shopping Now
                    </a>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section id="products" class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Section Header -->
                <div class="mb-12 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-900">🛒 Our Products</h2>
                    <p class="text-lg text-gray-600">Discover amazing products with AI-powered recommendations</p>
                </div>

                <!-- Search Bar -->
                <div class="mb-8">
                    <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                        <div class="max-w-md flex-1">
                            <input type="text" placeholder="🔍 Search products..."
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500"
                                x-model="searchQuery" @input="filterProducts()">
                        </div>
                        <div class="flex gap-2">
                            <select
                                class="rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500"
                                x-model="selectedCategory" @change="filterProducts()">
                                <option value="">All Categories</option>
                                <option value="electronics">Electronics</option>
                                <option value="clothing">Clothing</option>
                                <option value="home">Home & Garden</option>
                                <option value="sports">Sports</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="mb-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div class="group cursor-pointer overflow-hidden rounded-lg border border-gray-200 bg-white shadow-md transition duration-300 hover:shadow-lg"
                            @click="window.location.href = '/products/' + product.id">
                            <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                                <img :src="product.image" :alt="product.name"
                                    class="h-48 w-full object-cover transition duration-300 group-hover:scale-105">
                            </div>
                            <div class="p-4">
                                <h3 class="mb-2 text-lg font-semibold text-gray-900 transition duration-300 group-hover:text-indigo-600"
                                    x-text="product.name"></h3>
                                <p class="mb-3 text-sm text-gray-600" x-text="product.description"></p>
                                <div class="flex items-center justify-between">
                                    <span class="text-2xl font-bold text-indigo-600" x-text="'$' + product.price"></span>
                                    <button @click.stop="addToCart(product)" :data-product-id="product.id"
                                        class="rounded-lg bg-indigo-600 px-4 py-2 text-white transition duration-300 hover:bg-indigo-700">
                                        🛒 Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="filteredProducts.length === 0" class="py-16 text-center">
                    <div class="mx-auto max-w-md">
                        <div class="mb-4 text-6xl">🔍</div>
                        <h2 class="mb-4 text-2xl font-bold text-gray-900">No products found</h2>
                        <p class="mb-8 text-gray-600">Try adjusting your search or filter criteria.</p>
                        <button @click="searchQuery = ''; selectedCategory = ''; filterProducts()"
                            class="inline-block rounded-lg bg-indigo-600 px-8 py-3 font-semibold text-white transition duration-300 hover:bg-indigo-700">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- AI Recommendations Section -->
        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-900">🤖 AI Recommendations</h2>
                    <p class="text-lg text-gray-600">Personalized suggestions powered by AI</p>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <template x-for="recommendation in recommendations" :key="recommendation.id">
                        <div class="group cursor-pointer overflow-hidden rounded-lg bg-white shadow-md transition duration-300 hover:shadow-lg"
                            @click="window.location.href = '/products/' + recommendation.id">
                            <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                                <img :src="recommendation.image" :alt="recommendation.name"
                                    class="h-48 w-full object-cover transition duration-300 group-hover:scale-105">
                            </div>
                            <div class="p-4">
                                <h3 class="mb-2 text-lg font-semibold text-gray-900 transition duration-300 group-hover:text-indigo-600"
                                    x-text="recommendation.name"></h3>
                                <p class="mb-3 text-sm text-gray-600" x-text="recommendation.description"></p>
                                <div class="flex items-center justify-between">
                                    <span class="text-2xl font-bold text-indigo-600"
                                        x-text="'$' + recommendation.price"></span>
                                    <button @click.stop="addToCart(recommendation)" :data-product-id="recommendation.id"
                                        class="rounded-lg bg-indigo-600 px-4 py-2 text-white transition duration-300 hover:bg-indigo-700">
                                        🛒 Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Loading state for recommendations -->
                <div x-show="loadingRecommendations" class="py-8 text-center">
                    <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-indigo-600"></div>
                    <p class="mt-2 text-gray-600">🤖 AI is thinking...</p>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('cart', () => ({
                items: [],
                cartCount: 0,
                searchQuery: '',
                selectedCategory: '',
                products: [],
                filteredProducts: [],
                recommendations: [],
                loadingRecommendations: false,

                init() {
                    this.loadCart();
                    this.loadProducts();
                    this.loadRecommendations();
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

                loadProducts() {
                    // Use products from database passed from controller
                    this.products = @json($products);
                    this.filteredProducts = [...this.products];
                },

                async loadRecommendations() {
                    this.loadingRecommendations = true;
                    try {
                        // Call AI recommendation endpoint
                        const response = await fetch('/ai/recommendations', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                user_id: {{ auth()->id() ?? 'null' }},
                                product_id: null,
                                cart_items: this.items,
                                context: 'homepage'
                            })
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.recommendations = data.recommendations || [];
                        } else {
                            // Fallback to random products
                            this.recommendations = this.products.sort(() => 0.5 - Math.random())
                                .slice(0, 4);
                        }
                    } catch (error) {
                        console.error('Error loading recommendations:', error);
                        // Fallback to random products
                        this.recommendations = this.products.sort(() => 0.5 - Math.random()).slice(
                            0, 4);
                    } finally {
                        this.loadingRecommendations = false;
                    }
                },

                filterProducts() {
                    let filtered = [...this.products];

                    // Filter by search query
                    if (this.searchQuery) {
                        filtered = filtered.filter(product =>
                            product.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                            product.description.toLowerCase().includes(this.searchQuery
                                .toLowerCase())
                        );
                    }

                    // Filter by category
                    if (this.selectedCategory) {
                        filtered = filtered.filter(product => product.category === this
                            .selectedCategory);
                    }

                    this.filteredProducts = filtered;
                },

                async addToCart(product) {
                    try {
                        const response = await fetch('/cart/add', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                product_id: product.id,
                                quantity: 1
                            })
                        });

                        if (response.ok) {
                            const data = await response.json();

                            if (data.success) {
                                this.items = data.cart_items;
                                this.updateCartCount();

                                // Dispatch cart updated event for layout
                                window.dispatchEvent(new CustomEvent('cartUpdated'));

                                // Track add to cart interaction
                                this.trackUserInteraction(product.id, 'add_to_cart');

                                // Show success message
                                this.showNotification('✅ Product added to cart!');
                            } else {
                                // Handle stock validation error
                                if (data.error_type === 'stock_issue') {
                                    this.showStockError(data.error);
                                    this.disableAddToCartButton(product.id);
                                } else {
                                    this.showNotification('❌ ' + data.error, 'error');
                                }
                            }
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
                    // Simple notification - you can enhance this with a proper notification system
                    const notification = document.createElement('div');
                    notification.className =
                        'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    notification.textContent = message;
                    document.body.appendChild(notification);

                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                },

                showStockError(message) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Item Out of Stock',
                        text: message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#f59e0b'
                    });
                },

                disableAddToCartButton(productId) {
                    // Find and disable the add to cart button for this product
                    const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
                    buttons.forEach(button => {
                        button.disabled = true;
                        button.textContent = 'Out of Stock';
                        button.classList.add('bg-gray-400', 'cursor-not-allowed');
                        button.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                    });
                },

                async trackUserInteraction(productId, interactionType) {
                    const userId = {{ auth()->id() ?? 'null' }};
                    if (!userId) return; // Don't track for non-logged-in users

                    try {
                        await fetch('/ai/track-interaction', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
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
