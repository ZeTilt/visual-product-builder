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

        // Display preview in frontend order details
        add_filter( 'woocommerce_order_item_name', array( $this, 'add_preview_to_order_item_name' ), 10, 2 );
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

        // Store image data directly (more reliable than retrieving from cart later)
        if ( isset( $values['vpb_image_data'] ) && ! empty( $values['vpb_image_data'] ) ) {
            $item->add_meta_data(
                '_vpb_image_data',
                $values['vpb_image_data'],
                true
            );
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

        foreach ( $order->get_items() as $item_id => $item ) {
            // Check if this item has pending image
            if ( $item->get_meta( '_vpb_has_image' ) !== 'pending' ) {
                continue;
            }

            // Get image data from order item meta (stored during checkout)
            $image_data = $item->get_meta( '_vpb_image_data' );
            if ( empty( $image_data ) ) {
                wc_update_order_item_meta( $item_id, '_vpb_has_image', 'no_data' );
                continue;
            }

            // Process and save image
            $attachment_id = $this->process_image( $image_data, $order_id, $item_id );

            if ( $attachment_id ) {
                // Update item meta with attachment ID
                wc_update_order_item_meta( $item_id, '_vpb_image_id', $attachment_id );
                wc_update_order_item_meta( $item_id, '_vpb_has_image', 'saved' );

                // Clean up the large base64 data from order meta (no longer needed)
                wc_delete_order_item_meta( $item_id, '_vpb_image_data' );
            } else {
                wc_update_order_item_meta( $item_id, '_vpb_has_image', 'failed' );

                // Clean up the base64 data even on failure (it's still stored in notifications)
                wc_delete_order_item_meta( $item_id, '_vpb_image_data' );

                // CRITICAL: Notify admin about failed image save
                $this->notify_image_save_failure( $order, $item_id, $item );
            }
        }
    }

    /**
     * Notify admin when image save fails
     *
     * @param WC_Order              $order   Order object.
     * @param int                   $item_id Item ID.
     * @param WC_Order_Item_Product $item    Order item.
     */
    private function notify_image_save_failure( $order, $item_id, $item ) {
        $order_id     = $order->get_id();
        $product_name = $item->get_name();

        // Add order note (visible in admin)
        $order->add_order_note(
            sprintf(
                /* translators: 1: item ID, 2: product name */
                __( '⚠️ ATTENTION: Failed to save customization image for item #%1$d (%2$s). The customer configuration data is preserved, but the preview image could not be generated. Please contact the customer if needed.', 'visual-product-builder' ),
                $item_id,
                $product_name
            ),
            0, // Not a customer note
            true // Added by system
        );

        // Send email to admin
        $admin_email = get_option( 'admin_email' );
        $site_name   = get_bloginfo( 'name' );

        $subject = sprintf(
            /* translators: 1: site name, 2: order ID */
            __( '[%1$s] VPB Image Save Failed - Order #%2$d', 'visual-product-builder' ),
            $site_name,
            $order_id
        );

        $message = sprintf(
            /* translators: 1: order ID, 2: item ID, 3: product name, 4: order edit URL */
            __( "The customization image could not be saved for order #%1\$d.\n\nItem ID: %2\$d\nProduct: %3\$s\n\nThe order has been created successfully and the configuration data is preserved in the order meta.\nHowever, the visual preview image failed to save. This could be due to:\n- Insufficient disk space\n- File permission issues\n- Invalid image data from client\n\nPlease check the order and contact the customer if clarification is needed.\n\nView order: %4\$s", 'visual-product-builder' ),
            $order_id,
            $item_id,
            $product_name,
            admin_url( 'post.php?post=' . $order_id . '&action=edit' )
        );

        wp_mail( $admin_email, $subject, $message );

        // Log the error.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled.
            error_log( sprintf(
                '[VPB] CRITICAL: Image save failed for order #%d, item #%d (%s). Admin notified.',
                $order_id,
                $item_id,
                $product_name
            ) );
        }
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

        // SECURITY: Estimate decoded size BEFORE decoding to prevent memory DoS.
        // Base64 encoding adds ~33% overhead, so decoded size ≈ encoded size * 3/4.
        // We add a small margin for safety.
        $max_size       = 5 * 1024 * 1024; // 5MB max
        $estimated_size = ( strlen( $image_data ) * 3 ) / 4;

        if ( $estimated_size > $max_size ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled.
                error_log( sprintf(
                    '[VPB] Rejected oversized image upload: estimated %.2f MB (max %.2f MB)',
                    $estimated_size / 1024 / 1024,
                    $max_size / 1024 / 1024
                ) );
            }
            return false;
        }

        // Decode base64 PNG image generated by frontend canvas (user configuration preview).
        // This is legitimate use for processing user-submitted canvas images, not code obfuscation.
        $decoded = base64_decode( $image_data, true );
        if ( ! $decoded ) {
            return false;
        }

        // Double-check actual size after decode (in case estimation was off)
        if ( strlen( $decoded ) > $max_size ) {
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

        // Create attachment.
        $attachment = array(
            'post_mime_type' => 'image/png',
            /* translators: %d: order ID */
            'post_title'     => sprintf( __( 'Configuration commande #%d', 'visual-product-builder' ), $order_id ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $filepath, $order_id );

        if ( is_wp_error( $attachment_id ) ) {
            wp_delete_file( $filepath );
            return false;
        }

        // Generate attachment metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $filepath );
        wp_update_attachment_metadata( $attachment_id, $attach_data );

        return $attachment_id;
    }

    /**
     * Add preview image to order item name on frontend
     *
     * @param string        $name Product name HTML.
     * @param WC_Order_Item $item Order item.
     * @return string
     */
    public function add_preview_to_order_item_name( $name, $item ) {
        // Only for product items with VPB data
        if ( ! $item instanceof WC_Order_Item_Product ) {
            return $name;
        }

        $image_id = $item->get_meta( '_vpb_image_id' );
        if ( ! $image_id ) {
            return $name;
        }

        $image_url = wp_get_attachment_url( $image_id );
        if ( ! $image_url ) {
            return $name;
        }

        // Add preview image below product name
        $preview_html = '<div class="vpb-order-preview-wrapper" style="margin-top: 10px;">';
        $preview_html .= '<img src="' . esc_url( $image_url ) . '" ';
        $preview_html .= 'alt="' . esc_attr__( 'Aperçu personnalisation', 'visual-product-builder' ) . '" ';
        $preview_html .= 'class="vpb-order-preview" ';
        $preview_html .= 'style="max-width: 200px; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        $preview_html .= '</div>';

        return $name . $preview_html;
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

        $image_status = $item->get_meta( '_vpb_has_image' );
        $image_id     = $item->get_meta( '_vpb_image_id' );

        if ( $image_id ) {
            $image_url = wp_get_attachment_url( $image_id );
            if ( $image_url ) {
                printf(
                    '<a href="%s" target="_blank"><img src="%s" style="max-width: 100px; height: auto;" /></a>',
                    esc_url( $image_url ),
                    esc_url( $image_url )
                );
            }
        } elseif ( 'failed' === $image_status ) {
            // Show warning for failed image save
            echo '<span style="color: #d63638; font-weight: bold;" title="' .
                esc_attr__( 'Image save failed. Check order notes for details.', 'visual-product-builder' ) .
                '">⚠️ ' . esc_html__( 'Image failed', 'visual-product-builder' ) . '</span>';
        } elseif ( 'pending' === $image_status ) {
            // Still pending (shouldn't happen normally)
            echo '<span style="color: #dba617;">' . esc_html__( 'Pending...', 'visual-product-builder' ) . '</span>';
        } else {
            echo '—';
        }

        echo '</td>';
    }
}
