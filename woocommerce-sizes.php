<?php
/**
 * Plugin Name: WooCommerce Product Dimensions Shortcode
 * Plugin URI:  https://emarketing.cy
 * Description: Adds a shortcode [product_dimensions] to display WooCommerce product shipping dimensions and [jis_code] for Japanese battery type codes.
 * Version:     2.0.0
 * Author:      Omar Tamim
 * Author URI:  https://emarketing.cy
 * License:     GPL2
 * Text Domain: woocommerce-product-dimensions
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPDS_VERSION', '2.0.0');
define('WPDS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPDS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPDS_TEXT_DOMAIN', 'woocommerce-product-dimensions');

/**
 * Check if WooCommerce is active before executing
 */
function wpds_check_woocommerce_active() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('This plugin requires WooCommerce to be installed and active.', WPDS_TEXT_DOMAIN),
            esc_html__('Plugin Activation Error', WPDS_TEXT_DOMAIN),
            array('back_link' => true)
        );
    }
}
register_activation_hook(__FILE__, 'wpds_check_woocommerce_active');

/**
 * Check if ACF is active (for JIS code functionality)
 */
function wpds_check_acf_active() {
    return function_exists('get_field') && function_exists('update_field');
}

/**
 * Load plugin text domain for translations
 */
function wpds_load_textdomain() {
    load_plugin_textdomain(
        WPDS_TEXT_DOMAIN,
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'wpds_load_textdomain');

/**
 * Add admin notice if ACF is not active but JIS functionality is enabled
 */
function wpds_acf_admin_notice() {
    if (!wpds_check_acf_active() && get_option('wpds_enable_jis_code', false)) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php
                echo esc_html__('WooCommerce Product Dimensions: JIS code functionality requires Advanced Custom Fields (ACF) to be installed and active.', WPDS_TEXT_DOMAIN);
                ?>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'wpds_acf_admin_notice');

/**
 * Register plugin settings
 */
function wpds_register_settings() {
    register_setting('wpds_settings_group', 'wpds_enable_jis_code', array(
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));

    register_setting('wpds_settings_group', 'wpds_jis_category_id', array(
        'type' => 'integer',
        'default' => 0,
        'sanitize_callback' => 'absint'
    ));

    register_setting('wpds_settings_group', 'wpds_jis_acf_field_key', array(
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));

    register_setting('wpds_settings_group', 'wpds_jis_ah_field_name', array(
        'type' => 'string',
        'default' => 'ah',
        'sanitize_callback' => 'sanitize_text_field'
    ));

    register_setting('wpds_settings_group', 'wpds_jis_terminals_field_name', array(
        'type' => 'string',
        'default' => 'terminals',
        'sanitize_callback' => 'sanitize_text_field'
    ));
}
add_action('admin_init', 'wpds_register_settings');

/**
 * Add settings page to WooCommerce menu
 */
function wpds_add_settings_page() {
    add_submenu_page(
        'woocommerce',
        __('Product Dimensions Settings', WPDS_TEXT_DOMAIN),
        __('Dimensions Settings', WPDS_TEXT_DOMAIN),
        'manage_woocommerce',
        'wpds-settings',
        'wpds_render_settings_page'
    );
}
add_action('admin_menu', 'wpds_add_settings_page');

/**
 * Render settings page
 */
function wpds_render_settings_page() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wpds_settings_group');
            do_settings_sections('wpds_settings_group');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wpds_enable_jis_code">
                            <?php esc_html_e('Enable JIS Code Functionality', WPDS_TEXT_DOMAIN); ?>
                        </label>
                    </th>
                    <td>
                        <input type="checkbox" id="wpds_enable_jis_code" name="wpds_enable_jis_code" value="1" <?php checked(get_option('wpds_enable_jis_code', false), true); ?> />
                        <p class="description">
                            <?php esc_html_e('Enable automatic JIS code calculation for Japanese battery types. Requires ACF plugin.', WPDS_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpds_jis_category_id">
                            <?php esc_html_e('JIS Category ID', WPDS_TEXT_DOMAIN); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" id="wpds_jis_category_id" name="wpds_jis_category_id" value="<?php echo esc_attr(get_option('wpds_jis_category_id', 0)); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('Category ID for Japanese Type products (e.g., 11710)', WPDS_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpds_jis_acf_field_key">
                            <?php esc_html_e('ACF JIS Code Field Key', WPDS_TEXT_DOMAIN); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" id="wpds_jis_acf_field_key" name="wpds_jis_acf_field_key" value="<?php echo esc_attr(get_option('wpds_jis_acf_field_key', '')); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('ACF field key where JIS code will be saved (e.g., field_67c437ef655d2)', WPDS_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpds_jis_ah_field_name">
                            <?php esc_html_e('ACF AH Field Name', WPDS_TEXT_DOMAIN); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" id="wpds_jis_ah_field_name" name="wpds_jis_ah_field_name" value="<?php echo esc_attr(get_option('wpds_jis_ah_field_name', 'ah')); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('ACF field name for performance rank (Ah)', WPDS_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpds_jis_terminals_field_name">
                            <?php esc_html_e('ACF Terminals Field Name', WPDS_TEXT_DOMAIN); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" id="wpds_jis_terminals_field_name" name="wpds_jis_terminals_field_name" value="<?php echo esc_attr(get_option('wpds_jis_terminals_field_name', 'terminals')); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('ACF field name for terminal position (Left/Right)', WPDS_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Get product dimensions with proper unit from WooCommerce settings
 *
 * @param WC_Product|null $product Product object or null to get from global
 * @return string HTML output of dimensions or empty string
 */
function wpds_get_product_dimensions($product = null) {
    // If no product provided, try to get from global
    if (!is_a($product, 'WC_Product')) {
        global $product;

        // If still not a product, try from global post
        if (!is_a($product, 'WC_Product')) {
            global $post;
            if ($post && $post->ID) {
                $product = wc_get_product($post->ID);
            }
        }
    }

    if (!$product) {
        return ''; // No product found
    }

    // Get dimensions
    $length = $product->get_length();
    $width  = $product->get_width();
    $height = $product->get_height();

    // Display only if all dimensions exist
    if ($length && $width && $height) {
        // Get dimension unit from WooCommerce settings
        $dimension_unit = get_option('woocommerce_dimension_unit');

        return sprintf(
            '<div class="product-dimensions">%s × %s × %s %s</div>',
            esc_html($length),
            esc_html($width),
            esc_html($height),
            esc_html($dimension_unit)
        );
    }

    return ''; // Return empty if no dimensions
}

/**
 * Shortcode to display product dimensions
 * Usage: [product_dimensions]
 */
function wpds_product_dimensions_shortcode($atts) {
    $atts = shortcode_atts(array(
        'product_id' => 0
    ), $atts, 'product_dimensions');

    $product = null;
    if ($atts['product_id']) {
        $product = wc_get_product(absint($atts['product_id']));
    }

    return wpds_get_product_dimensions($product);
}
add_shortcode('product_dimensions', 'wpds_product_dimensions_shortcode');

/**
 * Calculate JIS code based on product dimensions and custom fields
 *
 * @param int $product_id Product ID
 * @return string JIS code or empty string
 */
function wpds_calculate_jis_code($product_id) {
    // Check if JIS functionality is enabled
    if (!get_option('wpds_enable_jis_code', false)) {
        return '';
    }

    // Check if ACF is available
    if (!wpds_check_acf_active()) {
        return '';
    }

    // Validate product ID
    if (!$product_id || !is_numeric($product_id)) {
        return '';
    }

    // Ensure we have a valid product object
    $product = wc_get_product($product_id);

    if (!$product) {
        return '';  // If product not found, return nothing
    }

    // Get configured category ID
    $jis_category_id = absint(get_option('wpds_jis_category_id', 0));

    if (!$jis_category_id) {
        return ''; // Category not configured
    }

    // Check if the product belongs to the configured category
    $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    if (is_wp_error($product_categories) || !in_array($jis_category_id, $product_categories)) {
        return ''; // If not in the configured category, return nothing
    }

    // Get product dimensions using WooCommerce functions
    $length = $product->get_length();  // Product length
    $width  = $product->get_width();   // Product width

    // Validate dimensions are numeric
    if (!is_numeric($length) || !is_numeric($width) || $length <= 0 || $width <= 0) {
        return '';
    }

    // Get configured ACF field names
    $ah_field_name = get_option('wpds_jis_ah_field_name', 'ah');
    $terminals_field_name = get_option('wpds_jis_terminals_field_name', 'terminals');

    // Get custom fields (ACF)
    $performance_rank = get_field($ah_field_name, $product_id);
    $terminal_position = get_field($terminals_field_name, $product_id);

    // Validate performance rank
    if (empty($performance_rank) || !is_numeric($performance_rank)) {
        return '';
    }

    // Validate and modify terminal position to be 'L' or 'R'
    if (empty($terminal_position)) {
        return '';
    }

    if (stripos($terminal_position, 'Right') !== false) {
        $terminal_position = 'R';
    } elseif (stripos($terminal_position, 'Left') !== false) {
        $terminal_position = 'L';
    } else {
        return ''; // If terminal position is not "Left" or "Right", return nothing
    }

    // JIS code width reference with a ±3 cm tolerance
    $widths = array(
        'A' => 12.7,
        'B' => 12.9,
        'D' => 17.3,
        'E' => 17.6,
        'F' => 18.2,
        'G' => 22.2,
        'H' => 27.8,
    );

    // Find the closest matching JIS width within ±3 cm
    $closest_letter = '';
    foreach ($widths as $letter => $width_value) {
        if (abs($width - $width_value) <= 3) {
            $closest_letter = $letter;
            break; // Stop at the first match
        }
    }

    // If no width match is found, return nothing
    if (!$closest_letter) {
        return '';
    }

    // Construct and return the JIS code
    return sanitize_text_field($performance_rank . $closest_letter . round($length) . $terminal_position);
}

/**
 * Shortcode to display JIS code
 * Usage: [jis_code] or [jis_code product_id="123"]
 */
function wpds_jis_code_shortcode($atts) {
    $atts = shortcode_atts(array(
        'product_id' => 0
    ), $atts, 'jis_code');

    // Get product ID from attribute or from global product
    $product_id = absint($atts['product_id']);

    if (!$product_id) {
        global $product;

        // Ensure we're in the loop and have a valid product object
        if ($product) {
            $product_id = $product->get_id();
        } else {
            return '<div class="jis-code">' . esc_html__('Product ID is required.', WPDS_TEXT_DOMAIN) . '</div>';
        }
    }

    // Calculate the JIS code for the product
    $jis_code = wpds_calculate_jis_code($product_id);

    // Return the JIS code to display
    return '<div class="jis-code">' . esc_html($jis_code) . '</div>';
}

// Register the shortcode
add_shortcode('jis_code', 'wpds_jis_code_shortcode');

/**
 * Save JIS code to ACF field when product is updated
 *
 * @param int $post_id Post ID
 */
function wpds_save_jis_code_on_product_update($post_id) {
    // Check if JIS functionality is enabled
    if (!get_option('wpds_enable_jis_code', false)) {
        return;
    }

    // Check if ACF is available
    if (!wpds_check_acf_active()) {
        return;
    }

    // Check if it's a product (avoid running on other post types)
    if ('product' !== get_post_type($post_id)) {
        return;
    }

    // Prevent the function from running during autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user capabilities
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Ensure the product ID is valid
    if (empty($post_id)) {
        return;
    }

    // Get configured ACF field key
    $jis_acf_field_key = get_option('wpds_jis_acf_field_key', '');

    if (empty($jis_acf_field_key)) {
        return; // Field key not configured
    }

    // Calculate the JIS code for the product
    $jis_code = wpds_calculate_jis_code($post_id);

    // Update ACF field if JIS code exists, else delete it
    if (!empty($jis_code)) {
        update_field($jis_acf_field_key, $jis_code, $post_id);
    } else {
        delete_field($jis_acf_field_key, $post_id); // Remove the field if no value
    }
}
add_action('save_post', 'wpds_save_jis_code_on_product_update', 20); // Priority 20 ensures ACF fields are saved first
