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
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe, constructed from $wpdb->prefix.
        $sql   = "SELECT * FROM $table WHERE active = 1";

        if ( ! empty( $category ) ) {
            $sql .= $wpdb->prepare( ' AND category = %s', $category );
        }

        $sql .= ' ORDER BY category ASC, name ASC, color ASC';

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL is built safely with table prefix and prepare().
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

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        $colors = $wpdb->get_col( "SELECT DISTINCT color FROM $table WHERE active = 1 ORDER BY color ASC" );

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

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT color, price, svg_file FROM $table WHERE slug = %s AND active = 1 ORDER BY color ASC",
                $slug
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Check if user can add more elements to a collection based on plan limits
     *
     * @param int|null $collection_id Collection ID (null for no collection).
     * @return array Array with 'allowed' (bool) and 'message' (string).
     */
    public static function can_add_element( $collection_id = null ) {
        $limits = vpb_get_plan_limits();
        $max_elements = $limits['elements_per_collection'];

        // -1 means unlimited
        if ( $max_elements === -1 ) {
            return array(
                'allowed' => true,
                'message' => '',
            );
        }

        // Count elements in the collection
        $current_count = self::get_element_count_in_collection( $collection_id );

        if ( $current_count >= $max_elements ) {
            $plan = vpb_get_plan_name();
            $upgrade_url = VPB_PRICING_URL;

            return array(
                'allowed' => false,
                'current' => $current_count,
                'max'     => $max_elements,
                'message' => sprintf(
                    /* translators: 1: current count, 2: max elements, 3: plan name, 4: upgrade URL */
                    __( 'You have reached the limit of %1$d/%2$d elements per collection for your %3$s plan. <a href="%4$s">Upgrade to add more</a>.', 'visual-product-builder' ),
                    $current_count,
                    $max_elements,
                    strtoupper( $plan ),
                    esc_url( $upgrade_url )
                ),
            );
        }

        return array(
            'allowed' => true,
            'current' => $current_count,
            'max'     => $max_elements,
            'message' => '',
        );
    }

    /**
     * Get element count in a specific collection
     *
     * @param int|null $collection_id Collection ID (null for elements without collection).
     * @return int
     */
    public static function get_element_count_in_collection( $collection_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vpb_elements';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        if ( null === $collection_id || '' === $collection_id ) {
            $result = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE collection_id IS NULL" );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return $result;
        }

        $result = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE collection_id = %d", $collection_id )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $result;
    }

    /**
     * Add new element
     *
     * @param array $data Element data.
     * @return int|false|WP_Error Insert ID, false on failure, or WP_Error if limit reached.
     */
    public static function add_element( $data ) {
        // Check plan limits
        $collection_id = isset( $data['collection_id'] ) ? $data['collection_id'] : null;
        $can_add = self::can_add_element( $collection_id );
        if ( ! $can_add['allowed'] ) {
            return new WP_Error( 'limit_reached', $can_add['message'] );
        }

        global $wpdb;

        $table    = $wpdb->prefix . 'vpb_elements';
        $defaults = array(
            'name'          => '',
            'slug'          => '',
            'category'      => 'letter',
            'svg_file'      => '',
            'color'         => 'default',
            'color_hex'     => '#4F9ED9',
            'collection_id' => null,
            'price'         => 0.00,
            'sort_order'    => 0,
            'active'        => 1,
        );

        $data = wp_parse_args( $data, $defaults );

        // Sanitize
        $insert_data = array(
            'name'       => sanitize_text_field( $data['name'] ),
            'slug'       => sanitize_title( $data['slug'] ),
            'category'   => sanitize_key( $data['category'] ),
            'svg_file'   => esc_url_raw( $data['svg_file'] ),
            'color'      => sanitize_text_field( $data['color'] ),
            'color_hex'  => sanitize_hex_color( $data['color_hex'] ) ?: '#4F9ED9',
            'price'      => floatval( $data['price'] ),
            'sort_order' => absint( $data['sort_order'] ),
            'active'     => absint( $data['active'] ),
        );

        // Handle collection_id (can be null)
        if ( ! empty( $data['collection_id'] ) ) {
            $insert_data['collection_id'] = absint( $data['collection_id'] );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct query.
        $result = $wpdb->insert( $table, $insert_data );

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

        $table       = $wpdb->prefix . 'vpb_elements';
        $update_data = array();

        // Sanitize each field
        if ( isset( $data['name'] ) ) {
            $update_data['name'] = sanitize_text_field( $data['name'] );
        }
        if ( isset( $data['slug'] ) ) {
            $update_data['slug'] = sanitize_title( $data['slug'] );
        }
        if ( isset( $data['category'] ) ) {
            $update_data['category'] = sanitize_key( $data['category'] );
        }
        if ( isset( $data['svg_file'] ) ) {
            $update_data['svg_file'] = esc_url_raw( $data['svg_file'] );
        }
        if ( isset( $data['color'] ) ) {
            $update_data['color'] = sanitize_text_field( $data['color'] );
        }
        if ( isset( $data['color_hex'] ) ) {
            $update_data['color_hex'] = sanitize_hex_color( $data['color_hex'] ) ?: '#4F9ED9';
        }
        if ( array_key_exists( 'collection_id', $data ) ) {
            $update_data['collection_id'] = ! empty( $data['collection_id'] ) ? absint( $data['collection_id'] ) : null;
        }
        if ( isset( $data['price'] ) ) {
            $update_data['price'] = floatval( $data['price'] );
        }
        if ( isset( $data['sort_order'] ) ) {
            $update_data['sort_order'] = absint( $data['sort_order'] );
        }
        if ( isset( $data['active'] ) ) {
            $update_data['active'] = absint( $data['active'] );
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query.
        return $wpdb->update( $table, $update_data, array( 'id' => $id ) ) !== false;
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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query.
        return $wpdb->delete( $table, array( 'id' => $id ) ) !== false;
    }

    /**
     * AJAX handler for getting elements
     */
    public function ajax_get_elements() {
        check_ajax_referer( 'vpb_nonce', 'nonce' );

        $category = isset( $_GET['category'] ) ? sanitize_key( wp_unslash( $_GET['category'] ) ) : '';
        $elements = self::get_elements_grouped();

        wp_send_json_success( $elements );
    }
}
