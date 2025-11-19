<?php
/**
 * Location-Based Products - Slider Management
 * Handles location-specific sliders/banners for headless frontends
 *
 * Features:
 * - Location-based slider assignment
 * - Schedule (start/end dates)
 * - Priority/ordering
 * - Image optimization for web
 * - REST API for headless consumption
 */

if (!defined('ABSPATH')) {
    exit;
}

class LBP_Slider_Management {

    public function __construct() {
        add_action('init', [$this, 'register_slider_post_type']);
        add_action('add_meta_boxes', [$this, 'add_slider_meta_boxes']);
        add_action('save_post_lbp_slider', [$this, 'save_slider_meta'], 10, 2);
        add_action('rest_api_init', [$this, 'register_slider_endpoints']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Register slider custom post type
     */
    public function register_slider_post_type() {
        $labels = [
            'name' => __('Sliders', 'location-based-products'),
            'singular_name' => __('Slider', 'location-based-products'),
            'menu_name' => __('Sliders', 'location-based-products'),
            'add_new' => __('Add New Slider', 'location-based-products'),
            'add_new_item' => __('Add New Slider', 'location-based-products'),
            'edit_item' => __('Edit Slider', 'location-based-products'),
            'new_item' => __('New Slider', 'location-based-products'),
            'view_item' => __('View Slider', 'location-based-products'),
            'search_items' => __('Search Sliders', 'location-based-products'),
            'not_found' => __('No sliders found', 'location-based-products'),
            'not_found_in_trash' => __('No sliders found in trash', 'location-based-products'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=lbp_location',
            'show_in_rest' => true,
            'rest_base' => 'sliders',
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 26,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-images-alt2',
        ];

        register_post_type('lbp_slider', $args);
    }

    /**
     * Add meta boxes for slider configuration
     */
    public function add_slider_meta_boxes() {
        add_meta_box(
            'lbp_slider_settings',
            __('Slider Settings', 'location-based-products'),
            [$this, 'render_slider_settings_meta_box'],
            'lbp_slider',
            'normal',
            'high'
        );

        add_meta_box(
            'lbp_slider_locations',
            __('Location Assignment', 'location-based-products'),
            [$this, 'render_slider_locations_meta_box'],
            'lbp_slider',
            'side',
            'default'
        );

        add_meta_box(
            'lbp_slider_schedule',
            __('Schedule', 'location-based-products'),
            [$this, 'render_slider_schedule_meta_box'],
            'lbp_slider',
            'side',
            'default'
        );
    }

    /**
     * Render slider settings meta box
     */
    public function render_slider_settings_meta_box($post) {
        wp_nonce_field('lbp_slider_meta_box', 'lbp_slider_meta_box_nonce');

        $button_text = get_post_meta($post->ID, '_lbp_slider_button_text', true);
        $button_link = get_post_meta($post->ID, '_lbp_slider_button_link', true);
        $button_target = get_post_meta($post->ID, '_lbp_slider_button_target', true);
        $subtitle = get_post_meta($post->ID, '_lbp_slider_subtitle', true);
        $text_color = get_post_meta($post->ID, '_lbp_slider_text_color', true) ?: '#ffffff';
        $overlay_opacity = get_post_meta($post->ID, '_lbp_slider_overlay_opacity', true) ?: '0.3';
        $text_position = get_post_meta($post->ID, '_lbp_slider_text_position', true) ?: 'center';
        $priority = get_post_meta($post->ID, '_lbp_slider_priority', true) ?: 1;
        ?>
        <table class="form-table">
            <tr>
                <th><label for="lbp_slider_subtitle"><?php _e('Subtitle', 'location-based-products'); ?></label></th>
                <td>
                    <input type="text" id="lbp_slider_subtitle" name="lbp_slider_subtitle"
                           value="<?php echo esc_attr($subtitle); ?>" class="regular-text" />
                    <p class="description"><?php _e('Optional subtitle text for the slider', 'location-based-products'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_slider_button_text"><?php _e('Button Text', 'location-based-products'); ?></label></th>
                <td>
                    <input type="text" id="lbp_slider_button_text" name="lbp_slider_button_text"
                           value="<?php echo esc_attr($button_text); ?>" class="regular-text"
                           placeholder="Shop Now" />
                </td>
            </tr>
            <tr>
                <th><label for="lbp_slider_button_link"><?php _e('Button Link', 'location-based-products'); ?></label></th>
                <td>
                    <input type="url" id="lbp_slider_button_link" name="lbp_slider_button_link"
                           value="<?php echo esc_url($button_link); ?>" class="regular-text"
                           placeholder="https://" />
                </td>
            </tr>
            <tr>
                <th><label for="lbp_slider_button_target"><?php _e('Link Target', 'location-based-products'); ?></label></th>
                <td>
                    <select id="lbp_slider_button_target" name="lbp_slider_button_target">
                        <option value="_self" <?php selected($button_target, '_self'); ?>><?php _e('Same Window', 'location-based-products'); ?></option>
                        <option value="_blank" <?php selected($button_target, '_blank'); ?>><?php _e('New Window', 'location-based-products'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_slider_text_position"><?php _e('Text Position', 'location-based-products'); ?></label></th>
                <td>
                    <select id="lbp_slider_text_position" name="lbp_slider_text_position">
                        <option value="left" <?php selected($text_position, 'left'); ?>><?php _e('Left', 'location-based-products'); ?></option>
                        <option value="center" <?php selected($text_position, 'center'); ?>><?php _e('Center', 'location-based-products'); ?></option>
                        <option value="right" <?php selected($text_position, 'right'); ?>><?php _e('Right', 'location-based-products'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_slider_text_color"><?php _e('Text Color', 'location-based-products'); ?></label></th>
                <td>
                    <input type="color" id="lbp_slider_text_color" name="lbp_slider_text_color"
                           value="<?php echo esc_attr($text_color); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="lbp_slider_overlay_opacity"><?php _e('Overlay Opacity', 'location-based-products'); ?></label></th>
                <td>
                    <input type="range" id="lbp_slider_overlay_opacity" name="lbp_slider_overlay_opacity"
                           value="<?php echo esc_attr($overlay_opacity); ?>" min="0" max="1" step="0.1" />
                    <span id="overlay_opacity_value"><?php echo esc_html($overlay_opacity); ?></span>
                    <p class="description"><?php _e('Background overlay darkness (0 = transparent, 1 = fully dark)', 'location-based-products'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="lbp_slider_priority"><?php _e('Priority', 'location-based-products'); ?></label></th>
                <td>
                    <input type="number" id="lbp_slider_priority" name="lbp_slider_priority"
                           value="<?php echo esc_attr($priority); ?>" min="1" max="100" class="small-text" />
                    <p class="description"><?php _e('Higher numbers appear first (1-100)', 'location-based-products'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render location assignment meta box
     */
    public function render_slider_locations_meta_box($post) {
        $slider_type = get_post_meta($post->ID, '_lbp_slider_type', true) ?: 'all';
        $selected_locations = get_post_meta($post->ID, '_lbp_slider_locations', true);
        if (!is_array($selected_locations)) {
            $selected_locations = [];
        }

        $locations = get_posts([
            'post_type' => 'lbp_location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        ?>
        <p>
            <label>
                <input type="radio" name="lbp_slider_type" value="all" <?php checked($slider_type, 'all'); ?> />
                <?php _e('All Locations', 'location-based-products'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="radio" name="lbp_slider_type" value="specific" <?php checked($slider_type, 'specific'); ?> />
                <?php _e('Specific Locations', 'location-based-products'); ?>
            </label>
        </p>
        <div id="specific_locations_selector" style="display: <?php echo $slider_type === 'specific' ? 'block' : 'none'; ?>; margin-left: 20px;">
            <?php foreach ($locations as $location): ?>
                <p>
                    <label>
                        <input type="checkbox" name="lbp_slider_locations[]"
                               value="<?php echo esc_attr($location->ID); ?>"
                               <?php checked(in_array($location->ID, $selected_locations)); ?> />
                        <?php echo esc_html($location->post_title); ?>
                    </label>
                </p>
            <?php endforeach; ?>
            <?php if (empty($locations)): ?>
                <p class="description"><?php _e('No locations found. Please create locations first.', 'location-based-products'); ?></p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('input[name="lbp_slider_type"]').change(function() {
                if ($(this).val() === 'specific') {
                    $('#specific_locations_selector').show();
                } else {
                    $('#specific_locations_selector').hide();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render schedule meta box
     */
    public function render_slider_schedule_meta_box($post) {
        $start_date = get_post_meta($post->ID, '_lbp_slider_start_date', true);
        $end_date = get_post_meta($post->ID, '_lbp_slider_end_date', true);
        $is_active = get_post_meta($post->ID, '_lbp_slider_is_active', true);
        ?>
        <p>
            <label for="lbp_slider_is_active">
                <input type="checkbox" id="lbp_slider_is_active" name="lbp_slider_is_active"
                       value="1" <?php checked($is_active, '1'); ?> />
                <?php _e('Active', 'location-based-products'); ?>
            </label>
        </p>
        <p>
            <label for="lbp_slider_start_date">
                <strong><?php _e('Start Date', 'location-based-products'); ?></strong>
            </label>
            <input type="datetime-local" id="lbp_slider_start_date" name="lbp_slider_start_date"
                   value="<?php echo esc_attr($start_date); ?>" style="width: 100%;" />
            <span class="description"><?php _e('Leave empty for immediate start', 'location-based-products'); ?></span>
        </p>
        <p>
            <label for="lbp_slider_end_date">
                <strong><?php _e('End Date', 'location-based-products'); ?></strong>
            </label>
            <input type="datetime-local" id="lbp_slider_end_date" name="lbp_slider_end_date"
                   value="<?php echo esc_attr($end_date); ?>" style="width: 100%;" />
            <span class="description"><?php _e('Leave empty for no expiration', 'location-based-products'); ?></span>
        </p>
        <?php
    }

    /**
     * Save slider meta data
     */
    public function save_slider_meta($post_id, $post) {
        // Check nonce
        if (!isset($_POST['lbp_slider_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['lbp_slider_meta_box_nonce'], 'lbp_slider_meta_box')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save slider settings
        $fields = [
            'lbp_slider_subtitle' => 'sanitize_text_field',
            'lbp_slider_button_text' => 'sanitize_text_field',
            'lbp_slider_button_link' => 'esc_url_raw',
            'lbp_slider_button_target' => 'sanitize_text_field',
            'lbp_slider_text_position' => 'sanitize_text_field',
            'lbp_slider_text_color' => 'sanitize_hex_color',
            'lbp_slider_overlay_opacity' => 'floatval',
            'lbp_slider_priority' => 'absint',
            'lbp_slider_type' => 'sanitize_text_field',
            'lbp_slider_start_date' => 'sanitize_text_field',
            'lbp_slider_end_date' => 'sanitize_text_field',
        ];

        foreach ($fields as $field => $sanitize_callback) {
            if (isset($_POST[$field])) {
                $value = call_user_func($sanitize_callback, $_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }

        // Save checkbox
        update_post_meta($post_id, '_lbp_slider_is_active', isset($_POST['lbp_slider_is_active']) ? '1' : '0');

        // Save selected locations
        if (isset($_POST['lbp_slider_locations']) && is_array($_POST['lbp_slider_locations'])) {
            $locations = array_map('absint', $_POST['lbp_slider_locations']);
            update_post_meta($post_id, '_lbp_slider_locations', $locations);
        } else {
            update_post_meta($post_id, '_lbp_slider_locations', []);
        }
    }

    /**
     * Register REST API endpoints for sliders
     */
    public function register_slider_endpoints() {
        $namespace = 'lbp/v1';

        // Get sliders for location
        register_rest_route($namespace, '/sliders', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sliders'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => [
                    'type' => 'integer',
                    'description' => 'Location ID to filter sliders'
                ],
                'postal_code' => [
                    'type' => 'string',
                    'description' => 'Postal code to detect location'
                ],
                'active_only' => [
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Show only active sliders'
                ],
                'limit' => [
                    'type' => 'integer',
                    'default' => 10,
                    'description' => 'Maximum number of sliders to return'
                ]
            ]
        ]);

        // Get single slider
        register_rest_route($namespace, '/sliders/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_single_slider'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer'
                ]
            ]
        ]);

        // Get active sliders (convenience endpoint)
        register_rest_route($namespace, '/sliders/active', [
            'methods' => 'GET',
            'callback' => [$this, 'get_active_sliders'],
            'permission_callback' => '__return_true',
            'args' => [
                'location_id' => [
                    'type' => 'integer'
                ]
            ]
        ]);
    }

    /**
     * Get sliders REST API callback
     */
    public function get_sliders($request) {
        $location_id = $request->get_param('location_id');
        $postal_code = $request->get_param('postal_code');
        $active_only = $request->get_param('active_only');
        $limit = $request->get_param('limit') ?: 10;

        // Determine location
        if (!$location_id && $postal_code) {
            $location = $this->find_location_by_postal_code($postal_code);
            $location_id = $location ? $location->ID : null;
        }

        // Build query args
        $args = [
            'post_type' => 'lbp_slider',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'meta_value_num',
            'meta_key' => '_lbp_slider_priority',
            'order' => 'DESC'
        ];

        $meta_query = [];

        // Filter by active status
        if ($active_only) {
            $meta_query[] = [
                'key' => '_lbp_slider_is_active',
                'value' => '1',
                'compare' => '='
            ];
        }

        // Filter by location
        if ($location_id) {
            $meta_query['relation'] = 'OR';
            $meta_query[] = [
                'key' => '_lbp_slider_type',
                'value' => 'all',
                'compare' => '='
            ];
            $meta_query[] = [
                'relation' => 'AND',
                [
                    'key' => '_lbp_slider_type',
                    'value' => 'specific',
                    'compare' => '='
                ],
                [
                    'key' => '_lbp_slider_locations',
                    'value' => serialize(strval($location_id)),
                    'compare' => 'LIKE'
                ]
            ];
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        $sliders = get_posts($args);
        $formatted_sliders = [];

        foreach ($sliders as $slider) {
            // Check schedule
            if (!$this->is_slider_scheduled($slider->ID)) {
                continue;
            }

            $formatted_sliders[] = $this->format_slider_data($slider);
        }

        return rest_ensure_response([
            'success' => true,
            'sliders' => $formatted_sliders,
            'total' => count($formatted_sliders),
            'location_id' => $location_id
        ]);
    }

    /**
     * Get single slider
     */
    public function get_single_slider($request) {
        $id = $request->get_param('id');
        $slider = get_post($id);

        if (!$slider || $slider->post_type !== 'lbp_slider') {
            return new WP_Error('slider_not_found', 'Slider not found', ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'slider' => $this->format_slider_data($slider)
        ]);
    }

    /**
     * Get active sliders
     */
    public function get_active_sliders($request) {
        $request->set_param('active_only', true);
        return $this->get_sliders($request);
    }

    /**
     * Format slider data for API response
     */
    private function format_slider_data($slider) {
        $thumbnail_id = get_post_thumbnail_id($slider->ID);
        $image_data = null;

        if ($thumbnail_id) {
            $image_data = [
                'id' => $thumbnail_id,
                'url' => wp_get_attachment_image_url($thumbnail_id, 'full'),
                'thumbnail' => wp_get_attachment_image_url($thumbnail_id, 'thumbnail'),
                'medium' => wp_get_attachment_image_url($thumbnail_id, 'medium'),
                'large' => wp_get_attachment_image_url($thumbnail_id, 'large'),
                'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
            ];
        }

        return [
            'id' => $slider->ID,
            'title' => $slider->post_title,
            'subtitle' => get_post_meta($slider->ID, '_lbp_slider_subtitle', true),
            'description' => $slider->post_content,
            'image' => $image_data,
            'button' => [
                'text' => get_post_meta($slider->ID, '_lbp_slider_button_text', true),
                'link' => get_post_meta($slider->ID, '_lbp_slider_button_link', true),
                'target' => get_post_meta($slider->ID, '_lbp_slider_button_target', true) ?: '_self',
            ],
            'style' => [
                'text_position' => get_post_meta($slider->ID, '_lbp_slider_text_position', true) ?: 'center',
                'text_color' => get_post_meta($slider->ID, '_lbp_slider_text_color', true) ?: '#ffffff',
                'overlay_opacity' => floatval(get_post_meta($slider->ID, '_lbp_slider_overlay_opacity', true) ?: 0.3),
            ],
            'priority' => intval(get_post_meta($slider->ID, '_lbp_slider_priority', true) ?: 1),
            'locations' => get_post_meta($slider->ID, '_lbp_slider_locations', true) ?: [],
            'schedule' => [
                'start_date' => get_post_meta($slider->ID, '_lbp_slider_start_date', true),
                'end_date' => get_post_meta($slider->ID, '_lbp_slider_end_date', true),
                'is_active' => get_post_meta($slider->ID, '_lbp_slider_is_active', true) === '1',
            ]
        ];
    }

    /**
     * Check if slider is currently scheduled
     */
    private function is_slider_scheduled($slider_id) {
        $start_date = get_post_meta($slider_id, '_lbp_slider_start_date', true);
        $end_date = get_post_meta($slider_id, '_lbp_slider_end_date', true);
        $current_time = current_time('timestamp');

        // Check start date
        if ($start_date) {
            $start_timestamp = strtotime($start_date);
            if ($current_time < $start_timestamp) {
                return false;
            }
        }

        // Check end date
        if ($end_date) {
            $end_timestamp = strtotime($end_date);
            if ($current_time > $end_timestamp) {
                return false;
            }
        }

        return true;
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
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;

        if ($post_type === 'lbp_slider') {
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('#lbp_slider_overlay_opacity').on('input', function() {
                    $('#overlay_opacity_value').text($(this).val());
                });
            });
            </script>
            <?php
        }
    }
}

// Initialize slider management
new LBP_Slider_Management();
