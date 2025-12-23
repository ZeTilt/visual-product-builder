<?php
/**
 * VPB Collection Class
 *
 * Handles CRUD operations for collections and product-collection relationships.
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_Collection class
 */
class VPB_Collection {

    /**
     * Get collections table name
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'vpb_collections';
    }

    /**
     * Get product-collections table name
     *
     * @return string
     */
    public static function get_product_collections_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'vpb_product_collections';
    }

    /**
     * Get all collections
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_collections( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'active'   => null,
            'orderby'  => 'sort_order',
            'order'    => 'ASC',
            'limit'    => -1,
            'offset'   => 0,
        );

        $args  = wp_parse_args( $args, $defaults );
        $table = self::get_table_name();

        $where = array( '1=1' );

        if ( null !== $args['active'] ) {
            $where[] = $wpdb->prepare( 'active = %d', $args['active'] );
        }

        $where_clause = implode( ' AND ', $where );

        // Sanitize orderby
        $allowed_orderby = array( 'id', 'name', 'slug', 'sort_order', 'created_at' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'sort_order';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe; $where_clause, $orderby, and $order are sanitized above.
        $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order}";

        if ( $args['limit'] > 0 ) {
            $sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL is built safely above with sanitized values.
		return $wpdb->get_results( $sql );
    }

    /**
     * Get a single collection by ID
     *
     * @param int $id Collection ID.
     * @return object|null
     */
    public static function get_collection( $id ) {
        global $wpdb;

        $table = self::get_table_name();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Get a collection by slug
     *
     * @param string $slug Collection slug.
     * @return object|null
     */
    public static function get_collection_by_slug( $slug ) {
        global $wpdb;

        $table = self::get_table_name();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Check if user can create more collections based on plan limits
     *
     * @return array Array with 'allowed' (bool) and 'message' (string).
     */
    public static function can_create_collection() {
        $limits = vpb_get_plan_limits();
        $max_collections = $limits['collections'];

        // -1 means unlimited
        if ( $max_collections === -1 ) {
            return array(
                'allowed' => true,
                'message' => '',
            );
        }

        // Only count custom collections (not sample ones)
        $current_count = self::get_custom_collection_count();

        if ( $current_count >= $max_collections ) {
            $plan = vpb_get_plan_name();
            $upgrade_url = VPB_PRICING_URL;

            return array(
                'allowed' => false,
                'current' => $current_count,
                'max'     => $max_collections,
                'message' => sprintf(
                    /* translators: 1: current count, 2: max allowed, 3: plan name, 4: upgrade URL */
                    __( 'You have reached the limit of %1$d/%2$d custom collection(s) for your %3$s plan. <a href="%4$s">Upgrade to add more</a>.', 'visual-product-builder' ),
                    $current_count,
                    $max_collections,
                    strtoupper( $plan ),
                    esc_url( $upgrade_url )
                ),
            );
        }

        return array(
            'allowed' => true,
            'current' => $current_count,
            'max'     => $max_collections,
            'message' => '',
        );
    }

    /**
     * Get total collection count
     *
     * @return int
     */
    public static function get_collection_count() {
        global $wpdb;
        $table = self::get_table_name();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Get custom collection count (excludes sample collections)
     *
     * @return int
     */
    public static function get_custom_collection_count() {
        global $wpdb;
        $table = self::get_table_name();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_sample = 0" );
    }

    /**
     * Get sample collection count
     *
     * @return int
     */
    public static function get_sample_collection_count() {
        global $wpdb;
        $table = self::get_table_name();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_sample = 1" );
    }

    /**
     * Check if a collection is a sample collection
     *
     * @param int $collection_id Collection ID.
     * @return bool
     */
    public static function is_sample_collection( $collection_id ) {
        $collection = self::get_collection( $collection_id );
        return $collection && ! empty( $collection->is_sample );
    }

    /**
     * Add a new collection
     *
     * @param array $data Collection data.
     * @return int|false|WP_Error Insert ID, false on failure, or WP_Error if limit reached.
     */
    public static function add_collection( $data ) {
        // Sample collections bypass plan limits
        $is_sample = ! empty( $data['is_sample'] );

        // Check plan limits only for custom collections
        if ( ! $is_sample ) {
            $can_create = self::can_create_collection();
            if ( ! $can_create['allowed'] ) {
                return new WP_Error( 'limit_reached', $can_create['message'] );
            }
        }

        global $wpdb;

        $table = self::get_table_name();

        $defaults = array(
            'name'          => '',
            'slug'          => '',
            'description'   => '',
            'color_hex'     => '#4F9ED9',
            'thumbnail_url' => '',
            'is_sample'     => 0,
            'sort_order'    => 0,
            'active'        => 1,
        );

        $data = wp_parse_args( $data, $defaults );

        // Generate slug if not provided
        if ( empty( $data['slug'] ) ) {
            $data['slug'] = sanitize_title( $data['name'] );
        }

        // Ensure unique slug
        $data['slug'] = self::get_unique_slug( $data['slug'] );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct query.
        $result = $wpdb->insert(
            $table,
            array(
                'name'          => sanitize_text_field( $data['name'] ),
                'slug'          => sanitize_title( $data['slug'] ),
                'description'   => sanitize_textarea_field( $data['description'] ),
                'color_hex'     => sanitize_hex_color( $data['color_hex'] ) ?: '#4F9ED9',
                'thumbnail_url' => esc_url_raw( $data['thumbnail_url'] ),
                'is_sample'     => $is_sample ? 1 : 0,
                'sort_order'    => intval( $data['sort_order'] ),
                'active'        => intval( $data['active'] ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a collection
     *
     * @param int   $id   Collection ID.
     * @param array $data Collection data.
     * @return bool
     */
    public static function update_collection( $id, $data ) {
        global $wpdb;

        $table = self::get_table_name();

        $update_data   = array();
        $update_format = array();

        if ( isset( $data['name'] ) ) {
            $update_data['name'] = sanitize_text_field( $data['name'] );
            $update_format[]     = '%s';
        }

        if ( isset( $data['slug'] ) ) {
            $slug = sanitize_title( $data['slug'] );
            $update_data['slug'] = self::get_unique_slug( $slug, $id );
            $update_format[]     = '%s';
        }

        if ( isset( $data['description'] ) ) {
            $update_data['description'] = sanitize_textarea_field( $data['description'] );
            $update_format[]            = '%s';
        }

        if ( isset( $data['color_hex'] ) ) {
            $update_data['color_hex'] = sanitize_hex_color( $data['color_hex'] ) ?: '#4F9ED9';
            $update_format[]          = '%s';
        }

        if ( isset( $data['thumbnail_url'] ) ) {
            $update_data['thumbnail_url'] = esc_url_raw( $data['thumbnail_url'] );
            $update_format[]              = '%s';
        }

        if ( isset( $data['sort_order'] ) ) {
            $update_data['sort_order'] = intval( $data['sort_order'] );
            $update_format[]           = '%d';
        }

        if ( isset( $data['active'] ) ) {
            $update_data['active'] = intval( $data['active'] );
            $update_format[]       = '%d';
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query.
        $result = $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $id ),
            $update_format,
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Delete a collection
     *
     * @param int $id Collection ID.
     * @return bool
     */
    public static function delete_collection( $id ) {
        global $wpdb;

        $table = self::get_table_name();

        // First, unassign all elements from this collection
        self::unassign_all_elements( $id );

        // Remove all product associations
        self::remove_all_product_associations( $id );

        // Delete the collection
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query.
        $result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

        return $result !== false;
    }

    /**
     * Get unique slug
     *
     * @param string   $slug       Desired slug.
     * @param int|null $exclude_id ID to exclude from check.
     * @return string
     */
    private static function get_unique_slug( $slug, $exclude_id = null ) {
        global $wpdb;

        $table         = self::get_table_name();
        $original_slug = $slug;
        $counter       = 1;

        while ( true ) {
            $sql = $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s", $slug );

            if ( $exclude_id ) {
                $sql .= $wpdb->prepare( ' AND id != %d', $exclude_id );
            }

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL built with prepare().
			$existing = $wpdb->get_var( $sql );

            if ( ! $existing ) {
                break;
            }

            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get elements in a collection
     *
     * @param int   $collection_id Collection ID.
     * @param array $args          Additional query arguments.
     * @return array
     */
    public static function get_elements( $collection_id, $args = array() ) {
        global $wpdb;

        $table_elements = $wpdb->prefix . 'vpb_elements';

        $defaults = array(
            'active'  => 1,
            'orderby' => 'sort_order',
            'order'   => 'ASC',
            'limit'   => 0,
        );

        $args = wp_parse_args( $args, $defaults );

        $where = array(
            $wpdb->prepare( 'collection_id = %d', $collection_id ),
        );

        if ( null !== $args['active'] ) {
            $where[] = $wpdb->prepare( 'active = %d', $args['active'] );
        }

        $where_clause = implode( ' AND ', $where );

        $allowed_orderby = array( 'id', 'name', 'sort_order', 'created_at' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'sort_order';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

        $limit_clause = '';
        if ( $args['limit'] > 0 ) {
            $limit_clause = $wpdb->prepare( ' LIMIT %d', $args['limit'] );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; $where_clause uses prepare(); $orderby/$order sanitized.
        return $wpdb->get_results( "SELECT * FROM {$table_elements} WHERE {$where_clause} ORDER BY {$orderby} {$order}{$limit_clause}" );
    }

    /**
     * Get element count for a collection
     *
     * @param int $collection_id Collection ID.
     * @return int
     */
    public static function get_element_count( $collection_id ) {
        global $wpdb;

        $table_elements = $wpdb->prefix . 'vpb_elements';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_elements} WHERE collection_id = %d",
                $collection_id
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Assign elements to a collection
     *
     * @param int   $collection_id Collection ID.
     * @param array $element_ids   Array of element IDs.
     * @return bool
     */
    public static function assign_elements( $collection_id, $element_ids ) {
        global $wpdb;

        $table_elements = $wpdb->prefix . 'vpb_elements';

        if ( empty( $element_ids ) ) {
            return false;
        }

        $element_ids = array_map( 'absint', $element_ids );
        $element_ids = array_filter( $element_ids ); // Remove zeros

        if ( empty( $element_ids ) ) {
            return false;
        }

        // Build proper prepared statement with placeholders for IN clause
        $placeholders = implode( ', ', array_fill( 0, count( $element_ids ), '%d' ) );
        $params       = array_merge( array( absint( $collection_id ) ), $element_ids );

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; placeholders are sanitized.
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table_elements} SET collection_id = %d WHERE id IN ($placeholders)",
                $params
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        return $result !== false;
    }

    /**
     * Unassign all elements from a collection
     *
     * @param int $collection_id Collection ID.
     * @return bool
     */
    public static function unassign_all_elements( $collection_id ) {
        global $wpdb;

        $table_elements = $wpdb->prefix . 'vpb_elements';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query.
        $result = $wpdb->update(
            $table_elements,
            array( 'collection_id' => null ),
            array( 'collection_id' => $collection_id ),
            array( '%d' ),
            array( '%d' )
        );

        return $result !== false;
    }

    // ========================================
    // Product-Collection Relationships
    // ========================================

    /**
     * Get collections for a product
     *
     * @param int $product_id Product ID.
     * @return array
     */
    public static function get_product_collections( $product_id ) {
        global $wpdb;

        $table            = self::get_table_name();
        $table_rel        = self::get_product_collections_table_name();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names from $wpdb->prefix are safe; custom tables.
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, pc.sort_order as product_sort_order
                FROM {$table} c
                INNER JOIN {$table_rel} pc ON c.id = pc.collection_id
                WHERE pc.product_id = %d AND c.active = 1
                ORDER BY pc.sort_order ASC, c.sort_order ASC",
                $product_id
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Get collection IDs for a product
     *
     * @param int $product_id Product ID.
     * @return array
     */
    public static function get_product_collection_ids( $product_id ) {
        global $wpdb;

        $table_rel = self::get_product_collections_table_name();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT collection_id FROM {$table_rel} WHERE product_id = %d ORDER BY sort_order ASC",
                $product_id
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Set collections for a product (replaces existing)
     *
     * @param int   $product_id     Product ID.
     * @param array $collection_ids Array of collection IDs.
     * @return bool
     */
    public static function set_product_collections( $product_id, $collection_ids ) {
        global $wpdb;

        $table_rel = self::get_product_collections_table_name();

        // Remove existing associations
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query.
        $wpdb->delete( $table_rel, array( 'product_id' => $product_id ), array( '%d' ) );

        if ( empty( $collection_ids ) ) {
            return true;
        }

        // Add new associations
        $sort_order = 0;
        foreach ( $collection_ids as $collection_id ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct query.
            $wpdb->insert(
                $table_rel,
                array(
                    'product_id'    => intval( $product_id ),
                    'collection_id' => intval( $collection_id ),
                    'sort_order'    => $sort_order,
                ),
                array( '%d', '%d', '%d' )
            );
            $sort_order++;
        }

        return true;
    }

    /**
     * Add a collection to a product
     *
     * @param int $product_id    Product ID.
     * @param int $collection_id Collection ID.
     * @return bool
     */
    public static function add_product_collection( $product_id, $collection_id ) {
        global $wpdb;

        $table_rel = self::get_product_collections_table_name();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        // Check if already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_rel} WHERE product_id = %d AND collection_id = %d",
                $product_id,
                $collection_id
            )
        );

        if ( $exists ) {
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return true;
        }

        // Get next sort order
        $max_sort = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(sort_order) FROM {$table_rel} WHERE product_id = %d",
                $product_id
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct query.
        $result = $wpdb->insert(
            $table_rel,
            array(
                'product_id'    => intval( $product_id ),
                'collection_id' => intval( $collection_id ),
                'sort_order'    => intval( $max_sort ) + 1,
            ),
            array( '%d', '%d', '%d' )
        );

        return $result !== false;
    }

    /**
     * Remove a collection from a product
     *
     * @param int $product_id    Product ID.
     * @param int $collection_id Collection ID.
     * @return bool
     */
    public static function remove_product_collection( $product_id, $collection_id ) {
        global $wpdb;

        $table_rel = self::get_product_collections_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query.
        $result = $wpdb->delete(
            $table_rel,
            array(
                'product_id'    => $product_id,
                'collection_id' => $collection_id,
            ),
            array( '%d', '%d' )
        );

        return $result !== false;
    }

    /**
     * Remove all product associations for a collection
     *
     * @param int $collection_id Collection ID.
     * @return bool
     */
    public static function remove_all_product_associations( $collection_id ) {
        global $wpdb;

        $table_rel = self::get_product_collections_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query.
        $result = $wpdb->delete(
            $table_rel,
            array( 'collection_id' => $collection_id ),
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Get products using a collection
     *
     * @param int $collection_id Collection ID.
     * @return array
     */
    public static function get_products_using_collection( $collection_id ) {
        global $wpdb;

        $table_rel = self::get_product_collections_table_name();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix is safe; custom table.
        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT product_id FROM {$table_rel} WHERE collection_id = %d",
                $collection_id
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    // ========================================
    // Utility Methods
    // ========================================

    /**
     * Get collections as dropdown options
     *
     * @param bool $include_empty Include an empty option.
     * @return array
     */
    public static function get_dropdown_options( $include_empty = true ) {
        $collections = self::get_collections( array( 'active' => 1 ) );

        $options = array();

        if ( $include_empty ) {
            $options[''] = __( '-- Aucune collection --', 'visual-product-builder' );
        }

        foreach ( $collections as $collection ) {
            $options[ $collection->id ] = $collection->name;
        }

        return $options;
    }
}
