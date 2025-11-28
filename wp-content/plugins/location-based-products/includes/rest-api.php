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

    /**
     * Get location availability meta query
     */
    private function get_location_availability_meta_query($location_id) {
        // This can be extended to filter products based on location availability settings
        // For now, we'll return null to include all products
        return null;
    }

    /**
     * Format product data for location
     */
    private function format_product_for_location($product, $location_id = null) {
        $data = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => get_permalink($product->get_id()),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'featured' => $product->is_featured(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock' => $product->get_manage_stock(),
            'average_rating' => $product->get_average_rating(),
            'rating_count' => $product->get_rating_count(),
            'categories' => $this->get_product_categories($product),
            'tags' => $this->get_product_tags($product),
            'images' => $this->get_product_images($product),
            'attributes' => $this->get_product_attributes($product),
            'variations' => $this->get_product_variations($product),
            'date_created' => $product->get_date_created() ? $product->get_date_created()->date('Y-m-d H:i:s') : null,
            'date_modified' => $product->get_date_modified() ? $product->get_date_modified()->date('Y-m-d H:i:s') : null,
        ];

        // Add location-specific data if location is provided
        if ($location_id) {
            $data['location_specific'] = [
                'location_id' => $location_id,
                'available' => true, // Can be extended with actual location availability logic
                'delivery_options' => $this->get_product_delivery_options($product, $location_id)
            ];
        }

        return $data;
    }

    /**
     * Get product categories
     */
    private function get_product_categories($product) {
        $categories = [];
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug
                ];
            }
        }
        return $categories;
    }

    /**
     * Get product tags
     */
    private function get_product_tags($product) {
        $tags = [];
        $terms = get_the_terms($product->get_id(), 'product_tag');
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $tags[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug
                ];
            }
        }
        return $tags;
    }

    /**
     * Get product images
     */
    private function get_product_images($product) {
        $images = [];

        // Main image
        $image_id = $product->get_image_id();
        if ($image_id) {
            $images[] = [
                'id' => $image_id,
                'src' => wp_get_attachment_image_url($image_id, 'full'),
                'thumbnail' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                'medium' => wp_get_attachment_image_url($image_id, 'medium'),
                'large' => wp_get_attachment_image_url($image_id, 'large'),
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
                'name' => get_the_title($image_id),
                'position' => 0
            ];
        }

        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        if ($gallery_ids) {
            $position = 1;
            foreach ($gallery_ids as $gallery_id) {
                $images[] = [
                    'id' => $gallery_id,
                    'src' => wp_get_attachment_image_url($gallery_id, 'full'),
                    'thumbnail' => wp_get_attachment_image_url($gallery_id, 'thumbnail'),
                    'medium' => wp_get_attachment_image_url($gallery_id, 'medium'),
                    'large' => wp_get_attachment_image_url($gallery_id, 'large'),
                    'alt' => get_post_meta($gallery_id, '_wp_attachment_image_alt', true),
                    'name' => get_the_title($gallery_id),
                    'position' => $position
                ];
                $position++;
            }
        }

        return $images;
    }

    /**
     * Get product attributes
     */
    private function get_product_attributes($product) {
        $attributes = [];
        $product_attributes = $product->get_attributes();

        foreach ($product_attributes as $attribute) {
            $attribute_data = [
                'id' => $attribute->get_id(),
                'name' => wc_attribute_label($attribute->get_name()),
                'slug' => $attribute->get_name(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
                'options' => []
            ];

            if ($attribute->is_taxonomy()) {
                $terms = $attribute->get_terms();
                if ($terms) {
                    foreach ($terms as $term) {
                        $attribute_data['options'][] = [
                            'id' => $term->term_id,
                            'name' => $term->name,
                            'slug' => $term->slug
                        ];
                    }
                }
            } else {
                $options = $attribute->get_options();
                foreach ($options as $option) {
                    $attribute_data['options'][] = [
                        'name' => $option,
                        'slug' => sanitize_title($option)
                    ];
                }
            }

            $attributes[] = $attribute_data;
        }

        return $attributes;
    }

    /**
     * Get product variations
     */
    private function get_product_variations($product) {
        $variations = [];

        if ($product->is_type('variable')) {
            $variation_ids = $product->get_children();
            foreach ($variation_ids as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $variations[] = [
                        'id' => $variation->get_id(),
                        'sku' => $variation->get_sku(),
                        'price' => $variation->get_price(),
                        'regular_price' => $variation->get_regular_price(),
                        'sale_price' => $variation->get_sale_price(),
                        'on_sale' => $variation->is_on_sale(),
                        'stock_status' => $variation->get_stock_status(),
                        'stock_quantity' => $variation->get_stock_quantity(),
                        'attributes' => $variation->get_variation_attributes(),
                        'image_id' => $variation->get_image_id()
                    ];
                }
            }
        }

        return $variations;
    }

    /**
     * Get product delivery options for location
     */
    private function get_product_delivery_options($product, $location_id) {
        // Get product-specific delivery options
        $delivery_type = get_post_meta($product->get_id(), '_lbp_delivery_type', true);

        $options = [];
        if (!$delivery_type || $delivery_type === 'standard') {
            $options[] = 'standard';
        }
        if ($delivery_type === 'express' || !$delivery_type) {
            $options[] = 'express';
        }
        if ($delivery_type === 'pickup_only') {
            $options[] = 'pickup';
        }

        return $options;
    }

    /**
     * Calculate delivery options for location
     */
    private function calculate_delivery_options($location_id, $cart_total) {
        $options = [
            [
                'type' => 'standard',
                'name' => 'Standard Delivery',
                'description' => '5-7 business days',
                'fee' => $cart_total >= 10000 ? 0 : 500, // Free delivery above ₹10,000
                'currency' => 'INR'
            ],
            [
                'type' => 'express',
                'name' => 'Express Delivery',
                'description' => '2-3 business days',
                'fee' => $cart_total >= 25000 ? 500 : 1000,
                'currency' => 'INR'
            ],
            [
                'type' => 'pickup',
                'name' => 'Store Pickup',
                'description' => 'Pickup from store',
                'fee' => 0,
                'currency' => 'INR'
            ]
        ];

        return $options;
    }
}

new LBP_REST_API();