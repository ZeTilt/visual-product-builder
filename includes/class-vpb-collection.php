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

        $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order}";

        if ( $args['limit'] > 0 ) {
            $sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
        }

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

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
        );
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

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug )
        );
    }

    /**
     * Add a new collection
     *
     * @param array $data Collection data.
     * @return int|false Insert ID or false on failure.
     */
    public static function add_collection( $data ) {
        global $wpdb;

        $table = self::get_table_name();

        $defaults = array(
            'name'          => '',
            'slug'          => '',
            'description'   => '',
            'color_hex'     => '#4F9ED9',
            'thumbnail_url' => '',
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

        $result = $wpdb->insert(
            $table,
            array(
                'name'          => sanitize_text_field( $data['name'] ),
                'slug'          => sanitize_title( $data['slug'] ),
                'description'   => sanitize_textarea_field( $data['description'] ),
                'color_hex'     => sanitize_hex_color( $data['color_hex'] ) ?: '#4F9ED9',
                'thumbnail_url' => esc_url_raw( $data['thumbnail_url'] ),
                'sort_order'    => intval( $data['sort_order'] ),
                'active'        => intval( $data['active'] ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
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
            'orderby' => 'name',
            'order'   => 'ASC',
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

        return $wpdb->get_results(
            "SELECT * FROM {$table_elements} WHERE {$where_clause} ORDER BY {$orderby} {$order}"
        );
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

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_elements} WHERE collection_id = %d",
                $collection_id
            )
        );
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

        $element_ids = array_map( 'intval', $element_ids );
        $ids_string  = implode( ',', $element_ids );

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table_elements} SET collection_id = %d WHERE id IN ({$ids_string})",
                $collection_id
            )
        );

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

        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT collection_id FROM {$table_rel} WHERE product_id = %d ORDER BY sort_order ASC",
                $product_id
            )
        );
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
        $wpdb->delete( $table_rel, array( 'product_id' => $product_id ), array( '%d' ) );

        if ( empty( $collection_ids ) ) {
            return true;
        }

        // Add new associations
        $sort_order = 0;
        foreach ( $collection_ids as $collection_id ) {
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

        // Check if already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_rel} WHERE product_id = %d AND collection_id = %d",
                $product_id,
                $collection_id
            )
        );

        if ( $exists ) {
            return true;
        }

        // Get next sort order
        $max_sort = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(sort_order) FROM {$table_rel} WHERE product_id = %d",
                $product_id
            )
        );

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

        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT product_id FROM {$table_rel} WHERE collection_id = %d",
                $collection_id
            )
        );
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
