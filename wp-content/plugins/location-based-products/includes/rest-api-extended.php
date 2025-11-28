<?php
/**
 * Location-Based Products - Extended REST API
 * Handles product sliders, categories, and content management
 */

if (!defined('ABSPATH')) {
    exit;
}

class LBP_REST_API_Extended {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        $namespace = 'lbp/v1';

        // ========== PRODUCT SLIDERS ==========

        // Featured products slider
        register_rest_route($namespace, '/products/featured', [
            'methods' => 'GET',
            'callback' => [$this, 'get_featured_products'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => ['type' => 'integer'],
                'per_page' => ['type' => 'integer', 'default' => 10, 'maximum' => 50],
                'page' => ['type' => 'integer', 'default' => 1]
            ]
        ]);

        // New arrivals slider
        register_rest_route($namespace, '/products/new-arrivals', [
            'methods' => 'GET',
            'callback' => [$this, 'get_new_arrivals'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => ['type' => 'integer'],
                'days' => ['type' => 'integer', 'default' => 30, 'description' => 'Products added in last N days'],
                'per_page' => ['type' => 'integer', 'default' => 10, 'maximum' => 50],
                'page' => ['type' => 'integer', 'default' => 1]
            ]
        ]);

        // Best sellers slider
        register_rest_route($namespace, '/products/best-sellers', [
            'methods' => 'GET',
            'callback' => [$this, 'get_best_sellers'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => ['type' => 'integer'],
                'per_page' => ['type' => 'integer', 'default' => 10, 'maximum' => 50],
                'page' => ['type' => 'integer', 'default' => 1]
            ]
        ]);

        // On sale products slider
        register_rest_route($namespace, '/products/on-sale', [
            'methods' => 'GET',
            'callback' => [$this, 'get_on_sale_products'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => ['type' => 'integer'],
                'per_page' => ['type' => 'integer', 'default' => 10, 'maximum' => 50],
                'page' => ['type' => 'integer', 'default' => 1]
            ]
        ]);

        // Trending products
        register_rest_route($namespace, '/products/trending', [
            'methods' => 'GET',
            'callback' => [$this, 'get_trending_products'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => ['type' => 'integer'],
                'per_page' => ['type' => 'integer', 'default' => 10, 'maximum' => 50]
            ]
        ]);

