<?php
/**
 * Cart Integration
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_Cart class
 */
class VPB_Cart {

    /**
     * Constructor
     */
    public function __construct() {
        // Add custom data to cart item
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );

        // Display custom data in cart
        add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );

        // Ensure unique cart items for different configurations
        add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 10, 1 );

        // Restore custom data from session
        add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
    }

    /**
     * Add custom configuration data to cart item
     *
     * @param array $cart_item_data Cart item data.
     * @param int   $product_id     Product ID.
     * @param int   $variation_id   Variation ID.
     * @return array
     */
    public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
        // Check if this is a VPB product
        if ( ! isset( $_POST['vpb_configuration'] ) ) {
            return $cart_item_data;
        }

        // Verify nonce
        if ( ! isset( $_POST['vpb_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vpb_nonce'] ) ), 'vpb_add_to_cart' ) ) {
            return $cart_item_data;
        }

        // Get and validate configuration
        $config_json = sanitize_text_field( wp_unslash( $_POST['vpb_configuration'] ) );
        $config      = json_decode( $config_json, true );

        if ( ! is_array( $config ) || empty( $config['elements'] ) ) {
            return $cart_item_data;
        }

        // Validate each element exists in database
        $validated_elements = array();
        foreach ( $config['elements'] as $element ) {
            if ( ! isset( $element['id'] ) ) {
                continue;
            }

            $db_element = VPB_Library::get_element( absint( $element['id'] ) );
            if ( $db_element ) {
                $validated_elements[] = array(
                    'id'    => $db_element['id'],
                    'name'  => $db_element['name'],
                    'color' => $db_element['color'],
                    'price' => floatval( $db_element['price'] ),
                );
            }
        }

        if ( empty( $validated_elements ) ) {
            return $cart_item_data;
        }

        // Store validated configuration
        $cart_item_data['vpb_elements']      = $validated_elements;
        $cart_item_data['vpb_configuration'] = $this->generate_config_summary( $validated_elements );

        // Store image data if provided
        if ( isset( $_POST['vpb_image_data'] ) ) {
            $image_data = sanitize_text_field( wp_unslash( $_POST['vpb_image_data'] ) );
            // Validate it's a valid base64 PNG
            if ( strpos( $image_data, 'data:image/png;base64,' ) === 0 ) {
                $cart_item_data['vpb_image_data'] = $image_data;
            }
        }

        // Generate unique key for this configuration
        $cart_item_data['unique_key'] = md5( wp_json_encode( $validated_elements ) . microtime() );

        return $cart_item_data;
    }

    /**
     * Generate human-readable configuration summary
     *
     * @param array $elements Validated elements.
     * @return string
     */
    private function generate_config_summary( $elements ) {
        $parts = array();
        foreach ( $elements as $element ) {
            $parts[] = sprintf(
                '%s (%s)',
                $element['name'],
                $element['color']
            );
        }
        return implode( ', ', $parts );
    }

    /**
     * Display custom data in cart and checkout
     *
     * @param array $item_data Item data to display.
     * @param array $cart_item Cart item.
     * @return array
     */
    public function get_item_data( $item_data, $cart_item ) {
        if ( ! isset( $cart_item['vpb_configuration'] ) ) {
            return $item_data;
        }

        $item_data[] = array(
            'key'   => __( 'Personnalisation', 'visual-product-builder' ),
            'value' => esc_html( $cart_item['vpb_configuration'] ),
        );

        // Add price breakdown
        if ( isset( $cart_item['vpb_elements'] ) ) {
            $total_elements_price = 0;
            foreach ( $cart_item['vpb_elements'] as $element ) {
                $total_elements_price += $element['price'];
            }

            $item_data[] = array(
                'key'   => __( 'Supplément personnalisation', 'visual-product-builder' ),
                'value' => wc_price( $total_elements_price ),
            );
        }

        return $item_data;
    }

    /**
     * Add cart item filter
     *
     * @param array $cart_item Cart item.
     * @return array
     */
    public function add_cart_item( $cart_item ) {
        if ( isset( $cart_item['vpb_elements'] ) ) {
            // Price is handled by VPB_Pricing class
            $cart_item['data']->vpb_elements = $cart_item['vpb_elements'];
        }
        return $cart_item;
    }

    /**
     * Restore custom data from session
     *
     * @param array $cart_item Cart item.
     * @param array $values    Session values.
     * @return array
     */
    public function get_cart_item_from_session( $cart_item, $values ) {
        if ( isset( $values['vpb_elements'] ) ) {
            $cart_item['vpb_elements']      = $values['vpb_elements'];
            $cart_item['vpb_configuration'] = $values['vpb_configuration'];
            $cart_item['data']->vpb_elements = $values['vpb_elements'];
        }

        if ( isset( $values['vpb_image_data'] ) ) {
            $cart_item['vpb_image_data'] = $values['vpb_image_data'];
        }

        return $cart_item;
    }
}
