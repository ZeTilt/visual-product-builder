<?php
/**
 * Plugin Name: Visual Product Builder - Product Customizer for WooCommerce
 * Plugin URI: https://alre-web.bzh/visual-product-builder
 * Description: WooCommerce add-on for visual product customization with linear element placement.
 * Version: 1.0.0
 * Author: Alré Web
 * Author URI: https://alre-web.bzh
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: visual-product-builder
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'VPB_VERSION', '1.0.0' );
define( 'VPB_DB_VERSION', '3' );
define( 'VPB_PLUGIN_FILE', __FILE__ );
define( 'VPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VPB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'VPB_PRICING_URL', 'https://alre-web.lemonsqueezy.com/' );

/**
 * Allow data: protocol for base64 images in wp_kses
 *
 * @param array $protocols Allowed protocols.
 * @return array
 */
function vpb_allow_data_protocol( $protocols ) {
	$protocols[] = 'data';
	return $protocols;
}
add_filter( 'kses_allowed_protocols', 'vpb_allow_data_protocol' );

/**
 * Get license instance
 *
 * @return VPB_License
 */
function vpb_license() {
	return VPB_License::instance();
}

/**
 * Check if user has PRO license or higher
 *
 * @return bool
 */
function vpb_is_pro() {
	return vpb_license()->is_pro();
}

/**
 * Check if user has BUSINESS license
 *
 * @return bool
 */
function vpb_is_business() {
	return vpb_license()->is_business();
}

/**
 * Check if user is on FREE plan
 *
 * @return bool
 */
function vpb_is_free() {
	return vpb_license()->is_free();
}

/**
 * Get current plan name
 *
 * @return string
 */
function vpb_get_plan_name() {
	return vpb_license()->get_plan();
}

/**
 * Get plan limits
 *
 * @return array
 */
function vpb_get_plan_limits() {
	return vpb_license()->get_limits();
}

/**
 * Check if a feature is available for current plan
 *
 * @param string $feature Feature name.
 * @return bool
 */
function vpb_can_use_feature( $feature ) {
	return vpb_license()->can_use( $feature );
}

/**
 * Check if WooCommerce is active
 */
function vpb_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'vpb_woocommerce_missing_notice' );
		return false;
	}
	return true;
}

/**
 * WooCommerce missing notice
 */
function vpb_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Visual Product Builder requires WooCommerce to be installed and activated.', 'visual-product-builder' ); ?></p>
	</div>
	<?php
}

/**
 * Initialize the plugin
 */
function vpb_init() {
	if ( ! vpb_check_woocommerce() ) {
		return;
	}

	// Load license class first (needed by other classes).
	require_once VPB_PLUGIN_DIR . 'includes/class-vpb-license.php';

	// Initialize license system.
	vpb_license();

	// Load plugin classes.
	require_once VPB_PLUGIN_DIR . 'includes/class-vpb-library.php';
	require_once VPB_PLUGIN_DIR . 'includes/class-vpb-cart.php';
	require_once VPB_PLUGIN_DIR . 'includes/class-vpb-order.php';
	require_once VPB_PLUGIN_DIR . 'includes/class-vpb-pricing.php';
	require_once VPB_PLUGIN_DIR . 'includes/class-vpb-shortcode.php';
	require_once VPB_PLUGIN_DIR . 'includes/class-vpb-sample-data.php';
	require_once VPB_PLUGIN_DIR . 'includes/class-vpb-collection.php';
	require_once VPB_PLUGIN_DIR . 'includes/class-vpb-image-optimizer.php';

	// Load admin classes.
	if ( is_admin() ) {
		require_once VPB_PLUGIN_DIR . 'admin/class-vpb-admin.php';
		new VPB_Admin();
	}

	// Initialize components.
	new VPB_Library();
	new VPB_Cart();
	new VPB_Order();
	new VPB_Pricing();
	new VPB_Shortcode();
	new VPB_Image_Optimizer();

	// Check for database updates.
	vpb_maybe_update_db();
}
add_action( 'plugins_loaded', 'vpb_init' );

