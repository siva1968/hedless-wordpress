# Headless WordPress E-commerce Project

## Project Overview

This is a headless WordPress installation configured for e-commerce with location-based product management. It serves as the backend API for a furniture business operating across multiple locations in India.

**Tech Stack:**
- WordPress (latest version)
- WooCommerce for e-commerce functionality
- JWT Authentication for secure API access
- Custom location-based products plugin
- REST API architecture for headless operations

## Architecture

### Headless WordPress Setup
- WordPress serves only as a backend API
- Frontend is decoupled (React/Next.js/Vue.js)
- All interactions happen through REST API endpoints
- JWT tokens for authentication
- CORS enabled for cross-origin requests

### Key Components

1. **WordPress Core** - Content management system
2. **WooCommerce** - E-commerce engine (Currency: INR)
3. **JWT Authentication** - `jwt-authentication-for-wp-rest-api` plugin
4. **Location-Based Products** - Custom plugin at `wp-content/plugins/location-based-products/`
5. **Headless WooCommerce API** - Custom plugin at `wp-content/plugins/headless-woocommerce-api.php`

## Project Structure

```
/home/user/hedless-wordpress/
├── wp-content/
│   ├── plugins/
│   │   ├── location-based-products/      # Custom location plugin
│   │   │   ├── location-based-products.php
│   │   │   ├── includes/
│   │   │   │   ├── admin.php            # Admin interface
│   │   │   │   ├── helpers.php          # Core functionality
│   │   │   │   ├── product-integration.php
│   │   │   │   └── rest-api.php         # API endpoints
│   │   │   └── assets/                  # CSS/JS assets
│   │   ├── jwt-authentication-for-wp-rest-api/
│   │   ├── woocommerce/                 # WooCommerce plugin
│   │   ├── headless-woocommerce-api.php # Custom WC API
│   │   └── headless-wp-config/          # Headless config
│   ├── themes/                          # WordPress themes
│   └── uploads/                         # Media uploads
├── wp-admin/                            # WordPress admin
├── wp-includes/                         # WordPress core files
├── .htaccess                            # Apache config (CORS, JWT headers)
├── wp-config.php                        # WordPress configuration
├── PLUGIN-IMPLEMENTATION-SUMMARY.md     # Location plugin docs
├── TEST-RESULTS.md                      # JWT auth test results
└── WOOCOMMERCE-COMPATIBILITY-RESOLVED.md

Test Files (for development/debugging):
├── activate-*.php                       # Plugin activation helpers
├── test-*.php                           # Various API tests
├── jwt-test.html                        # JWT token testing UI
└── debug-location-plugin.php            # Location plugin debugger
```

## Core Features

### 1. Location-Based Product Management
**Purpose:** Manage furniture inventory across multiple physical locations with location-specific pricing, stock, and delivery options.

**Capabilities:**
- Create and manage multiple store/warehouse locations
- GPS coordinates and delivery radius
- Postal code mapping for location detection
- Location-specific product availability
- Location-based pricing (regular and sale prices)
- Separate inventory tracking per location
- Delivery options: standard, express, pickup-only

**Custom Post Types:**
- `lbp_location` - Store/warehouse locations
- `lbp_service_area` - Service delivery areas

### 2. JWT Authentication
**Purpose:** Secure token-based authentication for headless API access.

**Configuration:**
- JWT secret key configured in `wp-config.php`
- Authorization headers forwarded via `.htaccess`
- Token validation endpoints available

### 3. WooCommerce Integration
**Purpose:** Full e-commerce functionality with products, orders, customers.

**Features:**
- Product catalog management
- Shopping cart functionality
- Order processing
- Customer management
- Payment gateway integration (extensible)
- Currency: INR (Indian Rupees)

### 4. REST API Endpoints

#### Authentication (`jwt-auth/v1`)
```
POST   /wp-json/jwt-auth/v1/token          # Generate token
POST   /wp-json/jwt-auth/v1/token/validate # Validate token
```

#### Location-Based Products (`lbp/v1`)
```
GET    /wp-json/lbp/v1/detect-location     # Auto-detect user location (IP/GPS/postal)
POST   /wp-json/lbp/v1/set-location        # Set user location manually
GET    /wp-json/lbp/v1/locations           # Get all locations
GET    /wp-json/lbp/v1/products            # Get location-filtered products
GET    /wp-json/lbp/v1/check-availability/{product_id}  # Check product availability
GET    /wp-json/lbp/v1/delivery-options    # Get delivery options
```

