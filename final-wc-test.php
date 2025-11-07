<?php
/**
 * Final WooCommerce API test - try creating a simple product first
 */

require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== Final WooCommerce API Test ===\n\n";

// Your actual keys
$consumer_key = 'ck_55a4ba5d3f37fa0933d60877cd71692cbd16e962';
$consumer_secret = 'cs_8e361e13009343fa79a634b96cc27947886fb9d1';

echo "🔑 Using API keys ending in: " . substr($consumer_key, -7) . "\n\n";

// First, let's make sure we have at least one product to test with
echo "📦 Ensuring test products exist...\n";

// Check if we have any products
$existing_products = get_posts([
    'post_type' => 'product',
    'posts_per_page' => 1
]);

if (empty($existing_products)) {
    echo "No products found. Creating a test product...\n";
    
    // Create a simple test product
    $product = new WC_Product_Simple();
    $product->set_name('Test Product for API');
    $product->set_regular_price('19.99');
    $product->set_short_description('A test product for API testing');
    $product->set_status('publish');
    $product_id = $product->save();
    
    if ($product_id) {
        echo "✅ Created test product with ID: $product_id\n";
    } else {
        echo "❌ Failed to create test product\n";
    }
} else {
    echo "✅ Found existing products in store\n";
}

// Now test the API with different methods
echo "\n🧪 Testing API Methods:\n\n";

// Method 1: Basic Auth (standard method)
echo "1️⃣ Testing Basic Authentication:\n";
$url = home_url('/wp-json/wc/v3/products');

$response = wp_remote_get($url, [
    'headers' => [
        'Authorization' => 'Basic ' . base64_encode("$consumer_key:$consumer_secret"),
        'Content-Type' => 'application/json'
    ],
    'timeout' => 15
]);

$code = wp_remote_retrieve_response_code($response);
echo "   HTTP Code: $code\n";

if ($code == 200) {
    echo "   ✅ Basic Auth SUCCESS!\n";
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (is_array($data)) {
        echo "   📦 Found " . count($data) . " products\n";
    }
} else {
    echo "   ❌ Basic Auth failed\n";
    $body = wp_remote_retrieve_body($response);
    echo "   Response: " . substr($body, 0, 100) . "\n";
}

// Method 2: Query Parameters
echo "\n2️⃣ Testing Query Parameter Authentication:\n";
$url_with_params = add_query_arg([
    'consumer_key' => $consumer_key,
    'consumer_secret' => $consumer_secret
], home_url('/wp-json/wc/v3/products'));

$response2 = wp_remote_get($url_with_params, ['timeout' => 15]);
$code2 = wp_remote_retrieve_response_code($response2);
echo "   HTTP Code: $code2\n";

if ($code2 == 200) {
    echo "   ✅ Query Parameters SUCCESS!\n";
    $body2 = wp_remote_retrieve_body($response2);
    $data2 = json_decode($body2, true);
    if (is_array($data2)) {
        echo "   📦 Found " . count($data2) . " products\n";
    }
} else {
    echo "   ❌ Query Parameters failed\n";
    $body2 = wp_remote_retrieve_body($response2);
    echo "   Response: " . substr($body2, 0, 100) . "\n";
}

// Method 3: Test a simpler endpoint
echo "\n3️⃣ Testing System Status Endpoint (simpler test):\n";
$status_url = home_url('/wp-json/wc/v3/system_status');
$response3 = wp_remote_get($status_url, [
    'headers' => [
        'Authorization' => 'Basic ' . base64_encode("$consumer_key:$consumer_secret")
    ],
    'timeout' => 15
]);

$code3 = wp_remote_retrieve_response_code($response3);
echo "   HTTP Code: $code3\n";

if ($code3 == 200) {
    echo "   ✅ System Status SUCCESS!\n";
    echo "   🎉 Your WooCommerce API is working!\n";
} else {
    echo "   ❌ System Status failed\n";
}

// Summary
echo "\n📋 Test Summary:\n";
if ($code == 200 || $code2 == 200 || $code3 == 200) {
    echo "🎉 SUCCESS! At least one authentication method is working.\n";
    
    if ($code2 == 200) {
        echo "\n💡 RECOMMENDATION: Use Query Parameter authentication for your frontend.\n";
        echo "Update your frontend API calls to use URLs like:\n";
        echo "/wp-json/wc/v3/products?consumer_key=YOUR_KEY&consumer_secret=YOUR_SECRET\n";
    } else if ($code == 200) {
        echo "\n💡 RECOMMENDATION: Use Basic Authentication (current setup should work).\n";
    }
} else {
    echo "❌ All authentication methods failed. There might be a server configuration issue.\n";
}

echo "\n=== Test Complete ===\n";
?>