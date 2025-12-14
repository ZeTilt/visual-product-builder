<?php
/**
 * Uninstall Visual Product Builder
 *
 * Removes all plugin data when uninstalled (not just deactivated).
 *
 * @package VisualProductBuilder
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user wants to keep data on uninstall.
$keep_data = get_option( 'vpb_keep_data_on_uninstall', false );

if ( $keep_data ) {
	return;
}

global $wpdb;

// Delete plugin options.
delete_option( 'vpb_version' );
delete_option( 'vpb_db_version' );
delete_option( 'vpb_custom_css' );
delete_option( 'vpb_keep_data_on_uninstall' );

// Delete all transients with vpb_ prefix.
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vpb_%' OR option_name LIKE '_transient_timeout_vpb_%'"
);

// Drop custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vpb_product_collections" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vpb_elements" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vpb_collections" );

// Delete post meta.
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_vpb_%'"
);

// Delete order item meta (WooCommerce orders).
if ( class_exists( 'WooCommerce' ) ) {
	$wpdb->query(
		"DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key LIKE '_vpb_%'"
	);
}

// Clean up upload directory (optional - commented out by default to preserve customer images).
/*
$upload_dir = wp_upload_dir();
$vpb_dir    = $upload_dir['basedir'] . '/vpb-elements/';

if ( is_dir( $vpb_dir ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;
	$wp_filesystem->rmdir( $vpb_dir, true );
}
*/

// Clear any cached data.
wp_cache_flush();
