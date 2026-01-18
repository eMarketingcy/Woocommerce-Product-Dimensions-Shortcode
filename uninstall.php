<?php
/**
 * Uninstall script for WooCommerce Product Dimensions Shortcode
 *
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It removes all plugin settings and cleans up the database.
 *
 * @package WooCommerce_Product_Dimensions
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove plugin options from database
 */
function wpds_uninstall_cleanup() {
    // List of plugin options to delete
    $options = array(
        'wpds_enable_jis_code',
        'wpds_jis_category_id',
        'wpds_jis_acf_field_key',
        'wpds_jis_ah_field_name',
        'wpds_jis_terminals_field_name',
    );

    // Delete each option
    foreach ($options as $option) {
        delete_option($option);
    }

    // For multisite installations, delete options from all sites
    if (is_multisite()) {
        global $wpdb;

        // Get all blog IDs
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);

            // Delete options for this site
            foreach ($options as $option) {
                delete_option($option);
            }

            restore_current_blog();
        }
    }
}

// Execute cleanup
wpds_uninstall_cleanup();

// Optional: Remove transients if any were used
// delete_transient('wpds_some_transient');

// Note: We do NOT delete ACF field data as it may be valuable user data
// Users can manually delete ACF fields if needed