        // Related products
        register_rest_route($namespace, '/products/related/(?P<product_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_related_products'],
            'permission_callback' => '__return_true',
            'args' => [
                'product_id' => ['required' => true, 'type' => 'integer'],
                'per_page' => ['type' => 'integer', 'default' => 4, 'maximum' => 20]
            ]
        ]);

        // ========== SINGLE PRODUCT ==========

        // Get single product by ID
        register_rest_route($namespace, '/product/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_single_product'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => ['required' => true, 'type' => 'integer'],
                'location_id' => ['type' => 'integer']
            ]
        ]);

        // Get single product by slug
        register_rest_route($namespace, '/product/slug/(?P<slug>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_product_by_slug'],
            'permission_callback' => '__return_true',
            'args' => [
                'slug' => ['required' => true, 'type' => 'string'],
                'location_id' => ['type' => 'integer']
            ]
        ]);

        // ========== CATEGORIES ==========

        // Get all categories
        register_rest_route($namespace, '/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categories'],
            'permission_callback' => '__return_true',
            'args' => [
                'parent' => ['type' => 'integer', 'description' => 'Get child categories of parent'],
                'hide_empty' => ['type' => 'boolean', 'default' => false],
                'per_page' => ['type' => 'integer', 'default' => 100]
            ]
        ]);

        // Get home screen categories (12 main categories)
        register_rest_route($namespace, '/categories/home', [
            'methods' => 'GET',
            'callback' => [$this, 'get_home_categories'],
            'permission_callback' => '__return_true'
        ]);

        // Get category by slug with products
        register_rest_route($namespace, '/category/(?P<slug>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_category_with_products'],
            'permission_callback' => '__return_true',
            'args' => [
                'slug' => ['required' => true, 'type' => 'string'],
                'location_id' => ['type' => 'integer'],
                'per_page' => ['type' => 'integer', 'default' => 12, 'maximum' => 100],
                'page' => ['type' => 'integer', 'default' => 1],
                'orderby' => ['type' => 'string', 'default' => 'date'],
                'order' => ['type' => 'string', 'default' => 'desc']
            ]
        ]);

        // ========== HOME PAGE CONTENT ==========

        // Get home page sections
        register_rest_route($namespace, '/home-sections', [
            'methods' => 'GET',
            'callback' => [$this, 'get_home_sections'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => ['type' => 'integer']
            ]
        ]);

        // Get banners
        register_rest_route($namespace, '/banners', [
            'methods' => 'GET',
            'callback' => [$this, 'get_banners'],
            'permission_callback' => '__return_true',
            'args' => [
                'position' => ['type' => 'string', 'description' => 'Banner position: hero, sidebar, footer'],
                'active_only' => ['type' => 'boolean', 'default' => true]
            ]
        ]);

        // Get promotions
        register_rest_route($namespace, '/promotions', [
            'methods' => 'GET',
            'callback' => [$this, 'get_promotions'],
            'permission_callback' => '__return_true',
            'args' => [
                'active_only' => ['type' => 'boolean', 'default' => true]
            ]
        ]);
    }

    // ========== PRODUCT SLIDERS IMPLEMENTATION ==========

    public function get_featured_products($request) {
        $location_id = $request->get_param('location_id');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_featured',
                    'value' => 'yes',
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        return $this->query_and_format_products($args, $location_id, 'Featured Products');
    }

    public function get_new_arrivals($request) {
        $location_id = $request->get_param('location_id');
        $days = $request->get_param('days');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');

        $date_query = date('Y-m-d', strtotime("-{$days} days"));

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $date_query,
                    'inclusive' => true
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        return $this->query_and_format_products($args, $location_id, 'New Arrivals');
    }

    public function get_best_sellers($request) {
        $location_id = $request->get_param('location_id');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ];

        return $this->query_and_format_products($args, $location_id, 'Best Sellers');
    }

    public function get_on_sale_products($request) {
        $location_id = $request->get_param('location_id');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'post__in' => array_merge([0], wc_get_product_ids_on_sale()),
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        return $this->query_and_format_products($args, $location_id, 'On Sale Products');
    }

    public function get_trending_products($request) {
        $location_id = $request->get_param('location_id');
        $per_page = $request->get_param('per_page');

        // Trending: High views + recent sales
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'post_status' => 'publish',
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'date_query' => [
                [
                    'after' => '30 days ago',
                    'inclusive' => true
                ]
            ]
        ];

        return $this->query_and_format_products($args, $location_id, 'Trending Products');
    }

    public function get_related_products($request) {
        $product_id = $request->get_param('product_id');
        $per_page = $request->get_param('per_page');

        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('product_not_found', 'Product not found', ['status' => 404]);
        }

        $related_ids = wc_get_related_products($product_id, $per_page);

        if (empty($related_ids)) {
            return rest_ensure_response([
                'success' => true,
                'products' => [],
                'total' => 0,
                'message' => 'No related products found'
            ]);
        }

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'post_status' => 'publish',
            'post__in' => $related_ids,
            'orderby' => 'post__in'
        ];

        return $this->query_and_format_products($args, null, 'Related Products');
    }

    // ========== SINGLE PRODUCT IMPLEMENTATION ==========

    public function get_single_product($request) {
        $product_id = $request->get_param('id');
        $location_id = $request->get_param('location_id');

        $product = wc_get_product($product_id);
        if (!$product || $product->get_status() !== 'publish') {
            return new WP_Error('product_not_found', 'Product not found', ['status' => 404]);
        }

        $product_data = $this->format_product_detailed($product, $location_id);

        return rest_ensure_response([
            'success' => true,
            'product' => $product_data
        ]);
    }

    public function get_product_by_slug($request) {
        $slug = $request->get_param('slug');
        $location_id = $request->get_param('location_id');

        $args = [
            'name' => $slug,
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1
        ];

        $products = get_posts($args);
        if (empty($products)) {
            return new WP_Error('product_not_found', 'Product not found', ['status' => 404]);
        }

        $product = wc_get_product($products[0]->ID);
        $product_data = $this->format_product_detailed($product, $location_id);

        return rest_ensure_response([
            'success' => true,
            'product' => $product_data
        ]);
    }

    // ========== CATEGORIES IMPLEMENTATION ==========

    public function get_categories($request) {
        $parent = $request->get_param('parent');
        $hide_empty = $request->get_param('hide_empty');
        $per_page = $request->get_param('per_page');

        $args = [
            'taxonomy' => 'product_cat',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => $hide_empty,
            'number' => $per_page
        ];

        if ($parent !== null) {
            $args['parent'] = $parent;
        }

        $categories = get_terms($args);
        $formatted_categories = [];

        foreach ($categories as $category) {
            $formatted_categories[] = $this->format_category($category);
        }

        return rest_ensure_response([
            'success' => true,
            'categories' => $formatted_categories,
            'total' => count($formatted_categories)
        ]);
    }

    public function get_home_categories($request) {
        // 12 main categories from AF Website changes.xlsx
        $category_slugs = [
            'af-luxe',
            'sofa-sets',
            'recliners',
            'tables',
            'beds',
            'mattress',
            'dining-sets',
            'storage-units',
            'outdoor-furniture',
            'study-office',
            'bean-bags-pouffes',
            'decor'
        ];

        $categories = [];
        foreach ($category_slugs as $slug) {
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $categories[] = $this->format_category($term);
            }
        }

        return rest_ensure_response([
            'success' => true,
            'categories' => $categories,
            'total' => count($categories),
            'display_format' => '2 columns, slideable'
        ]);
    }

    public function get_category_with_products($request) {
        $slug = $request->get_param('slug');
        $location_id = $request->get_param('location_id');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');

        $term = get_term_by('slug', $slug, 'product_cat');
        if (!$term || is_wp_error($term)) {
            return new WP_Error('category_not_found', 'Category not found', ['status' => 404]);
        }

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $term->term_id
                ]
            ],
            'orderby' => $orderby,
            'order' => strtoupper($order)
        ];

        $query = new WP_Query($args);
        $products = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $products[] = $this->format_product_basic($product, $location_id);
            }
        }

        return rest_ensure_response([
            'success' => true,
            'category' => $this->format_category($term),
            'products' => $products,
            'pagination' => [
                'total' => $query->found_posts,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $query->max_num_pages
            ]
        ]);
    }

    // ========== HOME PAGE CONTENT IMPLEMENTATION ==========

    public function get_home_sections($request) {
        $location_id = $request->get_param('location_id');

        $sections = [
            [
                'id' => 'hero',
                'type' => 'banner',
                'title' => 'Welcome to Our Store',
                'enabled' => true,
                'order' => 1
            ],
            [
                'id' => 'categories',
                'type' => 'category_slider',
                'title' => 'Shop by Category',
                'columns' => 2,
                'slideable' => true,
                'enabled' => true,
                'order' => 2
            ],
            [
                'id' => 'featured',
                'type' => 'product_slider',
                'title' => 'Featured Products',
                'endpoint' => '/lbp/v1/products/featured',
                'enabled' => true,
                'order' => 3
            ],
            [
                'id' => 'new_arrivals',
                'type' => 'product_slider',
                'title' => 'New Arrivals',
                'endpoint' => '/lbp/v1/products/new-arrivals',
                'enabled' => true,
                'order' => 4
            ],
            [
                'id' => 'on_sale',
                'type' => 'product_slider',
                'title' => 'On Sale',
                'endpoint' => '/lbp/v1/products/on-sale',
                'enabled' => true,
                'order' => 5
            ],
            [
                'id' => 'best_sellers',
                'type' => 'product_slider',
                'title' => 'Best Sellers',
                'endpoint' => '/lbp/v1/products/best-sellers',
                'enabled' => true,
                'order' => 6
            ]
        ];

        return rest_ensure_response([
            'success' => true,
            'sections' => $sections
        ]);
    }

    public function get_banners($request) {
        $position = $request->get_param('position');
        $active_only = $request->get_param('active_only');

        // This would typically come from a custom post type or options table
        // For now, returning sample data structure
        $banners = [
            [
                'id' => 1,
                'title' => 'Summer Sale',
                'description' => 'Up to 50% off on selected items',
                'image' => '',
                'link' => '/shop/sale',
                'position' => 'hero',
                'order' => 1,
                'active' => true
            ]
        ];

        if ($position) {
            $banners = array_filter($banners, function($banner) use ($position) {
                return $banner['position'] === $position;
            });
        }

        if ($active_only) {
            $banners = array_filter($banners, function($banner) {
                return $banner['active'] === true;
            });
        }

        return rest_ensure_response([
            'success' => true,
            'banners' => array_values($banners)
        ]);
    }

    public function get_promotions($request) {
        $active_only = $request->get_param('active_only');

        $promotions = [
            [
                'id' => 1,
                'title' => 'Free Delivery',
                'description' => 'Free delivery on orders above ₹10,000',
                'type' => 'delivery',
                'active' => true
            ]
        ];

        if ($active_only) {
            $promotions = array_filter($promotions, function($promo) {
                return $promo['active'] === true;
            });
        }

        return rest_ensure_response([
            'success' => true,
            'promotions' => array_values($promotions)
        ]);
    }

    // ========== HELPER METHODS ==========

    private function query_and_format_products($args, $location_id, $title) {
        $query = new WP_Query($args);
        $products = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $products[] = $this->format_product_basic($product, $location_id);
            }
        }

        return rest_ensure_response([
            'success' => true,
            'title' => $title,
            'products' => $products,
            'total' => $query->found_posts,
            'pagination' => [
                'per_page' => $args['posts_per_page'],
                'current_page' => isset($args['paged']) ? $args['paged'] : 1,
                'total_pages' => $query->max_num_pages
            ]
        ]);
    }

    private function format_product_basic($product, $location_id = null) {
        $image_id = $product->get_image_id();

        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => get_permalink($product->get_id()),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'stock_status' => $product->get_stock_status(),
            'featured' => $product->is_featured(),
            'image' => $image_id ? [
                'src' => wp_get_attachment_image_url($image_id, 'medium'),
                'thumbnail' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
            ] : null,
            'average_rating' => $product->get_average_rating(),
            'rating_count' => $product->get_rating_count()
        ];
    }

    private function format_product_detailed($product, $location_id = null) {
        // Reuse the main API's format_product_for_location method
        $rest_api = new LBP_REST_API();
        $reflection = new ReflectionClass($rest_api);
        $method = $reflection->getMethod('format_product_for_location');
        $method->setAccessible(true);

        return $method->invoke($rest_api, $product, $location_id);
    }

    private function format_category($term) {
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);

        return [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'count' => $term->count,
            'parent' => $term->parent,
            'image' => $thumbnail_id ? [
                'src' => wp_get_attachment_image_url($thumbnail_id, 'medium'),
                'thumbnail' => wp_get_attachment_image_url($thumbnail_id, 'thumbnail'),
                'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true)
            ] : null,
            'link' => get_term_link($term)
        ];
    }
}

new LBP_REST_API_Extended();
