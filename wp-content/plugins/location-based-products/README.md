# Location-Based Products for WooCommerce

A comprehensive location-based product management system for furniture e-commerce built for headless WordPress with WooCommerce.

## Overview

This plugin enables location-specific product availability, pricing, and inventory management. It's designed for furniture businesses that need to show different products and pricing based on customer location.

## Features

### Location Management
- **Custom Location Types**: Manage locations and service areas with hierarchical relationships
- **Geographic Detection**: Support for IP-based, postal code, GPS coordinates, and city-based location detection
- **Service Areas**: Define delivery areas for each location with postal code ranges
- **Priority System**: Set location priority for overlapping service areas

### Product Integration
- **Availability Control**: Set products as available in all locations, specific locations, or exclude from certain locations
- **Location-Based Pricing**: Different regular and sale prices per location
- **Location-Specific Inventory**: Separate stock levels for each location
- **Delivery Options**: Configure standard, express, or pickup-only delivery per product and location

### REST API Endpoints
- **Location Detection**: `/wp-json/lbp/v1/detect-location` - Automatically detect user location
- **Product Filtering**: `/wp-json/lbp/v1/products/location/{location_id}` - Get products for specific location
- **Location Data**: `/wp-json/lbp/v1/locations` - Manage location information
- **Search Products**: `/wp-json/lbp/v1/products/search` - Search products by location and query

### Admin Interface
- **Location Management**: User-friendly interface for adding and managing locations
- **Product Configuration**: Bulk actions for setting location availability and pricing
- **Testing Tools**: Test location detection with different inputs (IP, postal code, coordinates)
- **Import/Export**: CSV import/export for bulk location management

## Installation

1. Upload the plugin to `/wp-content/plugins/location-based-products/`
2. Activate the plugin through WordPress admin
3. Ensure WooCommerce is installed and activated
4. Navigate to "Location Products" in the admin menu to start configuration

## Quick Start Guide

### 1. Create Your First Location
1. Go to **Location Products > Locations**
2. Click "Add New Location"
3. Fill in the required information:
   - Location Name (e.g., "Mumbai Store")
   - Address
   - Postal codes served (comma-separated)
   - GPS coordinates (optional)
   - Delivery radius in kilometers

### 2. Configure Products
1. Edit any WooCommerce product
2. In the **Location Settings** section:
   - Set availability type (All locations, Specific locations, Exclude locations)
   - Enable location-based pricing if needed
   - Set location-specific stock levels
   - Choose delivery type (Standard, Express, Pickup only)

### 3. Test Location Detection
1. Go to **Location Products > Test Detection**
2. Test with different inputs:
   - IP addresses
   - Postal codes
   - GPS coordinates
   - City names

### 4. Frontend Integration
Use the REST API endpoints in your headless frontend to:
- Detect user location automatically
- Filter products by location
- Show location-specific pricing and availability

## REST API Usage

### Detect User Location
```javascript
// Auto-detect from user's IP
fetch('/wp-json/lbp/v1/detect-location')
  .then(response => response.json())
  .then(location => console.log(location));

// Detect by postal code
fetch('/wp-json/lbp/v1/detect-location', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ postal_code: '110001' })
})
```

### Get Products for Location
```javascript
// Get products available in specific location
fetch('/wp-json/lbp/v1/products/location/123')
  .then(response => response.json())
  .then(products => {
    products.forEach(product => {
      console.log(product.name, product.location_availability);
    });
  });
```

### Search Products
```javascript
// Search products by query and location
fetch('/wp-json/lbp/v1/products/search?location_id=123&q=sofa&per_page=12')
  .then(response => response.json())
  .then(results => console.log(results));
```

## Configuration Options

### Location Detection Methods
1. **IP Address**: Automatically detect using IP geolocation services
2. **Postal Code**: Manual entry by user or auto-detection
3. **GPS Coordinates**: Browser geolocation API
4. **City/Region**: Fuzzy matching based on city names

### Product Availability Types
- **All Locations**: Product available everywhere
- **Specific Locations**: Product only available in selected locations
- **Exclude Locations**: Product available everywhere except selected locations