/**
 * Activation hook
 */
function vpb_activate() {
	// Create custom tables if needed.
	vpb_create_tables();

	// Set default options.
	add_option( 'vpb_version', VPB_VERSION );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'vpb_activate' );

/**
 * Deactivation hook
 */
function vpb_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'vpb_deactivate' );

/**
 * Uninstall cleanup
 */
function vpb_uninstall_cleanup() {
	// Check if user wants to keep data on uninstall.
	$keep_data = get_option( 'vpb_keep_data_on_uninstall', false );

	if ( $keep_data ) {
		return;
	}

	global $wpdb;

	// Clean up license data.
	if ( class_exists( 'VPB_License' ) ) {
		VPB_License::uninstall();
	}

	// Delete plugin options.
	delete_option( 'vpb_version' );
	delete_option( 'vpb_db_version' );
	delete_option( 'vpb_custom_css' );
	delete_option( 'vpb_keep_data_on_uninstall' );

	// Delete all transients with vpb_ prefix.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vpb_%' OR option_name LIKE '_transient_timeout_vpb_%'"
	);

	// Drop custom tables.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vpb_product_collections" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vpb_elements" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vpb_collections" );

	// Delete post meta.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_vpb_%'"
	);

	// Delete order item meta (WooCommerce orders).
	if ( class_exists( 'WooCommerce' ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key LIKE '_vpb_%'"
		);
	}

	// Clear any cached data.
	wp_cache_flush();
}

/**
 * Create custom database tables
 */
function vpb_create_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// Elements library table.
	$table_elements = $wpdb->prefix . 'vpb_elements';

	$sql_elements = "CREATE TABLE $table_elements (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		slug varchar(100) NOT NULL,
		category varchar(50) NOT NULL DEFAULT 'letter',
		svg_file varchar(255) NOT NULL,
		color varchar(50) NOT NULL DEFAULT 'default',
		color_hex varchar(7) DEFAULT '#4F9ED9',
		collection_id bigint(20) unsigned DEFAULT NULL,
		price decimal(10,2) NOT NULL DEFAULT 0.00,
		sort_order int(11) NOT NULL DEFAULT 0,
		active tinyint(1) NOT NULL DEFAULT 1,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY category (category),
		KEY color (color),
		KEY collection_id (collection_id),
		KEY active (active)
	) $charset_collate;";

	// Collections table.
	$table_collections = $wpdb->prefix . 'vpb_collections';

	$sql_collections = "CREATE TABLE $table_collections (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		slug varchar(100) NOT NULL,
		description text DEFAULT NULL,
		color_hex varchar(7) DEFAULT '#4F9ED9',
		thumbnail_url varchar(255) DEFAULT NULL,
		is_sample tinyint(1) NOT NULL DEFAULT 0,
		sort_order int(11) NOT NULL DEFAULT 0,
		active tinyint(1) NOT NULL DEFAULT 1,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY slug (slug),
		KEY is_sample (is_sample)
	) $charset_collate;";

	// Product-Collections relationship table (many-to-many).
	$table_product_collections = $wpdb->prefix . 'vpb_product_collections';

	$sql_product_collections = "CREATE TABLE $table_product_collections (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		product_id bigint(20) unsigned NOT NULL,
		collection_id bigint(20) unsigned NOT NULL,
		sort_order int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		UNIQUE KEY product_collection (product_id, collection_id),
		KEY product_id (product_id),
		KEY collection_id (collection_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_elements );
	dbDelta( $sql_collections );
	dbDelta( $sql_product_collections );

	update_option( 'vpb_db_version', VPB_DB_VERSION );
}

/**
 * Check if database needs updating
 */
function vpb_maybe_update_db() {
	$installed_db_version = get_option( 'vpb_db_version', '1' );

	if ( version_compare( $installed_db_version, VPB_DB_VERSION, '<' ) ) {
		vpb_create_tables();
	}
}

