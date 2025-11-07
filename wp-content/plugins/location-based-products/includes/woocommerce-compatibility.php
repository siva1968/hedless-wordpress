<?php
/**
 * Location-Based Products - WooCommerce Compatibility
 * Additional compatibility features and declarations
 */

if (!defined('ABSPATH')) {
    exit;
}

class LBP_WooCommerce_Compatibility {
    
    public function __construct() {
        // Add WooCommerce specific hooks
        add_action('woocommerce_loaded', [$this, 'on_woocommerce_loaded']);
        add_action('woocommerce_init', [$this, 'on_woocommerce_init']);
        
        // Cart and checkout compatibility
        add_filter('woocommerce_cart_item_data', [$this, 'add_location_to_cart_item'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_location_in_cart'], 10, 2);
        
        // Order compatibility
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_location_to_order_item'], 10, 4);
        
        // Product query compatibility
        add_filter('woocommerce_product_query_meta_query', [$this, 'filter_products_by_location_query'], 10, 2);
        
        // REST API compatibility
        add_filter('woocommerce_rest_prepare_product_object', [$this, 'add_location_data_to_api'], 10, 3);
        add_filter('woocommerce_rest_product_object_query', [$this, 'filter_products_api_by_location'], 10, 2);
    }
    
    public function on_woocommerce_loaded() {
        // Plugin fully loaded after WooCommerce
        do_action('lbp_woocommerce_loaded');
    }
    
    public function on_woocommerce_init() {
        // Initialize location-specific features after WooCommerce init
        do_action('lbp_woocommerce_init');
    }
    
    public function add_location_to_cart_item($cart_item_data, $product_id, $variation_id) {
        // Add detected location to cart item for location-specific pricing
        if (isset($_POST['location_id']) || isset($_SESSION['user_location_id'])) {
            $location_id = isset($_POST['location_id']) ? 
                intval($_POST['location_id']) : 
                intval($_SESSION['user_location_id']);
                
            if ($location_id > 0) {
                $cart_item_data['location_id'] = $location_id;
                $cart_item_data['unique_key'] = md5(microtime() . rand());
            }
        }
        
        return $cart_item_data;
    }
    
    public function display_location_in_cart($item_data, $cart_item) {
        if (isset($cart_item['location_id'])) {
            $location = get_post($cart_item['location_id']);
            if ($location) {
                $item_data[] = [
                    'key' => __('Delivery Location', 'location-based-products'),
                    'value' => $location->post_title,
                    'display' => $location->post_title
                ];
            }
        }
        
        return $item_data;
    }
    
    public function save_location_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['location_id'])) {
            $item->add_meta_data('_location_id', $values['location_id']);
            
            $location = get_post($values['location_id']);
            if ($location) {
                $item->add_meta_data('_location_name', $location->post_title);
            }
        }
    }
    
    public function filter_products_by_location_query($meta_query, $query) {
        // Add location filtering to WooCommerce product queries
        if (isset($query->query_vars['location_id'])) {
            $location_id = intval($query->query_vars['location_id']);
            
            if ($location_id > 0) {
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key' => '_lbp_availability_type',
                        'value' => 'all',
                        'compare' => '='
                    ],
                    [
                        'relation' => 'AND',
                        [
                            'key' => '_lbp_availability_type',
                            'value' => 'specific',
                            'compare' => '='
                        ],
                        [
                            'key' => '_lbp_selected_locations',
                            'value' => serialize(strval($location_id)),
                            'compare' => 'LIKE'
                        ]
                    ]
                ];
            }
        }
        
        return $meta_query;
    }
    
    public function add_location_data_to_api($response, $object, $request) {
        // Add location availability to WooCommerce REST API product response
        $product_id = $object->get_id();
        
        $location_data = [
            'availability_type' => get_post_meta($product_id, '_lbp_availability_type', true),
            'selected_locations' => get_post_meta($product_id, '_lbp_selected_locations', true),
            'location_pricing_enabled' => get_post_meta($product_id, '_lbp_location_pricing', true) === 'yes',
            'location_stock_enabled' => get_post_meta($product_id, '_lbp_location_stock', true) === 'yes',
            'delivery_type' => get_post_meta($product_id, '_lbp_delivery_type', true)
        ];
        
        // Add location-specific data if location_id is provided
        $location_id = $request->get_param('location_id');
        if ($location_id) {
            if (class_exists('LBP_Helpers')) {
                $availability = LBP_Helpers::check_product_location_availability($object, $location_id);
                $location_data['location_availability'] = $availability;
            }
        }
        
        $response->data['location_data'] = $location_data;
        
        return $response;
    }
    
    public function filter_products_api_by_location($args, $request) {
        // Filter WooCommerce REST API products by location
        if ($location_id = $request->get_param('location_id')) {
            $args['location_id'] = intval($location_id);
        }
        
        return $args;
    }
    
    /**
     * Check if WooCommerce blocks are available
     */
    public static function is_blocks_available() {
        return class_exists('Automattic\WooCommerce\Blocks\Package');
    }
    
    /**
     * Check if HPOS is enabled
     */
    public static function is_hpos_enabled() {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }
    
    /**
     * Get WooCommerce version
     */
    public static function get_woocommerce_version() {
        return defined('WC_VERSION') ? WC_VERSION : false;
    }
    
    /**
     * Check if current WooCommerce version supports feature
     */
    public static function version_supports($feature, $version) {
        $wc_version = self::get_woocommerce_version();
        return $wc_version && version_compare($wc_version, $version, '>=');
    }
}

// Initialize WooCommerce compatibility
new LBP_WooCommerce_Compatibility();