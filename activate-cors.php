<?php
/**
 * Quick script to activate the CORS plugin
 * Run this once to activate the CORS plugin
 */

// WordPress bootstrap
require_once dirname(__FILE__) . '/wp-load.php';

if (!function_exists('activate_plugin')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Activate the CORS plugin
$plugin_path = 'headless-wordpress-cors.php';

if (!is_plugin_active($plugin_path)) {
    $result = activate_plugin($plugin_path);
    if (is_wp_error($result)) {
        echo "Error activating CORS plugin: " . $result->get_error_message() . "\n";
    } else {
        echo "CORS plugin activated successfully!\n";
    }
} else {
    echo "CORS plugin is already active.\n";
}

// Also add some debug info
echo "WordPress URL: " . get_site_url() . "\n";
echo "REST API URL: " . get_rest_url() . "\n";
echo "Plugins directory: " . WP_PLUGIN_DIR . "\n";
echo "Active plugins: " . implode(", ", get_option('active_plugins', [])) . "\n";
?>