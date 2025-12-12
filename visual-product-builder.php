<?php
/**
 * Plugin Name: Visual Product Builder
 * Plugin URI: https://github.com/your-repo/visual-product-builder
 * Description: WooCommerce add-on for visual product customization with linear element placement.
 * Version: 0.1.0
 * Author: ZeTilt
 * Author URI: https://zetilt.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: visual-product-builder
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants
define( 'VPB_VERSION', '0.1.0' );
define( 'VPB_PLUGIN_FILE', __FILE__ );
define( 'VPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VPB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

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
        <p>Visual Product Builder nécessite que WooCommerce soit installé et activé.</p>
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

    // Load plugin classes
    require_once VPB_PLUGIN_DIR . 'includes/class-vpb-library.php';
    require_once VPB_PLUGIN_DIR . 'includes/class-vpb-cart.php';
    require_once VPB_PLUGIN_DIR . 'includes/class-vpb-order.php';
    require_once VPB_PLUGIN_DIR . 'includes/class-vpb-pricing.php';
    require_once VPB_PLUGIN_DIR . 'includes/class-vpb-shortcode.php';
    require_once VPB_PLUGIN_DIR . 'includes/class-vpb-sample-data.php';

    // Load admin classes
    if ( is_admin() ) {
        require_once VPB_PLUGIN_DIR . 'admin/class-vpb-admin.php';
        new VPB_Admin();
    }

    // Initialize components
    new VPB_Library();
    new VPB_Cart();
    new VPB_Order();
    new VPB_Pricing();
    new VPB_Shortcode();
}
add_action( 'plugins_loaded', 'vpb_init' );

/**
 * Activation hook
 */
function vpb_activate() {
    // Create custom tables if needed
    vpb_create_tables();

    // Set default options
    add_option( 'vpb_version', VPB_VERSION );

    // Flush rewrite rules
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
 * Create custom database tables
 */
function vpb_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Elements library table
    $table_elements = $wpdb->prefix . 'vpb_elements';

    $sql = "CREATE TABLE $table_elements (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        category varchar(50) NOT NULL DEFAULT 'letter',
        svg_file varchar(255) NOT NULL,
        color varchar(50) NOT NULL DEFAULT 'default',
        price decimal(10,2) NOT NULL DEFAULT 0.00,
        sort_order int(11) NOT NULL DEFAULT 0,
        active tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY category (category),
        KEY color (color),
        KEY active (active)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
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
        'vpb-element' => 'VPB Élément (100x100)',
    ) );
}
add_filter( 'image_size_names_choose', 'vpb_add_image_size_names' );

/**
 * Enqueue frontend assets
 */
function vpb_enqueue_assets() {
    if ( ! is_product() && ! has_shortcode( get_post()->post_content ?? '', 'vpb_configurator' ) ) {
        return;
    }

    wp_enqueue_style(
        'vpb-configurator',
        VPB_PLUGIN_URL . 'assets/css/configurator.css',
        array(),
        VPB_VERSION
    );

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
        'i18n'      => array(
            'addedToCart'    => 'Ajouté au panier',
            'undone'         => 'Action annulée',
            'limitReached'   => 'Limite atteinte !',
            'confirmReset'   => 'Voulez-vous vraiment recommencer ?',
            'elementAdded'   => 'Élément ajouté !',
        ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'vpb_enqueue_assets' );

/**
 * Declare HPOS compatibility
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
