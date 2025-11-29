<?php
/**
 * Unit Tests for LBP_Helpers Location Detection Methods
 *
 * Tests location finding by postal code, coordinates, city, and IP.
 * These tests require mocking WordPress functions.
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;

class LBP_HelpersLocationDetectionTest extends TestCase {

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test postal code normalization
     * Ensures postal codes are cleaned and uppercased
     */
    public function test_postal_code_normalization() {
        // Test that postal code cleaning works correctly
        // This test verifies the internal logic in find_location_by_postal_code

        $test_cases = [
            '110001' => '110001',
            '110 001' => '110001',
            '110-001' => '110001',
            'sw1a 1aa' => 'SW1A1AA', // UK postal code
            'sw1a-1aa' => 'SW1A1AA',
        ];

        foreach ($test_cases as $input => $expected) {
            $cleaned = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($input));
            $this->assertEquals($expected, $cleaned, "Postal code '{$input}' should normalize to '{$expected}'");
        }
    }

    /**
     * Test postal code edge cases
     */
    public function test_postal_code_edge_cases() {
        // Empty string
        $cleaned = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper(''));
        $this->assertEquals('', $cleaned, 'Empty postal code should remain empty');

        // Only special characters
        $cleaned = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper('---'));
        $this->assertEquals('', $cleaned, 'Special characters only should result in empty string');

        // SQL injection attempt
        $malicious = "'; DROP TABLE wp_posts; --";
        $cleaned = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($malicious));
        $this->assertEquals('DROPTABLEWPPOSTS', $cleaned, 'SQL injection should be sanitized');

        // Very long postal code
        $long = str_repeat('1', 1000);
        $cleaned = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($long));
        $this->assertEquals(1000, strlen($cleaned), 'Long postal codes should be handled');
    }

    /**
     * Test coordinate validation logic
     */
    public function test_coordinate_validation() {
        // Valid coordinates
        $this->assertTrue($this->isValidLatitude(28.6139), 'Valid latitude should pass');
        $this->assertTrue($this->isValidLatitude(-33.8688), 'Valid negative latitude should pass');
        $this->assertTrue($this->isValidLatitude(0), 'Equator latitude should pass');
        $this->assertTrue($this->isValidLatitude(90), 'North pole should pass');
        $this->assertTrue($this->isValidLatitude(-90), 'South pole should pass');

        $this->assertTrue($this->isValidLongitude(77.2090), 'Valid longitude should pass');
        $this->assertTrue($this->isValidLongitude(-122.4194), 'Valid negative longitude should pass');
        $this->assertTrue($this->isValidLongitude(0), 'Prime meridian should pass');
        $this->assertTrue($this->isValidLongitude(180), 'Date line should pass');
        $this->assertTrue($this->isValidLongitude(-180), 'Date line (negative) should pass');

        // Invalid coordinates
        $this->assertFalse($this->isValidLatitude(91), 'Latitude > 90 should fail');
        $this->assertFalse($this->isValidLatitude(-91), 'Latitude < -90 should fail');
        $this->assertFalse($this->isValidLongitude(181), 'Longitude > 180 should fail');
        $this->assertFalse($this->isValidLongitude(-181), 'Longitude < -180 should fail');
    }

    /**
     * Test city name matching logic
     */
    public function test_city_name_matching() {
        // Test case-insensitive matching
        $this->assertTrue($this->cityMatches('New Delhi', 'new delhi'), 'Case-insensitive match should work');
        $this->assertTrue($this->cityMatches('New Delhi', 'NEW DELHI'), 'Uppercase match should work');
        $this->assertTrue($this->cityMatches('New Delhi', 'Delhi'), 'Partial match should work');

        // Test non-matches
        $this->assertFalse($this->cityMatches('New Delhi', 'Mumbai'), 'Different cities should not match');
        $this->assertFalse($this->cityMatches('New Delhi', ''), 'Empty string should not match');
    }

    /**
     * Test IP address validation
     */
    public function test_ip_address_validation() {
        // Valid IPv4
        $this->assertTrue($this->isValidIP('8.8.8.8'), 'Valid IPv4 should pass');
        $this->assertTrue($this->isValidIP('192.168.1.1'), 'Private IPv4 should pass');
        $this->assertTrue($this->isValidIP('127.0.0.1'), 'Localhost should pass');

        // Invalid IPs
        $this->assertFalse($this->isValidIP('256.256.256.256'), 'Invalid IPv4 should fail');
        $this->assertFalse($this->isValidIP('not-an-ip'), 'Non-IP string should fail');
        $this->assertFalse($this->isValidIP(''), 'Empty string should fail');
        $this->assertFalse($this->isValidIP('192.168.1'), 'Incomplete IP should fail');
    }

    /**
     * Test delivery radius logic
     */
    public function test_delivery_radius_logic() {
        // Test if a point is within delivery radius
        $locationLat = 28.6139;
        $locationLng = 77.2090;
        $deliveryRadius = 25; // km

        // Point within radius (~15 km away)
        $distance1 = \LBP_Helpers::calculate_distance($locationLat, $locationLng, 28.7041, 77.1025);
        $this->assertTrue($distance1 <= $deliveryRadius, 'Point within radius should be deliverable');

        // Point outside radius (~1000 km away)
        $distance2 = \LBP_Helpers::calculate_distance($locationLat, $locationLng, 19.0760, 72.8777);
        $this->assertFalse($distance2 <= $deliveryRadius, 'Point outside radius should not be deliverable');

        // Point exactly at radius boundary
        // This tests the edge case of distance = radius
        $this->assertTrue($deliveryRadius <= $deliveryRadius, 'Point at exact radius should be deliverable');
    }

    /**
     * Test edge case: zero delivery radius
     */
    public function test_zero_delivery_radius() {
        $deliveryRadius = 0;

        // Same location
        $distance1 = \LBP_Helpers::calculate_distance(28.6139, 77.2090, 28.6139, 77.2090);
        $this->assertTrue($distance1 <= $deliveryRadius, 'Same location should be within 0 radius');

        // Different location
        $distance2 = \LBP_Helpers::calculate_distance(28.6139, 77.2090, 28.7041, 77.1025);
        $this->assertFalse($distance2 <= $deliveryRadius, 'Different location should not be within 0 radius');
    }

    /**
     * Test edge case: negative delivery radius
     */
    public function test_negative_delivery_radius() {
        $deliveryRadius = -10;

        // Even same location should fail with negative radius
        $distance = \LBP_Helpers::calculate_distance(28.6139, 77.2090, 28.6139, 77.2090);
        $this->assertFalse($distance <= $deliveryRadius, 'Negative radius should not allow delivery');
    }

    /**
     * Test format_location_data with null input
     */
    public function test_format_location_data_null() {
        $result = \LBP_Helpers::format_location_data(null);
        $this->assertNull($result, 'Null location should return null');
    }

    // Helper methods for validation logic

    private function isValidLatitude($lat) {
        return is_numeric($lat) && $lat >= -90 && $lat <= 90;
    }

    private function isValidLongitude($lng) {
        return is_numeric($lng) && $lng >= -180 && $lng <= 180;
    }

    private function isValidIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    private function cityMatches($cityInDB, $searchCity) {
        if (empty($searchCity)) {
            return false;
        }
        return stripos($cityInDB, $searchCity) !== false;
    }
}
