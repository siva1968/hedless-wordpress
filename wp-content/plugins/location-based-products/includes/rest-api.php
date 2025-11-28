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
                    'type' => 'string',
                    'description' => 'Product category slug or ID'
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 10,
                    'maximum' => 100
                ],
                'page' => [
                    'type' => 'integer',
                    'default' => 1
                ],
                'orderby' => [
                    'type' => 'string',
                    'default' => 'date',
                    'enum' => ['date', 'title', 'price', 'popularity', 'rating']
                ],
                'order' => [
                    'type' => 'string',
                    'default' => 'desc',
                    'enum' => ['asc', 'desc']
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Search query for products'
                ],
                // Filter parameters
                'type' => [
                    'type' => 'string',
                    'description' => 'Product type filter (comma-separated for multiple)'
                ],
                'size' => [
                    'type' => 'string',
                    'description' => 'Size filter (comma-separated for multiple)'
                ],
                'material' => [
                    'type' => 'string',
                    'description' => 'Material filter (comma-separated for multiple)'
                ],
                'colour' => [
                    'type' => 'string',
                    'description' => 'Colour filter (comma-separated for multiple)'
                ],
                'storage' => [
                    'type' => 'string',
                    'description' => 'Storage type filter'
                ],
                'headboard' => [
                    'type' => 'string',
                    'description' => 'Headboard type filter'
                ],
                'upholstery' => [
                    'type' => 'string',
                    'description' => 'Upholstery filter'
                ],
                'brand' => [
                    'type' => 'string',
                    'description' => 'Brand filter (comma-separated for multiple)'
                ],
                'mechanism' => [
                    'type' => 'string',
                    'description' => 'Mechanism filter (for recliners)'
                ],
                'thickness' => [
                    'type' => 'string',
                    'description' => 'Thickness filter (for mattresses)'
                ],
                'discount_range' => [
                    'type' => 'string',
                    'description' => 'Discount range filter'
                ],
                // Price filters
                'price_min' => [
                    'type' => 'number',
                    'description' => 'Minimum price filter'
                ],
                'price_max' => [
                    'type' => 'number',
                    'description' => 'Maximum price filter'
                ],
                // Stock filter
                'stock_status' => [
                    'type' => 'string',
                    'description' => 'Stock status filter',
                    'enum' => ['instock', 'outofstock', 'onbackorder']
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
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $search = $request->get_param('search');

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
            'order' => strtoupper($order),
            'meta_query' => [
                'relation' => 'AND'
            ],
            'tax_query' => [
                'relation' => 'AND'
            ]
        ];

        // Handle search
        if ($search) {
            $args['s'] = sanitize_text_field($search);
        }

        // Handle orderby
        switch ($orderby) {
            case 'price':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
                break;
            case 'title':
                $args['orderby'] = 'title';
                break;
            case 'popularity':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'total_sales';
                break;
            case 'rating':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_wc_average_rating';
                break;
            default:
                $args['orderby'] = 'date';
        }

        // Category filter
        if ($category) {
            $field = is_numeric($category) ? 'term_id' : 'slug';
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field' => $field,
                'terms' => $category
            ];
        }

        // Apply product attribute filters
        $attribute_filters = [
            'type' => 'pa_type',
            'size' => 'pa_size',
            'material' => 'pa_material',
            'colour' => 'pa_colour',
            'storage' => 'pa_storage',
            'headboard' => 'pa_headboard',
            'upholstery' => 'pa_upholstery',
            'brand' => 'pa_brand',
            'mechanism' => 'pa_mechanism',
            'thickness' => 'pa_thickness',
            'discount_range' => 'pa_discount_range'
        ];

        foreach ($attribute_filters as $param => $taxonomy) {
            $value = $request->get_param($param);
            if ($value) {
                // Support comma-separated values for multiple filters
                $terms = array_map('trim', explode(',', $value));
                $terms = array_map('sanitize_text_field', $terms);

                // Convert to slugs for taxonomy query
                $term_slugs = array_map('sanitize_title', $terms);

                $args['tax_query'][] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $term_slugs,
                    'operator' => 'IN'
                ];
            }
        }

        // Price range filter
        $price_min = $request->get_param('price_min');
        $price_max = $request->get_param('price_max');

        if ($price_min !== null || $price_max !== null) {
            $price_query = ['relation' => 'AND'];

            if ($price_min !== null) {
                $price_query[] = [
                    'key' => '_price',
                    'value' => floatval($price_min),
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                ];
            }

            if ($price_max !== null) {
                $price_query[] = [
                    'key' => '_price',
                    'value' => floatval($price_max),
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                ];
            }

            $args['meta_query'][] = $price_query;
        }

        // Stock status filter
        $stock_status = $request->get_param('stock_status');
        if ($stock_status) {
            $args['meta_query'][] = [
                'key' => '_stock_status',
                'value' => sanitize_text_field($stock_status),
                'compare' => '='
            ];
        }

        // Filter by location availability (if location-based filtering is enabled)
        if ($location && method_exists($this, 'get_location_availability_meta_query')) {
            $location_query = $this->get_location_availability_meta_query($location->ID);
            if ($location_query) {
                $args['meta_query'][] = $location_query;
            }
        }

        // Remove empty tax_query to avoid errors
        if (count($args['tax_query']) === 1) {
            unset($args['tax_query']);
        }

        // Execute query
        $query = new WP_Query($args);
        $products = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $product_data = $this->format_product_for_location($product, $location ? $location->ID : null);
                $products[] = $product_data;
            }
        }

        // Get applied filters for response
        $applied_filters = $this->get_applied_filters($request);

        return rest_ensure_response([
            'success' => true,
            'products' => $products,
            'location' => $this->format_location_data($location),
            'filters' => $applied_filters,
            'pagination' => [
                'total' => $query->found_posts,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $query->max_num_pages
            ]
        ]);
    }

    /**
     * Get applied filters from request
     */
    private function get_applied_filters($request) {
        $filters = [];
        $filter_params = [
            'type', 'size', 'material', 'colour', 'storage',
            'headboard', 'upholstery', 'brand', 'mechanism',
            'thickness', 'discount_range', 'stock_status'
        ];

        foreach ($filter_params as $param) {
            $value = $request->get_param($param);
            if ($value) {
                $filters[$param] = $value;
            }
        }

        // Add price range
        $price_min = $request->get_param('price_min');
        $price_max = $request->get_param('price_max');
        if ($price_min !== null || $price_max !== null) {
            $filters['price_range'] = [
                'min' => $price_min,
                'max' => $price_max
            ];
        }

        return $filters;
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