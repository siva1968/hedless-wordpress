<?php
/**
 * Simple LBP test
 */
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "Testing LBP API class instantiation...\n";

try {
    if (class_exists('LBP_REST_API')) {
        echo "✅ LBP_REST_API class exists\n";
        $api = new LBP_REST_API();
        echo "✅ LBP_REST_API instantiated successfully\n";
    } else {
        echo "❌ LBP_REST_API class not found\n";
    }
    
    if (class_exists('LBP_Helpers')) {
        echo "✅ LBP_Helpers class exists\n";
        $location = LBP_Helpers::get_default_location();
        echo "✅ Default location: " . ($location ? $location->post_title : 'None') . "\n";
    } else {
        echo "❌ LBP_Helpers class not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
}

echo "Test complete.\n";
?>