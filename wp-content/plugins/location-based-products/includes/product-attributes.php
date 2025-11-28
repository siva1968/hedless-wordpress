<?php
/**
 * Location-Based Products - Product Attributes Manager
 * Creates and manages WooCommerce product attributes for filtering
 * Based on AF Website changes.xlsx specifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class LBP_Product_Attributes {

    private $attributes_config = [];

    public function __construct() {
        add_action('init', [$this, 'setup_attributes'], 20);
        add_action('admin_menu', [$this, 'add_attributes_admin_page']);
    }

    /**
     * Setup product attributes configuration
     */
    public function setup_attributes() {
        // Define all product attributes based on filter specifications
        $this->attributes_config = [
            'pa_type' => [
                'name' => 'Product Type',
                'slug' => 'pa_type',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_size' => [
                'name' => 'Size',
                'slug' => 'pa_size',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_material' => [
                'name' => 'Material',
                'slug' => 'pa_material',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_colour' => [
                'name' => 'Colour',
                'slug' => 'pa_colour',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_storage' => [
                'name' => 'Storage Type',
                'slug' => 'pa_storage',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_headboard' => [
                'name' => 'Headboard Type',
                'slug' => 'pa_headboard',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_upholstery' => [
                'name' => 'Upholstery',
                'slug' => 'pa_upholstery',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_brand' => [
                'name' => 'Brand',
                'slug' => 'pa_brand',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_mechanism' => [
                'name' => 'Mechanism',
                'slug' => 'pa_mechanism',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_thickness' => [
                'name' => 'Thickness',
                'slug' => 'pa_thickness',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ],
            'pa_discount_range' => [
                'name' => 'Discount Range',
                'slug' => 'pa_discount_range',
                'type' => 'select',
                'orderby' => 'menu_order',
                'public' => true
            ]
        ];

        // Register each attribute
        foreach ($this->attributes_config as $slug => $config) {
            $this->register_product_attribute($slug, $config);
        }
    }

    /**
     * Register a single product attribute
     */
    private function register_product_attribute($slug, $config) {
        // Check if attribute already exists
        $attribute_id = wc_attribute_taxonomy_id_by_name($slug);

        if (!$attribute_id) {
            // Create the attribute
            $args = [
                'slug' => $slug,
                'name' => $config['name'],
                'type' => $config['type'],
                'order_by' => $config['orderby'],
                'has_archives' => $config['public']
            ];

            wc_create_attribute($args);
        }

        // Register taxonomy
        if (!taxonomy_exists($slug)) {
            register_taxonomy($slug, 'product', [
                'label' => $config['name'],
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => str_replace('pa_', '', $slug)],
            ]);
        }
    }

    /**
     * Create attribute terms based on filter specifications
     */
    public function create_attribute_terms() {
        $terms = $this->get_attribute_terms_config();

        foreach ($terms as $taxonomy => $term_list) {
            foreach ($term_list as $term_name) {
                if (!term_exists($term_name, $taxonomy)) {
                    wp_insert_term($term_name, $taxonomy);
                }
            }
        }

        return count($terms);
    }

    /**
     * Get all attribute terms configuration from specifications
     */
    private function get_attribute_terms_config() {
        return [
            // Product Types by category
            'pa_type' => [
                // Sofas
                'Sofa sets',
                'Recliner Sets',
                'Loungers sofas',
                'Corner Sofas',
                'Sofa Cum Beds',
                'Diwans',
                // Recliners
                'Motorised Recliner Sets',
                'Loungers',
                'Corner',
                // Tables
                'Centre Tables',
                'End Tables',
                'Console Tables',
                'Nesting Tables',
                // Chairs
                'Accent Chairs',
                'Folding Chairs',
                'Café Chairs',
                'Rocking Chairs',
                'Stools',
                // Beds
                'Solid Wood Beds',
                'Upholstered Beds',
                'Engineered Wood Beds',
                'Hydraulic Storage Beds',
                'Poster Beds',
                'Metal Beds',
                // Mattress
                'Memory Foam Mattress',
                'Foam Mattress',
                'Latex Mattress',
                'Spring Mattress',
                'Coir Mattress',
                'Bonnel Spring Mattress',
                'Pocket Spring Mattress',
                'Coir and Foam Mattress'
            ],

            // Sizes
            'pa_size' => [
                '3+2+1', '3+2', '3+1+1', '3+2+C',
                '1 seater', '2 seater', '3 seater', '4 seater',
                '2ft', '3ft', '4ft', '5ft',
                'King / 6FT Beds',
                'Queen / 5FT Beds',
                'Double / 4FT Beds',
                'Single / 3FT Beds',
                'King XL / 7FT Beds',
                'Bunk Beds',
                'Kids Beds'
            ],

            // Materials
            'pa_material' => [
                'Fabric',
                'Body touch leather',
                'Full leather',
                'Wood',
                'Metal',
                'Micro Fabric',
                'Vegan Leather',
                'Marble',
                'Solid Wood',
                'Engineered Wood',
                'Solid wood',
                'Engineered wood'
            ],

            // Colours
            'pa_colour' => [
                'Beige', 'Blue', 'Brown', 'Cream', 'Black',
                'Green', 'Grey', 'Peach', 'Pink', 'Teal',
                'Yellow', 'Red'
            ],

            // Storage Types
            'pa_storage' => [
                'With Storage',
                'Without Storage',
                'Hydraulic Storage'
            ],

            // Headboard Types
            'pa_headboard' => [
                'Solid',
                'Upholstered'
            ],

            // Upholstery
            'pa_upholstery' => [
                'Fabric',
                'Velvet',
                'Leather',
                'Vegan Leather'
            ],

            // Brands
            'pa_brand' => [
                'Reclix',
                'Century',
                'Kurlon',
                'Sleepwell',
                'DuroFlex'
            ],

            // Mechanism (for recliners)
            'pa_mechanism' => [
                'Motorised',
                'Manual'
            ],

            // Thickness (for mattresses)
            'pa_thickness' => [
                '4 inch',
                '5 inch',
                '6 inch',
                '7 inch',
                '8 inch',
                '10 inch',
                '12 inch'
            ],

            // Discount Ranges
            'pa_discount_range' => [
                '0-15%',
                '16-35%',
                'Above 35%'
            ]
        ];
    }

    /**
     * Add admin page for attributes management
     */
    public function add_attributes_admin_page() {
        add_submenu_page(
            'edit.php?post_type=product',
            'Product Filter Attributes',
            'Filter Attributes',
            'manage_options',
            'lbp-product-attributes',
            [$this, 'render_attributes_admin_page']
        );
    }

    /**
     * Render attributes admin page
     */
    public function render_attributes_admin_page() {
        // Handle form submission
        if (isset($_POST['lbp_create_attributes']) && check_admin_referer('lbp_create_attributes')) {
            $created = $this->create_attribute_terms();
            echo '<div class="notice notice-success"><p>Successfully created/updated ' . $created . ' attribute taxonomies with their terms!</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Product Filter Attributes</h1>
            <p>Manage product attributes for filtering based on AF Website specifications.</p>

            <div class="card">
                <h2>Setup Product Attributes</h2>
                <p>Click the button below to create all product attributes and their terms based on the filter specifications.</p>

                <form method="post">
                    <?php wp_nonce_field('lbp_create_attributes'); ?>
                    <p>
                        <input type="submit" name="lbp_create_attributes" class="button button-primary" value="Create/Update All Attributes">
                    </p>
                </form>
            </div>

            <div class="card">
                <h2>Available Attributes</h2>
                <p>The following attributes will be created:</p>
                <ul>
                    <?php foreach ($this->attributes_config as $slug => $config): ?>
                        <li>
                            <strong><?php echo esc_html($config['name']); ?></strong>
                            (<?php echo esc_html($slug); ?>)
                            <?php
                            $attribute_id = wc_attribute_taxonomy_id_by_name($slug);
                            echo $attribute_id ? ' <span style="color: green;">✓ Created</span>' : ' <span style="color: orange;">Not created yet</span>';
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card">
                <h2>Attribute Terms Count</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Attribute</th>
                            <th>Terms Count</th>
                            <th>Sample Terms</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $terms_config = $this->get_attribute_terms_config();
                        foreach ($terms_config as $taxonomy => $terms):
                            $sample_terms = array_slice($terms, 0, 5);
                        ?>
                        <tr>
                            <td><?php echo esc_html($taxonomy); ?></td>
                            <td><?php echo count($terms); ?></td>
                            <td><?php echo esc_html(implode(', ', $sample_terms)); ?>...</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Usage in REST API</h2>
                <p>Once created, you can filter products using these attributes:</p>
                <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto;">
# Filter by single attribute
GET /wp-json/lbp/v1/products?category=sofas&type=Corner+Sofas

# Filter by multiple attributes
GET /wp-json/lbp/v1/products?category=beds&material=Solid+wood&storage=With+Storage

# Filter with multiple values
GET /wp-json/lbp/v1/products?category=mattress&brand=Kurlon,Sleepwell&thickness=6+inch,8+inch
                </pre>
            </div>
        </div>
        <?php
    }

    /**
     * Get filter options for a category
     */
    public static function get_category_filters($category_slug) {
        $filters = [
            'sofas' => ['type', 'size', 'material', 'colour', 'discount_range'],
            'recliners' => ['type', 'size', 'material', 'colour', 'mechanism', 'discount_range'],
            'tables' => ['type', 'size', 'material'],
            'chairs' => ['type', 'size', 'material'],
            'beds' => ['type', 'size', 'material', 'storage', 'headboard', 'upholstery', 'discount_range'],
            'mattress' => ['type', 'size', 'brand', 'thickness']
        ];

        return isset($filters[$category_slug]) ? $filters[$category_slug] : [];
    }
}

// Initialize the attributes manager
new LBP_Product_Attributes();