#### WooCommerce (`wc/v3`)
```
GET    /wp-json/wc/v3/products             # List products
GET    /wp-json/wc/v3/products/{id}        # Get product
POST   /wp-json/wc/v3/orders               # Create order
GET    /wp-json/wc/v3/orders               # List orders
GET    /wp-json/wc/v3/customers            # List customers
GET    /wp-json/wc/v3/categories           # Product categories
```

#### WordPress Core (`wp/v2`)
```
GET    /wp-json/wp/v2/posts                # Posts
GET    /wp-json/wp/v2/pages                # Pages
GET    /wp-json/wp/v2/media                # Media files
```

#### Custom Headless (`headless/v1`)
```
GET    /wp-json/headless/v1/site-info      # Site information
GET    /wp-json/headless/v1/store-info     # Store configuration
```

## Database Schema

### Location Post Meta
- `_lbp_address` - Physical address
- `_lbp_city` - City name
- `_lbp_state` - State/region
- `_lbp_country` - Country code (e.g., IN)
- `_lbp_postal_codes` - Comma-separated postal codes
- `_lbp_latitude` - GPS latitude (decimal)
- `_lbp_longitude` - GPS longitude (decimal)
- `_lbp_delivery_radius` - Delivery radius in kilometers
- `_lbp_is_active` - Active status (1/0)
- `_lbp_priority` - Location priority (higher = preferred)

### Product Location Meta
- `_lbp_availability_type` - Values: `all`, `specific`, `exclude`
- `_lbp_selected_locations` - Array of location IDs
- `_lbp_location_pricing` - Enable location pricing (yes/no)
- `_lbp_location_prices` - Array: `[location_id => ['price' => x, 'sale_price' => y]]`
- `_lbp_location_stock` - Enable location stock (yes/no)
- `_lbp_location_stock_levels` - Array: `[location_id => quantity]`
- `_lbp_delivery_type` - Values: `standard`, `express`, `pickup_only`

## Common Development Tasks

### Testing JWT Authentication
```bash
# Visit the test UI
open http://localhost/hedless/jwt-test.html

# Or test via curl
curl -X POST http://localhost/hedless/wp-json/jwt-auth/v1/token \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"your_password"}'
```

### Testing Location Detection
```bash
# Auto-detect via IP
curl "http://localhost/hedless/wp-json/lbp/v1/detect-location"

# Detect by postal code
curl "http://localhost/hedless/wp-json/lbp/v1/detect-location?postal_code=400050"

# Detect by GPS
curl "http://localhost/hedless/wp-json/lbp/v1/detect-location?lat=19.0596&lng=72.8295"
```

### Getting Location-Filtered Products
```bash
# Get products for specific location
curl "http://localhost/hedless/wp-json/lbp/v1/products?location_id=123"

# Search products by keyword
curl "http://localhost/hedless/wp-json/lbp/v1/products?q=sofa&location_id=123"

# Get products with pagination
curl "http://localhost/hedless/wp-json/lbp/v1/products?location_id=123&per_page=20&page=2"
```

### Checking Product Availability
```bash
curl "http://localhost/hedless/wp-json/lbp/v1/check-availability/456?location_id=123&quantity=2"
```

### WooCommerce API Requests
```bash
# List products (requires WooCommerce API keys)
curl "http://localhost/hedless/wp-json/wc/v3/products" \
  -u consumer_key:consumer_secret

# Create an order
curl -X POST "http://localhost/hedless/wp-json/wc/v3/orders" \
  -u consumer_key:consumer_secret \
  -H "Content-Type: application/json" \
  -d '{...order_data...}'
```

## Admin Access

### WordPress Admin
URL: `http://localhost/hedless/wp-admin/`
- Manage content, users, settings
- Install/activate plugins
- Configure WooCommerce

### Location-Based Products Admin
URL: `http://localhost/hedless/wp-admin/admin.php?page=location-based-products`
- Add/edit locations
- Configure product availability
- Test location detection
- Import/export location data

## Configuration Files

### wp-config.php
Key configurations:
- Database credentials
- JWT secret key: `JWT_AUTH_SECRET_KEY`
- Debug mode settings
- WP_HOME and WP_SITEURL (for headless)

### .htaccess
Important rules:
- Authorization header forwarding for JWT
- CORS headers configuration
- URL rewriting for pretty permalinks

## Git Workflow

**Current Branch:** `claude/update-claude-md-01853StnRijgW7n6N2Ldv3Dz`
**Main Branch:** Not specified (check repository settings)

### Branch Naming Convention
All Claude development branches must:
- Start with `claude/`
- End with the session ID
- Example: `claude/feature-name-sessionid`

### Commit Guidelines
- Use clear, descriptive commit messages
- Reference issue numbers if applicable
- Keep commits focused and atomic

