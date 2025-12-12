<?php
/**
 * Shortcode for Product Configurator
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_Shortcode class
 */
class VPB_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode( 'vpb_configurator', array( $this, 'render_configurator' ) );
    }

    /**
     * Render the configurator shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_configurator( $atts ) {
        $atts = shortcode_atts(
            array(
                'product_id' => 0,
                'limit'      => 10,
            ),
            $atts,
            'vpb_configurator'
        );

        $product_id = absint( $atts['product_id'] );
        $limit      = absint( $atts['limit'] );

        // If no product ID, try to get from current product page
        if ( ! $product_id && is_product() ) {
            global $product;
            if ( $product ) {
                $product_id = $product->get_id();
            }
        }

        if ( ! $product_id ) {
            return '<p class="vpb-error">' . esc_html__( 'No product specified.', 'visual-product-builder' ) . '</p>';
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return '<p class="vpb-error">' . esc_html__( 'Product not found.', 'visual-product-builder' ) . '</p>';
        }

        // Get elements
        $elements = VPB_Library::get_elements_grouped();
        $colors   = VPB_Library::get_available_colors();

        // Start output buffering
        ob_start();

        // Include template
        include VPB_PLUGIN_DIR . 'templates/configurator-shortcode.php';

        return ob_get_clean();
    }
}
