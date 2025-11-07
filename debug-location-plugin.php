<?php
/**
 * Debug Location-Based Products plugin
 */

require_once __DIR__ . '/wp-config.php';

echo "=== Debugging Location-Based Products Plugin ===\n";

// Check if plugin is active
if (is_plugin_active('location-based-products/location-based-products.php')) {
    echo "✅ Plugin is active\n";
} else {
    echo "❌ Plugin is not active\n";
}

// Check if classes exist
$classes = [
    'LocationBasedProducts',
    'LBP_Helpers',
    'LBP_REST_API', 
    'LBP_Product_Integration',
    'LBP_Admin'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✅ Class exists: $class\n";
    } else {
        echo "❌ Class missing: $class\n";
    }
}

// Check post types
$post_types = get_post_types(['public' => true], 'names');
if (isset($post_types['lbp_location'])) {
    echo "✅ lbp_location post type registered\n";
} else {
    echo "❌ lbp_location post type not registered\n";
}

if (isset($post_types['lbp_service_area'])) {
    echo "✅ lbp_service_area post type registered\n";
} else {
    echo "❌ lbp_service_area post type not registered\n";
}

// Test basic class methods
if (class_exists('LBP_Helpers')) {
    echo "Testing LBP_Helpers methods:\n";
    
    // Test distance calculation
    try {
        $distance = LBP_Helpers::calculate_distance(28.6139, 77.2090, 28.7041, 77.1025);
        echo "✅ Distance calculation: {$distance} km\n";
    } catch (Exception $e) {
        echo "❌ Distance calculation failed: " . $e->getMessage() . "\n";
    }
    
    // Test default location
    try {
        $default_location = LBP_Helpers::get_default_location();
        if ($default_location) {
            echo "✅ Default location found: " . $default_location->post_title . "\n";
        } else {
            echo "ℹ️ No default location (expected if no locations created)\n";
        }
    } catch (Exception $e) {
        echo "❌ Default location test failed: " . $e->getMessage() . "\n";
    }
}

// Test creating a simple location
echo "\nTesting location creation:\n";
try {
    $location_id = wp_insert_post([
        'post_title' => 'Debug Test Location',
        'post_type' => 'lbp_location',
        'post_status' => 'publish'
    ]);
    
    if (!is_wp_error($location_id)) {
        echo "✅ Location created with ID: $location_id\n";
        
        // Add meta
        update_post_meta($location_id, '_lbp_is_active', '1');
        update_post_meta($location_id, '_lbp_city', 'Test City');
        
        $meta_value = get_post_meta($location_id, '_lbp_is_active', true);
        if ($meta_value === '1') {
            echo "✅ Location meta saved successfully\n";
        } else {
            echo "❌ Location meta not saved properly\n";
        }
        
        // Clean up
        wp_delete_post($location_id, true);
        echo "✅ Test location cleaned up\n";
    } else {
        echo "❌ Failed to create location: " . $location_id->get_error_message() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Location creation test failed: " . $e->getMessage() . "\n";
}

// Check REST API namespace
echo "\nTesting REST API registration:\n";

// Try to check if routes are registered
try {
    $rest_server = rest_get_server();
    $routes = $rest_server->get_routes();
    
    $lbp_routes = [];
    foreach ($routes as $route => $handlers) {
        if (strpos($route, '/lbp/') !== false) {
            $lbp_routes[] = $route;
        }
    }
    
    if (!empty($lbp_routes)) {
        echo "✅ LBP REST routes found:\n";
        foreach ($lbp_routes as $route) {
            echo "  - $route\n";
        }
    } else {
        echo "❌ No LBP REST routes found\n";
    }
} catch (Exception $e) {
    echo "❌ REST API test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";