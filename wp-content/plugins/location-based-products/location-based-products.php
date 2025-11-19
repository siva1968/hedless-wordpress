<?php
/**
 * Plugin Name: Location-Based Products for WooCommerce
 * Plugin URI: https://github.com/siva1968/hedless-wordpress
 * Description: Advanced location-based product management system for furniture e-commerce. Supports location detection, area-specific product availability, location-based inventory, GST calculation, and location-based sliders.
 * Version: 1.1.0
 * Author: Siva
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 10.3
 * Woo: 12345678:abcdefghijklmnopqrstuvwxyz123456
 * License: GPL v2 or later
 * Text Domain: location-based-products
 * Domain Path: /languages
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // Add admin notice if WooCommerce is not active
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>Location-Based Products:</strong> This plugin requires WooCommerce to be installed and activated.</p></div>';
    });
    return;
}

define('LBP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LBP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LBP_VERSION', '1.1.0');

class LocationBasedProducts {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('before_woocommerce_init', [$this, 'declare_compatibility']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    public function init() {
        // Check WooCommerce version compatibility
        if (!$this->check_woocommerce_version()) {
            return;
        }
        
        // Register custom post types
        $this->register_location_post_type();
        $this->register_service_area_post_type();
        
        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_location_meta']);
        
        // WooCommerce product integration is handled by LBP_Product_Integration class
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }
    
    public function register_location_post_type() {
        $labels = [
            'name' => __('Locations', 'location-based-products'),
            'singular_name' => __('Location', 'location-based-products'),
            'menu_name' => __('Locations', 'location-based-products'),
            'add_new' => __('Add New Location', 'location-based-products'),
            'add_new_item' => __('Add New Location', 'location-based-products'),
            'edit_item' => __('Edit Location', 'location-based-products'),
            'new_item' => __('New Location', 'location-based-products'),
            'view_item' => __('View Location', 'location-based-products'),
            'search_items' => __('Search Locations', 'location-based-products'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=product',
            'show_in_rest' => true,
            'rest_base' => 'locations',
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => true,
            'menu_position' => 25,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'menu_icon' => 'dashicons-location-alt',
        ];
        
        register_post_type('lbp_location', $args);
    }
    
    public function register_service_area_post_type() {
        $labels = [
            'name' => __('Service Areas', 'location-based-products'),
            'singular_name' => __('Service Area', 'location-based-products'),
            'menu_name' => __('Service Areas', 'location-based-products'),
            'add_new' => __('Add Service Area', 'location-based-products'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=lbp_location',
            'show_in_rest' => true,
            'rest_base' => 'service-areas',
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-admin-site',
        ];
        
        register_post_type('lbp_service_area', $args);
    }
    
    public function add_meta_boxes() {
        // Location meta box
        add_meta_box(
            'lbp_location_details',
            __('Location Details', 'location-based-products'),
            [$this, 'location_meta_box'],
            'lbp_location',
            'normal',
            'high'
        );
        
        // Service area meta box
        add_meta_box(
            'lbp_service_area_details',
            __('Service Area Details', 'location-based-products'),
            [$this, 'service_area_meta_box'],
            'lbp_service_area',
            'normal',
            'high'
        );
    }
    
    public function location_meta_box($post) {
        wp_nonce_field('lbp_location_meta_box', 'lbp_location_meta_box_nonce');
        
        $latitude = get_post_meta($post->ID, '_lbp_latitude', true);
        $longitude = get_post_meta($post->ID, '_lbp_longitude', true);
        $address = get_post_meta($post->ID, '_lbp_address', true);
        $city = get_post_meta($post->ID, '_lbp_city', true);
        $state = get_post_meta($post->ID, '_lbp_state', true);
        $country = get_post_meta($post->ID, '_lbp_country', true);
        $postal_codes = get_post_meta($post->ID, '_lbp_postal_codes', true);
        $delivery_radius = get_post_meta($post->ID, '_lbp_delivery_radius', true);
        $is_active = get_post_meta($post->ID, '_lbp_is_active', true);
        $priority = get_post_meta($post->ID, '_lbp_priority', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="lbp_address"><?php _e('Address', 'location-based-products'); ?></label></th>
                <td><input type="text" id="lbp_address" name="lbp_address" value="<?php echo esc_attr($address); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lbp_city"><?php _e('City', 'location-based-products'); ?></label></th>
                <td><input type="text" id="lbp_city" name="lbp_city" value="<?php echo esc_attr($city); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lbp_state"><?php _e('State/Province', 'location-based-products'); ?></label></th>
                <td><input type="text" id="lbp_state" name="lbp_state" value="<?php echo esc_attr($state); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lbp_country"><?php _e('Country', 'location-based-products'); ?></label></th>
                <td>
                    <select id="lbp_country" name="lbp_country" class="regular-text">
                        <option value="IN" <?php selected($country, 'IN'); ?>>India</option>
                        <option value="US" <?php selected($country, 'US'); ?>>United States</option>
                        <option value="GB" <?php selected($country, 'GB'); ?>>United Kingdom</option>
                        <option value="CA" <?php selected($country, 'CA'); ?>>Canada</option>
                        <option value="AU" <?php selected($country, 'AU'); ?>>Australia</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_latitude"><?php _e('Latitude', 'location-based-products'); ?></label></th>
                <td><input type="text" id="lbp_latitude" name="lbp_latitude" value="<?php echo esc_attr($latitude); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lbp_longitude"><?php _e('Longitude', 'location-based-products'); ?></label></th>
                <td><input type="text" id="lbp_longitude" name="lbp_longitude" value="<?php echo esc_attr($longitude); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lbp_postal_codes"><?php _e('Postal Codes (comma-separated)', 'location-based-products'); ?></label></th>
                <td>
                    <textarea id="lbp_postal_codes" name="lbp_postal_codes" rows="3" class="regular-text"><?php echo esc_textarea($postal_codes); ?></textarea>
                    <p class="description"><?php _e('Enter postal codes separated by commas (e.g., 500001, 500002, 500003)', 'location-based-products'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_delivery_radius"><?php _e('Delivery Radius (km)', 'location-based-products'); ?></label></th>
                <td><input type="number" id="lbp_delivery_radius" name="lbp_delivery_radius" value="<?php echo esc_attr($delivery_radius); ?>" min="0" step="0.1" class="small-text" /></td>
            </tr>
            <tr>
                <th><label for="lbp_priority"><?php _e('Priority', 'location-based-products'); ?></label></th>
                <td>
                    <input type="number" id="lbp_priority" name="lbp_priority" value="<?php echo esc_attr($priority ?: 1); ?>" min="1" class="small-text" />
                    <p class="description"><?php _e('Higher numbers = higher priority when multiple locations match', 'location-based-products'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_is_active"><?php _e('Active', 'location-based-products'); ?></label></th>
                <td>
                    <input type="checkbox" id="lbp_is_active" name="lbp_is_active" value="1" <?php checked($is_active, '1'); ?> />
                    <label for="lbp_is_active"><?php _e('This location is active and available for orders', 'location-based-products'); ?></label>
                </td>
            </tr>
            <tr>
                <td colspan="2"><hr><h3><?php _e('GST / Tax Settings', 'location-based-products'); ?></h3></td>
            </tr>
            <tr>
                <th><label for="lbp_gst_rate"><?php _e('GST Rate (%)', 'location-based-products'); ?></label></th>
                <td>
                    <select id="lbp_gst_rate" name="lbp_gst_rate" class="small-text">
                        <option value="0" <?php selected(get_post_meta($post->ID, '_lbp_gst_rate', true), '0'); ?>>0% (Exempt)</option>
                        <option value="5" <?php selected(get_post_meta($post->ID, '_lbp_gst_rate', true), '5'); ?>>5%</option>
                        <option value="12" <?php selected(get_post_meta($post->ID, '_lbp_gst_rate', true), '12'); ?>>12%</option>
                        <option value="18" <?php selected(get_post_meta($post->ID, '_lbp_gst_rate', true) ?: '18', '18'); ?>>18% (Default)</option>
                        <option value="28" <?php selected(get_post_meta($post->ID, '_lbp_gst_rate', true), '28'); ?>>28% (Luxury)</option>
                    </select>
                    <p class="description"><?php _e('Default GST rate for products in this location', 'location-based-products'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_gst_type"><?php _e('GST Type', 'location-based-products'); ?></label></th>
                <td>
                    <select id="lbp_gst_type" name="lbp_gst_type" class="regular-text">
                        <option value="intrastate" <?php selected(get_post_meta($post->ID, '_lbp_gst_type', true) ?: 'intrastate', 'intrastate'); ?>>Intrastate (CGST + SGST)</option>
                        <option value="interstate" <?php selected(get_post_meta($post->ID, '_lbp_gst_type', true), 'interstate'); ?>>Interstate (IGST)</option>
                    </select>
                    <p class="description"><?php _e('Intrastate: Within same state (CGST+SGST). Interstate: Between states (IGST)', 'location-based-products'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_default_hsn_code"><?php _e('Default HSN Code', 'location-based-products'); ?></label></th>
                <td>
                    <input type="text" id="lbp_default_hsn_code" name="lbp_default_hsn_code" value="<?php echo esc_attr(get_post_meta($post->ID, '_lbp_default_hsn_code', true) ?: '9403'); ?>" class="regular-text" placeholder="9403" />
                    <p class="description"><?php _e('Default HSN code for furniture products (e.g., 9403 for furniture)', 'location-based-products'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function service_area_meta_box($post) {
        wp_nonce_field('lbp_service_area_meta_box', 'lbp_service_area_meta_box_nonce');
        
        $parent_location = get_post_meta($post->ID, '_lbp_parent_location', true);
        $postal_codes = get_post_meta($post->ID, '_lbp_service_postal_codes', true);
        $coordinates = get_post_meta($post->ID, '_lbp_service_coordinates', true);
        $delivery_fee = get_post_meta($post->ID, '_lbp_delivery_fee', true);
        $min_order_amount = get_post_meta($post->ID, '_lbp_min_order_amount', true);
        
        $locations = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="lbp_parent_location"><?php _e('Parent Location', 'location-based-products'); ?></label></th>
                <td>
                    <select id="lbp_parent_location" name="lbp_parent_location" class="regular-text">
                        <option value=""><?php _e('Select Location', 'location-based-products'); ?></option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location->ID; ?>" <?php selected($parent_location, $location->ID); ?>>
                                <?php echo esc_html($location->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_service_postal_codes"><?php _e('Service Postal Codes', 'location-based-products'); ?></label></th>
                <td>
                    <textarea id="lbp_service_postal_codes" name="lbp_service_postal_codes" rows="4" class="large-text"><?php echo esc_textarea($postal_codes); ?></textarea>
                    <p class="description"><?php _e('Enter postal codes separated by commas', 'location-based-products'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_delivery_fee"><?php _e('Delivery Fee', 'location-based-products'); ?></label></th>
                <td><input type="number" id="lbp_delivery_fee" name="lbp_delivery_fee" value="<?php echo esc_attr($delivery_fee); ?>" min="0" step="0.01" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lbp_min_order_amount"><?php _e('Minimum Order Amount', 'location-based-products'); ?></label></th>
                <td><input type="number" id="lbp_min_order_amount" name="lbp_min_order_amount" value="<?php echo esc_attr($min_order_amount); ?>" min="0" step="0.01" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    public function save_location_meta($post_id) {
        if (!isset($_POST['lbp_location_meta_box_nonce']) || !wp_verify_nonce($_POST['lbp_location_meta_box_nonce'], 'lbp_location_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'lbp_location') {
            $fields = ['latitude', 'longitude', 'address', 'city', 'state', 'country', 'postal_codes', 'delivery_radius', 'priority', 'gst_rate', 'gst_type', 'default_hsn_code'];

            foreach ($fields as $field) {
                if (isset($_POST["lbp_$field"])) {
                    update_post_meta($post_id, "_lbp_$field", sanitize_text_field($_POST["lbp_$field"]));
                }
            }

            update_post_meta($post_id, '_lbp_is_active', isset($_POST['lbp_is_active']) ? '1' : '0');
        }
        
        if ($post_type === 'lbp_service_area') {
            if (isset($_POST['lbp_parent_location'])) {
                update_post_meta($post_id, '_lbp_parent_location', intval($_POST['lbp_parent_location']));
            }
            
            $service_fields = ['service_postal_codes', 'delivery_fee', 'min_order_amount'];
            foreach ($service_fields as $field) {
                if (isset($_POST["lbp_$field"])) {
                    update_post_meta($post_id, "_lbp_$field", sanitize_text_field($_POST["lbp_$field"]));
                }
            }
        }
    }
    
    public function activate() {
        $this->register_location_post_type();
        $this->register_service_area_post_type();
        flush_rewrite_rules();
        
        // Create default location
        $this->create_default_location();
    }
    
    private function create_default_location() {
        $existing = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        if (empty($existing)) {
            $default_location = wp_insert_post([
                'post_title' => 'Default Location',
                'post_content' => 'Default service location for all areas',
                'post_type' => 'lbp_location',
                'post_status' => 'publish'
            ]);
            
            if ($default_location) {
                update_post_meta($default_location, '_lbp_is_active', '1');
                update_post_meta($default_location, '_lbp_city', 'Default City');
                update_post_meta($default_location, '_lbp_country', 'IN');
                update_post_meta($default_location, '_lbp_priority', '1');
            }
        }
    }
    
    public function declare_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            // Declare High-Performance Order Storage (HPOS) compatibility
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            
            // Declare Cart & Checkout Blocks compatibility  
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
            
            // Declare Analytics compatibility
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('analytics', __FILE__, true);
        }
    }
    
    public function check_woocommerce_version() {
        if (defined('WC_VERSION')) {
            $required_version = '6.0';
            if (version_compare(WC_VERSION, $required_version, '<')) {
                add_action('admin_notices', function() use ($required_version) {
                    echo '<div class="error"><p><strong>Location-Based Products:</strong> This plugin requires WooCommerce version ' . $required_version . ' or higher. You are running version ' . WC_VERSION . '.</p></div>';
                });
                return false;
            }
        }
        return true;
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function admin_scripts($hook) {
        if (in_array($hook, ['post.php', 'post-new.php'])) {
            $screen = get_current_screen();
            if (in_array($screen->post_type, ['lbp_location', 'lbp_service_area'])) {
                wp_enqueue_script('lbp-admin', LBP_PLUGIN_URL . 'assets/admin.js', ['jquery'], LBP_VERSION, true);
                wp_enqueue_style('lbp-admin', LBP_PLUGIN_URL . 'assets/admin.css', [], LBP_VERSION);
            }
        }
    }
}

// Include helper classes and functionality
require_once LBP_PLUGIN_PATH . 'includes/helpers.php';
require_once LBP_PLUGIN_PATH . 'includes/woocommerce-compatibility.php';
require_once LBP_PLUGIN_PATH . 'includes/product-integration.php';
require_once LBP_PLUGIN_PATH . 'includes/rest-api.php';
require_once LBP_PLUGIN_PATH . 'includes/admin.php';
require_once LBP_PLUGIN_PATH . 'includes/gst-integration.php';
require_once LBP_PLUGIN_PATH . 'includes/slider-management.php';

// Initialize the plugin
new LocationBasedProducts();