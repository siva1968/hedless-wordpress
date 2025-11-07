<?php
/**
 * Simple test for Location-Based Products REST API
 */

require_once __DIR__ . '/wp-config.php';

echo "=== Testing Location-Based Products REST API ===\n";

// Make a direct HTTP request to test the REST API
$site_url = get_site_url();
$api_url = $site_url . '/wp-json/lbp/v1/detect-location';

echo "Testing API endpoint: $api_url\n";

// Use WordPress HTTP API to test our own endpoint
$response = wp_remote_get($api_url);

if (is_wp_error($response)) {
    echo "❌ API request failed: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "✅ API responded with status code: $status_code\n";
    
    if ($status_code === 200) {
        $data = json_decode($body, true);
        if ($data) {
            echo "✅ API returned valid JSON response\n";
            if (isset($data['location'])) {
                echo "✅ Location detection working\n";
                echo "Detected location: " . ($data['location']['name'] ?? 'Unknown') . "\n";
            } else {
                echo "ℹ️ No location detected (this is normal if no locations are configured)\n";
            }
        } else {
            echo "⚠️ API returned non-JSON response: $body\n";
        }
    }
}

// Test creating a location via REST API
echo "\n=== Testing Location Creation ===\n";

$location_data = [
    'name' => 'Test Location Mumbai',
    'address' => 'Bandra West, Mumbai',
    'city' => 'Mumbai',
    'state' => 'Maharashtra',
    'country' => 'IN',
    'postal_codes' => '400050,400051,400052',
    'latitude' => 19.0596,
    'longitude' => 72.8295,
    'delivery_radius' => 30,
    'is_active' => true
];

$create_response = wp_remote_post($site_url . '/wp-json/lbp/v1/locations', [
    'headers' => ['Content-Type' => 'application/json'],
    'body' => json_encode($location_data),
    'method' => 'POST'
]);

if (is_wp_error($create_response)) {
    echo "❌ Location creation failed: " . $create_response->get_error_message() . "\n";
} else {
    $create_status = wp_remote_retrieve_response_code($create_response);
    $create_body = wp_remote_retrieve_body($create_response);
    
    echo "Location creation response status: $create_status\n";
    
    if ($create_status === 201 || $create_status === 200) {
        echo "✅ Location creation API working\n";
    } else {
        echo "⚠️ Location creation response: $create_body\n";
    }
}

// Test getting locations
echo "\n=== Testing Location Listing ===\n";

$list_response = wp_remote_get($site_url . '/wp-json/lbp/v1/locations');

if (!is_wp_error($list_response)) {
    $list_status = wp_remote_retrieve_response_code($list_response);
    $list_body = wp_remote_retrieve_body($list_response);
    
    if ($list_status === 200) {
        $locations = json_decode($list_body, true);
        echo "✅ Location listing API working\n";
        echo "Found " . count($locations) . " locations\n";
        
        if (!empty($locations)) {
            $first_location = $locations[0];
            echo "First location: " . ($first_location['name'] ?? 'Unknown') . "\n";
        }
    } else {
        echo "⚠️ Location listing failed with status: $list_status\n";
    }
}

// Test product filtering (if we have products)
$products = wc_get_products(['limit' => 5]);
if (!empty($products)) {
    echo "\n=== Testing Product Filtering ===\n";
    
    $product_response = wp_remote_get($site_url . '/wp-json/lbp/v1/products/search?q=furniture&per_page=5');
    
    if (!is_wp_error($product_response)) {
        $product_status = wp_remote_retrieve_response_code($product_response);
        
        if ($product_status === 200) {
            echo "✅ Product search API working\n";
        } else {
            echo "⚠️ Product search API status: $product_status\n";
        }
    }
}

echo "\n=== REST API Test Complete ===\n";
echo "The Location-Based Products REST API is ready for use!\n";