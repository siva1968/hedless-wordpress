<?php
/**
 * Plugin Name: Headless WordPress Configuration
 * Plugin URI: https://github.com/siva1968/hedless-wordpress
 * Description: Essential configurations for headless WordPress with WooCommerce
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HeadlessWPConfig {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('rest_api_init', [$this, 'add_cors_support']);
        add_action('wp_enqueue_scripts', [$this, 'disable_admin_bar_for_api']);
        add_filter('rest_authentication_errors', [$this, 'rest_authentication_errors']);
    }
    
    public function init() {
        // Enable CORS headers
        $this->enable_cors();
        
        // Add custom REST API endpoints
        add_action('rest_api_init', [$this, 'register_custom_endpoints']);
    }
    
    public function enable_cors() {
        // Add CORS headers for all requests
        add_action('init', function() {
            if (isset($_SERVER['HTTP_ORIGIN'])) {
                header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }
            
            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                
                exit(0);
            }
        });
    }
    
    public function add_cors_support() {
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', function($value) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
            return $value;
        });
    }
    
    public function register_custom_endpoints() {
        // Custom endpoint for site info
        register_rest_route('headless/v1', '/site-info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_site_info'],
            'permission_callback' => '__return_true'
        ]);
        
        // Custom endpoint for menu data
        register_rest_route('headless/v1', '/menus/(?P<location>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_menu_data'],
            'permission_callback' => '__return_true'
        ]);
        
        // Custom endpoint for WooCommerce store info
        register_rest_route('headless/v1', '/store-info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_store_info'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    public function get_site_info($request) {
        return rest_ensure_response([
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url(),
            'wp_version' => get_bloginfo('version'),
            'is_woocommerce_active' => class_exists('WooCommerce'),
            'rest_api_base' => rest_url()
        ]);
    }
    
    public function get_menu_data($request) {
        $location = $request['location'];
        $menu = wp_get_nav_menu_items($location);
        
        if (!$menu) {
            return new WP_Error('no_menu', 'Menu not found', ['status' => 404]);
        }
        
        return rest_ensure_response($menu);
    }
    
    public function get_store_info($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 400]);
        }
        
        return rest_ensure_response([
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'countries' => WC()->countries->get_countries(),
            'states' => WC()->countries->get_states(),
            'store_address' => [
                'address' => get_option('woocommerce_store_address'),
                'city' => get_option('woocommerce_store_city'),
                'postcode' => get_option('woocommerce_store_postcode'),
                'country' => get_option('woocommerce_default_country')
            ]
        ]);
    }
    
    public function disable_admin_bar_for_api() {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            show_admin_bar(false);
        }
    }
    
    public function rest_authentication_errors($result) {
        if (!empty($result)) {
            return $result;
        }
        
        if (!is_user_logged_in()) {
            return true;
        }
        
        return $result;
    }
}

// Initialize the plugin
new HeadlessWPConfig();