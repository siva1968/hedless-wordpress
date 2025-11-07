<?php
/**
 * Activate Location-Based Products plugin programmatically
 */

require_once __DIR__ . '/wp-config.php';

// Include WordPress plugin functions
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin_path = 'location-based-products/location-based-products.php';

echo "=== Activating Location-Based Products Plugin ===\n";

// Check if plugin exists
if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_path)) {
    echo "❌ Plugin file not found: $plugin_path\n";
    exit;
}

echo "✅ Plugin file found\n";

// Check if plugin is already active
if (is_plugin_active($plugin_path)) {
    echo "ℹ️ Plugin is already active\n";
} else {
    // Activate the plugin
    $result = activate_plugin($plugin_path);
    
    if (is_wp_error($result)) {
        echo "❌ Failed to activate plugin: " . $result->get_error_message() . "\n";
        exit;
    } else {
        echo "✅ Plugin activated successfully!\n";
    }
}

// Flush rewrite rules to register custom post types and REST routes
flush_rewrite_rules();
echo "✅ Rewrite rules flushed\n";

// Trigger init action to ensure everything is loaded
do_action('init');
do_action('rest_api_init');

echo "\n=== Testing Plugin After Activation ===\n";

// Test custom post types
$post_types = get_post_types();
if (in_array('lbp_location', $post_types)) {
    echo "✅ lbp_location post type registered\n";
} else {
    echo "❌ lbp_location post type still not registered\n";
}

if (in_array('lbp_service_area', $post_types)) {
    echo "✅ lbp_service_area post type registered\n";
} else {
    echo "❌ lbp_service_area post type still not registered\n";
}

// Test if classes are loaded
if (class_exists('LocationBasedProducts')) {
    echo "✅ LocationBasedProducts main class loaded\n";
} else {
    echo "❌ LocationBasedProducts main class not loaded\n";
}

if (class_exists('LBP_Helpers')) {
    echo "✅ LBP_Helpers class loaded\n";
} else {
    echo "❌ LBP_Helpers class not loaded\n";
}

if (class_exists('LBP_Admin')) {
    echo "✅ LBP_Admin class loaded\n";
} else {
    echo "❌ LBP_Admin class not loaded\n";
}

if (class_exists('LBP_Product_Integration')) {
    echo "✅ LBP_Product_Integration class loaded\n";
} else {
    echo "❌ LBP_Product_Integration class not loaded\n";
}

if (class_exists('LBP_REST_API')) {
    echo "✅ LBP_REST_API class loaded\n";
} else {
    echo "❌ LBP_REST_API class not loaded\n";
}

// Test admin menu registration
global $menu, $submenu;
$location_menu_found = false;
if (!empty($menu)) {
    foreach ($menu as $menu_item) {
        if (isset($menu_item[2]) && $menu_item[2] === 'location-based-products') {
            $location_menu_found = true;
            break;
        }
    }
}

if ($location_menu_found) {
    echo "✅ Admin menu registered\n";
} else {
    echo "❌ Admin menu not registered\n";
}

echo "\n=== Plugin Activation Complete ===\n";
echo "Plugin is now active and ready to use!\n";
echo "You can now:\n";
echo "1. Access the admin interface at: " . admin_url('admin.php?page=location-based-products') . "\n";
echo "2. Create locations and service areas\n";
echo "3. Configure products with location settings\n";
echo "4. Use the REST API endpoints for your frontend\n";