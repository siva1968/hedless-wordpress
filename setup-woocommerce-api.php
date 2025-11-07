<?php
/**
 * Setup WooCommerce API Keys for Headless Access
 * This script creates the necessary API keys for WooCommerce REST API access
 */

require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

if (!class_exists('WooCommerce')) {
    die("❌ WooCommerce is not installed or activated!\n");
}

echo "=== WooCommerce API Key Setup ===\n\n";

// Check if we're running from command line or web
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    echo "<pre>";
}

try {
    // Check if API keys already exist
    global $wpdb;
    $table_name = $wpdb->prefix . 'woocommerce_api_keys';
    
    $existing_keys = $wpdb->get_results(
        "SELECT * FROM $table_name WHERE description LIKE '%Headless%'"
    );
    
    if (!empty($existing_keys)) {
        echo "✅ Headless API keys already exist:\n";
        foreach ($existing_keys as $key) {
            echo "   Description: " . $key->description . "\n";
            echo "   Consumer Key: " . substr($key->consumer_key, 0, 10) . "...\n";
            echo "   Permissions: " . $key->permissions . "\n\n";
        }
    } else {
        echo "🔧 Creating new API keys for headless access...\n";
        
        // Generate consumer key and secret (shorter for database compatibility)
        $consumer_key = 'ck_' . substr(wc_rand_hash(), 0, 32);
        $consumer_secret = 'cs_' . substr(wc_rand_hash(), 0, 32);
        
        // Hash the consumer secret for storage
        $consumer_secret_hash = hash('sha256', $consumer_secret);
        
        // Insert into database
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => 1, // Admin user
                'description' => 'Headless Frontend API Access',
                'permissions' => 'read_write',
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret_hash,
                'truncated_key' => substr($consumer_key, -7)
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result !== false) {
            echo "✅ API keys created successfully!\n\n";
            echo "🔑 Your WooCommerce API Credentials:\n";
            echo "   Consumer Key: $consumer_key\n";
            echo "   Consumer Secret: $consumer_secret\n";
            echo "   Permissions: read_write\n\n";
            
            echo "📝 Add these to your frontend .env.local file:\n";
            echo "   NEXT_PUBLIC_WC_CONSUMER_KEY=$consumer_key\n";
            echo "   NEXT_PUBLIC_WC_CONSUMER_SECRET=$consumer_secret\n\n";
            
            // Test the API with the new keys
            echo "🧪 Testing API access...\n";
            $test_url = home_url('/wp-json/wc/v3/products');
            $auth_string = base64_encode($consumer_key . ':' . $consumer_secret);
            
            $response = wp_remote_get($test_url, array(
                'headers' => array(
                    'Authorization' => 'Basic ' . $auth_string
                ),
                'timeout' => 10
            ));
            
            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code === 200) {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    echo "✅ API test successful! Found " . count($body) . " products.\n";
                } else {
                    echo "⚠️ API test returned HTTP $response_code\n";
                }
            } else {
                echo "❌ API test failed: " . $response->get_error_message() . "\n";
            }
            
        } else {
            echo "❌ Failed to create API keys!\n";
            echo "Database error: " . $wpdb->last_error . "\n";
        }
    }
    
    // Show current WooCommerce settings
    echo "\n📋 Current WooCommerce API Settings:\n";
    echo "   REST API Enabled: " . (get_option('woocommerce_api_enabled') ? 'Yes' : 'No') . "\n";
    echo "   Legacy API Enabled: " . (get_option('woocommerce_legacy_api_enabled') ? 'Yes' : 'No') . "\n";
    
    // Enable REST API if not enabled
    if (!get_option('woocommerce_api_enabled')) {
        update_option('woocommerce_api_enabled', 'yes');
        echo "✅ WooCommerce REST API has been enabled!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

if (!$is_cli) {
    echo "</pre>";
}

echo "\n🚀 Next Steps:\n";
echo "1. Copy the API credentials to your frontend .env.local file\n";
echo "2. Update your frontend API calls to use these credentials\n";
echo "3. Run the health check script again\n";
?>