/**
 * Register custom image size for elements
 */
function vpb_register_image_sizes() {
	add_image_size( 'vpb-element', 100, 100, false );
}
add_action( 'after_setup_theme', 'vpb_register_image_sizes' );

/**
 * Add VPB element size to media library options
 *
 * @param array $sizes Existing image sizes.
 * @return array
 */
function vpb_add_image_size_names( $sizes ) {
	return array_merge( $sizes, array(
		'vpb-element' => 'VPB Element (100x100)',
	) );
}
add_filter( 'image_size_names_choose', 'vpb_add_image_size_names' );

/**
 * Enqueue frontend assets
 */
function vpb_enqueue_assets() {
	$post = get_post();
	if ( ! is_product() && ( ! $post || ! has_shortcode( $post->post_content ?? '', 'vpb_configurator' ) ) ) {
		return;
	}

	wp_enqueue_style(
		'vpb-configurator',
		VPB_PLUGIN_URL . 'assets/css/configurator.css',
		array(),
		VPB_VERSION
	);

	// Add custom CSS from settings (PRO feature).
	if ( vpb_can_use_feature( 'custom_css' ) ) {
		$custom_css = get_option( 'vpb_custom_css', '' );
		if ( ! empty( $custom_css ) ) {
			wp_add_inline_style( 'vpb-configurator', $custom_css );
		}
	}

	wp_enqueue_script(
		'vpb-configurator',
		VPB_PLUGIN_URL . 'assets/js/configurator.js',
		array(),
		VPB_VERSION,
		true
	);

	wp_localize_script( 'vpb-configurator', 'vpbData', array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'vpb_nonce' ),
		'pluginUrl' => VPB_PLUGIN_URL,
		'features'  => array(
			'dragDrop'    => vpb_can_use_feature( 'drag_drop' ),
			'pngHdExport' => vpb_can_use_feature( 'png_hd_export' ),
		),
		'plan'      => vpb_get_plan_name(),
		'isFree'    => vpb_is_free(),
		'i18n'      => array(
			'addedToCart'    => __( 'Added to cart', 'visual-product-builder' ),
			'adding'         => __( 'Adding...', 'visual-product-builder' ),
			'undone'         => __( 'Action undone', 'visual-product-builder' ),
			'limitReached'   => __( 'Limit reached!', 'visual-product-builder' ),
			'confirmReset'   => __( 'Do you really want to start over?', 'visual-product-builder' ),
			'elementAdded'   => __( 'Element added!', 'visual-product-builder' ),
			'cancel'         => __( 'Cancel', 'visual-product-builder' ),
			'confirm'        => __( 'Confirm', 'visual-product-builder' ),
			'oneElementLeft' => __( 'Only one element left', 'visual-product-builder' ),
			'dragDropPro'    => __( 'Drag & drop is a PRO feature', 'visual-product-builder' ),
			'loadedFromCart' => __( 'Design loaded from cart', 'visual-product-builder' ),
			'cartUpdated'    => __( 'Cart updated', 'visual-product-builder' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'vpb_enqueue_assets' );

/**
 * Add settings link on plugins page
 *
 * @param array $links Plugin action links.
 * @return array
 */
function vpb_plugin_action_links( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=vpb-settings' ) . '">' . __( 'Settings', 'visual-product-builder' ) . '</a>';
	array_unshift( $links, $settings_link );

	if ( vpb_is_free() ) {
		$upgrade_link = '<a href="https://alre-web.bzh/visual-product-builder#pricing" target="_blank" style="color:#FF6B35;font-weight:bold;">' . __( 'Go PRO', 'visual-product-builder' ) . '</a>';
		$links[] = $upgrade_link;
	}

	return $links;
}
add_filter( 'plugin_action_links_' . VPB_PLUGIN_BASENAME, 'vpb_plugin_action_links' );

/**
 * Declare HPOS compatibility
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
