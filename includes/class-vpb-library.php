<?php
/**
 * Element Library Management
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_Library class
 */
class VPB_Library {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_ajax_vpb_get_elements', array( $this, 'ajax_get_elements' ) );
        add_action( 'wp_ajax_nopriv_vpb_get_elements', array( $this, 'ajax_get_elements' ) );
    }

    /**
     * Get all active elements
     *
     * @param string $category Optional category filter.
     * @return array
     */
    public static function get_elements( $category = '' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'vpb_elements';
        $sql   = "SELECT * FROM $table WHERE active = 1";

        if ( ! empty( $category ) ) {
            $sql .= $wpdb->prepare( ' AND category = %s', $category );
        }

        $sql .= ' ORDER BY category ASC, name ASC, color ASC';

        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Get element by ID
     *
     * @param int $id Element ID.
     * @return array|null
     */
    public static function get_element( $id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'vpb_elements';

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ),
            ARRAY_A
        );
    }

    /**
     * Get elements grouped by category
     *
     * @return array
     */
    public static function get_elements_grouped() {
        $elements = self::get_elements();
        $grouped  = array();

        foreach ( $elements as $element ) {
            $category = $element['category'];
            if ( ! isset( $grouped[ $category ] ) ) {
                $grouped[ $category ] = array();
            }
            $grouped[ $category ][] = $element;
        }

        return $grouped;
    }

    /**
     * Get all available colors
     *
     * @return array
     */
    public static function get_available_colors() {
        global $wpdb;

        $table = $wpdb->prefix . 'vpb_elements';

        $colors = $wpdb->get_col(
            "SELECT DISTINCT color FROM $table WHERE active = 1 ORDER BY color ASC"
        );

        return $colors ?: array();
    }

    /**
     * Get available colors for an element
     *
     * @param string $slug Element slug (e.g., 'A').
     * @return array
     */
    public static function get_element_colors( $slug ) {
        global $wpdb;

        $table = $wpdb->prefix . 'vpb_elements';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT color, price, svg_file FROM $table WHERE slug = %s AND active = 1 ORDER BY color ASC",
                $slug
            ),
            ARRAY_A
        );
    }

    /**
     * Add new element
     *
     * @param array $data Element data.
     * @return int|false
     */
    public static function add_element( $data ) {
        global $wpdb;

        $table    = $wpdb->prefix . 'vpb_elements';
        $defaults = array(
            'name'       => '',
            'slug'       => '',
            'category'   => 'letter',
            'svg_file'   => '',
            'color'      => 'default',
            'price'      => 0.00,
            'sort_order' => 0,
            'active'     => 1,
        );

        $data = wp_parse_args( $data, $defaults );

        // Sanitize
        $data['name']       = sanitize_text_field( $data['name'] );
        $data['slug']       = sanitize_title( $data['slug'] );
        $data['category']   = sanitize_key( $data['category'] );
        $data['svg_file']   = esc_url_raw( $data['svg_file'] );
        $data['color']      = sanitize_key( $data['color'] );
        $data['price']      = floatval( $data['price'] );
        $data['sort_order'] = absint( $data['sort_order'] );
        $data['active']     = absint( $data['active'] );

        $result = $wpdb->insert( $table, $data );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update element
     *
     * @param int   $id   Element ID.
     * @param array $data Element data.
     * @return bool
     */
    public static function update_element( $id, $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'vpb_elements';

        // Sanitize
        if ( isset( $data['name'] ) ) {
            $data['name'] = sanitize_text_field( $data['name'] );
        }
        if ( isset( $data['slug'] ) ) {
            $data['slug'] = sanitize_title( $data['slug'] );
        }
        if ( isset( $data['category'] ) ) {
            $data['category'] = sanitize_key( $data['category'] );
        }
        if ( isset( $data['svg_file'] ) ) {
            $data['svg_file'] = esc_url_raw( $data['svg_file'] );
        }
        if ( isset( $data['color'] ) ) {
            $data['color'] = sanitize_key( $data['color'] );
        }
        if ( isset( $data['price'] ) ) {
            $data['price'] = floatval( $data['price'] );
        }
        if ( isset( $data['sort_order'] ) ) {
            $data['sort_order'] = absint( $data['sort_order'] );
        }
        if ( isset( $data['active'] ) ) {
            $data['active'] = absint( $data['active'] );
        }

        return $wpdb->update( $table, $data, array( 'id' => $id ) ) !== false;
    }

    /**
     * Delete element
     *
     * @param int $id Element ID.
     * @return bool
     */
    public static function delete_element( $id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'vpb_elements';

        return $wpdb->delete( $table, array( 'id' => $id ) ) !== false;
    }

    /**
     * AJAX handler for getting elements
     */
    public function ajax_get_elements() {
        check_ajax_referer( 'vpb_nonce', 'nonce' );

        $category = isset( $_GET['category'] ) ? sanitize_key( $_GET['category'] ) : '';
        $elements = self::get_elements_grouped();

        wp_send_json_success( $elements );
    }
}
