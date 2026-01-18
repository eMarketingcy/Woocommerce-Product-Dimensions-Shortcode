<?php
/**
 * Plugin Name: WooCommerce Product Dimensions Shortcode
 * Plugin URI:  https://emarketing.cy
 * Description: Adds a shortcode [product_dimensions] to display WooCommerce product shipping dimensions.
 * Version:     1.4
 * Author:      Omar Tamim
 * Author URI:  https://emarketing.cy
 * License:     GPL2
 * Text Domain: woocommerce-product-dimensions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ensure WooCommerce is active before executing
function wpds_check_woocommerce_active() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WooCommerce to be installed and active.');
    }
}
register_activation_hook(__FILE__, 'wpds_check_woocommerce_active');

/**
 * Function to get product dimensions
 */
function wpds_get_product_dimensions() {
    global $product;

    // If not inside WooCommerce loop, fetch product manually
    if (!is_a($product, 'WC_Product')) {
        global $post;
        $product = wc_get_product($post->ID);
    }

    if (!$product) return ''; // No product found

    // Get dimensions
    $length = $product->get_length();
    $width  = $product->get_width();
    $height = $product->get_height();

    // Display only if all dimensions exist
    if ($length && $width && $height) {
        return '<div class="product-dimensions">' . esc_html($length) . ' × ' . esc_html($width) . ' × ' . esc_html($height) . ' cm</div>';
    }

    return ''; // Return empty if no dimensions
}

/**
 * Shortcode to display product dimensions
 */
function wpds_product_dimensions_shortcode() {
    return wpds_get_product_dimensions();
}
add_shortcode('product_dimensions', 'wpds_product_dimensions_shortcode');



function calculate_jis_code($product_id) {
    // Ensure we have a valid product object
    $product = wc_get_product($product_id);

    if (!$product) {
        return '';  // If product not found, return nothing
    }

    // Check if the product belongs to the "Japanese Type" category (ID: 11710, Slug: japanese-type)
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (!in_array(11710, $product_categories)) {
        return ''; // If not in the "Japanese Type" category, return nothing
    }

    // Get product dimensions using WooCommerce functions
    $length = $product->get_length();  // Product length in cm
    $width  = $product->get_width();   // Product width in cm

    // Get custom fields (ACF and other product info)
    $performance_rank = get_field('ah', $product_id); // ACF Field for performance rank (e.g., 46)
    $terminal_position = get_field('terminals', $product_id); // ACF Field for terminal position (Left or Right)

    // Modify terminal position to be 'L' or 'R'
    if (stripos($terminal_position, 'Right') !== false) {
        $terminal_position = 'R';
    } elseif (stripos($terminal_position, 'Left') !== false) {
        $terminal_position = 'L';
    } else {
        return ''; // If terminal position is not "Left" or "Right", return nothing
    }

    // JIS code width reference with a ±3 cm tolerance
    $widths = [
        'A' => 12.7,
        'B' => 12.9,
        'D' => 17.3,
        'E' => 17.6,
        'F' => 18.2,
        'G' => 22.2,
        'H' => 27.8,
    ];

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
    return $performance_rank . $closest_letter . round($length) . $terminal_position;
}


function jis_code_shortcode() {
    // Automatically get the product ID from the current WooCommerce product in the loop
    global $product;

    // Ensure we're in the loop and have a valid product object
    if ($product) {
        $product_id = $product->get_id(); // Get the product ID automatically
    } else {
        return 'Product ID is required.';
    }

    // Calculate the JIS code for the product
    $jis_code = calculate_jis_code($product_id);

    // Return the JIS code to display on the shop page
    return '<div class="jis-code">' . esc_html($jis_code) . '</div>';
}

// Register the shortcode
add_shortcode('jis_code', 'jis_code_shortcode');

function save_jis_code_on_product_update($post_id) {
    // Check if it's a product (avoid running on other post types)
    if ('product' !== get_post_type($post_id)) {
        return;
    }

    // Prevent the function from running in a loop (when saving the meta data)
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Ensure the product ID is valid
    if (empty($post_id)) {
        return;
    }

    // Calculate the JIS code for the product
    $jis_code = calculate_jis_code($post_id);

    // Update ACF field if JIS code exists, else delete it
    if (!empty($jis_code)) {
        update_field('field_67c437ef655d2', $jis_code, $post_id);
    } else {
        delete_field('field_67c437ef655d2', $post_id); // Remove the field if no value
    }
}
add_action('save_post', 'save_jis_code_on_product_update', 20); // Priority 20 ensures ACF fields are saved first
