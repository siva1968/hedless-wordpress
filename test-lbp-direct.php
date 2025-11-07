<?php
/**
 * Test Location API with detailed error reporting
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== Location API Direct Test ===\n\n";

// Test if the REST API routes are properly registered
$server = rest_get_server();
$routes = $server->get_routes();

echo "Checking LBP routes:\n";
foreach ($routes as $route => $handlers) {
    if (strpos($route, '/lbp/') !== false) {
        echo "✅ Route registered: $route\n";
    }
}

// Test the detect-location endpoint directly
echo "\nTesting detect-location functionality:\n";
if (class_exists('LBP_REST_API')) {
    $api = new LBP_REST_API();
    
    // Create a mock request
    $request = new WP_REST_Request('POST', '/lbp/v1/detect-location');
    $request->set_param('latitude', 12.9716);
    $request->set_param('longitude', 77.5946);
    
    try {
        $response = $api->detect_location($request);
        if ($response instanceof WP_REST_Response) {
            echo "✅ Direct API call successful\n";
            echo "Response data: " . json_encode($response->get_data()) . "\n";
        } else {
            echo "❌ Direct API call failed\n";
            var_dump($response);
        }
    } catch (Exception $e) {
        echo "❌ Exception in API call: " . $e->getMessage() . "\n";
    }
}

// Test locations endpoint
echo "\nTesting locations endpoint:\n";
try {
    $request = new WP_REST_Request('GET', '/lbp/v1/locations');
    if (class_exists('LBP_REST_API')) {
        $api = new LBP_REST_API();
        $response = $api->get_locations($request);
        if ($response instanceof WP_REST_Response) {
            echo "✅ Locations endpoint working\n";
            $data = $response->get_data();
            echo "Found " . count($data) . " locations\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>