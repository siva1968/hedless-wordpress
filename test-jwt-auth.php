<?php
/**
 * Test JWT Authentication Plugin
 */

require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== JWT Authentication Plugin Test ===\n\n";

// Check if plugin is active
if (is_plugin_active('jwt-authentication-for-wp-rest-api/jwt-auth.php')) {
    echo "✅ JWT Authentication plugin is active\n";
} else {
    echo "❌ JWT Authentication plugin is not active\n";
    echo "Attempting to activate...\n";
    $result = activate_plugin('jwt-authentication-for-wp-rest-api/jwt-auth.php');
    if (is_wp_error($result)) {
        echo "❌ Activation failed: " . $result->get_error_message() . "\n";
    } else {
        echo "✅ Plugin activated successfully\n";
    }
}

// Check JWT endpoints
echo "\nChecking JWT endpoints:\n";
$server = rest_get_server();
$routes = $server->get_routes();

$jwt_routes = [];
foreach ($routes as $route => $handlers) {
    if (strpos($route, 'jwt-auth') !== false) {
        $jwt_routes[] = $route;
    }
}

if (!empty($jwt_routes)) {
    echo "✅ JWT routes found:\n";
    foreach ($jwt_routes as $route) {
        echo "   - $route\n";
    }
} else {
    echo "❌ No JWT routes found\n";
}

// Check for required JWT configuration
echo "\nChecking JWT configuration:\n";

$jwt_secret = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : null;
if ($jwt_secret) {
    echo "✅ JWT_AUTH_SECRET_KEY is defined\n";
} else {
    echo "❌ JWT_AUTH_SECRET_KEY not defined in wp-config.php\n";
    echo "Add this to your wp-config.php:\n";
    echo "define('JWT_AUTH_SECRET_KEY', '" . wp_generate_password(64, true, true) . "');\n";
}

$cors_enabled = defined('JWT_AUTH_CORS_ENABLE') ? JWT_AUTH_CORS_ENABLE : false;
echo "CORS enabled: " . ($cors_enabled ? 'Yes' : 'No') . "\n";

echo "\n=== JWT Test Complete ===\n";
?>