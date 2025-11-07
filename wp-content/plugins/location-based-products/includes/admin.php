<?php
/**
 * Location-Based Products - Admin Interface
 * Backend admin functionality for managing locations and products
 */

if (!defined('ABSPATH')) {
    exit;
}

class LBP_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_lbp_save_location', [$this, 'ajax_save_location']);
        add_action('wp_ajax_lbp_delete_location', [$this, 'ajax_delete_location']);
        add_action('wp_ajax_lbp_get_location_data', [$this, 'ajax_get_location_data']);
        add_action('wp_ajax_lbp_test_location_detection', [$this, 'ajax_test_location_detection']);
        add_action('wp_ajax_lbp_bulk_update_products', [$this, 'ajax_bulk_update_products']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Location-Based Products',
            'Location Products',
            'manage_options',
            'location-based-products',
            [$this, 'admin_page'],
            'dashicons-location',
            30
        );
        
        add_submenu_page(
            'location-based-products',
            'Locations',
            'Locations',
            'manage_options',
            'location-based-products',
            [$this, 'admin_page']
        );
        
        add_submenu_page(
            'location-based-products',
            'Service Areas',
            'Service Areas',
            'manage_options',
            'lbp-service-areas',
            [$this, 'service_areas_page']
        );
        
        add_submenu_page(
            'location-based-products',
            'Product Management',
            'Product Management',
            'manage_options',
            'lbp-products',
            [$this, 'products_page']
        );
        
        add_submenu_page(
            'location-based-products',
            'Settings',
            'Settings',
            'manage_options',
            'lbp-settings',
            [$this, 'settings_page']
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'location-based-products') === false && strpos($hook, 'lbp-') === false) {
            return;
        }
        
        wp_enqueue_style('lbp-admin-css', plugin_dir_url(dirname(__FILE__)) . 'assets/admin.css');
        wp_enqueue_script('lbp-admin-js', plugin_dir_url(dirname(__FILE__)) . 'assets/admin.js', ['jquery'], '1.0.0', true);
        
        wp_localize_script('lbp-admin-js', 'lbp_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lbp_admin_nonce'),
            'messages' => [
                'confirm_delete' => 'Are you sure you want to delete this location?',
                'save_success' => 'Location saved successfully!',
                'delete_success' => 'Location deleted successfully!',
                'error' => 'An error occurred. Please try again.'
            ]
        ]);
    }
    
    public function admin_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'locations';
        
        ?>
        <div class="wrap">
            <h1>Location-Based Products</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=location-based-products&tab=locations" class="nav-tab <?php echo $tab === 'locations' ? 'nav-tab-active' : ''; ?>">Locations</a>
                <a href="?page=location-based-products&tab=test" class="nav-tab <?php echo $tab === 'test' ? 'nav-tab-active' : ''; ?>">Test Detection</a>
                <a href="?page=location-based-products&tab=import" class="nav-tab <?php echo $tab === 'import' ? 'nav-tab-active' : ''; ?>">Import/Export</a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ($tab) {
                    case 'locations':
                        $this->locations_tab();
                        break;
                    case 'test':
                        $this->test_tab();
                        break;
                    case 'import':
                        $this->import_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    public function locations_tab() {
        $locations = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft'],
            'orderby' => 'meta_value_num',
            'meta_key' => '_lbp_priority',
            'order' => 'DESC'
        ]);
        ?>
        <div class="lbp-locations-container">
            <div class="lbp-header">
                <h2>Manage Locations</h2>
                <button type="button" class="button button-primary" id="add-new-location">Add New Location</button>
            </div>
            
            <div class="lbp-locations-grid">
                <?php foreach ($locations as $location): ?>
                    <div class="location-card" data-location-id="<?php echo $location->ID; ?>">
                        <div class="location-header">
                            <h3><?php echo esc_html($location->post_title); ?></h3>
                            <div class="location-actions">
                                <button type="button" class="button edit-location" data-location-id="<?php echo $location->ID; ?>">Edit</button>
                                <button type="button" class="button delete-location" data-location-id="<?php echo $location->ID; ?>">Delete</button>
                            </div>
                        </div>
                        
                        <div class="location-details">
                            <?php
                            $address = get_post_meta($location->ID, '_lbp_address', true);
                            $city = get_post_meta($location->ID, '_lbp_city', true);
                            $postal_codes = get_post_meta($location->ID, '_lbp_postal_codes', true);
                            $is_active = get_post_meta($location->ID, '_lbp_is_active', true);
                            $priority = get_post_meta($location->ID, '_lbp_priority', true);
                            ?>
                            
                            <p><strong>Address:</strong> <?php echo esc_html($address); ?></p>
                            <?php if ($city): ?>
                                <p><strong>City:</strong> <?php echo esc_html($city); ?></p>
                            <?php endif; ?>
                            <?php if ($postal_codes): ?>
                                <p><strong>Postal Codes:</strong> <?php echo esc_html($postal_codes); ?></p>
                            <?php endif; ?>
                            <p><strong>Status:</strong> 
                                <span class="status-badge <?php echo $is_active === '1' ? 'active' : 'inactive'; ?>">
                                    <?php echo $is_active === '1' ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                            <p><strong>Priority:</strong> <?php echo esc_html($priority ?: '0'); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($locations)): ?>
                    <div class="no-locations">
                        <p>No locations found. <a href="#" id="add-first-location">Add your first location</a>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Location Modal -->
        <div id="location-modal" class="lbp-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modal-title">Add New Location</h3>
                    <span class="close">&times;</span>
                </div>
                
                <form id="location-form">
                    <input type="hidden" id="location-id" name="location_id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="location-name">Location Name *</label></th>
                            <td><input type="text" id="location-name" name="location_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-description">Description</label></th>
                            <td><textarea id="location-description" name="location_description" rows="3" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-address">Address *</label></th>
                            <td><input type="text" id="location-address" name="location_address" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-city">City</label></th>
                            <td><input type="text" id="location-city" name="location_city" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-state">State/Region</label></th>
                            <td><input type="text" id="location-state" name="location_state" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-country">Country</label></th>
                            <td>
                                <select id="location-country" name="location_country" class="regular-text">
                                    <option value="">Select Country</option>
                                    <option value="IN">India</option>
                                    <option value="US">United States</option>
                                    <option value="CA">Canada</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="AU">Australia</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-postal-codes">Postal Codes</label></th>
                            <td>
                                <input type="text" id="location-postal-codes" name="location_postal_codes" class="regular-text" placeholder="Enter comma-separated postal codes">
                                <p class="description">Enter postal codes served by this location, separated by commas (e.g., 110001, 110002, 110003)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-latitude">Latitude</label></th>
                            <td><input type="number" id="location-latitude" name="location_latitude" class="regular-text" step="any" placeholder="28.6139"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-longitude">Longitude</label></th>
                            <td><input type="number" id="location-longitude" name="location_longitude" class="regular-text" step="any" placeholder="77.2090"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-delivery-radius">Delivery Radius (km)</label></th>
                            <td><input type="number" id="location-delivery-radius" name="location_delivery_radius" class="regular-text" placeholder="25"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-priority">Priority</label></th>
                            <td>
                                <input type="number" id="location-priority" name="location_priority" class="regular-text" value="0" min="0">
                                <p class="description">Higher priority locations are preferred when multiple locations match</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location-active">Status</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="location-active" name="location_active" value="1" checked>
                                    Active
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="modal-footer">
                        <button type="button" class="button" id="cancel-location">Cancel</button>
                        <button type="submit" class="button button-primary" id="save-location">Save Location</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    public function test_tab() {
        ?>
        <div class="lbp-test-container">
            <h2>Test Location Detection</h2>
            <p>Use this tool to test how the location detection system works with different inputs.</p>
            
            <div class="test-methods">
                <div class="test-method">
                    <h3>Test by IP Address</h3>
                    <form id="test-ip-form">
                        <input type="text" id="test-ip" placeholder="Enter IP address (e.g., 8.8.8.8)" class="regular-text">
                        <button type="submit" class="button">Test IP</button>
                    </form>
                    <div id="ip-result" class="test-result"></div>
                </div>
                
                <div class="test-method">
                    <h3>Test by Postal Code</h3>
                    <form id="test-postal-form">
                        <input type="text" id="test-postal" placeholder="Enter postal code (e.g., 110001)" class="regular-text">
                        <button type="submit" class="button">Test Postal Code</button>
                    </form>
                    <div id="postal-result" class="test-result"></div>
                </div>
                
                <div class="test-method">
                    <h3>Test by Coordinates</h3>
                    <form id="test-coordinates-form">
                        <input type="number" id="test-latitude" placeholder="Latitude" class="regular-text" step="any">
                        <input type="number" id="test-longitude" placeholder="Longitude" class="regular-text" step="any">
                        <button type="submit" class="button">Test Coordinates</button>
                    </form>
                    <div id="coordinates-result" class="test-result"></div>
                </div>
                
                <div class="test-method">
                    <h3>Test by City</h3>
                    <form id="test-city-form">
                        <input type="text" id="test-city" placeholder="Enter city name" class="regular-text">
                        <input type="text" id="test-region" placeholder="Enter region/state (optional)" class="regular-text">
                        <button type="submit" class="button">Test City</button>
                    </form>
                    <div id="city-result" class="test-result"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function import_tab() {
        ?>
        <div class="lbp-import-container">
            <h2>Import/Export Locations</h2>
            
            <div class="import-export-sections">
                <div class="section">
                    <h3>Export Locations</h3>
                    <p>Export all locations to a CSV file for backup or migration purposes.</p>
                    <a href="<?php echo admin_url('admin-ajax.php?action=lbp_export_locations&nonce=' . wp_create_nonce('lbp_export_nonce')); ?>" class="button">Export to CSV</a>
                </div>
                
                <div class="section">
                    <h3>Import Locations</h3>
                    <p>Import locations from a CSV file. The CSV should have the following columns:</p>
                    <code>name, description, address, city, state, country, postal_codes, latitude, longitude, delivery_radius, priority, is_active</code>
                    
                    <form id="import-form" enctype="multipart/form-data">
                        <input type="file" id="import-file" name="import_file" accept=".csv" required>
                        <button type="submit" class="button button-primary">Import CSV</button>
                    </form>
                    <div id="import-result"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function service_areas_page() {
        echo '<div class="wrap"><h1>Service Areas</h1><p>Service area management functionality will be added here.</p></div>';
    }
    
    public function products_page() {
        $products = wc_get_products([
            'limit' => 50,
            'status' => 'publish'
        ]);
        
        ?>
        <div class="wrap">
            <h1>Product Location Management</h1>
            <p>Configure location-based availability and pricing for your products.</p>
            
            <div class="bulk-actions">
                <select id="bulk-action-type">
                    <option value="">Select Bulk Action</option>
                    <option value="set_availability">Set Availability</option>
                    <option value="enable_location_pricing">Enable Location Pricing</option>
                    <option value="enable_location_stock">Enable Location Stock</option>
                    <option value="set_delivery_type">Set Delivery Type</option>
                </select>
                <button type="button" class="button" id="apply-bulk-action">Apply</button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="check-column"><input type="checkbox" id="select-all-products"></td>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Availability Type</th>
                        <th>Location Pricing</th>
                        <th>Location Stock</th>
                        <th>Delivery Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="product_ids[]" value="<?php echo $product->get_id(); ?>">
                            </th>
                            <td>
                                <strong><?php echo esc_html($product->get_name()); ?></strong>
                                <?php if ($product->get_image_id()): ?>
                                    <br><img src="<?php echo wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($product->get_sku()); ?></td>
                            <td>
                                <?php
                                $availability_type = get_post_meta($product->get_id(), '_lbp_availability_type', true) ?: 'all';
                                echo ucfirst($availability_type);
                                ?>
                            </td>
                            <td>
                                <?php
                                $location_pricing = get_post_meta($product->get_id(), '_lbp_location_pricing', true) === 'yes' ? 'Enabled' : 'Disabled';
                                echo $location_pricing;
                                ?>
                            </td>
                            <td>
                                <?php
                                $location_stock = get_post_meta($product->get_id(), '_lbp_location_stock', true) === 'yes' ? 'Enabled' : 'Disabled';
                                echo $location_stock;
                                ?>
                            </td>
                            <td>
                                <?php
                                $delivery_type = get_post_meta($product->get_id(), '_lbp_delivery_type', true) ?: 'standard';
                                echo ucfirst($delivery_type);
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $product->get_id() . '&action=edit'); ?>" class="button button-small">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['save_settings'])) {
            update_option('lbp_default_location_detection', sanitize_text_field($_POST['default_location_detection']));
            update_option('lbp_fallback_behavior', sanitize_text_field($_POST['fallback_behavior']));
            update_option('lbp_enable_geolocation', isset($_POST['enable_geolocation']));
            update_option('lbp_geolocation_api_key', sanitize_text_field($_POST['geolocation_api_key']));
            update_option('lbp_cache_duration', intval($_POST['cache_duration']));
            
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $default_detection = get_option('lbp_default_location_detection', 'ip');
        $fallback_behavior = get_option('lbp_fallback_behavior', 'show_all');
        $enable_geolocation = get_option('lbp_enable_geolocation', true);
        $api_key = get_option('lbp_geolocation_api_key', '');
        $cache_duration = get_option('lbp_cache_duration', 3600);
        ?>
        
        <div class="wrap">
            <h1>Location-Based Products Settings</h1>
            
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Location Detection Method</th>
                        <td>
                            <select name="default_location_detection">
                                <option value="ip" <?php selected($default_detection, 'ip'); ?>>IP Address</option>
                                <option value="postal" <?php selected($default_detection, 'postal'); ?>>Postal Code</option>
                                <option value="coordinates" <?php selected($default_detection, 'coordinates'); ?>>GPS Coordinates</option>
                            </select>
                            <p class="description">Primary method for detecting user location</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Fallback Behavior</th>
                        <td>
                            <select name="fallback_behavior">
                                <option value="show_all" <?php selected($fallback_behavior, 'show_all'); ?>>Show All Products</option>
                                <option value="hide_all" <?php selected($fallback_behavior, 'hide_all'); ?>>Hide Location-Specific Products</option>
                                <option value="default_location" <?php selected($fallback_behavior, 'default_location'); ?>>Use Default Location</option>
                            </select>
                            <p class="description">What to do when location cannot be detected</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Geolocation</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_geolocation" value="1" <?php checked($enable_geolocation); ?>>
                                Enable browser geolocation requests
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Geolocation API Key</th>
                        <td>
                            <input type="text" name="geolocation_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                            <p class="description">API key for enhanced geolocation services (optional)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cache Duration (seconds)</th>
                        <td>
                            <input type="number" name="cache_duration" value="<?php echo esc_attr($cache_duration); ?>" min="0">
                            <p class="description">How long to cache location detection results</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings', 'primary', 'save_settings'); ?>
            </form>
        </div>
        <?php
    }
    
    // AJAX Handlers
    public function ajax_save_location() {
        check_ajax_referer('lbp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $location_id = intval($_POST['location_id']);
        $location_data = [
            'post_title' => sanitize_text_field($_POST['location_name']),
            'post_content' => sanitize_textarea_field($_POST['location_description']),
            'post_type' => 'lbp_location',
            'post_status' => 'publish'
        ];
        
        if ($location_id) {
            $location_data['ID'] = $location_id;
            $result = wp_update_post($location_data);
        } else {
            $result = wp_insert_post($location_data);
        }
        
        if ($result) {
            // Save meta fields
            $meta_fields = [
                '_lbp_address' => sanitize_text_field($_POST['location_address']),
                '_lbp_city' => sanitize_text_field($_POST['location_city']),
                '_lbp_state' => sanitize_text_field($_POST['location_state']),
                '_lbp_country' => sanitize_text_field($_POST['location_country']),
                '_lbp_postal_codes' => sanitize_text_field($_POST['location_postal_codes']),
                '_lbp_latitude' => floatval($_POST['location_latitude']),
                '_lbp_longitude' => floatval($_POST['location_longitude']),
                '_lbp_delivery_radius' => intval($_POST['location_delivery_radius']),
                '_lbp_priority' => intval($_POST['location_priority']),
                '_lbp_is_active' => isset($_POST['location_active']) ? '1' : '0'
            ];
            
            foreach ($meta_fields as $key => $value) {
                update_post_meta($result, $key, $value);
            }
            
            wp_send_json_success(['message' => 'Location saved successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to save location.']);
        }
    }
    
    public function ajax_delete_location() {
        check_ajax_referer('lbp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $location_id = intval($_POST['location_id']);
        $result = wp_delete_post($location_id, true);
        
        if ($result) {
            wp_send_json_success(['message' => 'Location deleted successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete location.']);
        }
    }
    
    public function ajax_get_location_data() {
        check_ajax_referer('lbp_admin_nonce', 'nonce');
        
        $location_id = intval($_POST['location_id']);
        $location = get_post($location_id);
        
        if (!$location) {
            wp_send_json_error(['message' => 'Location not found.']);
        }
        
        $location_data = [
            'id' => $location->ID,
            'name' => $location->post_title,
            'description' => $location->post_content,
            'address' => get_post_meta($location->ID, '_lbp_address', true),
            'city' => get_post_meta($location->ID, '_lbp_city', true),
            'state' => get_post_meta($location->ID, '_lbp_state', true),
            'country' => get_post_meta($location->ID, '_lbp_country', true),
            'postal_codes' => get_post_meta($location->ID, '_lbp_postal_codes', true),
            'latitude' => get_post_meta($location->ID, '_lbp_latitude', true),
            'longitude' => get_post_meta($location->ID, '_lbp_longitude', true),
            'delivery_radius' => get_post_meta($location->ID, '_lbp_delivery_radius', true),
            'priority' => get_post_meta($location->ID, '_lbp_priority', true),
            'is_active' => get_post_meta($location->ID, '_lbp_is_active', true)
        ];
        
        wp_send_json_success($location_data);
    }
    
    public function ajax_test_location_detection() {
        check_ajax_referer('lbp_admin_nonce', 'nonce');
        
        $method = sanitize_text_field($_POST['method']);
        $result = null;
        
        switch ($method) {
            case 'ip':
                $ip = sanitize_text_field($_POST['ip']);
                $result = LBP_Helpers::find_location_by_ip($ip);
                break;
            case 'postal':
                $postal = sanitize_text_field($_POST['postal']);
                $result = LBP_Helpers::find_location_by_postal_code($postal);
                break;
            case 'coordinates':
                $lat = floatval($_POST['latitude']);
                $lng = floatval($_POST['longitude']);
                $result = LBP_Helpers::find_location_by_coordinates($lat, $lng);
                break;
            case 'city':
                $city = sanitize_text_field($_POST['city']);
                $region = sanitize_text_field($_POST['region']);
                $result = LBP_Helpers::find_location_by_city($city, $region);
                break;
        }
        
        if ($result) {
            $location_data = LBP_Helpers::format_location_data($result);
            wp_send_json_success($location_data);
        } else {
            wp_send_json_success(['message' => 'No matching location found.']);
        }
    }
}

new LBP_Admin();