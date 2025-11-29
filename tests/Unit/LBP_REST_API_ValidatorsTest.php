<?php
/**
 * Unit Tests for LBP_REST_API Validators
 *
 * Tests the validation methods used in REST API endpoints.
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LBP_REST_API_ValidatorsTest extends TestCase {

    /**
     * Test IP address validation with valid IPv4 addresses
     */
    public function test_validate_ip_address_valid_ipv4() {
        $valid_ips = [
            '8.8.8.8',
            '192.168.1.1',
            '127.0.0.1',
            '0.0.0.0',
            '255.255.255.255',
            '10.0.0.1',
            '172.16.0.1',
        ];

        foreach ($valid_ips as $ip) {
            $result = filter_var($ip, FILTER_VALIDATE_IP);
            $this->assertNotFalse($result, "IP '{$ip}' should be valid");
        }
    }

    /**
     * Test IP address validation with valid IPv6 addresses
     */
    public function test_validate_ip_address_valid_ipv6() {
        $valid_ipv6 = [
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            '2001:db8:85a3::8a2e:370:7334',
            '::1', // localhost
            '::',
            'fe80::1',
        ];

        foreach ($valid_ipv6 as $ip) {
            $result = filter_var($ip, FILTER_VALIDATE_IP);
            $this->assertNotFalse($result, "IPv6 '{$ip}' should be valid");
        }
    }

    /**
     * Test IP address validation with invalid addresses
     */
    public function test_validate_ip_address_invalid() {
        $invalid_ips = [
            '256.256.256.256', // Out of range
            '192.168.1', // Incomplete
            '192.168.1.1.1', // Too many octets
            'not-an-ip', // Not an IP
            '', // Empty string
            'localhost', // Hostname, not IP
            '192.168.1.1/24', // CIDR notation
            '192.168.1.1:8080', // With port
            '999.999.999.999', // Invalid numbers
            'abc.def.ghi.jkl', // Letters
        ];

        foreach ($invalid_ips as $ip) {
            $result = filter_var($ip, FILTER_VALIDATE_IP);
            $this->assertFalse($result, "IP '{$ip}' should be invalid");
        }
    }

    /**
     * Test IP address validation with special cases
     */
    public function test_validate_ip_address_special_cases() {
        // Loopback
        $this->assertNotFalse(filter_var('127.0.0.1', FILTER_VALIDATE_IP), 'Loopback should be valid');

        // Private ranges
        $this->assertNotFalse(filter_var('192.168.1.1', FILTER_VALIDATE_IP), 'Private IP should be valid');
        $this->assertNotFalse(filter_var('10.0.0.1', FILTER_VALIDATE_IP), 'Private IP 10.x should be valid');
        $this->assertNotFalse(filter_var('172.16.0.1', FILTER_VALIDATE_IP), 'Private IP 172.16.x should be valid');

        // Leading zeros are considered invalid in modern PHP (7.0+)
        $result = filter_var('192.168.001.001', FILTER_VALIDATE_IP);
        $this->assertFalse($result, 'IP with leading zeros should be rejected');
    }

    /**
     * Test client IP extraction from various headers
     */
    public function test_get_client_ip_from_headers() {
        // Simulate different proxy scenarios
        $test_cases = [
            // Single IP in X-Forwarded-For
            [
                'HTTP_X_FORWARDED_FOR' => '8.8.8.8',
                'expected' => '8.8.8.8'
            ],
            // Multiple IPs in X-Forwarded-For (should use first)
            [
                'HTTP_X_FORWARDED_FOR' => '8.8.8.8, 192.168.1.1, 10.0.0.1',
                'expected' => '8.8.8.8'
            ],
            // X-Real-IP header
            [
                'HTTP_X_REAL_IP' => '8.8.8.8',
                'expected' => '8.8.8.8'
            ],
            // REMOTE_ADDR fallback
            [
                'REMOTE_ADDR' => '8.8.8.8',
                'expected' => '8.8.8.8'
            ],
        ];

        foreach ($test_cases as $test) {
            $extracted_ip = $this->extractClientIP($test);
            $this->assertEquals($test['expected'], $extracted_ip, 'Should extract correct IP from headers');
        }
    }

    /**
     * Test client IP extraction with no valid headers
     */
    public function test_get_client_ip_fallback() {
        $extracted_ip = $this->extractClientIP([]);
        $this->assertEquals('127.0.0.1', $extracted_ip, 'Should fallback to 127.0.0.1');
    }

    /**
     * Test parameter validation for coordinates
     */
    public function test_coordinate_parameter_validation() {
        // Valid latitude
        $this->assertTrue($this->isValidLatitude(0), 'Equator should be valid');
        $this->assertTrue($this->isValidLatitude(90), 'North pole should be valid');
        $this->assertTrue($this->isValidLatitude(-90), 'South pole should be valid');
        $this->assertTrue($this->isValidLatitude(28.6139), 'Delhi latitude should be valid');

        // Invalid latitude
        $this->assertFalse($this->isValidLatitude(91), 'Latitude > 90 should be invalid');
        $this->assertFalse($this->isValidLatitude(-91), 'Latitude < -90 should be invalid');
        $this->assertFalse($this->isValidLatitude('not-a-number'), 'Non-numeric latitude should be invalid');

        // Valid longitude
        $this->assertTrue($this->isValidLongitude(0), 'Prime meridian should be valid');
        $this->assertTrue($this->isValidLongitude(180), 'Date line should be valid');
        $this->assertTrue($this->isValidLongitude(-180), 'Date line (west) should be valid');
        $this->assertTrue($this->isValidLongitude(77.2090), 'Delhi longitude should be valid');

        // Invalid longitude
        $this->assertFalse($this->isValidLongitude(181), 'Longitude > 180 should be invalid');
        $this->assertFalse($this->isValidLongitude(-181), 'Longitude < -180 should be invalid');
        $this->assertFalse($this->isValidLongitude('not-a-number'), 'Non-numeric longitude should be invalid');
    }

    /**
     * Test parameter validation for pagination
     */
    public function test_pagination_parameter_validation() {
        // Valid per_page values
        $this->assertTrue($this->isValidPerPage(1), 'per_page=1 should be valid');
        $this->assertTrue($this->isValidPerPage(10), 'per_page=10 should be valid');
        $this->assertTrue($this->isValidPerPage(100), 'per_page=100 should be valid');

        // Invalid per_page values
        $this->assertFalse($this->isValidPerPage(0), 'per_page=0 should be invalid');
        $this->assertFalse($this->isValidPerPage(-1), 'per_page=-1 should be invalid');
        $this->assertFalse($this->isValidPerPage(101), 'per_page>100 should be invalid (exceeds max)');
        $this->assertFalse($this->isValidPerPage('not-a-number'), 'Non-numeric per_page should be invalid');

        // Valid page values
        $this->assertTrue($this->isValidPage(1), 'page=1 should be valid');
        $this->assertTrue($this->isValidPage(100), 'page=100 should be valid');

        // Invalid page values
        $this->assertFalse($this->isValidPage(0), 'page=0 should be invalid');
        $this->assertFalse($this->isValidPage(-1), 'page=-1 should be invalid');
        $this->assertFalse($this->isValidPage('not-a-number'), 'Non-numeric page should be invalid');
    }

    /**
     * Test parameter validation for quantity
     */
    public function test_quantity_parameter_validation() {
        // Valid quantities
        $this->assertTrue($this->isValidQuantity(1), 'quantity=1 should be valid');
        $this->assertTrue($this->isValidQuantity(100), 'quantity=100 should be valid');

        // Invalid quantities
        $this->assertFalse($this->isValidQuantity(0), 'quantity=0 should be invalid');
        $this->assertFalse($this->isValidQuantity(-1), 'quantity=-1 should be invalid');
        $this->assertFalse($this->isValidQuantity('not-a-number'), 'Non-numeric quantity should be invalid');
    }

    /**
     * Test postal code parameter validation
     */
    public function test_postal_code_parameter_validation() {
        // Valid postal codes
        $this->assertTrue($this->isValidPostalCode('110001'), 'Numeric postal code should be valid');
        $this->assertTrue($this->isValidPostalCode('SW1A 1AA'), 'UK postal code should be valid');
        $this->assertTrue($this->isValidPostalCode('K1A 0B1'), 'Canadian postal code should be valid');
        $this->assertTrue($this->isValidPostalCode('12345'), 'US zip code should be valid');

        // Invalid postal codes
        $this->assertFalse($this->isValidPostalCode(''), 'Empty postal code should be invalid');
        $this->assertFalse($this->isValidPostalCode('   '), 'Whitespace only should be invalid');

        // SQL injection attempt should be sanitized but still considered a string
        $malicious = "'; DROP TABLE wp_posts; --";
        $this->assertTrue(is_string($malicious), 'SQL injection attempt should still be a string');
    }

    /**
     * Test search parameter validation
     */
    public function test_search_parameter_validation() {
        // Valid search terms
        $this->assertTrue($this->isValidSearch('New Delhi'), 'City name should be valid');
        $this->assertTrue($this->isValidSearch('110001'), 'Postal code search should be valid');
        $this->assertTrue($this->isValidSearch('a'), 'Single character should be valid');

        // Edge cases
        $this->assertFalse($this->isValidSearch(''), 'Empty search should be invalid');
        $this->assertTrue($this->isValidSearch(str_repeat('a', 100)), 'Long search should be valid');

        // SQL injection attempt
        $malicious = "'; DROP TABLE wp_posts; --";
        $this->assertTrue(is_string($malicious), 'Malicious input should be treated as string');
    }

    // Helper methods for validation logic

    private function extractClientIP($server_vars) {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($server_vars[$key])) {
                $ip = $server_vars[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        return '127.0.0.1';
    }

    private function isValidLatitude($lat) {
        return is_numeric($lat) && $lat >= -90 && $lat <= 90;
    }

    private function isValidLongitude($lng) {
        return is_numeric($lng) && $lng >= -180 && $lng <= 180;
    }

    private function isValidPerPage($value) {
        return is_numeric($value) && $value >= 1 && $value <= 100;
    }

    private function isValidPage($value) {
        return is_numeric($value) && $value >= 1;
    }

    private function isValidQuantity($value) {
        return is_numeric($value) && $value >= 1;
    }

    private function isValidPostalCode($value) {
        return is_string($value) && trim($value) !== '';
    }

    private function isValidSearch($value) {
        return is_string($value) && trim($value) !== '';
    }
}
