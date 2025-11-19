<?php
/**
 * Test GST API Endpoints
 *
 * This script tests the newly implemented GST functionality for the location-based products plugin.
 */

require_once __DIR__ . '/wp-load.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== LOCATION-BASED GST API TEST ===\n\n";

// Test 1: Get GST Rates for a location
echo "TEST 1: Get GST Rates for Location\n";
echo "-----------------------------------\n";

$locations = get_posts([
    'post_type' => 'lbp_location',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (!empty($locations)) {
    $location_id = $locations[0]->ID;
    echo "Testing with Location ID: $location_id\n";
    echo "Location Name: " . $locations[0]->post_title . "\n\n";

    $request = new WP_REST_Request('GET', '/lbp/v1/gst/rates');
    $request->set_param('location_id', $location_id);

    $response = rest_do_request($request);
    $data = $response->get_data();

    echo "Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "❌ No locations found. Please create a location first.\n\n";
}

// Test 2: Calculate GST for a product
echo "\nTEST 2: Calculate GST for Product\n";
echo "-----------------------------------\n";

$products = get_posts([
    'post_type' => 'product',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (!empty($products) && !empty($locations)) {
    $product_id = $products[0]->ID;
    $location_id = $locations[0]->ID;

    echo "Product ID: $product_id\n";
    echo "Product Name: " . $products[0]->post_title . "\n";
    echo "Location ID: $location_id\n\n";

    $request = new WP_REST_Request('POST', '/lbp/v1/gst/calculate');
    $request->set_param('product_id', $product_id);
    $request->set_param('location_id', $location_id);
    $request->set_param('quantity', 2);
    $request->set_param('price', 10000);

    $response = rest_do_request($request);
    $data = $response->get_data();

    echo "Request: Calculate GST for 2 units at ₹10,000 each\n";
    echo "Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "❌ No products or locations found.\n\n";
}

// Test 3: Validate GSTIN
echo "\nTEST 3: Validate GSTIN\n";
echo "-----------------------------------\n";

$test_gstins = [
    '29ABCDE1234F1Z5' => 'Valid GSTIN format',
    '27AAACR5055K1ZV' => 'Real GSTIN example',
    'INVALID123' => 'Invalid GSTIN format'
];

foreach ($test_gstins as $gstin => $description) {
    echo "Testing: $gstin ($description)\n";

    $request = new WP_REST_Request('POST', '/lbp/v1/gst/validate-gstin');
    $request->set_param('gstin', $gstin);

    $response = rest_do_request($request);
    $data = $response->get_data();

    if ($data['valid']) {
        echo "✓ Valid GSTIN\n";
        if (isset($data['details'])) {
            echo "  State Code: " . $data['details']['state_code'] . "\n";
            echo "  PAN: " . $data['details']['pan'] . "\n";
        }
    } else {
        echo "✗ Invalid GSTIN\n";
        echo "  Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
    echo "\n";
}

// Test 4: Get Tax Breakdown
echo "\nTEST 4: Get Tax Breakdown\n";
echo "-----------------------------------\n";

if (!empty($locations)) {
    $location_id = $locations[0]->ID;

    echo "Location ID: $location_id\n";
    echo "Cart Total: ₹50,000\n\n";

    $request = new WP_REST_Request('GET', '/lbp/v1/gst/breakdown');
    $request->set_param('location_id', $location_id);
    $request->set_param('cart_total', 50000);

    $response = rest_do_request($request);
    $data = $response->get_data();

    echo "Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
}

// Test 5: Direct GST Calculation Example
echo "\nTEST 5: Direct GST Calculation\n";
echo "-----------------------------------\n";

if (!empty($locations)) {
    $gst_integration = new LBP_GST_Integration();
    $gst_data = $gst_integration->get_location_gst_data($locations[0]->ID);

    echo "Location: " . $locations[0]->post_title . "\n";
    echo "GST Configuration:\n";
    echo "  Type: " . $gst_data['gst_type'] . "\n";
    echo "  Total Rate: " . $gst_data['gst_rate'] . "%\n";

    if ($gst_data['gst_type'] === 'intrastate') {
        echo "  CGST Rate: " . $gst_data['cgst_rate'] . "%\n";
        echo "  SGST Rate: " . $gst_data['sgst_rate'] . "%\n";
    } else {
        echo "  IGST Rate: " . $gst_data['igst_rate'] . "%\n";
    }
    echo "  HSN Code: " . ($gst_data['hsn_code'] ?: 'Not set') . "\n\n";

    // Manual calculation example
    $base_price = 20000;
    $gst_amount = ($base_price * $gst_data['gst_rate']) / 100;
    $total = $base_price + $gst_amount;

    echo "Example Calculation for ₹20,000:\n";
    echo "  Base Price: ₹" . number_format($base_price, 2) . "\n";
    echo "  GST Amount: ₹" . number_format($gst_amount, 2) . "\n";
    echo "  Total Price: ₹" . number_format($total, 2) . "\n";
}

echo "\n=== GST API TESTS COMPLETE ===\n";