### Delivery Types
- **Standard**: Regular delivery (5-7 days, free)
- **Express**: Fast delivery (1-2 days, additional cost)
- **Pickup Only**: Must be picked up from store

## Advanced Features

### Location-Based Pricing
Enable different pricing for different locations:
```php
// Example: Mumbai gets special pricing
$mumbai_price = get_post_meta($product_id, '_lbp_location_prices', true);
$mumbai_price[123] = [
    'regular' => 15000,
    'sale' => 12000
];
update_post_meta($product_id, '_lbp_location_prices', $mumbai_price);
```

### Service Areas
Create service areas for each location to handle complex delivery zones:
- Parent location relationship
- Postal code ranges
- Custom delivery rules

### Caching and Performance
- Location detection results are cached to improve performance
- Configurable cache duration in plugin settings
- Optimized database queries for large product catalogs

## Hooks and Filters

### Actions
- `lbp_location_detected` - Fired when location is detected
- `lbp_product_availability_checked` - Fired when product availability is checked
- `lbp_before_location_save` - Before location is saved
- `lbp_after_location_save` - After location is saved

### Filters
- `lbp_location_detection_methods` - Modify available detection methods
- `lbp_product_location_availability` - Filter product availability
- `lbp_location_query_args` - Modify location query arguments
- `lbp_delivery_options` - Customize delivery options

## Troubleshooting

### Location Not Detected
1. Check if locations are marked as "Active"
2. Verify postal codes are correctly formatted
3. Test with the built-in testing tool
4. Check error logs for API issues

### Products Not Showing
1. Verify product availability settings
2. Check location-specific stock levels
3. Ensure location is within delivery radius
4. Review product status and visibility

### Performance Issues
1. Increase cache duration in settings
2. Optimize location data (remove unused postal codes)
3. Consider using external geolocation services for better accuracy

## Support and Development

### File Structure
```
location-based-products/
├── location-based-products.php (Main plugin file)
├── includes/
│   ├── admin.php (Admin interface)
│   ├── helpers.php (Core functionality)
│   ├── product-integration.php (WooCommerce integration)
│   └── rest-api.php (API endpoints)
├── assets/
│   ├── admin.css (Admin styles)
│   └── admin.js (Admin JavaScript)
└── README.md
```

### Database Tables
The plugin uses WordPress custom posts and meta tables:
- `wp_posts` - Locations and service areas
- `wp_postmeta` - Location settings and product configurations

### Requirements
- WordPress 6.0+
- WooCommerce 6.0+
- PHP 7.4+
- MySQL 5.6+

## Examples

### Frontend React Component
```jsx
import { useState, useEffect } from 'react';

function LocationBasedProducts() {
  const [location, setLocation] = useState(null);
  const [products, setProducts] = useState([]);

  useEffect(() => {
    // Detect user location
    fetch('/wp-json/lbp/v1/detect-location')
      .then(res => res.json())
      .then(loc => {
        setLocation(loc);
        // Get products for this location
        return fetch(`/wp-json/lbp/v1/products/location/${loc.id}`);
      })
      .then(res => res.json())
      .then(setProducts);
  }, []);

  return (
    <div>
      {location && <p>Showing products for {location.name}</p>}
      {products.map(product => (
        <div key={product.id}>
          <h3>{product.name}</h3>
          <p>Price: ₹{product.price}</p>
          <p>Stock: {product.stock_quantity} available</p>
        </div>
      ))}
    </div>
  );
}
```

### WordPress Theme Integration
```php
// Get user's location in theme
$user_location = do_action('lbp_detect_user_location');

// Get products for location
$products = get_posts([
    'post_type' => 'product',
    'meta_query' => [
        [
            'key' => '_lbp_available_locations',
            'value' => $user_location->ID,
            'compare' => 'LIKE'
        ]
    ]
]);
```

## Version History

### 1.0.0
- Initial release
- Location management system
- Product integration
- REST API endpoints
- Admin interface
- Location detection algorithms
- Import/export functionality

## License

GPL v2 or later. See the plugin header for full license information.