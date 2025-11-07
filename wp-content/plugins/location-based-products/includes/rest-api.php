<?php
/**
 * Location-Based Products - REST API
 * Handles location detection and product filtering APIs
 */

if (!defined('ABSPATH')) {
    exit;
}

class LBP_REST_API {
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        $namespace = 'lbp/v1';
        
        // Location detection endpoint
        register_rest_route($namespace, '/detect-location', [
            'methods' => 'GET',
            'callback' => [$this, 'detect_user_location'],
            'permission_callback' => '__return_true',
            'args' => [
                'ip' => [
                    'description' => 'IP address to detect location for',
                    'type' => 'string',
                    'validate_callback' => [$this, 'validate_ip_address']
                ],
                'postal_code' => [
                    'description' => 'Postal code for location detection',
                    'type' => 'string'
                ],
                'lat' => [
                    'description' => 'Latitude for coordinate-based detection',
                    'type' => 'number'
                ],
                'lng' => [
                    'description' => 'Longitude for coordinate-based detection', 
                    'type' => 'number'
                ]
            ]
        ]);
        
        // Set user location
        register_rest_route($namespace, '/set-location', [
            'methods' => 'POST',
            'callback' => [$this, 'set_user_location'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => [$this, 'validate_location_id']
                ]
            ]
        ]);
        
        // Get available locations
        register_rest_route($namespace, '/locations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_locations'],
            'permission_callback' => '__return_true',
            'args' => [
                'active_only' => [
                    'type' => 'boolean',
                    'default' => true
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Search locations by name or postal code'
                ]
            ]
        ]);
        
        // Get location-filtered products
        register_rest_route($namespace, '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'get_location_products'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => [
                    'type' => 'integer',
                    'description' => 'Location ID to filter products'
                ],
                'postal_code' => [
                    'type' => 'string',
                    'description' => 'Postal code to filter products'
                ],
                'category' => [
                    'type' => 'integer',
                    'description' => 'Product category ID'
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 10,
                    'maximum' => 100
                ],
                'page' => [
                    'type' => 'integer',
                    'default' => 1
                ]
            ]
        ]);
        
        // Check product availability for location
        register_rest_route($namespace, '/check-availability/(?P<product_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'check_product_availability'],
            'permission_callback' => '__return_true',
            'args' => [
                'product_id' => [
                    'required' => true,
                    'type' => 'integer'
                ],
                'location_id' => [
                    'type' => 'integer'
                ],
                'postal_code' => [
                    'type' => 'string'
                ],
                'quantity' => [
                    'type' => 'integer',
                    'default' => 1
                ]
            ]
        ]);
        
        // Get delivery options for location
        register_rest_route($namespace, '/delivery-options', [
            'methods' => 'GET', 
            'callback' => [$this, 'get_delivery_options'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => [
                    'type' => 'integer'
                ],
                'postal_code' => [
                    'type' => 'string'
                ],
                'cart_total' => [
                    'type' => 'number',
                    'description' => 'Cart total to calculate delivery fees'
                ]
            ]
        ]);
    }
    
    public function detect_user_location($request) {
        $ip = $request->get_param('ip') ?: $this->get_client_ip();
        $postal_code = $request->get_param('postal_code');
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');
        
        $detected_location = null;
        
        // Priority 1: Coordinates
        if ($lat && $lng) {
            $detected_location = $this->find_location_by_coordinates($lat, $lng);
        }
        
        // Priority 2: Postal code
        if (!$detected_location && $postal_code) {
            $detected_location = $this->find_location_by_postal_code($postal_code);
        }
        
        // Priority 3: IP geolocation
        if (!$detected_location && $ip) {
            $detected_location = $this->find_location_by_ip($ip);
        }
        
        // Fallback: Default location
        if (!$detected_location) {
            $detected_location = $this->get_default_location();
        }
        
        return rest_ensure_response([
            'success' => true,
            'location' => $this->format_location_data($detected_location),
            'detection_method' => $this->get_detection_method($lat, $lng, $postal_code, $ip),
            'timestamp' => current_time('timestamp')
        ]);
    }
    
    public function set_user_location($request) {
        $location_id = $request->get_param('location_id');
        
        $location = get_post($location_id);
        if (!$location || $location->post_type !== 'lbp_location') {
            return new WP_Error('invalid_location', 'Invalid location ID', ['status' => 400]);
        }
        
        // Store in session if available, or return for frontend storage
        if (session_id() === '') {
            session_start();
        }
        $_SESSION['lbp_selected_location'] = $location_id;
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'Location set successfully',
            'location' => $this->format_location_data($location)
        ]);
    }
    
    public function get_locations($request) {
        $active_only = $request->get_param('active_only');
        $search = $request->get_param('search');
        
        $args = [
            'post_type' => 'lbp_location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ];
        
        if ($active_only) {
            $args['meta_query'] = [
                [
                    'key' => '_lbp_is_active',
                    'value' => '1',
                    'compare' => '='
                ]
            ];
        }
        
        if ($search) {
            $args['s'] = $search;
        }
        
        $locations = get_posts($args);
        $formatted_locations = [];
        
        foreach ($locations as $location) {
            $formatted_locations[] = $this->format_location_data($location);
        }
        
        return rest_ensure_response([
            'success' => true,
            'locations' => $formatted_locations,
            'total' => count($formatted_locations)
        ]);
    }
    
    public function get_location_products($request) {
        $location_id = $request->get_param('location_id');
        $postal_code = $request->get_param('postal_code');
        $category = $request->get_param('category');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        
        // Determine location
        $location = null;
        if ($location_id) {
            $location = get_post($location_id);
        } elseif ($postal_code) {
            $location = $this->find_location_by_postal_code($postal_code);
        }
        
        if (!$location) {
            $location = $this->get_default_location();
        }
        
        // Get products
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_visibility',
                    'value' => ['hidden', 'search'],
                    'compare' => 'NOT IN'
                ]
            ]
        ];
        
        if ($category) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category
                ]
            ];
        }
        
        // Filter by location availability
        $args['meta_query'][] = $this->get_location_availability_meta_query($location->ID);
        
        $query = new WP_Query($args);
        $products = [];
        
        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $product_data = $this->format_product_for_location($product, $location->ID);
                $products[] = $product_data;
            }
        }
        
        return rest_ensure_response([
            'success' => true,
            'products' => $products,
            'location' => $this->format_location_data($location),
            'pagination' => [
                'total' => $query->found_posts,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $query->max_num_pages
            ]
        ]);
    }
    
    public function check_product_availability($request) {
        $product_id = $request->get_param('product_id');
        $location_id = $request->get_param('location_id');
        $postal_code = $request->get_param('postal_code');
        $quantity = $request->get_param('quantity');
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', 'Product not found', ['status' => 404]);
        }
        
        // Determine location
        $location = null;
        if ($location_id) {
            $location = get_post($location_id);
        } elseif ($postal_code) {
            $location = $this->find_location_by_postal_code($postal_code);
        }
        
        if (!$location) {
            $location = $this->get_default_location();
        }
        
        $availability = $this->check_product_location_availability($product, $location->ID, $quantity);
        
        return rest_ensure_response([
            'success' => true,
            'available' => $availability['available'],
            'stock_quantity' => $availability['stock_quantity'],
            'stock_status' => $availability['stock_status'],
            'price' => $availability['price'],
            'delivery_options' => $availability['delivery_options'],
            'location' => $this->format_location_data($location),
            'messages' => $availability['messages']
        ]);
    }
    
    public function get_delivery_options($request) {
        $location_id = $request->get_param('location_id');
        $postal_code = $request->get_param('postal_code');
        $cart_total = $request->get_param('cart_total') ?: 0;
        
        // Determine location
        $location = null;
        if ($location_id) {
            $location = get_post($location_id);
        } elseif ($postal_code) {
            $location = $this->find_location_by_postal_code($postal_code);
        }
        
        if (!$location) {
            $location = $this->get_default_location();
        }
        
        $delivery_options = $this->calculate_delivery_options($location->ID, $cart_total);
        
        return rest_ensure_response([
            'success' => true,
            'location' => $this->format_location_data($location),
            'delivery_options' => $delivery_options,
            'cart_total' => $cart_total
        ]);
    }
    
    // Helper methods
    private function get_default_location() {
        return LBP_Helpers::get_default_location();
    }
    
    private function find_location_by_coordinates($lat, $lng) {
        return LBP_Helpers::find_location_by_coordinates($lat, $lng);
    }
    
    private function find_location_by_postal_code($postal_code) {
        // Find location by postal code from database
        $locations = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_lbp_postal_codes',
                    'value' => $postal_code,
                    'compare' => 'LIKE'
                ]
            ]
        ]);
        
        return !empty($locations) ? $locations[0] : null;
    }
    
    private function find_location_by_ip($ip) {
        // Basic IP geolocation - would need a service like MaxMind in production
        return null; // Fallback to default location
    }
    
    private function get_detection_method($lat, $lng, $postal_code, $ip) {
        if ($lat && $lng) return 'coordinates';
        if ($postal_code) return 'postal_code';
        if ($ip) return 'ip_geolocation';
        return 'default';
    }
    
    private function format_location_data($location) {
        if (!$location) return null;
        
        return [
            'id' => $location->ID,
            'name' => $location->post_title,
            'address' => get_post_meta($location->ID, '_lbp_address', true),
            'latitude' => get_post_meta($location->ID, '_lbp_latitude', true),
            'longitude' => get_post_meta($location->ID, '_lbp_longitude', true),
            'postal_codes' => get_post_meta($location->ID, '_lbp_postal_codes', true),
        ];
    }
    
    private function check_product_location_availability($product, $location_id, $quantity = 1) {
        // Basic availability check - extend as needed
        return [
            'available' => true,
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'price' => $product->get_price(),
            'delivery_options' => ['standard', 'express'],
            'messages' => []
        ];
    }
    
    private function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        return '127.0.0.1';
    }
    
    public function validate_ip_address($value) {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    public function validate_location_id($value) {
        $location = get_post($value);
        return $location && $location->post_type === 'lbp_location';
    }
}

new LBP_REST_API();