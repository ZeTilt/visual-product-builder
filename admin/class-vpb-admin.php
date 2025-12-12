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
        add_action( 'wp_ajax_vpb_save_element', array( $this, 'ajax_save_element' ) );
        add_action( 'wp_ajax_vpb_delete_element', array( $this, 'ajax_delete_element' ) );
        add_action( 'wp_ajax_vpb_import_sample_data', array( $this, 'ajax_import_sample_data' ) );
        add_action( 'wp_ajax_vpb_bulk_update_price', array( $this, 'ajax_bulk_update_price' ) );
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
            'Bibliothèque d\'éléments',
            'Éléments',
            'manage_woocommerce',
            'vpb-elements',
            array( $this, 'render_elements_page' )
        );

        add_submenu_page(
            'vpb-settings',
            'Réglages',
            'Réglages',
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
                'confirmDelete' => 'Voulez-vous vraiment supprimer cet élément ?',
                'saved'         => 'Enregistré avec succès',
                'error'         => 'Une erreur est survenue',
                'selectImage'   => 'Sélectionner une image',
                'useImage'      => 'Utiliser cette image',
            ),
        ) );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Vous n\'avez pas la permission d\'accéder à cette page.' );
        }

        include VPB_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render elements library page
     */
    public function render_elements_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Vous n\'avez pas la permission d\'accéder à cette page.' );
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
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        $id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $data = array(
            'name'       => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
            'slug'       => isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '',
            'category'   => isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'letter',
            'svg_file'   => isset( $_POST['svg_file'] ) ? esc_url_raw( wp_unslash( $_POST['svg_file'] ) ) : '',
            'color'      => isset( $_POST['color'] ) ? sanitize_key( wp_unslash( $_POST['color'] ) ) : 'default',
            'price'      => isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0.00,
            'sort_order' => isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0,
            'active'     => isset( $_POST['active'] ) ? 1 : 0,
        );

        // Validate required fields
        if ( empty( $data['name'] ) || empty( $data['svg_file'] ) ) {
            wp_send_json_error( array( 'message' => 'Le nom et l\'image sont requis' ) );
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
            $id     = $result;
        }

        if ( $result ) {
            wp_send_json_success( array(
                'message' => 'Élément enregistré',
                'id'      => $id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Échec de l\'enregistrement' ) );
        }
    }

    /**
     * AJAX: Delete element
     */
    public function ajax_delete_element() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => 'ID d\'élément invalide' ) );
        }

        $result = VPB_Library::delete_element( $id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Élément supprimé' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Échec de la suppression' ) );
        }
    }

    /**
     * AJAX: Import sample data
     */
    public function ajax_import_sample_data() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        $imported = VPB_Sample_Data::import();

        wp_send_json_success( array(
            'message'  => $imported . ' éléments importés avec succès',
            'imported' => $imported,
        ) );
    }

    /**
     * AJAX: Bulk update prices
     */
    public function ajax_bulk_update_price() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        $ids   = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
        $price = isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0;
        $mode  = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'set';

        if ( empty( $ids ) ) {
            wp_send_json_error( array( 'message' => 'Aucun élément sélectionné' ) );
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
            'message' => $updated . ' prix mis à jour',
            'updated' => $updated,
        ) );
    }
}
