# Headless WordPress E-commerce - AI Assistant Guide

> **Last Updated**: 2025-11-19
> **Project Type**: Headless WordPress + WooCommerce E-commerce Platform
> **Primary Use Case**: Furniture E-commerce with Location-Based Product Management

## Table of Contents

1. [Project Overview](#project-overview)
2. [Codebase Structure](#codebase-structure)
3. [Architecture](#architecture)
4. [Development Environment](#development-environment)
5. [Key Components](#key-components)
6. [API Endpoints](#api-endpoints)
7. [Development Workflows](#development-workflows)
8. [Testing](#testing)
9. [Conventions & Standards](#conventions--standards)
10. [Common Tasks](#common-tasks)
11. [Security Considerations](#security-considerations)
12. [Deployment](#deployment)

---

## Project Overview

### What This Project Is

This is a **headless WordPress installation** configured for e-commerce, specifically designed for a furniture business with location-based product management. The WordPress backend serves as a content and product management system, exposing data through REST APIs for consumption by headless frontends (React, Next.js, Vue, etc.).

### Key Features

- **Headless Architecture**: WordPress backend with REST API for frontend consumption
- **JWT Authentication**: Secure token-based authentication for API access
- **WooCommerce Integration**: Full e-commerce functionality (products, orders, cart, checkout)
- **Location-Based Products**: Custom plugin for location-specific availability, pricing, and inventory
- **CORS Enabled**: Configured for cross-origin requests from headless frontends
- **RESTful APIs**: Comprehensive API endpoints for all functionality
- **Security Hardened**: File editing disabled, security headers configured

### Technology Stack

- **CMS**: WordPress 6.x
- **E-commerce**: WooCommerce 10.3+
- **Authentication**: JWT Authentication for WP REST API
- **Database**: MySQL 5.6+
- **PHP**: 7.4+
- **Server**: Apache with mod_rewrite
- **Custom Plugins**: Location-Based Products, Headless WooCommerce API Bridge

---

## Codebase Structure

```
hedless-wordpress/
├── wp-admin/                    # WordPress admin (standard)
├── wp-includes/                 # WordPress core (standard)
├── wp-content/
│   ├── plugins/
│   │   ├── location-based-products/          # Custom location management plugin
│   │   │   ├── location-based-products.php   # Main plugin file
│   │   │   ├── includes/
│   │   │   │   ├── admin.php                 # Admin interface
│   │   │   │   ├── helpers.php               # Core location logic
│   │   │   │   ├── product-integration.php   # WooCommerce integration
│   │   │   │   └── rest-api.php              # API endpoints
│   │   │   ├── assets/
│   │   │   │   ├── admin.css                 # Admin styles
│   │   │   │   └── admin.js                  # Admin JavaScript
│   │   │   └── README.md
│   │   ├── jwt-authentication-for-wp-rest-api/  # JWT auth plugin
│   │   ├── woocommerce/                      # WooCommerce core
│   │   ├── headless-woocommerce-api.php      # Custom API bridge plugin
│   │   └── headless-wp-config/               # Headless configuration
│   └── themes/
│       ├── astra/                            # Default theme
│       └── headless-theme/                   # Minimal headless theme
├── .htaccess                    # Apache configuration (CORS, auth headers)
├── wp-config.php               # WordPress configuration
├── .gitignore                  # Git ignore rules
│
├── # Test & Setup Scripts
├── activate-cors.php
├── activate-headless-wc-api.php
├── activate-location-plugin.php
├── test-jwt.php
├── test-location-api.php
├── test-woocommerce-compatibility.php
├── jwt-test.html              # JWT testing interface
│
└── # Documentation
    ├── PLUGIN-IMPLEMENTATION-SUMMARY.md
    ├── WOOCOMMERCE-COMPATIBILITY-RESOLVED.md
    ├── TEST-RESULTS.md
    └── CLAUDE.md (this file)
```

### Key Directories

- **`wp-content/plugins/location-based-products/`**: Custom plugin for all location-based functionality
- **Test Scripts (root)**: Various PHP test scripts for validating setup and functionality
- **Documentation (root)**: Markdown files documenting implementation and testing

---

## Architecture

### System Architecture

```
┌─────────────────┐
│  Frontend App   │ (React/Next.js/Vue - not in this repo)
│  (Headless)     │
└────────┬────────┘
         │ HTTP/HTTPS + JWT
         │
┌────────▼────────────────────────────────────┐
│         WordPress REST API                  │
│  ┌──────────────────────────────────────┐  │
│  │  wp-json/ endpoints                  │  │
│  │  - /wp/v2/* (WordPress Core)         │  │
│  │  - /wc/v3/* (WooCommerce)            │  │
│  │  - /jwt-auth/v1/* (Authentication)   │  │
│  │  - /lbp/v1/* (Location Products)     │  │
│  │  - /headless-wc/v1/* (Custom API)    │  │
│  └──────────────────────────────────────┘  │
│                                             │
│  ┌──────────────────────────────────────┐  │
│  │  WordPress Core                      │  │
│  │  - Posts, Pages, Media, Users        │  │
│  └──────────────────────────────────────┘  │
│                                             │
│  ┌──────────────────────────────────────┐  │
│  │  WooCommerce                         │  │
│  │  - Products, Orders, Cart, Checkout  │  │
│  │  - Inventory, Pricing, Shipping      │  │
│  └──────────────────────────────────────┘  │
│                                             │
│  ┌──────────────────────────────────────┐  │
│  │  Location-Based Products Plugin      │  │
│  │  - Location Detection                │  │
│  │  - Product Filtering by Location     │  │
│  │  - Location-Specific Pricing         │  │
│  │  - Location-Based Inventory          │  │
│  └──────────────────────────────────────┘  │
└─────────────────┬───────────────────────────┘
                  │
         ┌────────▼────────┐
         │  MySQL Database │
         │  - wp_* tables  │
         └─────────────────┘
```

### Authentication Flow

```
1. Frontend → POST /wp-json/jwt-auth/v1/token
              { username, password }

2. Backend  → Validates credentials
              Generates JWT token

3. Backend  → Returns { token, user_data }

4. Frontend → Stores token
              Includes in subsequent requests:
              Header: Authorization: Bearer {token}

5. Backend  → Validates JWT on protected endpoints
              Returns requested data
```

### Location-Based Product Flow

```
1. User visits site → Frontend detects location
   - Auto: IP geolocation
   - Manual: User input (postal code, city)

2. Frontend → GET /wp-json/lbp/v1/detect-location
              ?postal_code=110001

3. Backend  → Location detection algorithm:
              - Check postal code match
              - Check GPS radius
              - Check city/region match
              - Return best matching location

4. Frontend → GET /wp-json/lbp/v1/products
              ?location_id={id}&per_page=12

5. Backend  → Filter products by:
              - Location availability
              - Location-specific pricing
              - Location inventory
              - Return filtered products

6. Frontend → Display products with:
              - Location-specific prices
              - Delivery options
              - Stock availability
```

---

## Development Environment

### Prerequisites

- PHP 7.4+ (with extensions: mysqli, gd, curl, mbstring, xml)
- MySQL 5.6+
- Apache with mod_rewrite
- Composer (optional, for dependency management)

### Environment Configuration

#### Database Configuration

Located in `wp-config.php:22-32`:

```php
define('DB_NAME', 'headless');
define('DB_USER', 'prasadmasina');
define('DB_PASSWORD', 'Sita@1968@manu');
define('DB_HOST', 'localhost');
```

⚠️ **Security Note**: Database credentials are committed in this repo. For production, use environment variables.

#### WordPress Configuration

Key settings in `wp-config.php`:

```php
// Debug mode
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// JWT Authentication
define('JWT_AUTH_SECRET_KEY', 'HeAdLeSs_WoRdPrEsS_2025_...');
define('JWT_AUTH_CORS_ENABLE', true);

// WooCommerce
define('WC_ADMIN_DISABLED', false);
define('WOOCOMMERCE_BLOCKS_PHASE', 3);

// Performance
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// Security
define('DISALLOW_FILE_EDIT', true);
```

#### Apache Configuration

`.htaccess` handles:

- **URL Rewriting**: WordPress permalinks
- **Authorization Headers**: Forwards JWT tokens
- **CORS**: OPTIONS request handling (main CORS in WordPress)
- **Security Headers**: XSS, clickjacking protection
- **Caching**: Static asset caching

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone <repo-url>
   cd hedless-wordpress
   ```

2. **Configure database**
   - Create MySQL database: `headless`
   - Import existing database or run WordPress installation

3. **Update wp-config.php** (if needed)
   - Adjust database credentials for your environment

4. **Set permissions**
   ```bash
   chmod 755 wp-content/
   chmod 644 wp-config.php
   ```

5. **Access WordPress admin**
   - URL: `http://localhost/hedless/wp-admin/`
   - Complete installation if needed

6. **Activate plugins**
   - WooCommerce
   - JWT Authentication for WP REST API
   - Location-Based Products
   - Headless WooCommerce API Bridge

7. **Test API endpoints**
   - Visit: `http://localhost/hedless/jwt-test.html`
   - Test authentication and API access

---

## Key Components

### 1. Location-Based Products Plugin

**Purpose**: Manages location-specific product availability, pricing, and inventory.

**Main File**: `wp-content/plugins/location-based-products/location-based-products.php`

**Core Classes**:

- `LBP_Main`: Plugin initialization, hooks registration
- `LBP_Admin`: Admin interface for location management
- `LBP_Helpers`: Location detection algorithms
- `LBP_Product_Integration`: WooCommerce product meta integration
- `LBP_REST_API`: REST API endpoint handlers

**Database Schema**:

Custom post types:
- `lbp_location`: Individual locations (stores, warehouses)
- `lbp_service_area`: Service areas with postal code ranges

Post meta (locations):
- `_lbp_address`: Physical address
- `_lbp_city`: City name
- `_lbp_state`: State/region
- `_lbp_country`: Country code
- `_lbp_postal_codes`: Comma-separated postal codes
- `_lbp_latitude`: GPS latitude
- `_lbp_longitude`: GPS longitude
- `_lbp_delivery_radius`: Delivery radius in km
- `_lbp_is_active`: Active status (1/0)
- `_lbp_priority`: Location priority

Post meta (products):
- `_lbp_availability_type`: all/specific/exclude
- `_lbp_selected_locations`: Array of location IDs
- `_lbp_location_pricing`: Enable location pricing (yes/no)
- `_lbp_location_prices`: Array of location-specific prices
- `_lbp_location_stock`: Enable location stock (yes/no)
- `_lbp_location_stock_levels`: Array of location stock levels
- `_lbp_delivery_type`: standard/express/pickup_only

### 2. Headless WooCommerce API Bridge

**Purpose**: Simplified WooCommerce API endpoints for headless frontends.

**Main File**: `wp-content/plugins/headless-woocommerce-api.php`

**Features**:
- Simplified product endpoints
- Store info endpoint
- API key authentication
- Compatible with JWT authentication

### 3. JWT Authentication

**Plugin**: JWT Authentication for WP REST API

**Purpose**: Token-based authentication for REST API access.

**Endpoints**:
- `POST /wp-json/jwt-auth/v1/token` - Generate token
- `POST /wp-json/jwt-auth/v1/token/validate` - Validate token

### 4. WooCommerce

**Version**: 10.3+

**Key Features**:
- Product management
- Order processing
- Cart and checkout
- Inventory management
- Pricing and tax
- Shipping methods

**Compatibility**:
- HPOS (High Performance Order Storage) enabled
- Cart & Checkout Blocks supported
- Analytics compatible

---

## API Endpoints

### WordPress Core API

**Base**: `/wp-json/wp/v2/`

```
GET    /posts              # List posts
GET    /posts/{id}         # Get single post
GET    /pages              # List pages
GET    /pages/{id}         # Get single page
GET    /media              # List media items
GET    /users              # List users (requires auth)
GET    /categories         # List categories
GET    /tags               # List tags
```

### WooCommerce API

**Base**: `/wp-json/wc/v3/`

**Authentication**: JWT token or WooCommerce consumer key/secret

```
# Products
GET    /products           # List products
GET    /products/{id}      # Get single product
POST   /products           # Create product (requires auth)
PUT    /products/{id}      # Update product (requires auth)
DELETE /products/{id}      # Delete product (requires auth)

# Product Categories
GET    /products/categories

# Orders
GET    /orders             # List orders (requires auth)
POST   /orders             # Create order
GET    /orders/{id}        # Get single order

# Customers
GET    /customers          # List customers (requires auth)
GET    /customers/{id}     # Get customer

# Cart (via WooCommerce Store API)
GET    /wc/store/cart      # Get cart
POST   /wc/store/cart/add-item    # Add to cart
POST   /wc/store/cart/remove-item # Remove from cart
```

### JWT Authentication API

**Base**: `/wp-json/jwt-auth/v1/`

```
# Generate Token
POST   /token
Body: { "username": "user", "password": "pass" }
Response: { "token": "...", "user_email": "...", "user_nicename": "...", "user_display_name": "..." }

# Validate Token
POST   /token/validate
Header: Authorization: Bearer {token}
Response: { "code": "jwt_auth_valid_token", "data": { "status": 200 } }
```

### Location-Based Products API

**Base**: `/wp-json/lbp/v1/`

**Authentication**: Most endpoints are public, some require JWT

```
# Location Detection
GET    /detect-location
Query: ?postal_code=110001 (optional)
Query: ?ip=1.2.3.4 (optional)
Query: ?lat=19.0596&lng=72.8295 (optional)
Response: {
  "id": 123,
  "name": "Mumbai Store",
  "city": "Mumbai",
  "postal_codes": ["400050", "400051"],
  "delivery_radius": 30
}

# Set Location Manually
POST   /set-location
Body: { "location_id": 123 }

# List All Locations
GET    /locations
Response: [ {...location objects...} ]

# Get Products by Location
GET    /products
Query: ?location_id=123&per_page=12&page=1&q=sofa
Response: [ {...product objects with location data...} ]

# Check Product Availability
GET    /check-availability/{product_id}
Query: ?location_id=123&quantity=1
Response: {
  "available": true,
  "price": 15000,
  "stock_quantity": 5,
  "delivery_options": ["standard", "express"]
}

# Get Delivery Options
GET    /delivery-options
Query: ?location_id=123&product_id=456
Response: {
  "standard": { "label": "Standard (5-7 days)", "cost": 0 },
  "express": { "label": "Express (1-2 days)", "cost": 500 }
}
```

### Headless WooCommerce API

**Base**: `/wp-json/headless-wc/v1/`

**Authentication**: API key (X-API-Key header) or JWT

```
# Store Information (public)
GET    /store-info
Response: {
  "store_name": "...",
  "currency": "INR",
  "currency_symbol": "₹",
  "woocommerce_version": "10.3.4"
}

# Products (requires auth)
GET    /products
Query: ?per_page=10&page=1&search=sofa

# Single Product (requires auth)
GET    /products/{id}
```

---

## Development Workflows

### Adding a New Location

#### Via WordPress Admin

1. Navigate to **Location Products > Locations**
2. Click **Add New Location**
3. Fill in required fields:
   - Location name
   - Address
   - City, state, country
   - Postal codes (comma-separated)
   - GPS coordinates (optional)
   - Delivery radius in km
4. Mark as **Active**
5. Set priority (higher = preferred when overlapping)
6. Click **Publish**

#### Programmatically

```php
$location_id = wp_insert_post([
    'post_title' => 'Mumbai Store',
    'post_type' => 'lbp_location',
    'post_status' => 'publish'
]);

update_post_meta($location_id, '_lbp_address', 'Bandra West, Mumbai');
update_post_meta($location_id, '_lbp_city', 'Mumbai');
update_post_meta($location_id, '_lbp_state', 'Maharashtra');
update_post_meta($location_id, '_lbp_country', 'IN');
update_post_meta($location_id, '_lbp_postal_codes', '400050,400051,400052');
update_post_meta($location_id, '_lbp_latitude', 19.0596);
update_post_meta($location_id, '_lbp_longitude', 72.8295);
update_post_meta($location_id, '_lbp_delivery_radius', 30);
update_post_meta($location_id, '_lbp_is_active', '1');
update_post_meta($location_id, '_lbp_priority', 10);
```

### Configuring Product Location Settings

#### Via Product Edit Screen

1. Edit any WooCommerce product
2. Scroll to **Location Settings** meta box
3. Set **Availability Type**:
   - All locations
   - Specific locations only
   - All except specific locations
4. Select specific locations (if applicable)
5. Enable **Location-Based Pricing** (optional)
   - Set prices per location
6. Enable **Location-Specific Inventory** (optional)
   - Set stock levels per location
7. Choose **Delivery Type**
8. Update product

#### Programmatically

```php
// Set availability
update_post_meta($product_id, '_lbp_availability_type', 'specific');
update_post_meta($product_id, '_lbp_selected_locations', [123, 456]);

// Set location pricing
update_post_meta($product_id, '_lbp_location_pricing', 'yes');
update_post_meta($product_id, '_lbp_location_prices', [
    123 => ['regular' => 15000, 'sale' => 12000],
    456 => ['regular' => 16000, 'sale' => 13000]
]);

// Set location stock
update_post_meta($product_id, '_lbp_location_stock', 'yes');
update_post_meta($product_id, '_lbp_location_stock_levels', [
    123 => 10,
    456 => 5
]);

// Set delivery type
update_post_meta($product_id, '_lbp_delivery_type', 'standard');
```

### Adding Custom API Endpoints

Create endpoints in plugin files or functions.php:

```php
add_action('rest_api_init', function() {
    register_rest_route('custom/v1', '/my-endpoint', [
        'methods' => 'GET',
        'callback' => 'my_endpoint_callback',
        'permission_callback' => '__return_true' // or custom auth
    ]);
});

function my_endpoint_callback($request) {
    return new WP_REST_Response([
        'success' => true,
        'data' => ['key' => 'value']
    ], 200);
}
```

### Modifying Plugin Behavior

Use WordPress hooks in custom plugin or theme:

```php
// Filter location detection results
add_filter('lbp_location_detection_result', function($location, $input) {
    // Modify location data before returning
    return $location;
}, 10, 2);

// Action after location is detected
add_action('lbp_location_detected', function($location, $method) {
    // Log, track, or trigger other actions
    error_log("Location detected: {$location->post_title} via {$method}");
}, 10, 2);

// Filter product availability
add_filter('lbp_product_location_availability', function($available, $product_id, $location_id) {
    // Custom availability logic
    return $available;
}, 10, 3);
```

---

## Testing

### Test Scripts

Multiple test scripts are available in the root directory for validating functionality:

#### JWT Authentication Tests

```bash
# Test JWT token generation and validation
php test-jwt.php

# Test JWT authentication flow
php test-jwt-auth.php

# Test JWT token validation
php test-jwt-validation.php

# Browser-based JWT testing
open http://localhost/hedless/jwt-test.html
```

#### Location Plugin Tests

```bash
# Test location plugin activation and functionality
php test-location-plugin.php

# Test location API endpoints
php test-location-api.php

# Debug location detection
php debug-location-plugin.php

# Direct LBP functionality test
php test-lbp-direct.php

# Simple LBP test
php simple-lbp-test.php
```

#### WooCommerce Tests

```bash
# Test WooCommerce compatibility
php test-woocommerce-compatibility.php

# Final WooCommerce integration test
php final-wc-test.php

# Final compatibility check
php final-compatibility-check.php
```

#### API Security Tests

```bash
# Test API security and authentication
php test-api-security.php
```

### Manual Testing Checklist

#### Authentication
- [ ] Generate JWT token with valid credentials
- [ ] Receive error with invalid credentials
- [ ] Validate token successfully
- [ ] Access protected endpoints with token
- [ ] Receive 401 without token on protected endpoints

#### Location Detection
- [ ] Auto-detect location from IP
- [ ] Detect location by postal code
- [ ] Detect location by GPS coordinates
- [ ] Detect location by city name
- [ ] Handle no location found gracefully

#### Product Filtering
- [ ] Get products for specific location
- [ ] See location-specific pricing
- [ ] See location-specific stock levels
- [ ] Search products within location
- [ ] Filter by categories within location

#### WooCommerce
- [ ] Create order via API
- [ ] Update order status
- [ ] Get customer data
- [ ] Add to cart
- [ ] Process checkout

### API Testing with cURL

```bash
# Generate JWT token
curl -X POST http://localhost/hedless/wp-json/jwt-auth/v1/token \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"your-password"}'

# Validate token
curl -X POST http://localhost/hedless/wp-json/jwt-auth/v1/token/validate \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Detect location
curl "http://localhost/hedless/wp-json/lbp/v1/detect-location?postal_code=110001"

# Get products by location
curl "http://localhost/hedless/wp-json/lbp/v1/products?location_id=123&per_page=10"

# Get WooCommerce products
curl http://localhost/hedless/wp-json/wc/v3/products \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Check product availability
curl "http://localhost/hedless/wp-json/lbp/v1/check-availability/456?location_id=123&quantity=1"
```

---

## Conventions & Standards

### Code Style

#### PHP
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use tabs for indentation
- Opening braces on same line for functions/classes
- Yoda conditions for comparisons: `if ( 'value' === $variable )`
- Single quotes for strings (unless interpolation needed)
- Prefix all functions/classes with plugin namespace

#### JavaScript
- Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- Use tabs for indentation
- Semicolons required
- jQuery available as `jQuery` (not `$` to avoid conflicts)

#### CSS
- Follow [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- Use tabs for indentation
- Properties alphabetically ordered within rules
- Prefix custom classes with plugin namespace

### Naming Conventions

#### Functions
- `lbp_` prefix for location-based products plugin
- `hwc_` prefix for headless WooCommerce
- Descriptive, lowercase with underscores
- Example: `lbp_get_location_by_postal_code()`

#### Classes
- PascalCase with plugin prefix
- Example: `LBP_REST_API`, `LBP_Helpers`

#### Database
- Post meta: `_lbp_` prefix (underscore prefix = hidden from custom fields UI)
- Options: `lbp_` prefix (no underscore = visible)
- Custom post types: `lbp_location`, `lbp_service_area`

#### Files
- Lowercase with hyphens
- Example: `product-integration.php`, `rest-api.php`

### Security Best Practices

#### Input Validation
```php
// Sanitize text input
$value = sanitize_text_field($_POST['field']);

// Validate email
$email = sanitize_email($_POST['email']);
if (!is_email($email)) {
    // Handle error
}

// Sanitize array
$array = array_map('sanitize_text_field', $_POST['array_field']);

// Validate numeric
$number = absint($_POST['number']);
```

#### Output Escaping
```php
// Escape HTML
echo esc_html($user_input);

// Escape attributes
echo '<div class="' . esc_attr($class) . '">';

// Escape URL
echo '<a href="' . esc_url($url) . '">';

// Escape JavaScript
echo '<script>var data = "' . esc_js($data) . '";</script>';
```

#### Nonce Verification
```php
// Create nonce
wp_nonce_field('my_action', 'my_nonce_field');

// Verify nonce
if (!isset($_POST['my_nonce_field']) ||
    !wp_verify_nonce($_POST['my_nonce_field'], 'my_action')) {
    wp_die('Security check failed');
}
```

#### Capability Checking
```php
// Check if user can edit posts
if (!current_user_can('edit_posts')) {
    wp_die('You do not have permission');
}

// Check for specific role
if (!current_user_can('manage_options')) {
    return;
}
```

#### SQL Queries
```php
// Use $wpdb->prepare() for all queries
global $wpdb;
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
    'lbp_location',
    'publish'
));

// Never concatenate user input into queries
// BAD: "SELECT * FROM table WHERE id = " . $_GET['id']
// GOOD: $wpdb->prepare("SELECT * FROM table WHERE id = %d", $_GET['id'])
```

### Error Handling

#### REST API Errors
```php
// Return WP_Error for failures
if (!$location) {
    return new WP_Error(
        'location_not_found',
        'Location not found for the given criteria',
        ['status' => 404]
    );
}

// Return WP_REST_Response for success
return new WP_REST_Response([
    'success' => true,
    'data' => $data
], 200);
```

#### Plugin Errors
```php
// Log errors (when WP_DEBUG_LOG is true)
error_log('LBP: Location detection failed - ' . print_r($input, true));

// Admin notices
add_action('admin_notices', function() {
    echo '<div class="notice notice-error"><p>Error message</p></div>';
});
```

### Database Queries

#### Use WP_Query for Posts
```php
$query = new WP_Query([
    'post_type' => 'lbp_location',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_lbp_is_active',
            'value' => '1',
            'compare' => '='
        ]
    ]
]);

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        // Process post
    }
    wp_reset_postdata();
}
```

#### Use get_posts for Simple Queries
```php
$locations = get_posts([
    'post_type' => 'lbp_location',
    'numberposts' => -1,
    'post_status' => 'publish'
]);
```

#### Use $wpdb for Custom Queries
```php
global $wpdb;
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title, pm.meta_value as city
     FROM {$wpdb->posts} p
     JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = %s
     AND p.post_status = %s
     AND pm.meta_key = '_lbp_city'
     AND pm.meta_value = %s",
    'lbp_location',
    'publish',
    $city
));
```

---

## Common Tasks

### Task: Add AI Assistant to Understand Location Detection Logic

**Location**: `wp-content/plugins/location-based-products/includes/helpers.php`

The `LBP_Helpers` class contains all location detection algorithms. Key methods:

```php
// Main detection method - tries multiple strategies
public function detect_location($input_data = [])

// Detect by postal code
private function detect_by_postal_code($postal_code)

// Detect by GPS coordinates
private function detect_by_coordinates($lat, $lng)

// Detect by city name
private function detect_by_city($city)

// Detect by IP address (uses external API)
private function detect_by_ip($ip)

// Calculate distance between two GPS points
private function calculate_distance($lat1, $lon1, $lat2, $lon2)
```

**Detection Priority**:
1. Exact postal code match (highest priority)
2. GPS coordinates within delivery radius
3. City/region fuzzy match
4. IP-based geolocation (fallback)

### Task: Modify Product Filtering Logic

**Location**: `wp-content/plugins/location-based-products/includes/product-integration.php`

The `LBP_Product_Integration` class handles all WooCommerce product filtering:

```php
// Check if product is available in location
public function is_product_available_in_location($product_id, $location_id)

// Get location-specific price
public function get_location_price($product_id, $location_id)

// Get location-specific stock
public function get_location_stock($product_id, $location_id)

// Get delivery options for location
public function get_delivery_options($product_id, $location_id)
```

**To modify filtering**: Add filters in this class or hook into existing filters from your theme/plugin.

### Task: Add New REST API Endpoint

**Location**: `wp-content/plugins/location-based-products/includes/rest-api.php`

Add to `LBP_REST_API::register_routes()`:

```php
// Add new endpoint
register_rest_route($this->namespace, '/my-endpoint', [
    'methods' => 'GET',
    'callback' => [$this, 'my_endpoint_callback'],
    'permission_callback' => '__return_true', // or custom auth
    'args' => [
        'param' => [
            'required' => false,
            'validate_callback' => function($param) {
                return is_numeric($param);
            }
        ]
    ]
]);

// Add callback method
public function my_endpoint_callback($request) {
    $param = $request->get_param('param');

    // Your logic here

    return new WP_REST_Response([
        'success' => true,
        'data' => $result
    ], 200);
}
```

### Task: Debug Location Detection Issues

1. **Enable debugging**:
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Check logs**:
   ```bash
   tail -f wp-content/debug.log
   ```

3. **Use test script**:
   ```bash
   php debug-location-plugin.php
   ```

4. **Test in admin**:
   - Go to **Location Products > Test Detection**
   - Try different inputs (postal code, coordinates, IP)
   - View detection results and matched location

5. **Add debug logging**:
   ```php
   error_log('LBP Debug: ' . print_r($data, true));
   ```

### Task: Create Custom Location Detection Method

Add to `LBP_Helpers` class:

```php
public function detect_by_custom_method($input) {
    // Your custom detection logic

    // Return location object or null
    $locations = get_posts([
        'post_type' => 'lbp_location',
        'meta_query' => [
            // Your meta query
        ]
    ]);

    if (!empty($locations)) {
        return $this->format_location_data($locations[0]);
    }

    return null;
}
```

Hook into detection:

```php
add_filter('lbp_location_detection_methods', function($methods) {
    $methods['custom'] = 'Custom Method';
    return $methods;
}, 10, 1);

add_filter('lbp_detect_location', function($location, $input) {
    if (!$location && isset($input['custom_field'])) {
        $helpers = new LBP_Helpers();
        return $helpers->detect_by_custom_method($input['custom_field']);
    }
    return $location;
}, 10, 2);
```

### Task: Integrate with Frontend (React Example)

```javascript
// 1. Authentication
const login = async (username, password) => {
    const response = await fetch('/wp-json/jwt-auth/v1/token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
    });
    const data = await response.json();
    localStorage.setItem('token', data.token);
    return data;
};

// 2. Detect Location
const detectLocation = async (postalCode = null) => {
    const url = postalCode
        ? `/wp-json/lbp/v1/detect-location?postal_code=${postalCode}`
        : '/wp-json/lbp/v1/detect-location';

    const response = await fetch(url);
    const location = await response.json();
    localStorage.setItem('location_id', location.id);
    return location;
};

// 3. Get Products
const getProducts = async (locationId, page = 1, search = '') => {
    const token = localStorage.getItem('token');
    const params = new URLSearchParams({
        location_id: locationId,
        per_page: 12,
        page: page,
        ...(search && { q: search })
    });

    const response = await fetch(`/wp-json/lbp/v1/products?${params}`, {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    return response.json();
};

// 4. Check Availability
const checkAvailability = async (productId, locationId, quantity = 1) => {
    const response = await fetch(
        `/wp-json/lbp/v1/check-availability/${productId}?location_id=${locationId}&quantity=${quantity}`
    );
    return response.json();
};

// 5. Create Order
const createOrder = async (orderData) => {
    const token = localStorage.getItem('token');
    const response = await fetch('/wp-json/wc/v3/orders', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(orderData)
    });
    return response.json();
};
```

---

## Security Considerations

### Authentication & Authorization

1. **JWT Token Security**
   - JWT secret key defined in `wp-config.php`
   - Change `JWT_AUTH_SECRET_KEY` for production (use strong random string)
   - Tokens expire after 7 days by default
   - Always use HTTPS in production

2. **API Key Management**
   - Headless WooCommerce API uses API keys
   - Store securely, rotate regularly
   - Never commit API keys to version control

3. **Permission Callbacks**
   - All REST endpoints have permission callbacks
   - Public endpoints: `'permission_callback' => '__return_true'`
   - Protected endpoints: `'permission_callback' => 'is_user_logged_in'`
   - Admin endpoints: `'permission_callback' => function() { return current_user_can('manage_options'); }`

### CORS Configuration

CORS is handled in WordPress (not .htaccess) for dynamic control:

**Typical implementation** (in plugin or mu-plugin):

```php
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *'); // Change * to specific domain in production
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        return $value;
    });
}, 15);
```

**Production**: Replace `*` with specific frontend domain(s).

### File Security

1. **File Editing Disabled**
   ```php
   define('DISALLOW_FILE_EDIT', true); // In wp-config.php
   ```

2. **Directory Permissions**
   - Directories: 755
   - Files: 644
   - wp-config.php: 440 or 400 (production)

3. **Uploads Security**
   - Validate file types
   - Scan for malware
   - Limit upload sizes

### Database Security

1. **Prepared Statements**
   - Always use `$wpdb->prepare()` for queries
   - Never concatenate user input into SQL

2. **Credentials**
   - Use environment variables for production
   - Never commit credentials to git
   - Use strong, unique passwords

3. **Database Prefix**
   - Default `wp_` prefix is fine
   - Can change for additional obscurity

### WordPress Security Headers

Configured in `.htaccess:33-37`:

```apache
X-Content-Type-Options: nosniff        # Prevent MIME sniffing
X-Frame-Options: DENY                  # Prevent clickjacking
X-XSS-Protection: 1; mode=block        # XSS protection
Strict-Transport-Security: max-age=31536000  # Force HTTPS
```

### Plugin Security

1. **Input Sanitization**
   - All user input sanitized before processing
   - Use WordPress sanitization functions

2. **Output Escaping**
   - All output escaped before rendering
   - Use WordPress escaping functions

3. **Nonce Verification**
   - All form submissions verified with nonces
   - Protects against CSRF attacks

4. **Capability Checks**
   - Admin functions check user capabilities
   - Prevents unauthorized access

---

## Deployment

### Pre-Deployment Checklist

#### Security
- [ ] Change JWT_AUTH_SECRET_KEY to strong random string
- [ ] Update database credentials (use environment variables)
- [ ] Update CORS to specific domain (remove `*`)
- [ ] Change WooCommerce API keys
- [ ] Change WordPress salts/keys
- [ ] Set file permissions correctly (755/644)
- [ ] Disable WP_DEBUG in wp-config.php
- [ ] Enable SSL/HTTPS
- [ ] Configure security headers
- [ ] Review .gitignore (ensure sensitive files excluded)

#### Performance
- [ ] Enable object caching (Redis/Memcached)
- [ ] Configure CDN for static assets
- [ ] Enable Gzip compression
- [ ] Optimize database (remove revisions, transients)
- [ ] Configure WP-Cron (use system cron instead)
- [ ] Minify CSS/JS assets
- [ ] Optimize images

#### Configuration
- [ ] Set correct site URL
- [ ] Configure email settings (SMTP)
- [ ] Set up backups (database + files)
- [ ] Configure error logging
- [ ] Set up monitoring/alerts
- [ ] Test all API endpoints
- [ ] Verify plugin compatibility

#### WooCommerce
- [ ] Configure payment gateways
- [ ] Set up shipping methods
- [ ] Configure tax settings
- [ ] Set currency and formats
- [ ] Configure email templates
- [ ] Test checkout flow
- [ ] Set up order notifications

### Environment Variables (Recommended)

Instead of hardcoding in `wp-config.php`, use environment variables:

```php
// wp-config.php
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST', getenv('DB_HOST'));
define('JWT_AUTH_SECRET_KEY', getenv('JWT_SECRET'));
```

**Set in server environment** (Apache .htaccess, Nginx config, or hosting panel):

```apache
# Apache
SetEnv DB_NAME "production_db"
SetEnv DB_USER "prod_user"
SetEnv DB_PASSWORD "strong_password"
SetEnv JWT_SECRET "very_long_random_string"
```

### Deployment Process

1. **Backup Current Site**
   ```bash
   # Database
   mysqldump -u user -p database > backup.sql

   # Files
   tar -czf backup.tar.gz wp-content/
   ```

2. **Prepare Production Code**
   ```bash
   # Clone repository
   git clone <repo-url> production
   cd production

   # Install dependencies (if any)
   composer install --no-dev
   ```

3. **Configure Production Environment**
   - Update `wp-config.php` with production settings
   - Or set environment variables
   - Update `.htaccess` for production domain

4. **Upload to Server**
   ```bash
   # Via SFTP/SCP
   scp -r * user@server:/var/www/html/

   # Or via Git on server
   ssh user@server
   cd /var/www/html
   git pull origin main
   ```

5. **Set Permissions**
   ```bash
   find . -type d -exec chmod 755 {} \;
   find . -type f -exec chmod 644 {} \;
   chmod 440 wp-config.php
   ```

6. **Import Database** (if fresh install)
   ```bash
   mysql -u user -p database < backup.sql

   # Update site URLs
   wp search-replace 'http://localhost/hedless' 'https://production.com' --all-tables
   ```

7. **Test Everything**
   - [ ] Site loads correctly
   - [ ] Admin panel accessible
   - [ ] All plugins active
   - [ ] REST API endpoints working
   - [ ] JWT authentication working
   - [ ] WooCommerce functional
   - [ ] Location detection working
   - [ ] Test orders

8. **Monitor**
   - Check error logs
   - Monitor performance
   - Track API requests
   - Review security logs

### Git Workflow

#### Branches

- `main` or `master`: Production-ready code
- `develop`: Development branch
- `feature/*`: Feature branches
- `bugfix/*`: Bug fix branches
- `hotfix/*`: Production hotfixes

#### Current Branch

This repository uses Claude-specific branches for AI-assisted development:

- **Current**: `claude/claude-md-mi67amzvyta9ts9o-01CpH6HhHazJjmcK2Teg5E1p`
- **Main**: (to be set as default production branch)

#### Commit Messages

Follow conventional commits:

```
feat: Add location-based pricing support
fix: Resolve postal code detection issue
docs: Update API documentation
refactor: Improve location detection algorithm
test: Add tests for product filtering
```

#### Pushing Changes

```bash
# Always push to the designated branch
git push -u origin claude/claude-md-mi67amzvyta9ts9o-01CpH6HhHazJjmcK2Teg5E1p

# Network failures: retry with exponential backoff (2s, 4s, 8s, 16s)
```

---

## Troubleshooting

### Common Issues

#### 1. JWT Authentication Not Working

**Symptoms**: 401 errors on protected endpoints

**Solutions**:
- Check JWT_AUTH_SECRET_KEY is set in wp-config.php
- Verify .htaccess forwards Authorization header (line 8)
- Ensure mod_rewrite is enabled on Apache
- Check if JWT plugin is active
- Validate token hasn't expired

**Debug**:
```bash
curl -X POST http://localhost/hedless/wp-json/jwt-auth/v1/token/validate \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 2. Location Not Detected

**Symptoms**: API returns "Location not found"

**Solutions**:
- Verify locations are published and marked as active
- Check postal codes are correctly formatted (no spaces)
- Ensure delivery radius is reasonable
- Verify GPS coordinates are correct
- Test with different detection methods

**Debug**:
```bash
php debug-location-plugin.php
```

#### 3. Products Not Showing for Location

**Symptoms**: Empty product list for valid location

**Solutions**:
- Check product availability settings
- Verify products are published and in stock
- Check location-specific stock levels
- Ensure products aren't excluded from location
- Verify WooCommerce is active

**Debug**:
```php
// Check product meta
$availability = get_post_meta($product_id, '_lbp_availability_type', true);
$locations = get_post_meta($product_id, '_lbp_selected_locations', true);
var_dump($availability, $locations);
```

#### 4. CORS Errors

**Symptoms**: Browser console shows CORS errors

**Solutions**:
- Check CORS headers are set in WordPress
- Verify OPTIONS requests are handled
- Ensure credentials are allowed if needed
- Check frontend domain is allowed

**Debug**:
```bash
curl -X OPTIONS http://localhost/hedless/wp-json/lbp/v1/products \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: GET" \
  -i
```

#### 5. WooCommerce API Errors

**Symptoms**: 404 or 401 on WooCommerce endpoints

**Solutions**:
- Ensure WooCommerce is active
- Verify authentication (JWT or consumer keys)
- Check endpoint URLs are correct
- Ensure REST API is enabled

**Debug**:
```bash
curl http://localhost/hedless/wp-json/wc/v3/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -v
```

#### 6. Plugin Compatibility Issues

**Symptoms**: WooCommerce shows compatibility warnings

**Solutions**:
- Check plugin headers for WC compatibility declarations
- Update to latest WooCommerce version
- Verify HPOS compatibility
- Check WordPress and PHP versions

**Reference**: See `WOOCOMMERCE-COMPATIBILITY-RESOLVED.md` for details.

---

## Additional Resources

### WordPress Documentation
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

### WooCommerce Documentation
- [WooCommerce REST API Docs](https://woocommerce.github.io/woocommerce-rest-api-docs/)
- [WooCommerce Developer Docs](https://woocommerce.com/documentation/plugins/woocommerce/woocommerce-codex/)
- [WooCommerce Hooks Reference](https://woocommerce.github.io/code-reference/hooks/hooks.html)

### JWT Authentication
- [JWT Authentication Plugin](https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/)
- [JWT.io](https://jwt.io/) - JWT debugger

### Project Documentation
- `PLUGIN-IMPLEMENTATION-SUMMARY.md` - Location plugin implementation details
- `WOOCOMMERCE-COMPATIBILITY-RESOLVED.md` - WooCommerce compatibility fixes
- `TEST-RESULTS.md` - JWT and API test results
- `wp-content/plugins/location-based-products/README.md` - Location plugin README

---

## Quick Reference

### Important File Paths

```
wp-config.php                                    # WordPress configuration
.htaccess                                        # Apache configuration
wp-content/plugins/location-based-products/      # Main custom plugin
wp-content/plugins/location-based-products/includes/helpers.php         # Location detection
wp-content/plugins/location-based-products/includes/rest-api.php        # API endpoints
wp-content/plugins/location-based-products/includes/product-integration.php  # WooCommerce integration
wp-content/plugins/headless-woocommerce-api.php  # Custom API bridge
```

### Key Constants

```php
JWT_AUTH_SECRET_KEY          # JWT secret (wp-config.php)
JWT_AUTH_CORS_ENABLE         # Enable CORS for JWT
WP_DEBUG                     # Debug mode
WP_DEBUG_LOG                 # Log to file
DISALLOW_FILE_EDIT           # Disable file editor
WC_ADMIN_DISABLED            # Disable WC admin (false = enabled)
```

### Common WordPress Functions

```php
// Posts
get_posts($args)
wp_insert_post($data)
wp_update_post($data)

// Post Meta
get_post_meta($post_id, $key, $single)
update_post_meta($post_id, $key, $value)
delete_post_meta($post_id, $key)

// Options
get_option($key, $default)
update_option($key, $value)

// Users
get_current_user_id()
current_user_can($capability)

// Hooks
add_action($tag, $callback, $priority, $args)
add_filter($tag, $callback, $priority, $args)
do_action($tag, ...$args)
apply_filters($tag, $value, ...$args)
```

### REST API Response Format

```php
// Success
new WP_REST_Response([
    'success' => true,
    'data' => $result
], 200);

// Error
new WP_Error(
    'error_code',
    'Error message',
    ['status' => 400]
);
```

---

## Conclusion

This codebase implements a comprehensive headless WordPress e-commerce solution with advanced location-based features. When making changes:

1. **Follow WordPress coding standards**
2. **Test thoroughly** using provided test scripts
3. **Document your changes** in code comments and update this file if needed
4. **Security first** - sanitize input, escape output, use nonces
5. **Think headless** - all data should be accessible via REST APIs
6. **Consider locations** - most features should account for location-based filtering

For questions or issues, refer to the project documentation files or WordPress/WooCommerce documentation.

**Happy coding!** 🚀
