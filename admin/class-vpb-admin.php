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
     * AJAX: Get element data
     */
    public function ajax_get_element() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => 'ID d\'élément invalide' ) );
        }

        $element = VPB_Library::get_element( $id );

        if ( $element ) {
            wp_send_json_success( $element );
        } else {
            wp_send_json_error( array( 'message' => 'Élément non trouvé' ) );
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

        $result = VPB_Sample_Data::import();

        $message = sprintf(
            '%d collections et %d éléments importés avec succès',
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

    /**
     * Render collections page
     */
    public function render_collections_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Vous n\'avez pas la permission d\'accéder à cette page.' );
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
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
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
            wp_send_json_error( array( 'message' => 'Le nom est requis' ) );
        }

        if ( $id > 0 ) {
            $result = VPB_Collection::update_collection( $id, $data );
        } else {
            $result = VPB_Collection::add_collection( $data );
            $id     = $result;
        }

        if ( $result ) {
            wp_send_json_success( array(
                'message' => 'Collection enregistrée',
                'id'      => $id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Échec de l\'enregistrement' ) );
        }
    }

    /**
     * AJAX: Delete collection
     */
    public function ajax_delete_collection() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => 'ID de collection invalide' ) );
        }

        $result = VPB_Collection::delete_collection( $id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Collection supprimée' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Échec de la suppression' ) );
        }
    }

    /**
     * AJAX: Purge all collections
     */
    public function ajax_purge_collections() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        global $wpdb;

        // Clear collections table
        $collections_table = $wpdb->prefix . 'vpb_collections';
        $wpdb->query( "TRUNCATE TABLE $collections_table" );

        // Clear product-collection relationships
        $product_collections_table = $wpdb->prefix . 'vpb_product_collections';
        $wpdb->query( "TRUNCATE TABLE $product_collections_table" );

        // Reset collection_id on all elements
        $elements_table = $wpdb->prefix . 'vpb_elements';
        $wpdb->query( "UPDATE $elements_table SET collection_id = NULL" );

        wp_send_json_success( array( 'message' => 'Toutes les collections ont été supprimées' ) );
    }

    /**
     * AJAX: Get collection data
     */
    public function ajax_get_collection() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => 'ID de collection invalide' ) );
        }

        $collection = VPB_Collection::get_collection( $id );

        if ( $collection ) {
            wp_send_json_success( $collection );
        } else {
            wp_send_json_error( array( 'message' => 'Collection non trouvée' ) );
        }
    }

    /**
     * AJAX: Bulk assign collection to elements
     */
    public function ajax_bulk_assign_collection() {
        check_ajax_referer( 'vpb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        $ids           = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
        $collection_id = isset( $_POST['collection_id'] ) && $_POST['collection_id'] !== '' ? absint( $_POST['collection_id'] ) : null;

        if ( empty( $ids ) ) {
            wp_send_json_error( array( 'message' => 'Aucun élément sélectionné' ) );
        }

        $updated = 0;

        foreach ( $ids as $id ) {
            $result = VPB_Library::update_element( $id, array( 'collection_id' => $collection_id ) );
            if ( $result ) {
                $updated++;
            }
        }

        wp_send_json_success( array(
            'message' => $updated . ' éléments assignés',
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
        ?>
        <p>
            <strong>Collections disponibles</strong><br>
            <small>Sélectionnez les collections à afficher pour ce produit.</small>
        </p>

        <?php if ( empty( $collections ) ) : ?>
            <p><em>Aucune collection disponible. <a href="<?php echo esc_url( admin_url( 'admin.php?page=vpb-collections' ) ); ?>">Créer une collection</a></em></p>
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
                        <small>(<?php echo esc_html( VPB_Collection::get_element_count( $collection->id ) ); ?> éléments)</small>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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

        if ( ! wp_verify_nonce( $_POST['vpb_product_collections_nonce'], 'vpb_product_collections' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $collection_ids = isset( $_POST['vpb_collections'] ) ? array_map( 'absint', $_POST['vpb_collections'] ) : array();

        VPB_Collection::set_product_collections( $post_id, $collection_ids );
    }
}
