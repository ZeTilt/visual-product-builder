<?php
/**
 * Dynamic Pricing
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_Pricing class
 *
 * CRITICAL: Always recalculate price server-side.
 * Never trust client-side price calculations.
 */
class VPB_Pricing {

    /**
     * Constructor
     */
    public function __construct() {
        // Recalculate prices before cart totals
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'recalculate_price' ), 20, 1 );
    }

    /**
     * Recalculate cart item prices based on configuration
     *
     * SECURITY: This method recalculates prices from database values,
     * ignoring any price sent from the client.
     *
     * @param WC_Cart $cart Cart object.
     */
    public function recalculate_price( $cart ) {
        // Avoid running in admin (except AJAX)
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        // Avoid running multiple times
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( ! isset( $cart_item['vpb_elements'] ) || empty( $cart_item['vpb_elements'] ) ) {
                continue;
            }

            // Get base product price
            $product    = $cart_item['data'];
            $base_price = floatval( $product->get_regular_price() );

            // Calculate elements price from DATABASE (not from cart data)
            $elements_price = $this->calculate_elements_price( $cart_item['vpb_elements'] );

            // Set new price
            $new_price = $base_price + $elements_price;
            $product->set_price( $new_price );
        }
    }

    /**
     * Calculate total price for elements
     *
     * SECURITY: Fetches prices from database, not from submitted data.
     *
     * @param array $elements Elements from cart.
     * @return float
     */
    private function calculate_elements_price( $elements ) {
        $total = 0.0;

        foreach ( $elements as $element ) {
            if ( ! isset( $element['id'] ) ) {
                continue;
            }

            // Fetch current price from database
            $db_element = VPB_Library::get_element( absint( $element['id'] ) );

            if ( $db_element && $db_element['active'] ) {
                $total += floatval( $db_element['price'] );
            }
        }

        return $total;
    }

    /**
     * Get price for a specific element
     *
     * @param int $element_id Element ID.
     * @return float
     */
    public static function get_element_price( $element_id ) {
        $element = VPB_Library::get_element( $element_id );

        if ( ! $element ) {
            return 0.0;
        }

        return floatval( $element['price'] );
    }

    /**
     * Format price for display
     *
     * @param float $price Price.
     * @return string
     */
    public static function format_price( $price ) {
        return wc_price( $price );
    }
}
