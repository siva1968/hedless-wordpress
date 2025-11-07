<?php
/**
 * Theme Name: Headless WordPress Theme
 * Description: A minimal theme optimized for headless WordPress with WooCommerce
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme setup
function headless_theme_setup() {
    // Add theme support for various features
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    
    // WooCommerce support
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    
    // Register navigation menus
    register_nav_menus([
        'primary' => __('Primary Menu', 'headless-theme'),
        'footer' => __('Footer Menu', 'headless-theme'),
        'mobile' => __('Mobile Menu', 'headless-theme'),
    ]);
}
add_action('after_setup_theme', 'headless_theme_setup');

// Enqueue styles and scripts
function headless_theme_scripts() {
    // Only load minimal CSS for admin preview
    if (is_admin() || !defined('REST_REQUEST')) {
        wp_enqueue_style('headless-theme-style', get_stylesheet_uri(), [], '1.0.0');
    }
}
add_action('wp_enqueue_scripts', 'headless_theme_scripts');

// Add REST API support for custom fields
function headless_theme_rest_api_init() {
    // Add featured image URLs to posts
    register_rest_field(['post', 'page', 'product'], 'featured_image_url', [
        'get_callback' => function($object) {
            $featured_image = wp_get_attachment_image_src(get_post_thumbnail_id($object['id']), 'full');
            return $featured_image ? $featured_image[0] : null;
        },
        'schema' => [
            'description' => __('Featured image URL'),
            'type' => 'string',
        ],
    ]);
    
    // Add custom fields to REST API
    register_rest_field(['post', 'page', 'product'], 'custom_fields', [
        'get_callback' => function($object) {
            return get_post_meta($object['id']);
        },
        'schema' => [
            'description' => __('Custom fields'),
            'type' => 'object',
        ],
    ]);
}
add_action('rest_api_init', 'headless_theme_rest_api_init');

// Remove unnecessary WordPress features for headless setup
function headless_theme_cleanup() {
    // Remove WordPress version from head
    remove_action('wp_head', 'wp_generator');
    
    // Remove RSD link
    remove_action('wp_head', 'rsd_link');
    
    // Remove Windows Live Writer
    remove_action('wp_head', 'wlwmanifest_link');
    
    // Remove shortlink
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Clean up head
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
}
add_action('init', 'headless_theme_cleanup');

// Customize WooCommerce for headless
function headless_woocommerce_setup() {
    // Remove WooCommerce styles on frontend (keep for admin)
    if (!is_admin() && (defined('REST_REQUEST') && REST_REQUEST)) {
        add_filter('woocommerce_enqueue_styles', '__return_empty_array');
    }
    
    // Add product gallery images to REST API
    add_action('rest_api_init', function() {
        register_rest_field('product', 'gallery_images', [
            'get_callback' => function($object) {
                $product = wc_get_product($object['id']);
                if (!$product) return [];
                
                $gallery_images = [];
                $attachment_ids = $product->get_gallery_image_ids();
                
                foreach ($attachment_ids as $attachment_id) {
                    $image = wp_get_attachment_image_src($attachment_id, 'full');
                    if ($image) {
                        $gallery_images[] = [
                            'id' => $attachment_id,
                            'src' => $image[0],
                            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                        ];
                    }
                }
                
                return $gallery_images;
            },
            'schema' => [
                'description' => __('Product gallery images'),
                'type' => 'array',
            ],
        ]);
    });
}
add_action('after_setup_theme', 'headless_woocommerce_setup');

// Handle theme customizer for headless
function headless_theme_customize_register($wp_customize) {
    // Add API settings section
    $wp_customize->add_section('api_settings', [
        'title' => __('API Settings', 'headless-theme'),
        'priority' => 30,
    ]);
    
    // Frontend URL setting
    $wp_customize->add_setting('frontend_url', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    
    $wp_customize->add_control('frontend_url', [
        'label' => __('Frontend URL', 'headless-theme'),
        'section' => 'api_settings',
        'type' => 'url',
        'description' => __('The URL of your frontend application'),
    ]);
}
add_action('customize_register', 'headless_theme_customize_register');