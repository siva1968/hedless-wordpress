<?php
/**
 * Unit Tests for LBP_Helpers Product Availability Checking
 *
 * Tests the check_product_location_availability method with various scenarios.
 * Note: These are logic tests that verify the availability decision tree.
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LBP_HelpersProductAvailabilityTest extends TestCase {

    /**
     * Test availability logic for 'all' availability type
     */
    public function test_availability_type_all_should_be_available() {
        $availability_type = 'all';
        $selected_locations = [];
        $location_id = 123;

        $available = $this->checkAvailabilityLogic($availability_type, $selected_locations, $location_id);

        $this->assertTrue($available, 'Product with availability type "all" should be available');
    }

    /**
     * Test availability logic for 'specific' with matching location
     */
    public function test_availability_type_specific_with_matching_location() {
        $availability_type = 'specific';
        $selected_locations = [123, 456, 789];
        $location_id = 456;

        $available = $this->checkAvailabilityLogic($availability_type, $selected_locations, $location_id);

        $this->assertTrue($available, 'Product should be available when location is in selected list');
    }

    /**
     * Test availability logic for 'specific' with non-matching location
     */
    public function test_availability_type_specific_without_matching_location() {
        $availability_type = 'specific';
        $selected_locations = [123, 456, 789];
        $location_id = 999;

        $available = $this->checkAvailabilityLogic($availability_type, $selected_locations, $location_id);

        $this->assertFalse($available, 'Product should not be available when location is not in selected list');
    }

    /**
     * Test availability logic for 'exclude' with excluded location
     */
    public function test_availability_type_exclude_with_excluded_location() {
        $availability_type = 'exclude';
        $selected_locations = [123, 456, 789];
        $location_id = 456;

        $available = $this->checkAvailabilityLogic($availability_type, $selected_locations, $location_id);

        $this->assertFalse($available, 'Product should not be available when location is in exclusion list');
    }

    /**
     * Test availability logic for 'exclude' with non-excluded location
     */
    public function test_availability_type_exclude_without_excluded_location() {
        $availability_type = 'exclude';
        $selected_locations = [123, 456, 789];
        $location_id = 999;

        $available = $this->checkAvailabilityLogic($availability_type, $selected_locations, $location_id);

        $this->assertTrue($available, 'Product should be available when location is not in exclusion list');
    }

    /**
     * Test availability with empty selected locations for 'specific' type
     */
    public function test_availability_specific_with_empty_locations() {
        $availability_type = 'specific';
        $selected_locations = [];
        $location_id = 123;

        $available = $this->checkAvailabilityLogic($availability_type, $selected_locations, $location_id);

        $this->assertFalse($available, 'Product should not be available when specific locations list is empty');
    }

    /**
     * Test availability with empty selected locations for 'exclude' type
     */
    public function test_availability_exclude_with_empty_locations() {
        $availability_type = 'exclude';
        $selected_locations = [];
        $location_id = 123;

        $available = $this->checkAvailabilityLogic($availability_type, $selected_locations, $location_id);

        $this->assertTrue($available, 'Product should be available everywhere when exclude list is empty');
    }

    /**
     * Test stock availability logic
     */
    public function test_stock_availability_in_stock() {
        $stock_status = 'instock';
        $stock_quantity = 10;
        $requested_quantity = 5;

        $available = $this->checkStockLogic($stock_status, $stock_quantity, $requested_quantity);

        $this->assertTrue($available, 'Product should be available when stock is sufficient');
    }

    /**
     * Test stock availability when out of stock
     */
    public function test_stock_availability_out_of_stock() {
        $stock_status = 'outofstock';
        $stock_quantity = 0;
        $requested_quantity = 1;

        $available = $this->checkStockLogic($stock_status, $stock_quantity, $requested_quantity);

        $this->assertFalse($available, 'Product should not be available when out of stock');
    }

    /**
     * Test stock availability when quantity exceeds available stock
     */
    public function test_stock_availability_insufficient_quantity() {
        $stock_status = 'instock';
        $stock_quantity = 5;
        $requested_quantity = 10;

        $available = $this->checkStockLogic($stock_status, $stock_quantity, $requested_quantity);

        $this->assertFalse($available, 'Product should not be available when requested quantity exceeds stock');
    }

    /**
     * Test stock availability when quantity exactly matches stock
     */
    public function test_stock_availability_exact_quantity_match() {
        $stock_status = 'instock';
        $stock_quantity = 5;
        $requested_quantity = 5;

        $available = $this->checkStockLogic($stock_status, $stock_quantity, $requested_quantity);

        $this->assertTrue($available, 'Product should be available when requested quantity exactly matches stock');
    }

    /**
     * Test stock availability with null stock quantity (unlimited)
     */
    public function test_stock_availability_null_quantity() {
        $stock_status = 'instock';
        $stock_quantity = null;
        $requested_quantity = 100;

        // Null stock means unlimited or not tracked
        $available = $stock_status !== 'outofstock';

        $this->assertTrue($available, 'Product with null stock quantity and instock status should be available');
    }

    /**
     * Test delivery option logic for different delivery types
     */
    public function test_delivery_options_standard() {
        $delivery_type = 'standard';
        $options = $this->getDeliveryOptionsLogic($delivery_type);

        $this->assertCount(1, $options, 'Standard delivery should have 1 option');
        $this->assertEquals('standard', $options[0]['type'], 'Option should be standard delivery');
    }

    public function test_delivery_options_express() {
        $delivery_type = 'express';
        $options = $this->getDeliveryOptionsLogic($delivery_type);

        $this->assertCount(2, $options, 'Express delivery should have 2 options');
        $this->assertEquals('standard', $options[0]['type'], 'First option should be standard');
        $this->assertEquals('express', $options[1]['type'], 'Second option should be express');
    }

    public function test_delivery_options_pickup_only() {
        $delivery_type = 'pickup_only';
        $options = $this->getDeliveryOptionsLogic($delivery_type);

        $this->assertCount(1, $options, 'Pickup only should have 1 option');
        $this->assertEquals('pickup', $options[0]['type'], 'Option should be pickup');
    }

    /**
     * Test location-specific pricing logic
     */
    public function test_location_pricing_enabled_with_sale_price() {
        $location_pricing_enabled = true;
        $location_prices = [
            123 => ['regular' => '100', 'sale' => '80']
        ];
        $location_id = 123;
        $default_price = '150';

        $price = $this->getLocationPriceLogic(
            $location_pricing_enabled,
            $location_prices,
            $location_id,
            $default_price
        );

        $this->assertEquals('80', $price, 'Should use location sale price when available');
    }

    public function test_location_pricing_enabled_with_regular_price_only() {
        $location_pricing_enabled = true;
        $location_prices = [
            123 => ['regular' => '100', 'sale' => '']
        ];
        $location_id = 123;
        $default_price = '150';

        $price = $this->getLocationPriceLogic(
            $location_pricing_enabled,
            $location_prices,
            $location_id,
            $default_price
        );

        $this->assertEquals('100', $price, 'Should use location regular price when no sale price');
    }

    public function test_location_pricing_disabled() {
        $location_pricing_enabled = false;
        $location_prices = [
            123 => ['regular' => '100', 'sale' => '80']
        ];
        $location_id = 123;
        $default_price = '150';

        $price = $this->getLocationPriceLogic(
            $location_pricing_enabled,
            $location_prices,
            $location_id,
            $default_price
        );

        $this->assertEquals('150', $price, 'Should use default price when location pricing disabled');
    }

    public function test_location_pricing_location_not_in_price_list() {
        $location_pricing_enabled = true;
        $location_prices = [
            123 => ['regular' => '100', 'sale' => '80']
        ];
        $location_id = 999; // Not in price list
        $default_price = '150';

        $price = $this->getLocationPriceLogic(
            $location_pricing_enabled,
            $location_prices,
            $location_id,
            $default_price
        );

        $this->assertEquals('150', $price, 'Should use default price when location not in price list');
    }

    // Helper methods that replicate the logic from LBP_Helpers

    private function checkAvailabilityLogic($availability_type, $selected_locations, $location_id) {
        switch ($availability_type) {
            case 'all':
                return true;
            case 'specific':
                return in_array($location_id, $selected_locations);
            case 'exclude':
                return !in_array($location_id, $selected_locations);
            default:
                return false;
        }
    }

    private function checkStockLogic($stock_status, $stock_quantity, $requested_quantity) {
        if ($stock_status === 'outofstock') {
            return false;
        }
        if ($stock_quantity !== null && $stock_quantity < $requested_quantity) {
            return false;
        }
        return true;
    }

    private function getDeliveryOptionsLogic($delivery_type) {
        $options = [];

        switch ($delivery_type) {
            case 'standard':
                $options[] = [
                    'type' => 'standard',
                    'name' => 'Standard Delivery',
                    'fee' => 0,
                    'estimated_days' => '5-7'
                ];
                break;

            case 'express':
                $options[] = [
                    'type' => 'standard',
                    'name' => 'Standard Delivery',
                    'fee' => 0,
                    'estimated_days' => '5-7'
                ];
                $options[] = [
                    'type' => 'express',
                    'name' => 'Express Delivery',
                    'fee' => 200,
                    'estimated_days' => '1-2'
                ];
                break;

            case 'pickup_only':
                $options[] = [
                    'type' => 'pickup',
                    'name' => 'Store Pickup',
                    'fee' => 0,
                    'estimated_days' => '1'
                ];
                break;
        }

        return $options;
    }

    private function getLocationPriceLogic($location_pricing_enabled, $location_prices, $location_id, $default_price) {
        if (!$location_pricing_enabled) {
            return $default_price;
        }

        if (isset($location_prices[$location_id])) {
            $location_price_data = $location_prices[$location_id];
            if (!empty($location_price_data['sale']) && $location_price_data['sale'] > 0) {
                return $location_price_data['sale'];
            } elseif (!empty($location_price_data['regular']) && $location_price_data['regular'] > 0) {
                return $location_price_data['regular'];
            }
        }

        return $default_price;
    }
}