### Push Instructions
```bash
# Always push to the designated branch
git push -u origin claude/update-claude-md-01853StnRijgW7n6N2Ldv3Dz

# Retry on network failures with exponential backoff
```

## Troubleshooting

### Common Issues

**Location not detected:**
- Verify postal codes are correctly formatted
- Check GPS coordinates are valid decimals
- Ensure locations have `_lbp_is_active = 1`

**Products not showing:**
- Check product availability settings
- Verify location-specific stock levels
- Confirm location is active

**JWT authentication failing:**
- Verify JWT_AUTH_SECRET_KEY in wp-config.php
- Check .htaccess has authorization header rules
- Ensure plugin is activated

**CORS errors:**
- Check .htaccess CORS configuration
- Verify allowed origins match frontend URL
- Check that headers are properly set

### Debug Tools
```bash
# Run location plugin debug
php debug-location-plugin.php

# Test JWT authentication
php test-jwt-auth.php

# Test WooCommerce API
php test-woocommerce-compatibility.php

# Test location API endpoints
php test-location-api.php
```

### Useful WP-CLI Commands
```bash
# List all plugins
wp plugin list

# Activate plugin
wp plugin activate location-based-products

# Check database
wp db check

# Flush rewrite rules
wp rewrite flush

# Export/import database
wp db export backup.sql
wp db import backup.sql
```

## Frontend Integration Example

### React Component for Location-Based Products
```javascript
import { useState, useEffect } from 'react';

function ProductListing() {
  const [location, setLocation] = useState(null);
  const [products, setProducts] = useState([]);

  useEffect(() => {
    // Auto-detect location
    fetch('/wp-json/lbp/v1/detect-location')
      .then(res => res.json())
      .then(data => {
        setLocation(data);
        // Get products for detected location
        return fetch(`/wp-json/lbp/v1/products?location_id=${data.id}`);
      })
      .then(res => res.json())
      .then(setProducts);
  }, []);

  return (
    <div>
      {location && <p>Showing products for: {location.name}</p>}
      <div className="products">
        {products.map(product => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </div>
  );
}
```

## Security Considerations

- JWT tokens should be stored securely (httpOnly cookies or secure storage)
- Always use HTTPS in production
- WooCommerce API keys should be kept secret
- Regular WordPress and plugin updates required
- Implement rate limiting on API endpoints
- Validate and sanitize all user inputs
- Use WordPress nonces for form submissions

## Performance Optimization

- Enable WordPress object caching (Redis/Memcached)
- Use CDN for static assets
- Implement API response caching
- Optimize database queries
- Use pagination for large datasets
- Lazy load images in frontend
- Minify and bundle frontend assets

## Testing Files (Development Only)

These files are for development and should NOT be deployed to production:
- `activate-*.php` - Plugin activation helpers
- `test-*.php` - API and functionality tests
- `debug-*.php` - Debug scripts
- `jwt-test.html` - JWT token testing UI

## Documentation References

- [PLUGIN-IMPLEMENTATION-SUMMARY.md](./PLUGIN-IMPLEMENTATION-SUMMARY.md) - Detailed location plugin documentation
- [TEST-RESULTS.md](./TEST-RESULTS.md) - JWT authentication test results
- [WOOCOMMERCE-COMPATIBILITY-RESOLVED.md](./WOOCOMMERCE-COMPATIBILITY-RESOLVED.md) - WooCommerce integration notes

## Project Status

✅ **Fully Operational**
- WordPress core installed and configured
- WooCommerce active and ready
- JWT authentication working
- Location-based products plugin active and tested
- REST API endpoints functional
- CORS properly configured
- Ready for frontend development

## Next Steps for Development

1. **Add Production Locations**
   - Create actual store/warehouse locations
   - Configure service areas and delivery zones
   - Set up postal code mappings

2. **Configure Products**
   - Add furniture product catalog
   - Set location-specific pricing
   - Configure inventory by location
   - Set delivery options

3. **Build Frontend Application**
   - Implement location detection UI
   - Create product listing pages
   - Build shopping cart functionality
   - Integrate checkout process

4. **Production Deployment**
   - Set up production server
   - Configure SSL certificates
   - Set up production database
   - Configure production API keys
   - Implement caching layer
   - Set up monitoring and logging

## Support and Resources

- WordPress Documentation: https://developer.wordpress.org/
- WooCommerce REST API: https://woocommerce.github.io/woocommerce-rest-api-docs/
- JWT Authentication Plugin: Check plugin directory in `wp-content/plugins/`
- Location Plugin: See `wp-content/plugins/location-based-products/README.md`

---

**Last Updated:** 2025-11-28
**Project Type:** Headless WordPress E-commerce
**Primary Use Case:** Location-based furniture retail system
