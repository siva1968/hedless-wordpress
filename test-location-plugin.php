<?php
/**
 * Test script for Location-Based Products plugin
 * Run this file to test basic functionality
 */

// Load WordPress environment
require_once __DIR__ . '/wp-config.php';

echo "=== Location-Based Products Plugin Test ===\n";

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    echo "❌ WooCommerce is not active\n";
    exit;
}
echo "✅ WooCommerce is active\n";

// Check if our plugin files exist
$plugin_path = wp_upload_dir()['basedir'] . '/../plugins/location-based-products/';
$required_files = [
    'location-based-products.php',
    'includes/helpers.php',
    'includes/product-integration.php',
    'includes/rest-api.php',
    'includes/admin.php',
    'assets/admin.css',
    'assets/admin.js'
];

foreach ($required_files as $file) {
    if (file_exists(WP_PLUGIN_DIR . '/location-based-products/' . $file)) {
        echo "✅ Found: $file\n";
    } else {
        echo "❌ Missing: $file\n";
    }
}

// Check if custom post types are registered
$post_types = get_post_types();
if (in_array('lbp_location', $post_types)) {
    echo "✅ lbp_location post type registered\n";
} else {
    echo "❌ lbp_location post type not registered\n";
}

if (in_array('lbp_service_area', $post_types)) {
    echo "✅ lbp_service_area post type registered\n";
} else {
    echo "❌ lbp_service_area post type not registered\n";
}

// Check REST API endpoints
$rest_server = rest_get_server();
$routes = $rest_server->get_routes();

$expected_routes = [
    '/lbp/v1/detect-location',
    '/lbp/v1/locations',
    '/lbp/v1/products/location/(?P<location_id>[\d]+)',
    '/lbp/v1/products/search'
];

foreach ($expected_routes as $route) {
    $found = false;
    foreach ($routes as $registered_route => $handlers) {
        if (preg_match('#^' . str_replace('/', '\/', $route) . '$#', $registered_route)) {
            $found = true;
            break;
        }
    }
    if ($found) {
        echo "✅ REST route registered: $route\n";
    } else {
        echo "❌ REST route missing: $route\n";
    }
}

// Test basic functionality
if (class_exists('LBP_Helpers')) {
    echo "✅ LBP_Helpers class loaded\n";
    
    // Test coordinate distance calculation
    $distance = LBP_Helpers::calculate_distance(28.6139, 77.2090, 28.7041, 77.1025);
    if ($distance > 0) {
        echo "✅ Distance calculation working: {$distance} km\n";
    } else {
        echo "❌ Distance calculation failed\n";
    }
} else {
    echo "❌ LBP_Helpers class not loaded\n";
}

// Test database tables (create test location)
$test_location = wp_insert_post([
    'post_title' => 'Test Location - Delhi',
    'post_type' => 'lbp_location',
    'post_status' => 'publish',
    'meta_input' => [
        '_lbp_address' => 'Connaught Place, New Delhi',
        '_lbp_city' => 'New Delhi',
        '_lbp_state' => 'Delhi',
        '_lbp_country' => 'IN',
        '_lbp_postal_codes' => '110001,110002,110003',
        '_lbp_latitude' => 28.6139,
        '_lbp_longitude' => 77.2090,
        '_lbp_delivery_radius' => 25,
        '_lbp_is_active' => '1',
        '_lbp_priority' => '1'
    ]
]);

if (!is_wp_error($test_location)) {
    echo "✅ Test location created successfully (ID: $test_location)\n";
    
    // Clean up
    wp_delete_post($test_location, true);
    echo "✅ Test location cleaned up\n";
} else {
    echo "❌ Failed to create test location: " . $test_location->get_error_message() . "\n";
}

// Test product integration
$products = wc_get_products(['limit' => 1]);
if (!empty($products)) {
    $product = $products[0];
    echo "✅ WooCommerce products accessible\n";
    
    // Test adding location meta to product
    update_post_meta($product->get_id(), '_lbp_availability_type', 'all');
    $saved_meta = get_post_meta($product->get_id(), '_lbp_availability_type', true);
    
    if ($saved_meta === 'all') {
        echo "✅ Product location meta can be saved and retrieved\n";
    } else {
        echo "❌ Product location meta save/retrieve failed\n";
    }
    
    // Clean up
    delete_post_meta($product->get_id(), '_lbp_availability_type');
} else {
    echo "⚠️ No WooCommerce products found for testing\n";
}

echo "\n=== Test Complete ===\n";
echo "The Location-Based Products plugin appears to be properly installed!\n";
echo "Next steps:\n";
echo "1. Activate the plugin in WordPress admin\n";
echo "2. Go to 'Location Products' in the admin menu\n";
echo "3. Add your first location\n";
echo "4. Configure products with location settings\n";
echo "5. Test the REST API endpoints\n";