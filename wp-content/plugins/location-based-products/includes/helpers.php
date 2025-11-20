<?php
/**
 * Location-Based Products - Helper Methods
 * Core functionality for location detection and product filtering
 */

if (!defined('ABSPATH')) {
    exit;
}

class LBP_Helpers {
    
    public static function find_location_by_coordinates($lat, $lng) {
        $locations = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_lbp_is_active',
                    'value' => '1'
                ]
            ]
        ]);
        
        $closest_location = null;
        $closest_distance = PHP_FLOAT_MAX;
        
        foreach ($locations as $location) {
            $location_lat = get_post_meta($location->ID, '_lbp_latitude', true);
            $location_lng = get_post_meta($location->ID, '_lbp_longitude', true);
            $delivery_radius = get_post_meta($location->ID, '_lbp_delivery_radius', true);
            
            if ($location_lat && $location_lng) {
                $distance = self::calculate_distance($lat, $lng, $location_lat, $location_lng);
                
                // Check if within delivery radius
                if ($delivery_radius && $distance <= $delivery_radius) {
                    if ($distance < $closest_distance) {
                        $closest_distance = $distance;
                        $closest_location = $location;
                    }
                }
            }
        }
        
        return $closest_location;
    }
    
    public static function find_location_by_postal_code($postal_code) {
        // Clean postal code
        $postal_code = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($postal_code));
        
        $locations = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_lbp_is_active',
                    'value' => '1'
                ]
            ]
        ]);
        
        // Check direct postal code match
        foreach ($locations as $location) {
            $location_postal_codes = get_post_meta($location->ID, '_lbp_postal_codes', true);
            if ($location_postal_codes) {
                $postal_array = array_map('trim', explode(',', $location_postal_codes));
                $postal_array = array_map(function($code) {
                    return preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($code));
                }, $postal_array);
                
                if (in_array($postal_code, $postal_array)) {
                    return $location;
                }
            }
        }
        
        // Check service areas
        $service_areas = get_posts([
            'post_type' => 'lbp_service_area',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        foreach ($service_areas as $area) {
            $area_postal_codes = get_post_meta($area->ID, '_lbp_service_postal_codes', true);
            if ($area_postal_codes) {
                $postal_array = array_map('trim', explode(',', $area_postal_codes));
                $postal_array = array_map(function($code) {
                    return preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($code));
                }, $postal_array);
                
                if (in_array($postal_code, $postal_array)) {
                    $parent_location_id = get_post_meta($area->ID, '_lbp_parent_location', true);
                    if ($parent_location_id) {
                        return get_post($parent_location_id);
                    }
                }
            }
        }
        
        return null;
    }
    
    public static function find_location_by_ip($ip) {
        // HIGH-08: Enhanced IP geolocation with multiple providers and caching

        if ($ip === '127.0.0.1' || $ip === 'localhost' || empty($ip)) {
            return self::get_default_location();
        }

        // Check cache first (1 hour expiry)
        $cache_key = 'lbp_ip_geo_' . md5($ip);
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            // Cached geolocation data found
            return self::match_location_from_geodata($cached_data);
        }

        // Get geolocation data from configured provider
        $geodata = self::get_ip_geolocation_data($ip);

        if (!$geodata) {
            return self::get_default_location();
        }

        // Cache the geodata for 1 hour
        set_transient($cache_key, $geodata, 3600);

        // Match location using geodata
        return self::match_location_from_geodata($geodata);
    }

    /**
     * Get IP geolocation data from configured provider (HIGH-08)
     *
     * @param string $ip IP address
     * @return array|null Geolocation data
     */
    private static function get_ip_geolocation_data($ip) {
        // Get configured provider and API key
        $provider = get_option('lbp_geolocation_provider', 'ip-api');
        $api_key = get_option('lbp_geolocation_api_key', '');

        // Try primary provider
        $geodata = self::fetch_from_provider($ip, $provider, $api_key);

        // If failed, try fallback providers
        if (!$geodata) {
            $fallback_providers = ['ip-api', 'ipapi'];
            foreach ($fallback_providers as $fallback) {
                if ($fallback !== $provider) {
                    $geodata = self::fetch_from_provider($ip, $fallback, '');
                    if ($geodata) {
                        break;
                    }
                }
            }
        }

        return $geodata;
    }

    /**
     * Fetch geolocation data from specific provider (HIGH-08)
     *
     * @param string $ip IP address
     * @param string $provider Provider name
     * @param string $api_key API key (if required)
     * @return array|null Geolocation data
     */
    private static function fetch_from_provider($ip, $provider, $api_key = '') {
        $url = '';
        $timeout = 3; // 3 second timeout

        switch ($provider) {
            case 'ip-api':
                // Free, no API key required, 45 requests/minute
                $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city,zip,lat,lon";
                break;

            case 'ipapi':
                // ipapi.co - Free tier: 1000 requests/day, no API key
                // Paid tier: requires API key
                if ($api_key) {
                    $url = "https://ipapi.co/{$ip}/json/?key={$api_key}";
                } else {
                    $url = "https://ipapi.co/{$ip}/json/";
                }
                break;

            case 'ipstack':
                // IPStack - requires API key (free tier: 10,000 requests/month)
                if ($api_key) {
                    $url = "http://api.ipstack.com/{$ip}?access_key={$api_key}";
                }
                break;

            case 'ipinfo':
                // IPInfo.io - 50,000 requests/month free
                if ($api_key) {
                    $url = "https://ipinfo.io/{$ip}/json?token={$api_key}";
                } else {
                    $url = "https://ipinfo.io/{$ip}/json";
                }
                break;

            default:
                return null;
        }

        if (empty($url)) {
            return null;
        }

        $response = wp_remote_get($url, ['timeout' => $timeout]);

        if (is_wp_error($response)) {
            error_log('LBP Geolocation Error (' . $provider . '): ' . $response->get_error_message());
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        // Normalize data from different providers
        return self::normalize_geodata($data, $provider);
    }

    /**
     * Normalize geolocation data from different providers (HIGH-08)
     *
     * @param array $data Raw data from provider
     * @param string $provider Provider name
     * @return array|null Normalized data
     */
    private static function normalize_geodata($data, $provider) {
        if (!$data) {
            return null;
        }

        $normalized = [];

        switch ($provider) {
            case 'ip-api':
                if (!isset($data['status']) || $data['status'] !== 'success') {
                    return null;
                }
                $normalized = [
                    'country' => $data['country'] ?? '',
                    'country_code' => $data['countryCode'] ?? '',
                    'region' => $data['regionName'] ?? '',
                    'city' => $data['city'] ?? '',
                    'zip' => $data['zip'] ?? '',
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null
                ];
                break;

            case 'ipapi':
                if (isset($data['error'])) {
                    return null;
                }
                $normalized = [
                    'country' => $data['country_name'] ?? '',
                    'country_code' => $data['country_code'] ?? '',
                    'region' => $data['region'] ?? '',
                    'city' => $data['city'] ?? '',
                    'zip' => $data['postal'] ?? '',
                    'lat' => $data['latitude'] ?? null,
                    'lon' => $data['longitude'] ?? null
                ];
                break;

            case 'ipstack':
                if (isset($data['error'])) {
                    return null;
                }
                $normalized = [
                    'country' => $data['country_name'] ?? '',
                    'country_code' => $data['country_code'] ?? '',
                    'region' => $data['region_name'] ?? '',
                    'city' => $data['city'] ?? '',
                    'zip' => $data['zip'] ?? '',
                    'lat' => $data['latitude'] ?? null,
                    'lon' => $data['longitude'] ?? null
                ];
                break;

            case 'ipinfo':
                if (isset($data['bogon'])) {
                    return null;
                }
                // IPInfo.io format: "loc": "lat,lon"
                $loc = isset($data['loc']) ? explode(',', $data['loc']) : [null, null];
                $normalized = [
                    'country' => $data['country'] ?? '',
                    'country_code' => $data['country'] ?? '',
                    'region' => $data['region'] ?? '',
                    'city' => $data['city'] ?? '',
                    'zip' => $data['postal'] ?? '',
                    'lat' => isset($loc[0]) ? floatval($loc[0]) : null,
                    'lon' => isset($loc[1]) ? floatval($loc[1]) : null
                ];
                break;

            default:
                return null;
        }

        return $normalized;
    }

    /**
     * Match location from normalized geodata (HIGH-08)
     *
     * @param array $geodata Normalized geolocation data
     * @return WP_Post|null Location post object
     */
    private static function match_location_from_geodata($geodata) {
        if (!$geodata) {
            return null;
        }

        // Priority 1: Match by coordinates
        if (isset($geodata['lat']) && isset($geodata['lon']) && $geodata['lat'] && $geodata['lon']) {
            $location = self::find_location_by_coordinates($geodata['lat'], $geodata['lon']);
            if ($location) {
                return $location;
            }
        }

        // Priority 2: Match by postal code
        if (!empty($geodata['zip'])) {
            $location = self::find_location_by_postal_code($geodata['zip']);
            if ($location) {
                return $location;
            }
        }

        // Priority 3: Match by city/region
        $city = $geodata['city'] ?? '';
        $region = $geodata['region'] ?? '';

        if ($city || $region) {
            $location = self::find_location_by_city($city, $region);
            if ($location) {
                return $location;
            }
        }

        return null;
    }
    
    public static function find_location_by_city($city, $region = '') {
        $locations = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_lbp_is_active',
                    'value' => '1'
                ]
            ]
        ]);
        
        foreach ($locations as $location) {
            $location_city = get_post_meta($location->ID, '_lbp_city', true);
            $location_state = get_post_meta($location->ID, '_lbp_state', true);
            
            // Check city match
            if ($location_city && stripos($location_city, $city) !== false) {
                return $location;
            }
            
            // Check region/state match
            if ($region && $location_state && stripos($location_state, $region) !== false) {
                return $location;
            }
        }
        
        return null;
    }
    
    public static function get_default_location() {
        $default = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_lbp_is_active',
                    'value' => '1'
                ]
            ],
            'orderby' => 'meta_value_num',
            'meta_key' => '_lbp_priority',
            'order' => 'DESC'
        ]);
        
        return !empty($default) ? $default[0] : null;
    }
    
    public static function format_location_data($location) {
        if (!$location) return null;
        
        $meta_fields = [
            'latitude' => '_lbp_latitude',
            'longitude' => '_lbp_longitude',
            'address' => '_lbp_address',
            'city' => '_lbp_city',
            'state' => '_lbp_state',
            'country' => '_lbp_country',
            'postal_codes' => '_lbp_postal_codes',
            'delivery_radius' => '_lbp_delivery_radius',
            'is_active' => '_lbp_is_active',
            'priority' => '_lbp_priority'
        ];
        
        $location_data = [
            'id' => $location->ID,
            'name' => $location->post_title,
            'description' => $location->post_content,
            'slug' => $location->post_name
        ];
        
        foreach ($meta_fields as $key => $meta_key) {
            $location_data[$key] = get_post_meta($location->ID, $meta_key, true);
        }
        
        // Parse postal codes into array
        if ($location_data['postal_codes']) {
            $location_data['postal_codes_array'] = array_map('trim', explode(',', $location_data['postal_codes']));
        } else {
            $location_data['postal_codes_array'] = [];
        }
        
        return $location_data;
    }
    
    public static function check_product_location_availability($product, $location_id, $quantity = 1) {
        $product_id = $product->get_id();
        $availability_type = get_post_meta($product_id, '_lbp_availability_type', true) ?: 'all';
        $selected_locations = get_post_meta($product_id, '_lbp_selected_locations', true) ?: [];
        
        $available = false;
        $messages = [];
        
        // Check availability type
        switch ($availability_type) {
            case 'all':
                $available = true;
                break;
            case 'specific':
                $available = in_array($location_id, $selected_locations);
                if (!$available) {
                    $messages[] = 'This product is not available in your location.';
                }
                break;
            case 'exclude':
                $available = !in_array($location_id, $selected_locations);
                if (!$available) {
                    $messages[] = 'This product is not available in your location.';
                }
                break;
        }
        
        if (!$available) {
            return [
                'available' => false,
                'stock_quantity' => 0,
                'stock_status' => 'outofstock',
                'price' => null,
                'delivery_options' => [],
                'messages' => $messages
            ];
        }
        
        // Check location-specific stock
        $location_stock_enabled = get_post_meta($product_id, '_lbp_location_stock', true) === 'yes';
        $stock_quantity = $product->get_stock_quantity();
        $stock_status = $product->get_stock_status();
        
        if ($location_stock_enabled) {
            $location_stock_levels = get_post_meta($product_id, '_lbp_location_stock_levels', true) ?: [];
            if (isset($location_stock_levels[$location_id])) {
                $location_stock = $location_stock_levels[$location_id];
                $stock_quantity = $location_stock['quantity'];
                $stock_status = $location_stock['status'];
            } else {
                $stock_quantity = 0;
                $stock_status = 'outofstock';
            }
        }
        
        // Check quantity availability
        if ($stock_status === 'outofstock' || ($stock_quantity && $stock_quantity < $quantity)) {
            $available = false;
            $messages[] = 'Insufficient stock for your location.';
        }
        
        // Get location-specific pricing
        $price = $product->get_price();
        $location_pricing_enabled = get_post_meta($product_id, '_lbp_location_pricing', true) === 'yes';
        
        if ($location_pricing_enabled) {
            $location_prices = get_post_meta($product_id, '_lbp_location_prices', true) ?: [];
            if (isset($location_prices[$location_id])) {
                $location_price_data = $location_prices[$location_id];
                if ($location_price_data['sale'] && $location_price_data['sale'] > 0) {
                    $price = $location_price_data['sale'];
                } elseif ($location_price_data['regular'] && $location_price_data['regular'] > 0) {
                    $price = $location_price_data['regular'];
                }
            }
        }
        
        // Get delivery options
        $delivery_type = get_post_meta($product_id, '_lbp_delivery_type', true) ?: 'standard';
        $delivery_options = self::get_product_delivery_options($product_id, $location_id, $delivery_type);
        
        return [
            'available' => $available,
            'stock_quantity' => $stock_quantity,
            'stock_status' => $stock_status,
            'price' => $price,
            'delivery_options' => $delivery_options,
            'messages' => $messages
        ];
    }
    
    public static function get_product_delivery_options($product_id, $location_id, $delivery_type) {
        $options = [];
        
        switch ($delivery_type) {
            case 'standard':
                $options[] = [
                    'type' => 'standard',
                    'name' => 'Standard Delivery',
                    'description' => 'Regular delivery within 5-7 business days',
                    'fee' => 0,
                    'estimated_days' => '5-7'
                ];
                break;
                
            case 'express':
                $options[] = [
                    'type' => 'standard',
                    'name' => 'Standard Delivery',
                    'description' => 'Regular delivery within 5-7 business days',
                    'fee' => 0,
                    'estimated_days' => '5-7'
                ];
                $options[] = [
                    'type' => 'express',
                    'name' => 'Express Delivery',
                    'description' => 'Fast delivery within 1-2 business days',
                    'fee' => 200, // INR
                    'estimated_days' => '1-2'
                ];
                break;
                
            case 'pickup_only':
                $options[] = [
                    'type' => 'pickup',
                    'name' => 'Store Pickup',
                    'description' => 'Pick up from store location',
                    'fee' => 0,
                    'estimated_days' => '1'
                ];
                break;
        }
        
        return $options;
    }
    
    public static function format_product_for_location($product, $location_id) {
        $availability = self::check_product_location_availability($product, $location_id);
        
        $product_data = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => $product->get_permalink(),
            'price' => $availability['price'],
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'price_html' => $product->get_price_html(),
            'on_sale' => $product->is_on_sale(),
            'purchasable' => $product->is_purchasable() && $availability['available'],
            'total_sales' => $product->get_total_sales(),
            'virtual' => $product->is_virtual(),
            'downloadable' => $product->is_downloadable(),
            'manage_stock' => $product->managing_stock(),
            'stock_quantity' => $availability['stock_quantity'],
            'stock_status' => $availability['stock_status'],
            'backorders' => $product->get_backorders(),
            'short_description' => $product->get_short_description(),
            'description' => $product->get_description(),
            'sku' => $product->get_sku(),
            'weight' => $product->get_weight(),
            'dimensions' => [
                'length' => $product->get_length(),
                'width' => $product->get_width(),
                'height' => $product->get_height()
            ],
            'shipping_required' => $product->needs_shipping(),
            'shipping_taxable' => $product->is_shipping_taxable(),
            'shipping_class' => $product->get_shipping_class(),
            'reviews_allowed' => $product->get_reviews_allowed(),
            'average_rating' => $product->get_average_rating(),
            'rating_count' => $product->get_rating_count(),
            'related_ids' => wc_get_related_products($product->get_id()),
            'upsell_ids' => $product->get_upsell_ids(),
            'cross_sell_ids' => $product->get_cross_sell_ids(),
            'parent_id' => $product->get_parent_id(),
            'purchase_note' => $product->get_purchase_note(),
            'categories' => self::get_product_categories($product),
            'tags' => self::get_product_tags($product),
            'images' => self::get_product_images($product),
            'attributes' => self::get_product_attributes($product),
            'default_attributes' => $product->get_default_attributes(),
            'variations' => [],
            'grouped_products' => [],
            'menu_order' => $product->get_menu_order(),
            'location_availability' => $availability,
            'meta_data' => $product->get_meta_data()
        ];
        
        return $product_data;
    }
    
    public static function calculate_distance($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371; // Earth's radius in kilometers
        
        $lat1_rad = deg2rad($lat1);
        $lng1_rad = deg2rad($lng1);
        $lat2_rad = deg2rad($lat2);
        $lng2_rad = deg2rad($lng2);
        
        $delta_lat = $lat2_rad - $lat1_rad;
        $delta_lng = $lng2_rad - $lng1_rad;
        
        $a = sin($delta_lat/2) * sin($delta_lat/2) + cos($lat1_rad) * cos($lat2_rad) * sin($delta_lng/2) * sin($delta_lng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
    
    private static function get_product_categories($product) {
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
    
    private static function get_product_tags($product) {
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
    
    private static function get_product_images($product) {
        $images = [];
        
        // Featured image
        $featured_image_id = $product->get_image_id();
        if ($featured_image_id) {
            $images[] = [
                'id' => $featured_image_id,
                'src' => wp_get_attachment_url($featured_image_id),
                'name' => get_post_field('post_title', $featured_image_id),
                'alt' => get_post_meta($featured_image_id, '_wp_attachment_image_alt', true)
            ];
        }
        
        // Gallery images
        $gallery_image_ids = $product->get_gallery_image_ids();
        foreach ($gallery_image_ids as $image_id) {
            $images[] = [
                'id' => $image_id,
                'src' => wp_get_attachment_url($image_id),
                'name' => get_post_field('post_title', $image_id),
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
            ];
        }
        
        return $images;
    }
    
    private static function get_product_attributes($product) {
        $attributes = [];
        $product_attributes = $product->get_attributes();
        
        foreach ($product_attributes as $attribute) {
            $attributes[] = [
                'id' => $attribute->get_id(),
                'name' => $attribute->get_name(),
                'position' => $attribute->get_position(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
                'options' => $attribute->get_options()
            ];
        }
        
        return $attributes;
    }
}