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

        // Display preview image after cart item name
        add_action( 'woocommerce_after_cart_item_name', array( $this, 'display_cart_preview' ), 10, 2 );

        // Display preview in checkout
        add_filter( 'woocommerce_checkout_cart_item_quantity', array( $this, 'add_preview_to_checkout' ), 10, 3 );

        // Add edit link and cart_item_key to product permalink in cart
        add_filter( 'woocommerce_cart_item_permalink', array( $this, 'add_edit_param_to_permalink' ), 10, 3 );

        // Ensure unique cart items for different configurations
        add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 10, 1 );

        // Restore custom data from session
        add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );

        // AJAX handler to get cart item configuration
        add_action( 'wp_ajax_vpb_get_cart_config', array( $this, 'ajax_get_cart_config' ) );
        add_action( 'wp_ajax_nopriv_vpb_get_cart_config', array( $this, 'ajax_get_cart_config' ) );

        // Handle cart item update (intercept before WooCommerce add-to-cart)
        add_action( 'wp_loaded', array( $this, 'handle_cart_item_update' ), 15 );
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
                $svg_file = $db_element['svg_file'] ?? '';
                $is_svg   = ! empty( $svg_file ) && strtolower( pathinfo( $svg_file, PATHINFO_EXTENSION ) ) === 'svg';

                $validated_elements[] = array(
                    'id'       => $db_element['id'],
                    'name'     => $db_element['name'],
                    'color'    => $db_element['color'],
                    'colorHex' => $db_element['color_hex'] ?? '#4F9ED9',
                    'svg'      => $svg_file,
                    'isSvg'    => $is_svg,
                    // IMPORTANT: Freeze price at add-to-cart time to prevent race conditions.
                    // If admin changes prices later, customer still pays what they saw.
                    // See VPB_Pricing::calculate_elements_price() for usage.
                    'price'    => floatval( $db_element['price'] ),
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
            // Note: We use wp_unslash only, NOT sanitize_text_field() which would corrupt base64 data.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Base64 data cannot be sanitized without corruption. Validation is done instead.
            $image_data = wp_unslash( $_POST['vpb_image_data'] );
            // Validate it's a valid base64 PNG
            if ( is_string( $image_data ) && strpos( $image_data, 'data:image/png;base64,' ) === 0 ) {
                // Additional validation: ensure the base64 part contains only valid characters
                $base64_part = substr( $image_data, strlen( 'data:image/png;base64,' ) );
                if ( preg_match( '/^[A-Za-z0-9+\/=]+$/', $base64_part ) ) {
                    $cart_item_data['vpb_image_data'] = $image_data;
                }
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

        // Show text summary only (image is added via woocommerce_cart_item_name filter)
        $item_data[] = array(
            'key'   => __( 'Personnalisation', 'visual-product-builder' ),
            'value' => $cart_item['vpb_configuration'],
        );

        // Add price breakdown
        if ( isset( $cart_item['vpb_elements'] ) ) {
            $total_elements_price = 0;
            foreach ( $cart_item['vpb_elements'] as $element ) {
                $total_elements_price += $element['price'];
            }

            if ( $total_elements_price > 0 ) {
                $item_data[] = array(
                    'key'   => __( 'Supplément personnalisation', 'visual-product-builder' ),
                    'value' => wp_kses_post( wc_price( $total_elements_price ) ),
                );
            }
        }

        return $item_data;
    }

    /**
     * Display preview image after cart item name (cart page)
     *
     * @param array  $cart_item     Cart item data.
     * @param string $cart_item_key Cart item key.
     */
    public function display_cart_preview( $cart_item, $cart_item_key ) {
        if ( ! isset( $cart_item['vpb_image_data'] ) || empty( $cart_item['vpb_image_data'] ) ) {
            return;
        }

        echo '<div class="vpb-cart-preview-wrapper" style="margin-top: 10px; clear: both;">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Base64 data URL is validated on input
        echo '<img src="' . $cart_item['vpb_image_data'] . '" ';
        echo 'alt="' . esc_attr__( 'Aperçu personnalisation', 'visual-product-builder' ) . '" ';
        echo 'class="vpb-cart-preview" ';
        echo 'style="max-width: 200px; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        echo '</div>';
    }

    /**
     * Add preview to checkout page
     *
     * @param string $quantity      Quantity HTML.
     * @param array  $cart_item     Cart item data.
     * @param string $cart_item_key Cart item key.
     * @return string
     */
    public function add_preview_to_checkout( $quantity, $cart_item, $cart_item_key ) {
        if ( ! isset( $cart_item['vpb_image_data'] ) || empty( $cart_item['vpb_image_data'] ) ) {
            return $quantity;
        }

        $preview_html = '<div class="vpb-checkout-preview" style="margin-top: 8px;">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Base64 data URL is validated on input
        $preview_html .= '<img src="' . $cart_item['vpb_image_data'] . '" ';
        $preview_html .= 'alt="' . esc_attr__( 'Aperçu', 'visual-product-builder' ) . '" ';
        $preview_html .= 'style="max-width: 150px; height: auto; border-radius: 6px;">';
        $preview_html .= '</div>';

        return $quantity . $preview_html;
    }

    /**
     * Handle cart item update when editing from cart
     *
     * Intercepts the form submission when vpb_edit_cart_key is present
     * and updates the existing cart item instead of adding a new one.
     */
    public function handle_cart_item_update() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified below
        if ( empty( $_POST['vpb_edit_cart_key'] ) ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_POST['vpb_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vpb_nonce'] ) ), 'vpb_add_to_cart' ) ) {
            return;
        }

        $cart_item_key = sanitize_text_field( wp_unslash( $_POST['vpb_edit_cart_key'] ) );
        $cart          = WC()->cart;

        if ( ! $cart ) {
            return;
        }

        $cart_item = $cart->get_cart_item( $cart_item_key );
        if ( ! $cart_item ) {
            wc_add_notice( __( 'Cart item not found.', 'visual-product-builder' ), 'error' );
            return;
        }

        // Get new configuration
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON to be validated
        $config_json = isset( $_POST['vpb_configuration'] ) ? wp_unslash( $_POST['vpb_configuration'] ) : '';
        $config      = json_decode( $config_json, true );

        // Validate structure - JSON is {"elements": [...]}
        if ( ! is_array( $config ) || ! isset( $config['elements'] ) || ! is_array( $config['elements'] ) ) {
            wc_add_notice( __( 'Invalid configuration.', 'visual-product-builder' ), 'error' );
            return;
        }

        // Validate each element against database (SECURITY: never trust client data for prices)
        $validated_elements = array();
        foreach ( $config['elements'] as $element ) {
            if ( ! isset( $element['id'] ) ) {
                continue;
            }

            // Re-fetch from DB to ensure integrity
            $db_element = VPB_Library::get_element( absint( $element['id'] ) );
            if ( $db_element ) {
                $svg_file = $db_element['svg_file'] ?? '';
                $is_svg   = ! empty( $svg_file ) && strtolower( pathinfo( $svg_file, PATHINFO_EXTENSION ) ) === 'svg';

                $validated_elements[] = array(
                    'id'       => $db_element['id'],
                    'name'     => $db_element['name'],
                    'color'    => $db_element['color'],
                    'colorHex' => $db_element['color_hex'] ?? '#4F9ED9',
                    'svg'      => $svg_file,
                    'isSvg'    => $is_svg,
                    'price'    => floatval( $db_element['price'] ),
                );
            }
        }

        if ( empty( $validated_elements ) ) {
            wc_add_notice( __( 'No valid elements in configuration.', 'visual-product-builder' ), 'error' );
            return;
        }

        // Update cart item data
        $cart->cart_contents[ $cart_item_key ]['vpb_elements']      = $validated_elements;
        $cart->cart_contents[ $cart_item_key ]['vpb_configuration'] = $this->generate_config_summary( $validated_elements );

        // Update image data if provided
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Base64 image data
        $image_data = isset( $_POST['vpb_image_data'] ) ? wp_unslash( $_POST['vpb_image_data'] ) : '';
        if ( ! empty( $image_data ) && strpos( $image_data, 'data:image/png;base64,' ) === 0 ) {
            $cart->cart_contents[ $cart_item_key ]['vpb_image_data'] = $image_data;
        }

        // Trigger cart update to recalculate prices
        $cart->set_session();

        // Add success notice
        wc_add_notice( __( 'Your design has been updated.', 'visual-product-builder' ), 'success' );

        // Redirect to cart page (prevent WooCommerce from adding new item)
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }

    /**
     * Add edit parameter to product permalink in cart for VPB items
     *
     * @param string $permalink   Product permalink.
     * @param array  $cart_item   Cart item data.
     * @param string $cart_item_key Cart item key.
     * @return string
     */
    public function add_edit_param_to_permalink( $permalink, $cart_item, $cart_item_key ) {
        if ( ! isset( $cart_item['vpb_elements'] ) || empty( $cart_item['vpb_elements'] ) ) {
            return $permalink;
        }

        // Add cart_item_key to permalink so configurator can load this design
        return add_query_arg( 'vpb_edit', $cart_item_key, $permalink );
    }

    /**
     * AJAX handler to get cart item configuration
     */
    public function ajax_get_cart_config() {
        $cart_item_key = isset( $_GET['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_GET['cart_item_key'] ) ) : '';

        if ( empty( $cart_item_key ) ) {
            wp_send_json_error( array( 'message' => 'Missing cart item key' ) );
        }

        $cart = WC()->cart;
        if ( ! $cart ) {
            wp_send_json_error( array( 'message' => 'Cart not available' ) );
        }

        $cart_item = $cart->get_cart_item( $cart_item_key );
        if ( ! $cart_item || ! isset( $cart_item['vpb_elements'] ) ) {
            wp_send_json_error( array( 'message' => 'Cart item not found or has no configuration' ) );
        }

        // Enrich elements with svg/colorHex/isSvg if missing (backward compatibility with old cart items)
        $elements = array();
        foreach ( $cart_item['vpb_elements'] as $element ) {
            if ( empty( $element['svg'] ) && ! empty( $element['id'] ) ) {
                // Fetch missing data from database
                $db_element = VPB_Library::get_element( absint( $element['id'] ) );
                if ( $db_element ) {
                    $element['svg']      = $db_element['svg_file'] ?? '';
                    $element['colorHex'] = $db_element['color_hex'] ?? '#4F9ED9';
                }
            }
            // Ensure isSvg property exists
            if ( ! isset( $element['isSvg'] ) ) {
                $svg_file         = $element['svg'] ?? '';
                $element['isSvg'] = ! empty( $svg_file ) && strtolower( pathinfo( $svg_file, PATHINFO_EXTENSION ) ) === 'svg';
            }
            $elements[] = $element;
        }

        wp_send_json_success( array(
            'elements'      => $elements,
            'cart_item_key' => $cart_item_key,
        ) );
    }

    /**
     * Add cart item filter
     *
     * @param array $cart_item Cart item.
     * @return array
     */
    public function add_cart_item( $cart_item ) {
        // Price calculation is handled by VPB_Pricing class using $cart_item['vpb_elements']
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
        }

        if ( isset( $values['vpb_image_data'] ) ) {
            $cart_item['vpb_image_data'] = $values['vpb_image_data'];
        }

        return $cart_item;
    }
}
