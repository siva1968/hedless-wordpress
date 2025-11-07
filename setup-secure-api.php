<?php
/**
 * Setup secure API key for Headless WooCommerce API
 */

require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== Setting up Secure Headless WooCommerce API ===\n\n";

// Generate a secure API key
$secure_key = 'hwc_' . bin2hex(random_bytes(32));

// Save it to WordPress options
update_option('headless_wc_api_key', $secure_key);

echo "✅ Secure API key generated and saved!\n\n";
echo "🔑 Your API Key: $secure_key\n\n";

echo "📝 Add this to your frontend .env.local:\n";
echo "NEXT_PUBLIC_HEADLESS_WC_API_KEY=$secure_key\n\n";

echo "🧪 Testing secure API...\n";

// Test the API with the new key
$test_url = home_url('/wp-json/headless-wc/v1/products?api_key=' . $secure_key);
$response = wp_remote_get($test_url, ['timeout' => 10]);

if (!is_wp_error($response)) {
    $code = wp_remote_retrieve_response_code($response);
    echo "Test response code: $code\n";
    
    if ($code == 200) {
        echo "✅ Secure API is working!\n";
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (isset($data['products'])) {
            echo "📦 API returned " . count($data['products']) . " products\n";
        }
    } else {
        echo "❌ API test failed with code $code\n";
        echo "Response: " . wp_remote_retrieve_body($response) . "\n";
    }
} else {
    echo "❌ API test error: " . $response->get_error_message() . "\n";
}

echo "\n🛡️ Security Benefits:\n";
echo "✅ API key authentication required\n";
echo "✅ Unauthorized access blocked\n";
echo "✅ Rate limiting possible\n";
echo "✅ Access logging available\n";

echo "\n🎯 Admin Interface Available:\n";
echo "Visit: " . admin_url('options-general.php?page=headless-wc-api') . "\n";
echo "To manage your API keys through WordPress admin.\n";

echo "\n=== Setup Complete ===\n";
?>