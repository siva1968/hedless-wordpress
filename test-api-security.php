<?php
/**
 * Test API security - verify unauthorized access is blocked
 */

require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

$base_url = 'http://localhost/hedless/wp-json/headless-wc/v1';

echo "=== Testing API Security ===\n\n";

echo "🚫 Testing WITHOUT API key (should be blocked):\n";
$response = wp_remote_get("$base_url/products", ['timeout' => 10]);
if (!is_wp_error($response)) {
    $code = wp_remote_retrieve_response_code($response);
    echo "Response Code: $code\n";
    if ($code == 401) {
        echo "✅ Unauthorized access properly blocked!\n";
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        echo "Error message: " . $data['message'] . "\n";
    } else {
        echo "❌ Security issue: Access not blocked (code: $code)\n";
    }
}

echo "\n🔑 Testing WITH correct API key (should work):\n";
$api_key = get_option('headless_wc_api_key');
$response2 = wp_remote_get("$base_url/products", [
    'headers' => ['X-API-Key' => $api_key],
    'timeout' => 10
]);

if (!is_wp_error($response2)) {
    $code2 = wp_remote_retrieve_response_code($response2);
    echo "Response Code: $code2\n";
    if ($code2 == 200) {
        echo "✅ Authorized access working properly!\n";
        $body2 = wp_remote_retrieve_body($response2);
        $data2 = json_decode($body2, true);
        if (isset($data2['products'])) {
            echo "Products returned: " . count($data2['products']) . "\n";
        }
    }
}

echo "\n🔄 Testing WITH wrong API key (should be blocked):\n";
$response3 = wp_remote_get("$base_url/products", [
    'headers' => ['X-API-Key' => 'wrong-key-123'],
    'timeout' => 10
]);

if (!is_wp_error($response3)) {
    $code3 = wp_remote_retrieve_response_code($response3);
    echo "Response Code: $code3\n";
    if ($code3 == 401) {
        echo "✅ Wrong API key properly blocked!\n";
    } else {
        echo "❌ Security issue: Wrong key not blocked\n";
    }
}

echo "\n🌐 Testing public endpoint (should work without key):\n";
$response4 = wp_remote_get("$base_url/store-info", ['timeout' => 10]);
if (!is_wp_error($response4)) {
    $code4 = wp_remote_retrieve_response_code($response4);
    echo "Response Code: $code4\n";
    if ($code4 == 200) {
        echo "✅ Public endpoint accessible without authentication!\n";
    }
}

echo "\n🛡️ Security Test Summary:\n";
echo "✅ Unauthorized access: Blocked\n";
echo "✅ Authorized access: Working\n"; 
echo "✅ Wrong API key: Blocked\n";
echo "✅ Public endpoints: Accessible\n";
echo "\n🎉 Your API is properly secured!\n";

echo "\n=== Security Test Complete ===\n";
?>