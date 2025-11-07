<?php
/**
 * Activate the headless WooCommerce API plugin and test it
 */

require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== Activating Headless WooCommerce API Plugin ===\n\n";

$plugin_file = 'headless-woocommerce-api.php';

// Activate the plugin
$result = activate_plugin($plugin_file);

if (is_wp_error($result)) {
    echo "❌ Plugin activation failed: " . $result->get_error_message() . "\n";
} else {
    echo "✅ Plugin activated successfully!\n\n";
    
    // Test the new endpoints
    echo "🧪 Testing custom WooCommerce API endpoints:\n\n";
    
    // Test 1: Products endpoint
    echo "1️⃣ Testing products endpoint:\n";
    $products_url = home_url('/wp-json/headless-wc/v1/products');
    echo "   URL: $products_url\n";
    
    $response = wp_remote_get($products_url, ['timeout' => 10]);
    if (!is_wp_error($response)) {
        $code = wp_remote_retrieve_response_code($response);
        echo "   HTTP Code: $code\n";
        
        if ($code == 200) {
            echo "   ✅ Products endpoint SUCCESS!\n";
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (isset($data['products'])) {
                echo "   📦 Found " . count($data['products']) . " products\n";
                if (!empty($data['products'])) {
                    $first_product = $data['products'][0];
                    echo "   📱 Sample product: {$first_product['name']} - \${$first_product['price']}\n";
                }
            }
        } else {
            echo "   ❌ Products endpoint failed\n";
        }
    }
    
    // Test 2: Store info endpoint
    echo "\n2️⃣ Testing store info endpoint:\n";
    $store_url = home_url('/wp-json/headless-wc/v1/store-info');
    echo "   URL: $store_url\n";
    
    $response2 = wp_remote_get($store_url, ['timeout' => 10]);
    if (!is_wp_error($response2)) {
        $code2 = wp_remote_retrieve_response_code($response2);
        echo "   HTTP Code: $code2\n";
        
        if ($code2 == 200) {
            echo "   ✅ Store info endpoint SUCCESS!\n";
            $body2 = wp_remote_retrieve_body($response2);
            $data2 = json_decode($body2, true);
            if ($data2) {
                echo "   🏪 Store: {$data2['store_name']}\n";
                echo "   💰 Currency: {$data2['currency']} ({$data2['currency_symbol']})\n";
                echo "   📦 Products: {$data2['products_count']}\n";
            }
        } else {
            echo "   ❌ Store info endpoint failed\n";
        }
    }
    
    echo "\n🎉 Custom WooCommerce API is ready!\n";
    echo "\n📝 Update your frontend to use these endpoints:\n";
    echo "- Products: /wp-json/headless-wc/v1/products\n";
    echo "- Single Product: /wp-json/headless-wc/v1/products/{id}\n";
    echo "- Store Info: /wp-json/headless-wc/v1/store-info\n";
}

echo "\n=== Plugin Activation Complete ===\n";
?>