<?php
/**
 * Plugin Name: Headless WooCommerce API Bridge
 * Description: Provides a simplified WooCommerce API for headless frontend
 * Version: 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HeadlessWooCommerceAPI {
    
    private $api_key;
    
    public function __construct() {
        // Set your API key - change this to something secure!
        $this->api_key = get_option('headless_wc_api_key', 'your-secure-api-key-here');
        
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_api_key_update']);
    }
    
    public function register_routes() {
        $namespace = 'headless-wc/v1';
        
        // Products endpoint
        register_rest_route($namespace, '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'get_products'],
            'permission_callback' => [$this, 'check_api_permission'],
            'args' => [
                'per_page' => [
                    'default' => 10,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                ],
                'page' => [
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ],
                'search' => [
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // Single product endpoint
        register_rest_route($namespace, '/products/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_product'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        // Store info endpoint (public - no auth needed)
        register_rest_route($namespace, '/store-info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_store_info'],
            'permission_callback' => '__return_true' // Public endpoint
        ]);
    }
    
    /**
     * Check API permissions
     */
    public function check_api_permission($request) {
        // Method 1: API Key in header
        $api_key_header = $request->get_header('X-API-Key');
        if ($api_key_header === $this->api_key) {
            return true;
        }
        
        // Method 2: API Key in query parameter
        $api_key_param = $request->get_param('api_key');
        if ($api_key_param === $this->api_key) {
            return true;
        }
        
        // Method 3: JWT Token (if user is logged in via JWT)
        $jwt_auth = $request->get_header('Authorization');
        if ($jwt_auth && strpos($jwt_auth, 'Bearer ') === 0) {
            // You can add JWT validation here if needed
            // For now, we'll accept any valid JWT format
            return current_user_can('read');
        }
        
        return new WP_Error(
            'rest_forbidden',
            'API key required. Add X-API-Key header or api_key parameter.',
            ['status' => 401]
        );
    }
    
    /**
     * Add admin menu for API key management
     */
    public function add_admin_menu() {
        add_options_page(
            'Headless WooCommerce API Settings',
            'Headless WC API',
            'manage_options',
            'headless-wc-api',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Admin page for API key management
     */
    public function admin_page() {
        $current_key = get_option('headless_wc_api_key', '');
        ?>
        <div class="wrap">
            <h1>Headless WooCommerce API Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('update_headless_api_key', 'headless_api_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text" name="headless_api_key" value="<?php echo esc_attr($current_key); ?>" 
                                   class="regular-text" placeholder="Enter your secure API key" />
                            <p class="description">
                                This key will be required to access your headless API endpoints.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Generate New Key</th>
                        <td>
                            <button type="button" id="generate-key" class="button">Generate Secure Key</button>
                            <p class="description">Click to generate a cryptographically secure API key.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save API Key'); ?>
            </form>
            
            <hr>
            
            <h2>API Usage</h2>
            <p>Your frontend can authenticate in two ways:</p>
            
            <h3>Method 1: Header Authentication (Recommended)</h3>
            <pre>
Headers:
X-API-Key: <?php echo $current_key ? esc_html($current_key) : 'your-api-key-here'; ?>
            </pre>
            
            <h3>Method 2: Query Parameter</h3>
            <pre>
GET /wp-json/headless-wc/v1/products?api_key=<?php echo $current_key ? esc_html($current_key) : 'your-api-key-here'; ?>
            </pre>
            
            <h3>Available Endpoints</h3>
            <ul>
                <li><code>GET /wp-json/headless-wc/v1/products</code> - List products (requires auth)</li>
                <li><code>GET /wp-json/headless-wc/v1/products/{id}</code> - Single product (requires auth)</li>
                <li><code>GET /wp-json/headless-wc/v1/store-info</code> - Store info (public)</li>
            </ul>
        </div>
        
        <script>
        document.getElementById('generate-key').onclick = function() {
            // Generate a secure random key
            const array = new Uint8Array(32);
            crypto.getRandomValues(array);
            const key = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
            document.querySelector('input[name="headless_api_key"]').value = 'hwc_' + key;
        };
        </script>
        <?php
    }
    
    /**
     * Handle API key updates
     */
    public function handle_api_key_update() {
        if (isset($_POST['headless_api_key']) && 
            wp_verify_nonce($_POST['headless_api_nonce'], 'update_headless_api_key')) {
            
            $new_key = sanitize_text_field($_POST['headless_api_key']);
            update_option('headless_wc_api_key', $new_key);
            $this->api_key = $new_key;
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>API key updated successfully!</p></div>';
            });
        }
    }
    
    public function get_products($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 400]);
        }
        
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $search = $request->get_param('search');
        
        $args = [
            'limit' => $per_page,
            'page' => $page,
            'status' => 'publish'
        ];
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $products = wc_get_products($args);
        $product_data = [];
        
        foreach ($products as $product) {
            $product_data[] = $this->format_product($product);
        }
        
        return rest_ensure_response([
            'products' => $product_data,
            'total' => count($product_data),
            'page' => $page,
            'per_page' => $per_page
        ]);
    }
    
    public function get_product($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 400]);
        }
        
        $product_id = $request->get_param('id');
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_Error('product_not_found', 'Product not found', ['status' => 404]);
        }
        
        return rest_ensure_response($this->format_product($product));
    }
    
    public function get_store_info($request) {
        return rest_ensure_response([
            'store_name' => get_bloginfo('name'),
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Unknown',
            'products_count' => wp_count_posts('product')->publish
        ]);
    }
    
    private function format_product($product) {
        $images = [];
        $image_ids = $product->get_gallery_image_ids();
        array_unshift($image_ids, $product->get_image_id());
        
        foreach ($image_ids as $image_id) {
            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
                if ($image_url) {
                    $images[] = [
                        'id' => $image_id,
                        'src' => $image_url,
                        'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
                    ];
                }
            }
        }
        
        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => $product->get_permalink(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'price_html' => $product->get_price_html(),
            'on_sale' => $product->is_on_sale(),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'short_description' => $product->get_short_description(),
            'description' => $product->get_description(),
            'categories' => wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']),
            'tags' => wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names']),
            'images' => $images,
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'featured' => $product->is_featured(),
            'date_created' => $product->get_date_created()->date('Y-m-d H:i:s'),
        ];
    }
}

new HeadlessWooCommerceAPI();
?>