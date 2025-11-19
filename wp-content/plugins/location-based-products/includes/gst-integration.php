<?php
/**
 * Location-Based Products - GST Integration
 * Handles GST/tax calculation based on location for Indian e-commerce
 *
 * Supports:
 * - Intrastate (CGST + SGST)
 * - Interstate (IGST)
 * - Location-based tax rates
 * - HSN code management
 * - GSTIN validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class LBP_GST_Integration {

    /**
     * India state codes mapping
     */
    private $indian_states = [
        'AN' => 'Andaman and Nicobar Islands',
        'AP' => 'Andhra Pradesh',
        'AR' => 'Arunachal Pradesh',
        'AS' => 'Assam',
        'BR' => 'Bihar',
        'CH' => 'Chandigarh',
        'CT' => 'Chhattisgarh',
        'DN' => 'Dadra and Nagar Haveli',
        'DD' => 'Daman and Diu',
        'DL' => 'Delhi',
        'GA' => 'Goa',
        'GJ' => 'Gujarat',
        'HR' => 'Haryana',
        'HP' => 'Himachal Pradesh',
        'JK' => 'Jammu and Kashmir',
        'JH' => 'Jharkhand',
        'KA' => 'Karnataka',
        'KL' => 'Kerala',
        'LD' => 'Lakshadweep',
        'MP' => 'Madhya Pradesh',
        'MH' => 'Maharashtra',
        'MN' => 'Manipur',
        'ML' => 'Meghalaya',
        'MZ' => 'Mizoram',
        'NL' => 'Nagaland',
        'OR' => 'Odisha',
        'PY' => 'Puducherry',
        'PB' => 'Punjab',
        'RJ' => 'Rajasthan',
        'SK' => 'Sikkim',
        'TN' => 'Tamil Nadu',
        'TG' => 'Telangana',
        'TR' => 'Tripura',
        'UP' => 'Uttar Pradesh',
        'UT' => 'Uttarakhand',
        'WB' => 'West Bengal'
    ];

    public function __construct() {
        // Hook into WooCommerce tax calculation
        add_filter('woocommerce_product_get_tax_class', [$this, 'apply_location_tax_class'], 10, 2);
        add_filter('woocommerce_product_variation_get_tax_class', [$this, 'apply_location_tax_class'], 10, 2);

        // Calculate location-based taxes
        add_filter('woocommerce_calc_tax', [$this, 'calculate_location_based_tax'], 10, 3);

        // Add GST breakdown to cart
        add_action('woocommerce_cart_calculate_fees', [$this, 'add_gst_breakdown_to_cart']);

        // Add GST data to order meta
        add_action('woocommerce_checkout_create_order', [$this, 'save_gst_data_to_order'], 10, 2);

        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_gst_endpoints']);
    }

    /**
     * Register GST REST API endpoints
     */
    public function register_gst_endpoints() {
        $namespace = 'lbp/v1';

        // Get GST rates for location
        register_rest_route($namespace, '/gst/rates', [
            'methods' => 'GET',
            'callback' => [$this, 'get_gst_rates'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => [
                    'type' => 'integer',
                    'description' => 'Location ID to get GST rates for'
                ],
                'postal_code' => [
                    'type' => 'string',
                    'description' => 'Postal code to detect location'
                ]
            ]
        ]);

        // Calculate GST for product
        register_rest_route($namespace, '/gst/calculate', [
            'methods' => 'POST',
            'callback' => [$this, 'calculate_gst'],
            'permission_callback' => '__return_true',
            'args' => [
                'product_id' => [
                    'required' => true,
                    'type' => 'integer'
                ],
                'location_id' => [
                    'type' => 'integer'
                ],
                'quantity' => [
                    'type' => 'integer',
                    'default' => 1
                ],
                'price' => [
                    'type' => 'number'
                ]
            ]
        ]);

        // Validate GSTIN
        register_rest_route($namespace, '/gst/validate-gstin', [
            'methods' => 'POST',
            'callback' => [$this, 'validate_gstin'],
            'permission_callback' => '__return_true',
            'args' => [
                'gstin' => [
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => [$this, 'sanitize_gstin']
                ]
            ]
        ]);

        // Get tax breakdown
        register_rest_route($namespace, '/gst/breakdown', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tax_breakdown'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => [
                    'required' => true,
                    'type' => 'integer'
                ],
                'product_id' => [
                    'type' => 'integer'
                ],
                'cart_total' => [
                    'type' => 'number'
                ]
            ]
        ]);
    }

    /**
     * Get GST rates for a location
     */
    public function get_gst_rates($request) {
        $location_id = $request->get_param('location_id');
        $postal_code = $request->get_param('postal_code');

        // Get location
        if (!$location_id && $postal_code) {
            $location = $this->find_location_by_postal_code($postal_code);
            $location_id = $location ? $location->ID : null;
        }

        if (!$location_id) {
            return new WP_Error('no_location', 'Location not found', ['status' => 404]);
        }

        $location = get_post($location_id);
        if (!$location || $location->post_type !== 'lbp_location') {
            return new WP_Error('invalid_location', 'Invalid location', ['status' => 400]);
        }

        $gst_data = $this->get_location_gst_data($location_id);

        return rest_ensure_response([
            'success' => true,
            'location' => [
                'id' => $location_id,
                'name' => $location->post_title,
                'state' => $gst_data['state'],
                'state_name' => $this->indian_states[$gst_data['state']] ?? $gst_data['state']
            ],
            'gst' => [
                'type' => $gst_data['gst_type'],
                'rate' => $gst_data['gst_rate'],
                'cgst_rate' => $gst_data['cgst_rate'],
                'sgst_rate' => $gst_data['sgst_rate'],
                'igst_rate' => $gst_data['igst_rate'],
                'hsn_code' => $gst_data['hsn_code']
            ]
        ]);
    }

    /**
     * Calculate GST for a product
     */
    public function calculate_gst($request) {
        $product_id = $request->get_param('product_id');
        $location_id = $request->get_param('location_id');
        $quantity = $request->get_param('quantity') ?: 1;
        $price = $request->get_param('price');

        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', 'Product not found', ['status' => 404]);
        }

        // Use provided price or product price
        if (!$price) {
            $price = $product->get_price();
        }

        // Get location GST data
        $location = get_post($location_id);
        if (!$location) {
            return new WP_Error('invalid_location', 'Location not found', ['status' => 404]);
        }

        $gst_data = $this->get_location_gst_data($location_id);
        $product_gst = $this->get_product_gst_data($product_id);

        // Calculate tax
        $base_price = $price * $quantity;
        $gst_rate = $product_gst['gst_rate'] ?: $gst_data['gst_rate'];

        $tax_amount = ($base_price * $gst_rate) / 100;
        $total_amount = $base_price + $tax_amount;

        // Breakdown
        $breakdown = [
            'base_price' => round($base_price, 2),
            'quantity' => $quantity,
            'gst_type' => $gst_data['gst_type']
        ];

        if ($gst_data['gst_type'] === 'intrastate') {
            $cgst = ($base_price * $gst_data['cgst_rate']) / 100;
            $sgst = ($base_price * $gst_data['sgst_rate']) / 100;
            $breakdown['cgst'] = round($cgst, 2);
            $breakdown['sgst'] = round($sgst, 2);
            $breakdown['cgst_rate'] = $gst_data['cgst_rate'];
            $breakdown['sgst_rate'] = $gst_data['sgst_rate'];
        } else {
            $igst = ($base_price * $gst_data['igst_rate']) / 100;
            $breakdown['igst'] = round($igst, 2);
            $breakdown['igst_rate'] = $gst_data['igst_rate'];
        }

        $breakdown['total_gst'] = round($tax_amount, 2);
        $breakdown['total_amount'] = round($total_amount, 2);

        return rest_ensure_response([
            'success' => true,
            'product' => [
                'id' => $product_id,
                'name' => $product->get_name(),
                'hsn_code' => $product_gst['hsn_code']
            ],
            'location' => [
                'id' => $location_id,
                'name' => $location->post_title,
                'state' => $gst_data['state']
            ],
            'calculation' => $breakdown
        ]);
    }

    /**
     * Validate GSTIN format
     */
    public function validate_gstin($request) {
        $gstin = strtoupper($request->get_param('gstin'));

        // GSTIN format: 2 digits state code + 10 digits PAN + 1 alphabet + 1 digit + 1 alphabet
        // Example: 29ABCDE1234F1Z5
        $pattern = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';

        $is_valid = preg_match($pattern, $gstin) === 1;

        $response = [
            'success' => true,
            'gstin' => $gstin,
            'valid' => $is_valid
        ];

        if ($is_valid) {
            $state_code = substr($gstin, 0, 2);
            $pan = substr($gstin, 2, 10);

            $response['details'] = [
                'state_code' => $state_code,
                'pan' => $pan,
                'format_valid' => true
            ];
        } else {
            $response['error'] = 'Invalid GSTIN format';
            $response['format'] = '2-digit state code + 10-digit PAN + 1 alphabet + 1 digit + Z + 1 check digit';
        }

        return rest_ensure_response($response);
    }

    /**
     * Get tax breakdown
     */
    public function get_tax_breakdown($request) {
        $location_id = $request->get_param('location_id');
        $product_id = $request->get_param('product_id');
        $cart_total = $request->get_param('cart_total');

        $location = get_post($location_id);
        if (!$location) {
            return new WP_Error('invalid_location', 'Location not found', ['status' => 404]);
        }

        $gst_data = $this->get_location_gst_data($location_id);

        $breakdown = [
            'location' => [
                'id' => $location_id,
                'name' => $location->post_title,
                'state' => $gst_data['state'],
                'state_name' => $this->indian_states[$gst_data['state']] ?? $gst_data['state']
            ],
            'gst_type' => $gst_data['gst_type'],
            'rates' => []
        ];

        if ($gst_data['gst_type'] === 'intrastate') {
            $breakdown['rates'] = [
                'cgst' => [
                    'rate' => $gst_data['cgst_rate'],
                    'label' => 'Central GST (CGST)',
                    'description' => 'Collected by Central Government'
                ],
                'sgst' => [
                    'rate' => $gst_data['sgst_rate'],
                    'label' => 'State GST (SGST)',
                    'description' => 'Collected by State Government'
                ],
                'total_rate' => $gst_data['cgst_rate'] + $gst_data['sgst_rate']
            ];
        } else {
            $breakdown['rates'] = [
                'igst' => [
                    'rate' => $gst_data['igst_rate'],
                    'label' => 'Integrated GST (IGST)',
                    'description' => 'Applicable on interstate transactions'
                ],
                'total_rate' => $gst_data['igst_rate']
            ];
        }

        // If cart total provided, calculate amounts
        if ($cart_total) {
            $gst_rate = $breakdown['rates']['total_rate'];
            $gst_amount = ($cart_total * $gst_rate) / 100;

            $breakdown['calculation'] = [
                'subtotal' => round($cart_total, 2),
                'gst_amount' => round($gst_amount, 2),
                'total' => round($cart_total + $gst_amount, 2)
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'breakdown' => $breakdown
        ]);
    }

    /**
     * Get location GST configuration
     */
    public function get_location_gst_data($location_id) {
        $state = get_post_meta($location_id, '_lbp_state', true);
        $gst_rate = get_post_meta($location_id, '_lbp_gst_rate', true) ?: 18; // Default 18%
        $gst_type = get_post_meta($location_id, '_lbp_gst_type', true) ?: 'intrastate';
        $hsn_code = get_post_meta($location_id, '_lbp_default_hsn_code', true);

        // For intrastate, split GST equally between CGST and SGST
        $cgst_rate = 0;
        $sgst_rate = 0;
        $igst_rate = 0;

        if ($gst_type === 'intrastate') {
            $cgst_rate = $gst_rate / 2;
            $sgst_rate = $gst_rate / 2;
        } else {
            $igst_rate = $gst_rate;
        }

        return [
            'state' => $state ?: 'MH',
            'gst_rate' => floatval($gst_rate),
            'gst_type' => $gst_type,
            'cgst_rate' => floatval($cgst_rate),
            'sgst_rate' => floatval($sgst_rate),
            'igst_rate' => floatval($igst_rate),
            'hsn_code' => $hsn_code
        ];
    }

    /**
     * Get product GST configuration
     */
    public function get_product_gst_data($product_id) {
        $gst_rate = get_post_meta($product_id, '_lbp_product_gst_rate', true);
        $hsn_code = get_post_meta($product_id, '_lbp_hsn_code', true);
        $gst_exempt = get_post_meta($product_id, '_lbp_gst_exempt', true);

        return [
            'gst_rate' => $gst_rate ? floatval($gst_rate) : null,
            'hsn_code' => $hsn_code ?: '9403', // Default HSN for furniture
            'gst_exempt' => $gst_exempt === 'yes'
        ];
    }

    /**
     * Apply location-based tax class
     */
    public function apply_location_tax_class($tax_class, $product) {
        // Get current location from session or cookie
        $location_id = $this->get_current_location_id();

        if (!$location_id) {
            return $tax_class;
        }

        $gst_data = $this->get_location_gst_data($location_id);

        // Map GST rate to WooCommerce tax class
        // You can create custom tax classes in WooCommerce > Settings > Tax
        switch ($gst_data['gst_rate']) {
            case 0:
                return 'zero-rate';
            case 5:
                return 'reduced-rate-5';
            case 12:
                return 'reduced-rate-12';
            case 18:
                return ''; // Standard rate
            case 28:
                return 'luxury-rate-28';
            default:
                return $tax_class;
        }
    }

    /**
     * Calculate location-based tax
     */
    public function calculate_location_based_tax($taxes, $price, $rates) {
        $location_id = $this->get_current_location_id();

        if (!$location_id) {
            return $taxes;
        }

        // Custom tax calculation logic here
        // This integrates with WooCommerce's tax system

        return $taxes;
    }

    /**
     * Add GST breakdown to cart
     */
    public function add_gst_breakdown_to_cart($cart) {
        $location_id = $this->get_current_location_id();

        if (!$location_id) {
            return;
        }

        $gst_data = $this->get_location_gst_data($location_id);

        // Store GST data in cart for later use
        WC()->session->set('lbp_gst_data', $gst_data);
    }

    /**
     * Save GST data to order
     */
    public function save_gst_data_to_order($order, $data) {
        $location_id = $this->get_current_location_id();

        if (!$location_id) {
            return;
        }

        $gst_data = $this->get_location_gst_data($location_id);

        $order->update_meta_data('_lbp_location_id', $location_id);
        $order->update_meta_data('_lbp_gst_type', $gst_data['gst_type']);
        $order->update_meta_data('_lbp_gst_rate', $gst_data['gst_rate']);
        $order->update_meta_data('_lbp_state', $gst_data['state']);

        if ($gst_data['gst_type'] === 'intrastate') {
            $order->update_meta_data('_lbp_cgst_rate', $gst_data['cgst_rate']);
            $order->update_meta_data('_lbp_sgst_rate', $gst_data['sgst_rate']);
        } else {
            $order->update_meta_data('_lbp_igst_rate', $gst_data['igst_rate']);
        }
    }

    /**
     * Get current location ID from session
     */
    private function get_current_location_id() {
        // Try session first
        if (function_exists('WC') && WC()->session) {
            $location_id = WC()->session->get('lbp_selected_location');
            if ($location_id) {
                return $location_id;
            }
        }

        // Try cookie
        if (isset($_COOKIE['lbp_location_id'])) {
            return intval($_COOKIE['lbp_location_id']);
        }

        // Try PHP session
        if (session_id() && isset($_SESSION['lbp_selected_location'])) {
            return intval($_SESSION['lbp_selected_location']);
        }

        return null;
    }

    /**
     * Find location by postal code
     */
    private function find_location_by_postal_code($postal_code) {
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

    /**
     * Sanitize GSTIN
     */
    public function sanitize_gstin($value) {
        return strtoupper(sanitize_text_field($value));
    }
}

// Initialize GST integration
new LBP_GST_Integration();
