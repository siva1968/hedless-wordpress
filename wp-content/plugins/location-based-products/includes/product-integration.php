<?php
/**
 * Location-Based Products - Product Integration
 * Extends the main plugin with WooCommerce product integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class LBP_Product_Integration {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
    }
    
    public function init() {
        // Add location fields to products
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_location_fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save_location_fields']);
        
        // Product filtering is handled through REST API endpoints
        // Variation support can be added later if needed
        
        // Add to REST API
        add_action('rest_api_init', [$this, 'register_product_location_fields']);
    }
    
    public function add_location_fields() {
        global $woocommerce, $post;
        
        echo '<div class="options_group">';
        
        // Location availability
        woocommerce_wp_select([
            'id' => '_lbp_availability_type',
            'label' => __('Location Availability', 'location-based-products'),
            'description' => __('Set how this product is available across locations', 'location-based-products'),
            'desc_tip' => true,
            'options' => [
                'all' => __('Available in all locations', 'location-based-products'),
                'specific' => __('Available in specific locations only', 'location-based-products'),
                'exclude' => __('Available everywhere except specific locations', 'location-based-products')
            ]
        ]);
        
        // Specific locations
        $locations = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $location_options = [];
        foreach ($locations as $location) {
            $location_options[$location->ID] = $location->post_title;
        }
        
        $selected_locations = get_post_meta($post->ID, '_lbp_selected_locations', true);
        if (!is_array($selected_locations)) {
            $selected_locations = [];
        }
        
        ?>
        <p class="form-field _lbp_selected_locations_field">
            <label for="_lbp_selected_locations"><?php _e('Specific Locations', 'location-based-products'); ?></label>
            <select id="_lbp_selected_locations" name="_lbp_selected_locations[]" multiple="multiple" style="width: 50%;">
                <?php foreach ($location_options as $id => $name): ?>
                    <option value="<?php echo esc_attr($id); ?>" <?php selected(in_array($id, $selected_locations)); ?>>
                        <?php echo esc_html($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="description"><?php _e('Select locations for specific or exclude availability types', 'location-based-products'); ?></span>
        </p>
        
        <?php
        
        // Location-specific pricing
        woocommerce_wp_checkbox([
            'id' => '_lbp_location_pricing',
            'label' => __('Enable location-specific pricing', 'location-based-products'),
            'description' => __('Allow different prices for different locations', 'location-based-products'),
        ]);
        
        // Delivery options
        woocommerce_wp_select([
            'id' => '_lbp_delivery_type',
            'label' => __('Delivery Type', 'location-based-products'),
            'options' => [
                'standard' => __('Standard delivery', 'location-based-products'),
                'express' => __('Express delivery available', 'location-based-products'),
                'pickup_only' => __('Pickup only', 'location-based-products'),
                'custom' => __('Custom delivery options', 'location-based-products')
            ]
        ]);
        
        // Stock management per location
        woocommerce_wp_checkbox([
            'id' => '_lbp_location_stock',
            'label' => __('Manage stock per location', 'location-based-products'),
            'description' => __('Enable separate inventory tracking for each location', 'location-based-products'),
        ]);

        echo '</div>';

        // GST Configuration
        echo '<div class="options_group">';
        echo '<h4>' . __('GST Configuration', 'location-based-products') . '</h4>';

        woocommerce_wp_select([
            'id' => '_lbp_gst_rate',
            'label' => __('GST Rate Override', 'location-based-products'),
            'description' => __('Override location default GST rate for this product. Leave empty to use location default.', 'location-based-products'),
            'desc_tip' => true,
            'options' => [
                '' => __('Use location default', 'location-based-products'),
                '0' => __('0% (Exempt)', 'location-based-products'),
                '5' => __('5% (Essential goods)', 'location-based-products'),
                '12' => __('12% (Standard goods)', 'location-based-products'),
                '18' => __('18% (Most goods)', 'location-based-products'),
                '28' => __('28% (Luxury)', 'location-based-products')
            ]
        ]);

        woocommerce_wp_text_input([
            'id' => '_lbp_hsn_code',
            'label' => __('HSN Code', 'location-based-products'),
            'description' => __('Harmonized System of Nomenclature code for GST. Leave empty to use location default (9403 for furniture).', 'location-based-products'),
            'desc_tip' => true,
            'type' => 'text',
            'placeholder' => '9403',
            'custom_attributes' => [
                'pattern' => '\d{4,8}',
                'title' => 'HSN code must be 4, 6, or 8 digits'
            ]
        ]);

        // HIGH-10: Reverse Charge Mechanism
        woocommerce_wp_checkbox([
            'id' => '_lbp_rcm_applicable',
            'label' => __('Reverse Charge Mechanism (RCM)', 'location-based-products'),
            'description' => __('Check if RCM applies to this product in B2B transactions. Tax liability will be on the buyer (recipient) instead of seller. Applicable for specific categories like legal services, GTA, etc.', 'location-based-products'),
            'desc_tip' => true
        ]);

        echo '</div>';
        
        // Location-specific pricing table
        echo '<div class="options_group" id="lbp_location_pricing_panel" style="display:none;">';
        echo '<h4>' . __('Location-Specific Pricing', 'location-based-products') . '</h4>';
        
        $location_prices = get_post_meta($post->ID, '_lbp_location_prices', true);
        if (!is_array($location_prices)) {
            $location_prices = [];
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Location</th><th>Regular Price</th><th>Sale Price</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($locations as $location) {
            $regular_price = isset($location_prices[$location->ID]['regular']) ? $location_prices[$location->ID]['regular'] : '';
            $sale_price = isset($location_prices[$location->ID]['sale']) ? $location_prices[$location->ID]['sale'] : '';
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($location->post_title) . '</strong></td>';
            echo '<td><input type="text" name="_lbp_location_prices[' . $location->ID . '][regular]" value="' . esc_attr($regular_price) . '" placeholder="Regular price" style="width:100px;" /></td>';
            echo '<td><input type="text" name="_lbp_location_prices[' . $location->ID . '][sale]" value="' . esc_attr($sale_price) . '" placeholder="Sale price" style="width:100px;" /></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
        
        // Location stock management table
        echo '<div class="options_group" id="lbp_location_stock_panel" style="display:none;">';
        echo '<h4>' . __('Location-Specific Stock', 'location-based-products') . '</h4>';
        
        $location_stock = get_post_meta($post->ID, '_lbp_location_stock_levels', true);
        if (!is_array($location_stock)) {
            $location_stock = [];
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Location</th><th>Stock Quantity</th><th>Stock Status</th><th>Backorder</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($locations as $location) {
            $quantity = isset($location_stock[$location->ID]['quantity']) ? $location_stock[$location->ID]['quantity'] : '';
            $status = isset($location_stock[$location->ID]['status']) ? $location_stock[$location->ID]['status'] : 'instock';
            $backorder = isset($location_stock[$location->ID]['backorder']) ? $location_stock[$location->ID]['backorder'] : 'no';
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($location->post_title) . '</strong></td>';
            echo '<td><input type="number" name="_lbp_location_stock_levels[' . $location->ID . '][quantity]" value="' . esc_attr($quantity) . '" placeholder="0" style="width:80px;" min="0" /></td>';
            echo '<td>';
            echo '<select name="_lbp_location_stock_levels[' . $location->ID . '][status]" style="width:120px;">';
            echo '<option value="instock" ' . selected($status, 'instock', false) . '>In stock</option>';
            echo '<option value="outofstock" ' . selected($status, 'outofstock', false) . '>Out of stock</option>';
            echo '<option value="onbackorder" ' . selected($status, 'onbackorder', false) . '>On backorder</option>';
            echo '</select>';
            echo '</td>';
            echo '<td>';
            echo '<select name="_lbp_location_stock_levels[' . $location->ID . '][backorder]" style="width:100px;">';
            echo '<option value="no" ' . selected($backorder, 'no', false) . '>Do not allow</option>';
            echo '<option value="notify" ' . selected($backorder, 'notify', false) . '>Allow, notify</option>';
            echo '<option value="yes" ' . selected($backorder, 'yes', false) . '>Allow</option>';
            echo '</select>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
        
        // Add JavaScript to toggle panels
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#_lbp_location_pricing').change(function() {
                if ($(this).is(':checked')) {
                    $('#lbp_location_pricing_panel').show();
                } else {
                    $('#lbp_location_pricing_panel').hide();
                }
            }).trigger('change');
            
            $('#_lbp_location_stock').change(function() {
                if ($(this).is(':checked')) {
                    $('#lbp_location_stock_panel').show();
                } else {
                    $('#lbp_location_stock_panel').hide();
                }
            }).trigger('change');
        });
        </script>
        <?php
    }
    
    public function save_location_fields($post_id) {
        // Security checks
        // Verify nonce
        if (!isset($_POST['woocommerce_meta_nonce']) ||
            !wp_verify_nonce($_POST['woocommerce_meta_nonce'], 'woocommerce_save_data')) {
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_product', $post_id)) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check if this is a revision
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Save basic location fields
        $fields = [
            '_lbp_availability_type',
            '_lbp_delivery_type',
            '_lbp_gst_rate'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Save and validate HSN code
        if (isset($_POST['_lbp_hsn_code'])) {
            $hsn_code = sanitize_text_field($_POST['_lbp_hsn_code']);

            // Validate HSN code (must be 4, 6, or 8 digits)
            if ($hsn_code === '' || preg_match('/^\d{4}(\d{2})?(\d{2})?$/', $hsn_code)) {
                update_post_meta($post_id, '_lbp_hsn_code', $hsn_code);
            }
        }

        // Save checkboxes
        update_post_meta($post_id, '_lbp_location_pricing', isset($_POST['_lbp_location_pricing']) ? 'yes' : 'no');
        update_post_meta($post_id, '_lbp_location_stock', isset($_POST['_lbp_location_stock']) ? 'yes' : 'no');
        update_post_meta($post_id, '_lbp_rcm_applicable', isset($_POST['_lbp_rcm_applicable']) ? 'yes' : 'no'); // HIGH-10
        
        // Save selected locations
        if (isset($_POST['_lbp_selected_locations']) && is_array($_POST['_lbp_selected_locations'])) {
            update_post_meta($post_id, '_lbp_selected_locations', array_map('intval', $_POST['_lbp_selected_locations']));
        } else {
            update_post_meta($post_id, '_lbp_selected_locations', []);
        }
        
        // Save location prices
        if (isset($_POST['_lbp_location_prices'])) {
            $location_prices = [];
            foreach ($_POST['_lbp_location_prices'] as $location_id => $prices) {
                // Use WooCommerce's decimal formatting for prices
                $regular_price = wc_format_decimal($prices['regular']);
                $sale_price = wc_format_decimal($prices['sale']);

                // Validate prices are non-negative
                $regular_price = ($regular_price !== '' && $regular_price < 0) ? '' : $regular_price;
                $sale_price = ($sale_price !== '' && $sale_price < 0) ? '' : $sale_price;

                // Ensure sale price is less than regular price
                if ($regular_price !== '' && $sale_price !== '' && $sale_price >= $regular_price) {
                    $sale_price = '';
                }

                $location_prices[intval($location_id)] = [
                    'regular' => $regular_price,
                    'sale' => $sale_price
                ];
            }
            update_post_meta($post_id, '_lbp_location_prices', $location_prices);
        }
        
        // Save location stock levels
        if (isset($_POST['_lbp_location_stock_levels'])) {
            $location_stock = [];
            foreach ($_POST['_lbp_location_stock_levels'] as $location_id => $stock) {
                $location_stock[intval($location_id)] = [
                    'quantity' => intval($stock['quantity']),
                    'status' => sanitize_text_field($stock['status']),
                    'backorder' => sanitize_text_field($stock['backorder'])
                ];
            }
            update_post_meta($post_id, '_lbp_location_stock_levels', $location_stock);
        }
    }
    
    public function register_product_location_fields() {
        // Add location data to product REST API
        register_rest_field('product', 'location_data', [
            'get_callback' => [$this, 'get_product_location_data'],
            'schema' => [
                'description' => __('Location-specific product data', 'location-based-products'),
                'type' => 'object',
                'context' => ['view', 'edit'],
                'properties' => [
                    'availability_type' => [
                        'type' => 'string',
                        'enum' => ['all', 'specific', 'exclude']
                    ],
                    'selected_locations' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer']
                    ],
                    'location_pricing' => ['type' => 'boolean'],
                    'location_stock' => ['type' => 'boolean'],
                    'delivery_type' => ['type' => 'string'],
                    'prices' => ['type' => 'object'],
                    'stock_levels' => ['type' => 'object']
                ]
            ]
        ]);
    }
    
    public function get_product_location_data($object) {
        $product_id = $object['id'];
        
        return [
            'availability_type' => get_post_meta($product_id, '_lbp_availability_type', true) ?: 'all',
            'selected_locations' => get_post_meta($product_id, '_lbp_selected_locations', true) ?: [],
            'location_pricing' => get_post_meta($product_id, '_lbp_location_pricing', true) === 'yes',
            'location_stock' => get_post_meta($product_id, '_lbp_location_stock', true) === 'yes',
            'delivery_type' => get_post_meta($product_id, '_lbp_delivery_type', true) ?: 'standard',
            'prices' => get_post_meta($product_id, '_lbp_location_prices', true) ?: [],
            'stock_levels' => get_post_meta($product_id, '_lbp_location_stock_levels', true) ?: []
        ];
    }
}

new LBP_Product_Integration();