# AI Smartshop - E-Commerce Platform

A modern, AI-powered e-commerce platform built with Laravel, Alpine.js, and Tailwind CSS. Features intelligent product recommendations, seamless shopping experience, and comprehensive admin management.

## 🚀 Features

### Core E-Commerce Features
- **Product Catalog**: 50 diverse products across multiple categories
- **Shopping Cart**: Persistent cart with real-time updates
- **Checkout Process**: Secure Stripe payment integration
- **User Management**: Customer and admin accounts
- **Order Management**: Complete order lifecycle tracking

### AI-Powered Features
- **Smart Recommendations**: AI-driven product suggestions using Google Gemini
- **User Behavior Tracking**: Monitor interactions for personalized experiences
- **Context-Aware Suggestions**: Recommendations based on viewing history and cart contents

### Technical Features
- **Responsive Design**: Built with Tailwind CSS
- **Real-time Updates**: Alpine.js for dynamic interactions
- **Database Optimization**: N+1 query prevention with eager loading
- **Admin Panel**: Filament-based admin interface
- **Payment Processing**: Stripe integration with webhook support

## 📋 Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js & NPM
- MySQL/PostgreSQL
- Stripe Account (for payments)
- Google Gemini API Key (for AI recommendations)

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd AI-Smartshop
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Environment Variables
Edit `.env` file with your configuration:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Stripe
STRIPE_SK=sk_test_xxx
STRIPE_PK=pk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Gemini AI
GEMINI_API_KEY=AIzaSyxxx
```

### 5. Database Setup
```bash
# Run migrations with database Seed 
php artisan migrate:fresh --seed
```

### 6. Build Assets
```bash
# Build frontend assets
npm run build

# Or for development with hot reload
npm run dev
```

### 7. Start the Application
```bash
# Start Laravel development server
php artisan serve

# The application will be available at http://127.0.0.1:8000
```

## 👥 Test Accounts

The application comes with pre-configured test accounts:

### Admin Account
- **Email**: admin@smartshop.com
- **Password**: password
- **Access**: Full admin panel access

### Customer Accounts
- **Email**: customer@smartshop.com
- **Password**: password
- **Access**: Shopping and account management

- **Email**: jane@smartshop.com
- **Password**: password

- **Email**: mike@smartshop.com
- **Password**: password

- **Email**: moha@gmail.com
- **Password**: password

## 🧠 AI Recommendation Algorithm

### Overview
The recommendation system uses Google Gemini AI to provide intelligent product suggestions based on user behavior, product context, and shopping patterns.

### Algorithm Components

#### 1. Data Collection
- **User Interactions**: Track views, cart additions, and purchases
- **Product Context**: Current product being viewed
- **Cart Contents**: Items currently in shopping cart
- **User History**: Past interactions and preferences

#### 2. Context Building
```php
// Example context structure sent to AI
$context = [
    'current_product' => $product->toArray(),
    'cart_items' => $cartItems,
    'user_interactions' => $userInteractions,
    'recommendation_context' => 'product_view' // or 'cart', 'homepage'
];
```

#### 3. AI Processing
- **Prompt Engineering**: Carefully crafted prompts for consistent results
- **Product Matching**: AI analyzes product features, categories, and tags
- **Personalization**: Considers user behavior patterns
- **Fallback System**: Database-based recommendations if AI fails (On page view, would display random products of current product category)

### Implementation Details

```php
// GeminiController.php - Main recommendation logic
public function recommendations(Request $request)
{
    $productId = $request->input('product_id');
    $cartItems = $request->input('cart_items', []);
    
    // Build context for AI
    $context = $this->buildRecommendationContext($productId, $cartItems);
    
    // Call Gemini AI
    $recommendations = $this->callGeminiAPI($context);
    
    // Fallback if AI fails
    if (empty($recommendations)) {
        $recommendations = $this->getFallbackRecommendations($productId, $cartItems);
    }
    
    return response()->json($recommendations);
}
```

## 🗄️ Database Schema

### Core Tables
- **users**: User accounts with admin flags
- **products**: Product catalog with inventory tracking
- **carts & cart_items**: Shopping cart management
- **orders & order_items**: Order processing and history
- **user_interactions**: Behavior tracking for AI
- **ai_recommendations**: Cached AI suggestions

### Key Relationships
- Users have one cart and many orders
- Products have many cart items and order items
- User interactions link users to products
- AI recommendations are cached for performance

## 🎨 Frontend Architecture

### Technology Stack
- **Laravel Blade**: Server-side templating
- **Alpine.js**: Client-side interactivity
- **Tailwind CSS**: Utility-first styling
- **SweetAlert2**: User notifications

### Component Structure
```
resources/views/
├── layouts/
│   ├── app.blade.php          # Main layout
│   ├── guest.blade.php        # Guest layout
│   └── navigation.blade.php   # Navigation component
├── home.blade.php             # Landing page
├── products/
│   └── show.blade.php         # Product detail
├── cart/
│   └── index.blade.php        # Shopping cart
├── checkout/
│   └── index.blade.php        # Checkout process
├── auth/                      # Authentication views
└── profile/                   # User profile management
```

### Alpine.js Components
- **Cart Management**: Add/remove items, quantity updates
- **Recommendations**: Dynamic AI-powered suggestions
- **Notifications**: Toast messages and alerts

## 🔧 Code Quality Features

### N+1 Query Prevention
```php
// Eager loading relationships
$products = Product::with(['cartItems', 'orderItems'])->get();

// Using select to limit columns
$products = Product::select(['id', 'name', 'price', 'image'])
    ->with(['cartItems:id,product_id,quantity'])
    ->get();
```

### Database Indexing
- User interactions indexed by user_id, product_id, interaction_type
- AI recommendations indexed by user_id and created_at
- Products indexed by category and in_stock status

## 🚀 Deployment

### Production Checklist
1. Set `APP_ENV=production` in `.env`
2. Configure proper database credentials
3. Set up Stripe webhook endpoints
4. Configure email settings for notifications
5. Set up SSL certificate
6. Set up monitoring and logging

### Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Stripe
STRIPE_SK=sk_test_xxx
STRIPE_PK=pk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Gemini AI
GEMINI_API_KEY=AIzaSyxxx
```

## 📊 Performance Optimization

### Database Optimizations
- Proper indexing on frequently queried columns
- Eager loading to prevent N+1 queries

### Frontend Optimizations
- Minified CSS and JavaScript
- Image optimization and lazy loading
- Alpine.js for minimal JavaScript footprint

## 🧪 Code Quality & Styling

### Running Code Quality Checks
```bash
# Run all code quality checks
composer test

# Individual checks
composer phpstan    # Static analysis (MAX level)
composer pint       # Code style check
composer blade-format # Blade template formatting check
```

## 🔄 Changelog

### Version 1.0.0
- Initial release
- Core e-commerce functionality
- AI-powered recommendations
- Stripe payment integration
- Admin panel
- 50 sample products
- Responsive design
- Code quality tools (PHPStan, Laravel Pint, Blade Formatter)

---

**Built with ❤️ using Laravel, Alpine.js, and Tailwind CSS**
