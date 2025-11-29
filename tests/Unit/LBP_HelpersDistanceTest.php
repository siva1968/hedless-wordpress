<?php
/**
 * Unit Tests for LBP_Helpers Distance Calculation
 *
 * Tests the Haversine formula implementation for calculating
 * distances between geographic coordinates.
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LBP_HelpersDistanceTest extends TestCase {

    /**
     * Test basic distance calculation between two known points
     * Delhi to Noida: approximately 10-15 km
     */
    public function test_calculate_distance_delhi_to_noida() {
        $distance = \LBP_Helpers::calculate_distance(28.6139, 77.2090, 28.7041, 77.1025);

        $this->assertGreaterThan(0, $distance, 'Distance should be greater than 0');
        $this->assertLessThan(50, $distance, 'Distance should be less than 50 km');
        $this->assertGreaterThan(10, $distance, 'Distance should be approximately 10-20 km');
    }

    /**
     * Test distance calculation with same coordinates
     * Should return 0
     */
    public function test_calculate_distance_same_coordinates() {
        $distance = \LBP_Helpers::calculate_distance(28.6139, 77.2090, 28.6139, 77.2090);

        $this->assertEquals(0, $distance, 'Distance between same coordinates should be 0');
    }

    /**
     * Test distance calculation with coordinates at equator
     * Ensures formula works at equator (0 latitude)
     */
    public function test_calculate_distance_at_equator() {
        // Two points on equator, 1 degree apart
        $distance = \LBP_Helpers::calculate_distance(0, 0, 0, 1);

        $this->assertGreaterThan(100, $distance, 'Distance should be approximately 111 km (1 degree at equator)');
        $this->assertLessThan(115, $distance, 'Distance should not exceed 115 km');
    }

    /**
     * Test distance calculation with negative coordinates
     * Tests southern and western hemispheres
     */
    public function test_calculate_distance_negative_coordinates() {
        // Sydney to Melbourne (both negative latitude)
        $distance = \LBP_Helpers::calculate_distance(-33.8688, 151.2093, -37.8136, 144.9631);

        $this->assertGreaterThan(700, $distance, 'Distance Sydney to Melbourne should be ~713 km');
        $this->assertLessThan(800, $distance, 'Distance should not exceed 800 km');
    }

    /**
     * Test distance calculation crossing prime meridian
     * Tests coordinates that cross 0 longitude
     */
    public function test_calculate_distance_crossing_prime_meridian() {
        // London to Paris
        $distance = \LBP_Helpers::calculate_distance(51.5074, -0.1278, 48.8566, 2.3522);

        $this->assertGreaterThan(300, $distance, 'Distance London to Paris should be ~340 km');
        $this->assertLessThan(400, $distance, 'Distance should not exceed 400 km');
    }

    /**
     * Test distance calculation crossing international date line
     * Tests coordinates that cross 180/-180 longitude
     */
    public function test_calculate_distance_crossing_date_line() {
        // Points near international date line
        $distance = \LBP_Helpers::calculate_distance(0, 179, 0, -179);

        $this->assertGreaterThan(200, $distance, 'Distance should be approximately 222 km (2 degrees)');
        $this->assertLessThan(250, $distance, 'Distance should not exceed 250 km');
    }

    /**
     * Test very small distances (meters range)
     * Ensures precision for nearby locations
     */
    public function test_calculate_distance_very_small() {
        // Two points very close together (0.001 degrees apart, ~111 meters)
        $distance = \LBP_Helpers::calculate_distance(28.6139, 77.2090, 28.6149, 77.2090);

        $this->assertGreaterThan(0, $distance, 'Distance should be greater than 0');
        $this->assertLessThan(2, $distance, 'Distance should be less than 2 km');
    }

    /**
     * Test maximum distance on Earth (antipodal points)
     * Half the Earth's circumference: ~20,000 km
     */
    public function test_calculate_distance_antipodal_points() {
        // Opposite sides of Earth
        $distance = \LBP_Helpers::calculate_distance(0, 0, 0, 180);

        $this->assertGreaterThan(19000, $distance, 'Antipodal distance should be ~20,015 km');
        $this->assertLessThan(21000, $distance, 'Distance should not exceed 21,000 km');
    }

    /**
     * Test distance symmetry
     * Distance from A to B should equal B to A
     */
    public function test_calculate_distance_symmetry() {
        $distanceAB = \LBP_Helpers::calculate_distance(28.6139, 77.2090, 19.0760, 72.8777);
        $distanceBA = \LBP_Helpers::calculate_distance(19.0760, 72.8777, 28.6139, 77.2090);

        $this->assertEquals($distanceAB, $distanceBA, 'Distance should be symmetric (A to B = B to A)');
    }

    /**
     * Test with extreme valid latitude values
     * North and South poles
     */
    public function test_calculate_distance_polar_regions() {
        // North pole to a point near it
        $distance = \LBP_Helpers::calculate_distance(90, 0, 89, 0);

        $this->assertGreaterThan(100, $distance, 'Distance should be approximately 111 km');
        $this->assertLessThan(115, $distance, 'Distance should not exceed 115 km');
    }

    /**
     * Test return type and format
     * Should return a positive float or 0
     */
    public function test_calculate_distance_return_type() {
        $distance = \LBP_Helpers::calculate_distance(28.6139, 77.2090, 28.7041, 77.1025);

        $this->assertIsNumeric($distance, 'Distance should be numeric');
        $this->assertGreaterThanOrEqual(0, $distance, 'Distance should never be negative');
    }
}
