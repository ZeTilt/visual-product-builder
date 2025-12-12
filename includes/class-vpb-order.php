<?php
/**
 * Order Integration
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_Order class
 */
class VPB_Order {

    /**
     * Constructor
     */
    public function __construct() {
        // Add custom data to order line item
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );

        // Save PNG image after order is created
        add_action( 'woocommerce_checkout_order_created', array( $this, 'save_custom_image' ), 10, 1 );

        // Display in order admin
        add_action( 'woocommerce_admin_order_item_headers', array( $this, 'admin_order_item_headers' ) );
        add_action( 'woocommerce_admin_order_item_values', array( $this, 'admin_order_item_values' ), 10, 3 );
    }

    /**
     * Add configuration data to order line item
     *
     * @param WC_Order_Item_Product $item          Order item.
     * @param string                $cart_item_key Cart item key.
     * @param array                 $values        Cart item values.
     * @param WC_Order              $order         Order.
     */
    public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
        if ( ! isset( $values['vpb_elements'] ) ) {
            return;
        }

        // Store configuration summary
        $item->add_meta_data(
            __( 'Personnalisation', 'visual-product-builder' ),
            $values['vpb_configuration'],
            true
        );

        // Store detailed elements (hidden in frontend)
        $item->add_meta_data(
            '_vpb_elements',
            $values['vpb_elements'],
            true
        );

        // Store elements price
        $elements_price = 0;
        foreach ( $values['vpb_elements'] as $element ) {
            $elements_price += floatval( $element['price'] );
        }
        $item->add_meta_data(
            '_vpb_elements_price',
            $elements_price,
            true
        );

        // Store image data reference for later processing
        if ( isset( $values['vpb_image_data'] ) ) {
            $item->add_meta_data(
                '_vpb_has_image',
                'pending',
                true
            );
        }
    }

    /**
     * Save custom PNG image as order attachment
     *
     * @param WC_Order $order Order object.
     */
    public function save_custom_image( $order ) {
        $order_id = $order->get_id();

        // Get cart to access image data
        $cart = WC()->cart;
        if ( ! $cart ) {
            return;
        }

        foreach ( $order->get_items() as $item_id => $item ) {
            // Check if this item has pending image
            if ( $item->get_meta( '_vpb_has_image' ) !== 'pending' ) {
                continue;
            }

            // Find corresponding cart item
            $cart_item_key = $this->find_cart_item_key( $cart, $item );
            if ( ! $cart_item_key ) {
                continue;
            }

            $cart_item = $cart->get_cart_item( $cart_item_key );
            if ( ! isset( $cart_item['vpb_image_data'] ) ) {
                continue;
            }

            // Process and save image
            $attachment_id = $this->process_image( $cart_item['vpb_image_data'], $order_id, $item_id );

            if ( $attachment_id ) {
                // Update item meta with attachment ID
                wc_update_order_item_meta( $item_id, '_vpb_image_id', $attachment_id );
                wc_update_order_item_meta( $item_id, '_vpb_has_image', 'saved' );
            } else {
                wc_update_order_item_meta( $item_id, '_vpb_has_image', 'failed' );
            }
        }
    }

    /**
     * Find cart item key matching order item
     *
     * @param WC_Cart               $cart Cart object.
     * @param WC_Order_Item_Product $item Order item.
     * @return string|false
     */
    private function find_cart_item_key( $cart, $item ) {
        $product_id   = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        $vpb_elements = $item->get_meta( '_vpb_elements' );

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( $cart_item['product_id'] !== $product_id ) {
                continue;
            }
            if ( $cart_item['variation_id'] !== $variation_id ) {
                continue;
            }
            if ( ! isset( $cart_item['vpb_elements'] ) ) {
                continue;
            }
            if ( wp_json_encode( $cart_item['vpb_elements'] ) === wp_json_encode( $vpb_elements ) ) {
                return $cart_item_key;
            }
        }

        return false;
    }

    /**
     * Process base64 image and create attachment
     *
     * @param string $image_data Base64 image data.
     * @param int    $order_id   Order ID.
     * @param int    $item_id    Item ID.
     * @return int|false Attachment ID or false on failure.
     */
    private function process_image( $image_data, $order_id, $item_id ) {
        // Remove data URI prefix
        $image_data = str_replace( 'data:image/png;base64,', '', $image_data );
        $image_data = str_replace( ' ', '+', $image_data );

        // Decode
        $decoded = base64_decode( $image_data );
        if ( ! $decoded ) {
            return false;
        }

        // Check size (max 5MB)
        if ( strlen( $decoded ) > 5 * 1024 * 1024 ) {
            return false;
        }

        // Verify it's a valid PNG
        $finfo = new finfo( FILEINFO_MIME_TYPE );
        $mime  = $finfo->buffer( $decoded );
        if ( $mime !== 'image/png' ) {
            return false;
        }

        // Prepare upload directory
        $upload_dir = wp_upload_dir();
        $vpb_dir    = $upload_dir['basedir'] . '/vpb-orders/' . gmdate( 'Y/m' );

        if ( ! file_exists( $vpb_dir ) ) {
            wp_mkdir_p( $vpb_dir );
            // Add index.php for security
            file_put_contents( $vpb_dir . '/index.php', '<?php // Silence is golden' );
        }

        // Generate filename
        $filename = sprintf( 'order-%d-item-%d-%s.png', $order_id, $item_id, wp_generate_password( 8, false ) );
        $filepath = $vpb_dir . '/' . $filename;

        // Save file
        if ( file_put_contents( $filepath, $decoded ) === false ) {
            return false;
        }

        // Create attachment
        $attachment = array(
            'post_mime_type' => 'image/png',
            'post_title'     => sprintf( __( 'Configuration commande #%d', 'visual-product-builder' ), $order_id ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $filepath, $order_id );

        if ( is_wp_error( $attachment_id ) ) {
            unlink( $filepath );
            return false;
        }

        // Generate attachment metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $filepath );
        wp_update_attachment_metadata( $attachment_id, $attach_data );

        return $attachment_id;
    }

    /**
     * Add header to order items table in admin
     */
    public function admin_order_item_headers() {
        echo '<th class="vpb-preview">' . esc_html__( 'Aperçu', 'visual-product-builder' ) . '</th>';
    }

    /**
     * Display preview image in order items table
     *
     * @param WC_Product            $product Product.
     * @param WC_Order_Item_Product $item    Item.
     * @param int                   $item_id Item ID.
     */
    public function admin_order_item_values( $product, $item, $item_id ) {
        echo '<td class="vpb-preview">';

        $image_id = $item->get_meta( '_vpb_image_id' );
        if ( $image_id ) {
            $image_url = wp_get_attachment_url( $image_id );
            if ( $image_url ) {
                printf(
                    '<a href="%s" target="_blank"><img src="%s" style="max-width: 100px; height: auto;" /></a>',
                    esc_url( $image_url ),
                    esc_url( $image_url )
                );
            }
        } else {
            echo '—';
        }

        echo '</td>';
    }
}
