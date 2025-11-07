<?php
/**
 * Test JWT validation endpoint
 */

require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== JWT Validation Endpoint Test ===\n\n";

// Test the actual endpoint
$url = home_url('/wp-json/jwt-auth/v1/token/validate');
echo "Testing URL: $url\n\n";

// Test without token (should return error)
echo "1. Testing without token:\n";
$response = wp_remote_post($url, [
    'timeout' => 10,
    'headers' => [
        'Content-Type' => 'application/json'
    ]
]);

if (is_wp_error($response)) {
    echo "❌ Error: " . $response->get_error_message() . "\n";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "Response code: $code\n";
    echo "Response body: $body\n";
}

// Test token generation first
echo "\n2. Testing token generation:\n";
$token_url = home_url('/wp-json/jwt-auth/v1/token');
$token_response = wp_remote_post($token_url, [
    'timeout' => 10,
    'headers' => [
        'Content-Type' => 'application/json'
    ],
    'body' => json_encode([
        'username' => 'prasadmasina', // Replace with actual admin username
        'password' => 'Sita@1968@manu' // Replace with actual password
    ])
]);

if (!is_wp_error($token_response)) {
    $token_code = wp_remote_retrieve_response_code($token_response);
    $token_body = wp_remote_retrieve_body($token_response);
    echo "Token response code: $token_code\n";
    echo "Token response: $token_body\n";
    
    if ($token_code == 200) {
        $token_data = json_decode($token_body, true);
        if (isset($token_data['token'])) {
            // Now test validation with token
            echo "\n3. Testing validation with token:\n";
            $validate_response = wp_remote_post($url, [
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token_data['token']
                ]
            ]);
            
            if (!is_wp_error($validate_response)) {
                $validate_code = wp_remote_retrieve_response_code($validate_response);
                $validate_body = wp_remote_retrieve_body($validate_response);
                echo "Validation response code: $validate_code\n";
                echo "Validation response: $validate_body\n";
            }
        }
    }
} else {
    echo "❌ Token generation error: " . $token_response->get_error_message() . "\n";
}

echo "\n=== Test Complete ===\n";
?>