<?php
/**
 * JWT Authentication Test Script
 * This script tests the JWT authentication setup
 */

// Test configuration
$base_url = 'http://localhost/hedless';
$test_username = 'admin'; // Change this to your WordPress admin username
$test_password = 'password'; // Change this to your WordPress admin password

echo "🔐 JWT Authentication Test Suite\n";
echo "================================\n\n";

// Test 1: Check if JWT endpoints are accessible
echo "Test 1: Checking JWT endpoints...\n";
$jwt_token_url = $base_url . '/wp-json/jwt-auth/v1/token';
$jwt_validate_url = $base_url . '/wp-json/jwt-auth/v1/token/validate';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Content-Type: application/json\r\n",
        'ignore_errors' => true
    ]
]);

// Check if endpoints respond
$response = @file_get_contents($jwt_token_url, false, $context);
if ($response !== false) {
    echo "✅ JWT token endpoint is accessible\n";
} else {
    echo "❌ JWT token endpoint is not accessible\n";
    echo "   URL: $jwt_token_url\n";
}

// Test 2: Check WordPress REST API
echo "\nTest 2: Checking WordPress REST API...\n";
$wp_api_url = $base_url . '/wp-json/wp/v2/posts';
$response = @file_get_contents($wp_api_url, false, $context);
if ($response !== false) {
    echo "✅ WordPress REST API is accessible\n";
    $posts = json_decode($response, true);
    if (is_array($posts)) {
        echo "   Found " . count($posts) . " posts\n";
    }
} else {
    echo "❌ WordPress REST API is not accessible\n";
}

// Test 3: Check WooCommerce REST API
echo "\nTest 3: Checking WooCommerce REST API...\n";
$wc_api_url = $base_url . '/wp-json/wc/v3/products';
$response = @file_get_contents($wc_api_url, false, $context);
if ($response !== false) {
    echo "✅ WooCommerce REST API is accessible\n";
    $products = json_decode($response, true);
    if (is_array($products)) {
        echo "   Found " . count($products) . " products\n";
    }
} else {
    echo "❌ WooCommerce REST API might not be fully configured\n";
    echo "   This is normal if WooCommerce isn't set up yet\n";
}

// Test 4: Check custom endpoints
echo "\nTest 4: Checking custom headless endpoints...\n";
$custom_endpoints = [
    '/wp-json/headless/v1/site-info',
    '/wp-json/headless/v1/store-info'
];

foreach ($custom_endpoints as $endpoint) {
    $url = $base_url . $endpoint;
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        echo "✅ Custom endpoint accessible: $endpoint\n";
        $data = json_decode($response, true);
        if ($endpoint === '/wp-json/headless/v1/site-info' && isset($data['name'])) {
            echo "   Site name: " . $data['name'] . "\n";
        }
    } else {
        echo "❌ Custom endpoint not accessible: $endpoint\n";
    }
}

// Test 5: Test JWT token generation (requires credentials)
echo "\nTest 5: JWT Token Generation Test\n";
echo "Note: This test requires valid WordPress credentials\n";
echo "Please update the \$test_username and \$test_password variables in this script\n";
echo "Current test username: $test_username\n";

// Check wp-config.php for JWT secret
echo "\nTest 6: Checking JWT configuration...\n";
$wp_config_path = __DIR__ . '/wp-config.php';
if (file_exists($wp_config_path)) {
    $wp_config = file_get_contents($wp_config_path);
    if (strpos($wp_config, 'JWT_AUTH_SECRET_KEY') !== false) {
        echo "✅ JWT_AUTH_SECRET_KEY is defined in wp-config.php\n";
    } else {
        echo "❌ JWT_AUTH_SECRET_KEY not found in wp-config.php\n";
    }
    
    if (strpos($wp_config, 'JWT_AUTH_CORS_ENABLE') !== false) {
        echo "✅ JWT_AUTH_CORS_ENABLE is defined\n";
    } else {
        echo "❌ JWT_AUTH_CORS_ENABLE not found\n";
    }
} else {
    echo "❌ wp-config.php not found\n";
}

// Test 7: Check .htaccess configuration
echo "\nTest 7: Checking .htaccess configuration...\n";
$htaccess_path = __DIR__ . '/.htaccess';
if (file_exists($htaccess_path)) {
    $htaccess = file_get_contents($htaccess_path);
    if (strpos($htaccess, 'HTTP_AUTHORIZATION') !== false) {
        echo "✅ Authorization header rewrite rule is present\n";
    } else {
        echo "❌ Authorization header rewrite rule missing\n";
    }
    
    if (strpos($htaccess, 'Access-Control-Allow-Origin') !== false) {
        echo "✅ CORS headers are configured\n";
    } else {
        echo "❌ CORS headers not found\n";
    }
} else {
    echo "❌ .htaccess file not found\n";
}

echo "\n================================\n";
echo "🏁 Test Suite Complete!\n\n";
echo "Next steps:\n";
echo "1. If WordPress isn't installed yet, visit: $base_url/wp-admin/\n";
echo "2. Activate the JWT Authentication plugin\n";
echo "3. Test JWT token generation with your actual credentials\n";
echo "4. Use the JWT test page: $base_url/jwt-test.html\n";
?>