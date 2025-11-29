<?php
/**
 * PHPUnit Bootstrap File
 * Loads necessary files for testing without full WordPress environment
 */

// Load Composer autoloader first
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// Define ABSPATH for plugin loading
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// For unit tests, we only need the plugin classes
// Load Location-Based Products plugin files
$plugin_dir = ABSPATH . 'wp-content/plugins/location-based-products/';

if (file_exists($plugin_dir . 'includes/helpers.php')) {
    require_once $plugin_dir . 'includes/helpers.php';
}

echo "PHPUnit Bootstrap: Test environment initialized (unit tests mode)\n";
echo "Note: WordPress functions are not loaded for unit tests.\n";
echo "For integration tests requiring WordPress, use the integration test suite.\n";
