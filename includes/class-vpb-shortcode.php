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
                'product_id'  => 0,
                'limit'       => 10,
                'collections' => '', // Comma-separated collection slugs or IDs
            ),
            $atts,
            'vpb_configurator'
        );

        $product_id  = absint( $atts['product_id'] );
        $limit       = absint( $atts['limit'] );
        $collections = $atts['collections'];

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

        // Get collections for this product
        $product_collections = array();

        if ( ! empty( $collections ) ) {
            // Use collections from shortcode attribute
            $collection_refs = array_map( 'trim', explode( ',', $collections ) );
            foreach ( $collection_refs as $ref ) {
                if ( is_numeric( $ref ) ) {
                    $col = VPB_Collection::get_collection( absint( $ref ) );
                } else {
                    $col = VPB_Collection::get_collection_by_slug( $ref );
                }
                if ( $col && $col->active ) {
                    $product_collections[] = $col;
                }
            }
        } else {
            // Use collections assigned to product
            $product_collections = VPB_Collection::get_product_collections( $product_id );
        }

        // Get elements - either by collections or all active
        $elements_data = array();

        if ( ! empty( $product_collections ) ) {
            // Get elements from assigned collections
            foreach ( $product_collections as $collection ) {
                $collection_elements = VPB_Collection::get_elements( $collection->id );
                foreach ( $collection_elements as $el ) {
                    $elements_data[] = (array) $el;
                }
            }
        } else {
            // Fallback to all active elements
            $elements_data = VPB_Library::get_elements();
        }

        // Group elements by category
        $elements = array();
        foreach ( $elements_data as $element ) {
            $category = $element['category'];
            if ( ! isset( $elements[ $category ] ) ) {
                $elements[ $category ] = array();
            }
            $elements[ $category ][] = $element;
        }

        // Get available colors from loaded elements
        $colors = array();
        foreach ( $elements_data as $element ) {
            if ( ! empty( $element['color'] ) && ! in_array( $element['color'], $colors, true ) ) {
                $colors[] = $element['color'];
            }
        }
        sort( $colors );

        // Get support image for this product
        $support_image = get_post_meta( $product_id, '_vpb_support_image', true );

        // Start output buffering
        ob_start();

        // Include template
        include VPB_PLUGIN_DIR . 'templates/configurator-shortcode.php';

        return ob_get_clean();
    }
}
