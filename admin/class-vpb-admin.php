<?php
/**
 * Admin Settings
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_Admin class
 */
class VPB_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Element AJAX handlers
        add_action( 'wp_ajax_vpb_save_element', array( $this, 'ajax_save_element' ) );
        add_action( 'wp_ajax_vpb_delete_element', array( $this, 'ajax_delete_element' ) );
        add_action( 'wp_ajax_vpb_get_element', array( $this, 'ajax_get_element' ) );
        add_action( 'wp_ajax_vpb_import_sample_data', array( $this, 'ajax_import_sample_data' ) );
        add_action( 'wp_ajax_vpb_bulk_update_price', array( $this, 'ajax_bulk_update_price' ) );
        add_action( 'wp_ajax_vpb_bulk_assign_collection', array( $this, 'ajax_bulk_assign_collection' ) );

        // Collection AJAX handlers
        add_action( 'wp_ajax_vpb_save_collection', array( $this, 'ajax_save_collection' ) );
        add_action( 'wp_ajax_vpb_delete_collection', array( $this, 'ajax_delete_collection' ) );
        add_action( 'wp_ajax_vpb_get_collection', array( $this, 'ajax_get_collection' ) );
        add_action( 'wp_ajax_vpb_purge_collections', array( $this, 'ajax_purge_collections' ) );
        add_action( 'wp_ajax_vpb_import_element', array( $this, 'ajax_import_element' ) );

        // WooCommerce product metabox
        add_action( 'add_meta_boxes', array( $this, 'add_product_metabox' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_collections' ) );
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        add_menu_page(
            'Visual Product Builder',
            'VPB',
            'manage_woocommerce',
            'vpb-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-art',
            56
        );

        add_submenu_page(
            'vpb-settings',
            'Collections',
            'Collections',
            'manage_woocommerce',
            'vpb-collections',
            array( $this, 'render_collections_page' )
        );

        add_submenu_page(
            'vpb-settings',
            __( 'Element Library', 'visual-product-builder' ),
            __( 'Elements', 'visual-product-builder' ),
            'manage_woocommerce',
            'vpb-elements',
            array( $this, 'render_elements_page' )
        );

        add_submenu_page(
            'vpb-settings',
            __( 'Settings', 'visual-product-builder' ),
            __( 'Settings', 'visual-product-builder' ),
            'manage_woocommerce',
            'vpb-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'vpb' ) === false ) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'vpb-admin',
            VPB_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            VPB_VERSION
        );

        wp_enqueue_script(
            'vpb-admin',
            VPB_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            VPB_VERSION,
            true
        );

        wp_localize_script( 'vpb-admin', 'vpbAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'vpb_admin_nonce' ),
            'i18n'    => array(
                'confirmDelete'       => __( 'Are you sure you want to delete this element?', 'visual-product-builder' ),
                'saved'               => __( 'Saved successfully', 'visual-product-builder' ),
                'error'               => __( 'An error occurred', 'visual-product-builder' ),
                'selectImage'         => __( 'Select an image', 'visual-product-builder' ),
                'useImage'            => __( 'Use this image', 'visual-product-builder' ),
                'addElement'          => __( 'Add Element', 'visual-product-builder' ),
                'editElement'         => __( 'Edit Element', 'visual-product-builder' ),
                'saving'              => __( 'Saving...', 'visual-product-builder' ),
                'save'                => __( 'Save', 'visual-product-builder' ),
                'connectionError'     => __( 'Connection error', 'visual-product-builder' ),
                'selected'            => __( 'selected', 'visual-product-builder' ),
                'elementsSelected'    => __( 'elements selected', 'visual-product-builder' ),
                'confirmImportSample' => __( 'Import sample data? This will add elements to your library.', 'visual-product-builder' ),
                'importing'           => __( 'Importing...', 'visual-product-builder' ),
                'importSampleData'    => __( 'Import Sample Data', 'visual-product-builder' ),
                // Collections page strings
                'addCollection'       => __( 'Add Collection', 'visual-product-builder' ),
                'editCollection'      => __( 'Edit Collection', 'visual-product-builder' ),
                'confirmDeleteColl'   => __( 'Do you really want to delete this collection? Elements will not be deleted but will no longer be assigned.', 'visual-product-builder' ),
                /* translators: %d: number of collections */
                'confirmPurge'        => __( "Do you really want to DELETE EVERYTHING?\n\n• %d collections\n• All elements\n\nThis action is irreversible.", 'visual-product-builder' ),
                'forcePurgeConfirm'   => __( 'Type "PURGE" to confirm forced deletion despite pending orders:', 'visual-product-builder' ),
                'purgeAborted'        => __( 'Purge aborted. You must type "PURGE" exactly.', 'visual-product-builder' ),
                'deleting'            => __( 'Deleting...', 'visual-product-builder' ),
                'purgeAll'            => __( 'Purge All', 'visual-product-builder' ),
                'chooseThumbnail'     => __( 'Choose Thumbnail', 'visual-product-builder' ),
                'useThisImage'        => __( 'Use This Image', 'visual-product-builder' ),
                'importInProgress'    => __( 'Import in progress...', 'visual-product-builder' ),
                /* translators: %d: number of elements imported */
                'elementsImported'    => __( '%d element(s) imported', 'visual-product-builder' ),
                /* translators: %d: number of errors */
                'errors'              => __( '%d error(s):', 'visual-product-builder' ),
                'networkError'        => __( 'Network Error', 'visual-product-builder' ),
            ),
        ) );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'visual-product-builder' ) );
        }

        // Handle custom CSS save.
        if ( isset( $_POST['vpb_save_css'] ) && isset( $_POST['vpb_css_nonce'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification.
            if ( wp_verify_nonce( wp_unslash( $_POST['vpb_css_nonce'] ), 'vpb_save_custom_css' ) ) {
                $custom_css = isset( $_POST['vpb_custom_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['vpb_custom_css'] ) ) : '';
                update_option( 'vpb_custom_css', $custom_css );
                add_settings_error( 'vpb_messages', 'vpb_css_saved', __( 'Custom CSS saved.', 'visual-product-builder' ), 'success' );
            }
        }

        settings_errors( 'vpb_messages' );

        include VPB_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render elements library page
     */
    public function render_elements_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'visual-product-builder' ) );
        }

        $elements = VPB_Library::get_elements();

        include VPB_PLUGIN_DIR . 'admin/views/elements.php';
    }

    /**
     * AJAX: Save element
     */
    public function ajax_save_element() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        $id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $data = array(
            'name'          => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
            'slug'          => isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '',
            'category'      => isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'letter',
            'svg_file'      => isset( $_POST['svg_file'] ) ? esc_url_raw( wp_unslash( $_POST['svg_file'] ) ) : '',
            'color'         => isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : 'default',
            'color_hex'     => isset( $_POST['color_hex'] ) ? sanitize_hex_color( wp_unslash( $_POST['color_hex'] ) ) : '#4F9ED9',
            'collection_id' => isset( $_POST['collection_id'] ) && $_POST['collection_id'] !== '' ? absint( $_POST['collection_id'] ) : null,
            'price'         => isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0.00,
            'sort_order'    => isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0,
            'active'        => isset( $_POST['active'] ) ? 1 : 0,
        );

        // Validate required fields
        if ( empty( $data['name'] ) || empty( $data['svg_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Name and image are required', 'visual-product-builder' ) ) );
        }

        // Auto-generate slug if empty
        if ( empty( $data['slug'] ) ) {
            $data['slug'] = sanitize_title( $data['name'] );
        }

        if ( $id > 0 ) {
            // Update existing
            $result = VPB_Library::update_element( $id, $data );
        } else {
            // Create new
            $result = VPB_Library::add_element( $data );

            // Check for limit error
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array(
                    'message'      => wp_strip_all_tags( $result->get_error_message() ),
                    'limit_reached' => true,
                    'upgrade_url'  => VPB_PRICING_URL,
                ) );
            }

            $id = $result;
        }

        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Element saved', 'visual-product-builder' ),
                'id'      => $id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Save failed', 'visual-product-builder' ) ) );
        }
    }

    /**
     * AJAX: Delete element
     */
    public function ajax_delete_element() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid element ID', 'visual-product-builder' ) ) );
        }

        $result = VPB_Library::delete_element( $id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Element deleted', 'visual-product-builder' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Delete failed', 'visual-product-builder' ) ) );
        }
    }

    /**
     * AJAX: Get element data
     */
    public function ajax_get_element() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid element ID', 'visual-product-builder' ) ) );
        }

        $element = VPB_Library::get_element( $id );

        if ( $element ) {
            wp_send_json_success( $element );
        } else {
            wp_send_json_error( array( 'message' => __( 'Element not found', 'visual-product-builder' ) ) );
        }
    }

    /**
     * AJAX: Import sample data
     */
    public function ajax_import_sample_data() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        $result = VPB_Sample_Data::import();

        $message = sprintf(
            /* translators: %1$d: number of collections, %2$d: number of elements */
            __( '%1$d collections and %2$d elements imported successfully', 'visual-product-builder' ),
            $result['collections'],
            $result['elements']
        );

        wp_send_json_success( array(
            'message'     => $message,
            'collections' => $result['collections'],
            'elements'    => $result['elements'],
        ) );
    }

    /**
     * AJAX: Bulk update prices (PRO feature)
     */
    public function ajax_bulk_update_price() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        // Bulk operations require PRO
        if ( ! vpb_can_use_feature( 'bulk_operations' ) ) {
            wp_send_json_error( array(
                'message'     => __( 'Bulk operations require a PRO license', 'visual-product-builder' ),
                'upgrade_url' => VPB_PRICING_URL,
            ) );
        }

        $ids   = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
        $price = isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0;
        $mode  = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'set';

        if ( empty( $ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No element selected', 'visual-product-builder' ) ) );
        }

        $updated = 0;

        foreach ( $ids as $id ) {
            $element = VPB_Library::get_element( $id );
            if ( ! $element ) {
                continue;
            }

            $new_price = $price;

            if ( 'add' === $mode ) {
                $new_price = floatval( $element['price'] ) + $price;
            } elseif ( 'subtract' === $mode ) {
                $new_price = max( 0, floatval( $element['price'] ) - $price );
            }

            $result = VPB_Library::update_element( $id, array( 'price' => $new_price ) );
            if ( $result ) {
                $updated++;
            }
        }

        wp_send_json_success( array(
            /* translators: %d: number of prices updated */
            'message' => sprintf( __( '%d prices updated', 'visual-product-builder' ), $updated ),
            'updated' => $updated,
        ) );
    }

    /**
     * Render collections page
     */
    public function render_collections_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'visual-product-builder' ) );
        }

        $collections = VPB_Collection::get_collections();

        include VPB_PLUGIN_DIR . 'admin/views/collections.php';
    }

    /**
     * AJAX: Save collection
     */
    public function ajax_save_collection() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        $id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $data = array(
            'name'          => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
            'slug'          => isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '',
            'description'   => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
            'color_hex'     => isset( $_POST['color_hex'] ) ? sanitize_hex_color( wp_unslash( $_POST['color_hex'] ) ) : '#4F9ED9',
            'thumbnail_url' => isset( $_POST['thumbnail_url'] ) ? esc_url_raw( wp_unslash( $_POST['thumbnail_url'] ) ) : '',
            'sort_order'    => isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0,
            'active'        => isset( $_POST['active'] ) ? 1 : 0,
        );

        // Validate required fields
        if ( empty( $data['name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Name is required', 'visual-product-builder' ) ) );
        }

        if ( $id > 0 ) {
            $result = VPB_Collection::update_collection( $id, $data );
        } else {
            $result = VPB_Collection::add_collection( $data );

            // Check for limit error
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array(
                    'message'       => wp_strip_all_tags( $result->get_error_message() ),
                    'limit_reached' => true,
                    'upgrade_url'   => VPB_PRICING_URL,
                ) );
            }

            $id = $result;
        }

        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Collection saved', 'visual-product-builder' ),
                'id'      => $id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Save failed', 'visual-product-builder' ) ) );
        }
    }

    /**
     * AJAX: Delete collection
     */
    public function ajax_delete_collection() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid collection ID', 'visual-product-builder' ) ) );
        }

        $result = VPB_Collection::delete_collection( $id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Collection deleted', 'visual-product-builder' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Delete failed', 'visual-product-builder' ) ) );
        }
    }

    /**
     * AJAX: Purge all collections and elements
     */
    public function ajax_purge_collections() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        $force = isset( $_POST['force'] ) && 'true' === $_POST['force'];

        // Check for pending/processing orders with VPB customizations
        $pending_orders = $this->get_orders_with_vpb_customizations();

        if ( ! empty( $pending_orders ) && ! $force ) {
            wp_send_json_error( array(
                'message'        => sprintf(
                    /* translators: %d: number of pending orders */
                    __( 'Cannot purge: %d order(s) with customizations are pending/processing. Purging would make order data unreadable. Use "Force purge" to proceed anyway.', 'visual-product-builder' ),
                    count( $pending_orders )
                ),
                'pending_orders' => count( $pending_orders ),
                'requires_force' => true,
            ) );
        }

        global $wpdb;

        // Log the purge action
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe, constructed from $wpdb->prefix. TRUNCATE cannot use prepare().
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query.
        $elements_count    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vpb_elements" );
        $collections_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vpb_collections" );

        // Clear elements table
        $elements_table = $wpdb->prefix . 'vpb_elements';
        $wpdb->query( "TRUNCATE TABLE $elements_table" );

        // Clear collections table
        $collections_table = $wpdb->prefix . 'vpb_collections';
        $wpdb->query( "TRUNCATE TABLE $collections_table" );

        // Clear product-collection relationships
        $product_collections_table = $wpdb->prefix . 'vpb_product_collections';
        $wpdb->query( "TRUNCATE TABLE $product_collections_table" );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        // Log purge to WordPress error log for audit trail
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $current_user = wp_get_current_user();
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging for troubleshooting.
            error_log( sprintf(
                '[VPB] PURGE: User "%s" (ID: %d) purged %d collections and %d elements. Force: %s. Pending orders: %d.',
                $current_user->user_login,
                $current_user->ID,
                $collections_count,
                $elements_count,
                $force ? 'yes' : 'no',
                count( $pending_orders )
            ) );
        }

        wp_send_json_success( array( 'message' => __( 'All collections and elements have been deleted', 'visual-product-builder' ) ) );
    }

    /**
     * Get WooCommerce orders with VPB customizations that are pending or processing.
     *
     * @return array Array of order IDs.
     */
    private function get_orders_with_vpb_customizations() {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return array();
        }

        // Get orders that are pending, processing, or on-hold
        $orders = wc_get_orders( array(
            'status' => array( 'pending', 'processing', 'on-hold' ),
            'limit'  => -1,
            'return' => 'ids',
        ) );

        $orders_with_vpb = array();

        foreach ( $orders as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }

            foreach ( $order->get_items() as $item ) {
                // Check if item has VPB customization meta
                $vpb_config = $item->get_meta( '_vpb_configuration' );
                if ( ! empty( $vpb_config ) ) {
                    $orders_with_vpb[] = $order_id;
                    break; // One VPB item is enough, move to next order
                }
            }
        }

        return $orders_with_vpb;
    }

    /**
     * AJAX: Import single element from uploaded file
     */
    public function ajax_import_element() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        if ( empty( $_FILES['file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file received', 'visual-product-builder' ) ) );
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- File upload is validated via wp_handle_upload() which performs full sanitization and type checking.
        $file = $_FILES['file'];

        // Allowed image extensions
        $allowed_extensions = array( 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp' );
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

        if ( ! in_array( $ext, $allowed_extensions, true ) ) {
            /* translators: %s: file extension */
            wp_send_json_error( array( 'message' => sprintf( __( 'Extension not allowed: %s', 'visual-product-builder' ), $ext ) ) );
        }

        // Get element name from filename (without extension)
        $element_name = pathinfo( $file['name'], PATHINFO_FILENAME );
        $element_slug = sanitize_title( $element_name );

        // Get other parameters.
        $collection_id = isset( $_POST['collection_id'] ) ? absint( $_POST['collection_id'] ) : null;
        $color_hex     = isset( $_POST['color_hex'] ) ? sanitize_hex_color( wp_unslash( $_POST['color_hex'] ) ) : '#4F9ED9';
        $category      = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'letter';
        $price         = isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0.00;

        // Get color name from collection or generate one
        $color_name = 'default';
        if ( $collection_id ) {
            $collection = VPB_Collection::get_collection( $collection_id );
            if ( $collection ) {
                $color_name = sanitize_title( $collection->name );
            }
        }

        // Sanitize SVG files before upload (security: prevent XSS/XXE)
        if ( 'svg' === $ext ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading temp upload file before sanitization.
            $svg_content = file_get_contents( $file['tmp_name'] );
            $sanitized   = $this->sanitize_svg( $svg_content );

            if ( false === $sanitized ) {
                wp_send_json_error( array( 'message' => __( 'SVG file contains potentially dangerous content', 'visual-product-builder' ) ) );
            }

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing sanitized content to temp upload file.
            file_put_contents( $file['tmp_name'], $sanitized );
        }

        // Upload file to WordPress uploads directory
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Allow SVG uploads temporarily
        add_filter( 'upload_mimes', function( $mimes ) {
            $mimes['svg']  = 'image/svg+xml';
            $mimes['webp'] = 'image/webp';
            return $mimes;
        } );

        $upload_overrides = array( 'test_form' => false );

        $movefile = wp_handle_upload( $file, $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            // Create element in database
            $element_data = array(
                'name'          => $element_name,
                'slug'          => $element_slug . '-' . $color_name,
                'category'      => $category,
                'svg_file'      => $movefile['url'],
                'color'         => $color_name,
                'color_hex'     => $color_hex,
                'collection_id' => $collection_id,
                'price'         => $price,
                'sort_order'    => 0,
                'active'        => 1,
            );

            $element_id = VPB_Library::add_element( $element_data );

            // Check for limit error
            if ( is_wp_error( $element_id ) ) {
                wp_send_json_error( array(
                    'message'       => wp_strip_all_tags( $element_id->get_error_message() ),
                    'limit_reached' => true,
                    'upgrade_url'   => VPB_PRICING_URL,
                ) );
            }

            if ( $element_id ) {
                wp_send_json_success( array(
                    'message'    => __( 'Element imported', 'visual-product-builder' ),
                    'element_id' => $element_id,
                    'name'       => $element_name,
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Error creating element', 'visual-product-builder' ) ) );
            }
        } else {
            wp_send_json_error( array( 'message' => $movefile['error'] ?? __( 'Upload error', 'visual-product-builder' ) ) );
        }
    }

    /**
     * AJAX: Get collection data
     */
    public function ajax_get_collection() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid collection ID', 'visual-product-builder' ) ) );
        }

        $collection = VPB_Collection::get_collection( $id );

        if ( $collection ) {
            wp_send_json_success( $collection );
        } else {
            wp_send_json_error( array( 'message' => __( 'Collection not found', 'visual-product-builder' ) ) );
        }
    }

    /**
     * AJAX: Bulk assign collection to elements (PRO feature)
     */
    public function ajax_bulk_assign_collection() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'visual-product-builder' ) ) );
        }

        // Bulk operations require PRO
        if ( ! vpb_can_use_feature( 'bulk_operations' ) ) {
            wp_send_json_error( array(
                'message'     => __( 'Bulk operations require a PRO license', 'visual-product-builder' ),
                'upgrade_url' => VPB_PRICING_URL,
            ) );
        }

        $ids           = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
        $collection_id = isset( $_POST['collection_id'] ) && $_POST['collection_id'] !== '' ? absint( $_POST['collection_id'] ) : null;

        if ( empty( $ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No element selected', 'visual-product-builder' ) ) );
        }

        $updated = 0;

        foreach ( $ids as $id ) {
            $result = VPB_Library::update_element( $id, array( 'collection_id' => $collection_id ) );
            if ( $result ) {
                $updated++;
            }
        }

        wp_send_json_success( array(
            /* translators: %d: number of elements assigned */
            'message' => sprintf( __( '%d elements assigned', 'visual-product-builder' ), $updated ),
            'updated' => $updated,
        ) );
    }

    /**
     * Add metabox to WooCommerce product edit page
     */
    public function add_product_metabox() {
        add_meta_box(
            'vpb_product_collections',
            'Visual Product Builder',
            array( $this, 'render_product_metabox' ),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Render the product metabox content
     *
     * @param WP_Post $post Post object.
     */
    public function render_product_metabox( $post ) {
        wp_nonce_field( 'vpb_product_collections', 'vpb_product_collections_nonce' );

        $collections         = VPB_Collection::get_collections( array( 'active' => 1 ) );
        $selected_ids        = VPB_Collection::get_product_collection_ids( $post->ID );
        $can_support_image   = vpb_can_use_feature( 'support_image' );
        $support_image       = $can_support_image ? get_post_meta( $post->ID, '_vpb_support_image', true ) : '';
        ?>

        <!-- Support image (BUSINESS feature) -->
        <p>
            <strong>
                <?php esc_html_e( 'Support Image', 'visual-product-builder' ); ?>
                <?php if ( ! $can_support_image ) : ?>
                    <span style="display: inline-block; padding: 2px 6px; font-size: 10px; font-weight: 600; text-transform: uppercase; border-radius: 3px; margin-left: 5px; vertical-align: middle; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff;"><?php esc_html_e( 'BUSINESS', 'visual-product-builder' ); ?></span>
                <?php endif; ?>
            </strong><br>
            <small><?php esc_html_e( 'Image on which elements will be placed.', 'visual-product-builder' ); ?></small>
        </p>
        <?php if ( $can_support_image ) : ?>
            <div class="vpb-support-image-field" style="margin-bottom: 15px;">
                <div id="vpb-support-image-preview" style="margin-bottom: 10px; <?php echo empty( $support_image ) ? 'display: none;' : ''; ?>">
                    <?php if ( $support_image ) : ?>
                        <img src="<?php echo esc_url( $support_image ); ?>" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px;">
                    <?php endif; ?>
                </div>
                <input type="hidden" name="vpb_support_image" id="vpb-support-image-input" value="<?php echo esc_url( $support_image ); ?>">
                <button type="button" class="button" id="vpb-support-image-btn">
                    <?php echo $support_image ? esc_html__( 'Change image', 'visual-product-builder' ) : esc_html__( 'Choose an image', 'visual-product-builder' ); ?>
                </button>
                <button type="button" class="button" id="vpb-support-image-remove" style="<?php echo empty( $support_image ) ? 'display: none;' : ''; ?>">
                    <?php esc_html_e( 'Remove', 'visual-product-builder' ); ?>
                </button>
            </div>
        <?php else : ?>
            <p style="margin-bottom: 15px;">
                <a href="<?php echo esc_url( VPB_PRICING_URL ); ?>" target="_blank" class="button button-secondary" style="width: 100%; text-align: center;">
                    <?php esc_html_e( 'Upgrade to BUSINESS', 'visual-product-builder' ); ?>
                </a>
            </p>
        <?php endif; ?>

        <hr style="margin: 15px 0;">

        <!-- Collections -->
        <p>
            <strong><?php esc_html_e( 'Available Collections', 'visual-product-builder' ); ?></strong><br>
            <small><?php esc_html_e( 'Select collections to display for this product.', 'visual-product-builder' ); ?></small>
        </p>

        <?php if ( empty( $collections ) ) : ?>
            <p><em><?php esc_html_e( 'No collections available.', 'visual-product-builder' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=vpb-collections' ) ); ?>"><?php esc_html_e( 'Create a collection', 'visual-product-builder' ); ?></a></em></p>
        <?php else : ?>
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                <?php foreach ( $collections as $collection ) : ?>
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="checkbox"
                               name="vpb_collections[]"
                               value="<?php echo esc_attr( $collection->id ); ?>"
                               <?php checked( in_array( $collection->id, $selected_ids, true ) ); ?>>
                        <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo esc_attr( $collection->color_hex ); ?>; border-radius: 2px; vertical-align: middle; margin-right: 5px;"></span>
                        <?php echo esc_html( $collection->name ); ?>
                        <small>(<?php
                            /* translators: %d: number of elements */
                            printf( esc_html__( '%d elements', 'visual-product-builder' ), intval( VPB_Collection::get_element_count( $collection->id ) ) );
                        ?>)</small>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <script>
        jQuery(document).ready(function($) {
            var vpbMetaboxI18n = {
                selectImage: '<?php echo esc_js( __( 'Choose a support image', 'visual-product-builder' ) ); ?>',
                useImage: '<?php echo esc_js( __( 'Use this image', 'visual-product-builder' ) ); ?>',
                changeImage: '<?php echo esc_js( __( 'Change image', 'visual-product-builder' ) ); ?>',
                chooseImage: '<?php echo esc_js( __( 'Choose an image', 'visual-product-builder' ) ); ?>'
            };

            // Support image upload
            $('#vpb-support-image-btn').on('click', function(e) {
                e.preventDefault();
                var frame = wp.media({
                    title: vpbMetaboxI18n.selectImage,
                    button: { text: vpbMetaboxI18n.useImage },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#vpb-support-image-input').val(attachment.url);
                    $('#vpb-support-image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px;">').show();
                    $('#vpb-support-image-btn').text(vpbMetaboxI18n.changeImage);
                    $('#vpb-support-image-remove').show();
                });
                frame.open();
            });

            // Remove support image
            $('#vpb-support-image-remove').on('click', function() {
                $('#vpb-support-image-input').val('');
                $('#vpb-support-image-preview').empty().hide();
                $('#vpb-support-image-btn').text(vpbMetaboxI18n.chooseImage);
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Save product collections on product save
     *
     * @param int $post_id Product ID.
     */
    public function save_product_collections( $post_id ) {
        if ( ! isset( $_POST['vpb_product_collections_nonce'] ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification.
        if ( ! wp_verify_nonce( wp_unslash( $_POST['vpb_product_collections_nonce'] ), 'vpb_product_collections' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save collections.
        $collection_ids = isset( $_POST['vpb_collections'] ) ? array_map( 'absint', $_POST['vpb_collections'] ) : array();
        VPB_Collection::set_product_collections( $post_id, $collection_ids );

        // Save support image (BUSINESS feature only).
        if ( vpb_can_use_feature( 'support_image' ) ) {
            $support_image = isset( $_POST['vpb_support_image'] ) ? esc_url_raw( wp_unslash( $_POST['vpb_support_image'] ) ) : '';
            if ( $support_image ) {
                update_post_meta( $post_id, '_vpb_support_image', $support_image );
            } else {
                delete_post_meta( $post_id, '_vpb_support_image' );
            }
        }
    }

    /**
     * Sanitize SVG content to prevent XSS and XXE attacks.
     *
     * @param string $svg_content Raw SVG content.
     * @return string|false Sanitized SVG content or false if malicious.
     */
    private function sanitize_svg( $svg_content ) {
        // Remove XML declaration and DOCTYPE (XXE prevention)
        $svg_content = preg_replace( '/\s*<\?xml[^>]*\?>\s*/i', '', $svg_content );
        $svg_content = preg_replace( '/\s*<!DOCTYPE[^>]*>\s*/i', '', $svg_content );

        // Remove ENTITY declarations (XXE prevention)
        $svg_content = preg_replace( '/<!ENTITY[^>]*>/i', '', $svg_content );

        // Remove script tags and their content
        $svg_content = preg_replace( '/<script[^>]*>.*?<\/script>/is', '', $svg_content );
        $svg_content = preg_replace( '/<script[^>]*\/>/i', '', $svg_content );

        // Remove event handlers (onclick, onload, onerror, onmouseover, etc.)
        $svg_content = preg_replace( '/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $svg_content );
        $svg_content = preg_replace( '/\s+on\w+\s*=\s*[^\s>]*/i', '', $svg_content );

        // Remove javascript: URLs
        $svg_content = preg_replace( '/href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', '', $svg_content );
        $svg_content = preg_replace( '/xlink:href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', '', $svg_content );

        // Remove data: URLs that could contain scripts (allow data:image only)
        $svg_content = preg_replace( '/href\s*=\s*["\']?\s*data:(?!image\/)[^"\'>\s]*/i', '', $svg_content );
        $svg_content = preg_replace( '/xlink:href\s*=\s*["\']?\s*data:(?!image\/)[^"\'>\s]*/i', '', $svg_content );

        // Remove foreignObject (can embed HTML/scripts)
        $svg_content = preg_replace( '/<foreignObject[^>]*>.*?<\/foreignObject>/is', '', $svg_content );

        // Remove use elements pointing to external resources
        $svg_content = preg_replace( '/<use[^>]*xlink:href\s*=\s*["\'][^#][^"\']*["\'][^>]*>/i', '', $svg_content );

        // Remove potentially dangerous elements
        $dangerous_elements = array( 'iframe', 'embed', 'object', 'applet', 'form', 'input', 'button' );
        foreach ( $dangerous_elements as $element ) {
            $svg_content = preg_replace( '/<' . $element . '[^>]*>.*?<\/' . $element . '>/is', '', $svg_content );
            $svg_content = preg_replace( '/<' . $element . '[^>]*\/>/i', '', $svg_content );
        }

        // Final check: reject if any dangerous patterns remain
        $dangerous_patterns = array(
            '/<script/i',
            '/javascript\s*:/i',
            '/\s+on\w+\s*=/i',
            '/<foreignObject/i',
            '/<!ENTITY/i',
        );

        foreach ( $dangerous_patterns as $pattern ) {
            if ( preg_match( $pattern, $svg_content ) ) {
                return false;
            }
        }

        // Ensure the content starts with an SVG tag
        if ( ! preg_match( '/<svg[^>]*>/i', $svg_content ) ) {
            return false;
        }

        return trim( $svg_content );
    }
}
