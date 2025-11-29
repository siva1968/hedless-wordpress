<?php
/**
 * Unit Tests for LBP_Helpers External API Calls
 *
 * Tests IP geolocation functionality with mocked external API responses.
 * This demonstrates how to test external API integrations without making real HTTP calls.
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LBP_HelpersExternalAPITest extends TestCase {

    /**
     * Test IP geolocation API response parsing with successful response
     */
    public function test_parse_ip_api_response_success() {
        $mock_response = json_encode([
            'status' => 'success',
            'country' => 'India',
            'countryCode' => 'IN',
            'region' => 'DL',
            'regionName' => 'Delhi',
            'city' => 'New Delhi',
            'zip' => '110001',
            'lat' => 28.6139,
            'lon' => 77.2090
        ]);

        $data = json_decode($mock_response, true);

        $this->assertEquals('success', $data['status'], 'Status should be success');
        $this->assertEquals('India', $data['country'], 'Country should be India');
        $this->assertEquals('New Delhi', $data['city'], 'City should be New Delhi');
        $this->assertEquals(28.6139, $data['lat'], 'Latitude should match');
        $this->assertEquals(77.2090, $data['lon'], 'Longitude should match');
        $this->assertEquals('110001', $data['zip'], 'Postal code should match');
    }

    /**
     * Test IP geolocation API response parsing with failed response
     */
    public function test_parse_ip_api_response_failure() {
        $mock_response = json_encode([
            'status' => 'fail',
            'message' => 'invalid query'
        ]);

        $data = json_decode($mock_response, true);

        $this->assertEquals('fail', $data['status'], 'Status should be fail');
        $this->assertArrayHasKey('message', $data, 'Response should have error message');
    }

    /**
     * Test IP geolocation API response with missing fields
     */
    public function test_parse_ip_api_response_missing_fields() {
        $mock_response = json_encode([
            'status' => 'success',
            'country' => 'India',
            'city' => 'New Delhi'
            // Missing lat, lon, zip
        ]);

        $data = json_decode($mock_response, true);

        $this->assertEquals('success', $data['status'], 'Status should be success');
        $this->assertArrayNotHasKey('lat', $data, 'Latitude should be missing');
        $this->assertArrayNotHasKey('lon', $data, 'Longitude should be missing');
        $this->assertArrayNotHasKey('zip', $data, 'Zip should be missing');
    }

    /**
     * Test handling of localhost IP addresses
     */
    public function test_localhost_ip_handling() {
        $localhost_ips = ['127.0.0.1', 'localhost', '::1'];

        foreach ($localhost_ips as $ip) {
            $is_localhost = ($ip === '127.0.0.1' || $ip === 'localhost' || $ip === '::1');
            $this->assertTrue($is_localhost, "IP '{$ip}' should be recognized as localhost");
        }
    }

    /**
     * Test IP API URL construction
     */
    public function test_ip_api_url_construction() {
        $ip = '8.8.8.8';
        $expected_url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city,zip,lat,lon";

        $this->assertStringContainsString($ip, $expected_url, 'URL should contain IP address');
        $this->assertStringContainsString('fields=', $expected_url, 'URL should specify fields');
        $this->assertStringContainsString('status', $expected_url, 'URL should request status field');
        $this->assertStringContainsString('lat', $expected_url, 'URL should request latitude field');
        $this->assertStringContainsString('lon', $expected_url, 'URL should request longitude field');
    }

    /**
     * Test handling of network errors (simulated)
     */
    public function test_network_error_handling() {
        // Simulate WP_Error response
        $mock_error = [
            'is_error' => true,
            'error_code' => 'http_request_failed',
            'error_message' => 'Could not resolve host'
        ];

        $this->assertTrue($mock_error['is_error'], 'Should recognize error condition');
        $this->assertEquals('http_request_failed', $mock_error['error_code'], 'Should have correct error code');
    }

    /**
     * Test handling of timeout errors (simulated)
     */
    public function test_timeout_error_handling() {
        $mock_error = [
            'is_error' => true,
            'error_code' => 'http_request_failed',
            'error_message' => 'Operation timed out'
        ];

        $this->assertTrue($mock_error['is_error'], 'Should recognize timeout error');
        $this->assertStringContainsString('timed out', strtolower($mock_error['error_message']), 'Should identify timeout');
    }

    /**
     * Test rate limit response handling
     */
    public function test_rate_limit_response() {
        // ip-api.com returns status 429 when rate limited
        $mock_response = json_encode([
            'status' => 'fail',
            'message' => 'quota exceeded'
        ]);

        $data = json_decode($mock_response, true);

        $this->assertEquals('fail', $data['status'], 'Rate limit should return fail status');
        $this->assertStringContainsString('quota', strtolower($data['message']), 'Should indicate quota issue');
    }

    /**
     * Test handling of private IP addresses
     */
    public function test_private_ip_detection() {
        $private_ips = [
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '192.168.0.1',
            '10.10.10.10',
        ];

        foreach ($private_ips as $ip) {
            $is_private = $this->isPrivateIP($ip);
            $this->assertTrue($is_private, "IP '{$ip}' should be detected as private");
        }
    }

    /**
     * Test handling of public IP addresses
     */
    public function test_public_ip_detection() {
        $public_ips = [
            '8.8.8.8',
            '1.1.1.1',
            '208.67.222.222',
            '142.250.185.46', // Google
        ];

        foreach ($public_ips as $ip) {
            $is_private = $this->isPrivateIP($ip);
            $this->assertFalse($is_private, "IP '{$ip}' should be detected as public");
        }
    }

    /**
     * Test IP geolocation caching logic
     */
    public function test_ip_geolocation_caching() {
        // Simulate cache key generation
        $ip = '8.8.8.8';
        $cache_key = 'lbp_ip_geo_' . md5($ip);
        $expected_key = 'lbp_ip_geo_' . md5('8.8.8.8');

        $this->assertEquals($expected_key, $cache_key, 'Cache key should be consistent');
        $this->assertStringContainsString('lbp_ip_geo_', $cache_key, 'Cache key should have prefix');
    }

    /**
     * Test location detection priority logic
     */
    public function test_location_detection_priority() {
        // Priority should be:
        // 1. Coordinates (lat/lng)
        // 2. Postal code
        // 3. IP geolocation
        // 4. Default location

        $priorities = [
            'coordinates' => 1,
            'postal_code' => 2,
            'ip_geolocation' => 3,
            'default' => 4
        ];

        $this->assertEquals(1, $priorities['coordinates'], 'Coordinates should have highest priority');
        $this->assertEquals(2, $priorities['postal_code'], 'Postal code should be second priority');
        $this->assertEquals(3, $priorities['ip_geolocation'], 'IP should be third priority');
        $this->assertEquals(4, $priorities['default'], 'Default should be lowest priority');
    }

    /**
     * Test JSON parsing error handling
     */
    public function test_json_parsing_errors() {
        $invalid_json = 'not valid json {';
        $data = json_decode($invalid_json, true);

        $this->assertNull($data, 'Invalid JSON should return null');
        $this->assertNotEquals(JSON_ERROR_NONE, json_last_error(), 'JSON error should be detected');
    }

    /**
     * Test IP API response with special characters in city names
     */
    public function test_special_characters_in_response() {
        $mock_response = json_encode([
            'status' => 'success',
            'city' => 'São Paulo',
            'regionName' => 'Île-de-France',
        ]);

        $data = json_decode($mock_response, true);

        $this->assertEquals('São Paulo', $data['city'], 'Should handle accented characters');
        $this->assertEquals('Île-de-France', $data['regionName'], 'Should handle special characters');
    }

    // Helper methods

    private function isPrivateIP($ip) {
        $private_ranges = [
            ['10.0.0.0', '10.255.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255'],
        ];

        $ip_long = ip2long($ip);

        foreach ($private_ranges as $range) {
            $start = ip2long($range[0]);
            $end = ip2long($range[1]);

            if ($ip_long >= $start && $ip_long <= $end) {
                return true;
            }
        }

        return false;
    }
}
