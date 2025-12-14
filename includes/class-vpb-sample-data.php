<?php
/**
 * Sample Data Import
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_Sample_Data class
 */
class VPB_Sample_Data {

    /**
     * Available colors with hex values
     */
    private static $colors = array(
        'bleu-ciel' => '#4F9ED9',
        'rose'      => '#E91E63',
        'vert'      => '#4CAF50',
        'orange'    => '#FF9800',
        'violet'    => '#9C27B0',
        'rouge'     => '#F44336',
        'jaune'     => '#FFEB3B',
        'turquoise' => '#00BCD4',
    );

    /**
     * Get sample collections data
     *
     * @return array
     */
    public static function get_collections() {
        $collections = array();

        // Create one collection per color for letters
        foreach ( self::$colors as $color_slug => $color_hex ) {
            $color_name = ucfirst( str_replace( '-', ' ', $color_slug ) );
            $collections[] = array(
                'name'        => 'Alphabet ' . $color_name,
                'slug'        => 'alphabet-' . $color_slug,
                'description' => 'Lettres de A à Z en ' . strtolower( $color_name ),
                'color_hex'   => $color_hex,
                'sort_order'  => count( $collections ) + 1,
                'active'      => 1,
            );
        }

        // Create one collection per color for numbers
        foreach ( self::$colors as $color_slug => $color_hex ) {
            $color_name = ucfirst( str_replace( '-', ' ', $color_slug ) );
            $collections[] = array(
                'name'        => 'Chiffres ' . $color_name,
                'slug'        => 'chiffres-' . $color_slug,
                'description' => 'Chiffres de 0 à 9 en ' . strtolower( $color_name ),
                'color_hex'   => $color_hex,
                'sort_order'  => count( $collections ) + 1,
                'active'      => 1,
            );
        }

        // Create one collection per color for symbols
        foreach ( self::$colors as $color_slug => $color_hex ) {
            $color_name = ucfirst( str_replace( '-', ' ', $color_slug ) );
            $collections[] = array(
                'name'        => 'Symboles ' . $color_name,
                'slug'        => 'symboles-' . $color_slug,
                'description' => 'Symboles décoratifs en ' . strtolower( $color_name ),
                'color_hex'   => $color_hex,
                'sort_order'  => count( $collections ) + 1,
                'active'      => 1,
            );
        }

        return $collections;
    }

    /**
     * Get sample elements data
     *
     * @param array $collection_ids Map of slug => id.
     * @return array
     */
    public static function get_elements( $collection_ids = array() ) {
        $elements = array();
        $letters  = range( 'A', 'Z' );

        // Letters in all colors
        foreach ( self::$colors as $color_slug => $color_hex ) {
            $collection_key = 'alphabet-' . $color_slug;
            foreach ( $letters as $index => $letter ) {
                $elements[] = array(
                    'name'          => $letter,
                    'slug'          => 'letter-' . strtolower( $letter ) . '-' . $color_slug,
                    'category'      => 'letter',
                    'color'         => $color_slug,
                    'color_hex'     => $color_hex,
                    'svg_file'      => VPB_PLUGIN_URL . 'assets/svg/letters/letter-' . $letter . '.svg',
                    'collection_id' => isset( $collection_ids[ $collection_key ] ) ? $collection_ids[ $collection_key ] : null,
                    'price'         => 0.00,
                    'sort_order'    => $index,
                    'active'        => 1,
                );
            }
        }

        // Numbers in all colors
        foreach ( self::$colors as $color_slug => $color_hex ) {
            $collection_key = 'chiffres-' . $color_slug;
            for ( $i = 0; $i <= 9; $i++ ) {
                $elements[] = array(
                    'name'          => (string) $i,
                    'slug'          => 'number-' . $i . '-' . $color_slug,
                    'category'      => 'number',
                    'color'         => $color_slug,
                    'color_hex'     => $color_hex,
                    'svg_file'      => VPB_PLUGIN_URL . 'assets/svg/numbers/number-' . $i . '.svg',
                    'collection_id' => isset( $collection_ids[ $collection_key ] ) ? $collection_ids[ $collection_key ] : null,
                    'price'         => 0.00,
                    'sort_order'    => $i,
                    'active'        => 1,
                );
            }
        }

        // Symbols
        $symbols = array(
            array( 'name' => 'Coeur',    'slug' => 'heart',    'file' => 'symbol-heart.svg' ),
            array( 'name' => 'Etoile',   'slug' => 'star',     'file' => 'symbol-star.svg' ),
            array( 'name' => 'Lune',     'slug' => 'moon',     'file' => 'symbol-moon.svg' ),
            array( 'name' => 'Soleil',   'slug' => 'sun',      'file' => 'symbol-sun.svg' ),
            array( 'name' => 'Fleur',    'slug' => 'flower',   'file' => 'symbol-flower.svg' ),
            array( 'name' => 'Nuage',    'slug' => 'cloud',    'file' => 'symbol-cloud.svg' ),
            array( 'name' => 'Diamant',  'slug' => 'diamond',  'file' => 'symbol-diamond.svg' ),
            array( 'name' => 'Cercle',   'slug' => 'circle',   'file' => 'symbol-circle.svg' ),
            array( 'name' => 'Carre',    'slug' => 'square',   'file' => 'symbol-square.svg' ),
            array( 'name' => 'Triangle', 'slug' => 'triangle', 'file' => 'symbol-triangle.svg' ),
        );

        // Symbols in all colors
        foreach ( self::$colors as $color_slug => $color_hex ) {
            $collection_key = 'symboles-' . $color_slug;
            foreach ( $symbols as $index => $symbol ) {
                $elements[] = array(
                    'name'          => $symbol['name'],
                    'slug'          => 'symbol-' . $symbol['slug'] . '-' . $color_slug,
                    'category'      => 'symbol',
                    'color'         => $color_slug,
                    'color_hex'     => $color_hex,
                    'svg_file'      => VPB_PLUGIN_URL . 'assets/svg/symbols/' . $symbol['file'],
                    'collection_id' => isset( $collection_ids[ $collection_key ] ) ? $collection_ids[ $collection_key ] : null,
                    'price'         => 0.00,
                    'sort_order'    => $index,
                    'active'        => 1,
                );
            }
        }

        return $elements;
    }

    /**
     * Import sample collections into database
     *
     * @return array Map of slug => id
     */
    public static function import_collections() {
        $collections    = self::get_collections();
        $collection_ids = array();

        foreach ( $collections as $collection ) {
            // Check if collection already exists
            $existing = VPB_Collection::get_collection_by_slug( $collection['slug'] );

            if ( $existing ) {
                $collection_ids[ $collection['slug'] ] = $existing->id;
                continue;
            }

            $id = VPB_Collection::add_collection( $collection );
            if ( $id ) {
                $collection_ids[ $collection['slug'] ] = $id;
            }
        }

        return $collection_ids;
    }

    /**
     * Import sample elements into database
     *
     * @param array $collection_ids Optional collection IDs map.
     * @return int Number of elements imported
     */
    public static function import_elements( $collection_ids = array() ) {
        $elements = self::get_elements( $collection_ids );
        $imported = 0;

        foreach ( $elements as $element ) {
            // Skip if no image
            if ( empty( $element['svg_file'] ) ) {
                continue;
            }

            // Check if element already exists (same slug)
            global $wpdb;
            $table    = $wpdb->prefix . 'vpb_elements';
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table WHERE slug = %s",
                    $element['slug']
                )
            );

            if ( $existing ) {
                continue; // Skip if already exists
            }

            $result = VPB_Library::add_element( $element );
            if ( $result ) {
                $imported++;
            }
        }

        return $imported;
    }

    /**
     * Import all sample data (collections + elements)
     *
     * @return array Import results
     */
    public static function import() {
        // First import collections
        $collection_ids = self::import_collections();

        // Then import elements with collection references
        $imported_elements = self::import_elements( $collection_ids );

        return array(
            'collections' => count( $collection_ids ),
            'elements'    => $imported_elements,
        );
    }

    /**
     * Check if sample data has been imported
     *
     * @return bool
     */
    public static function is_imported() {
        global $wpdb;
        $table = $wpdb->prefix . 'vpb_elements';
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        return $count > 0;
    }

    /**
     * Clear all sample data
     *
     * @return bool
     */
    public static function clear() {
        global $wpdb;

        // Clear elements
        $elements_table = $wpdb->prefix . 'vpb_elements';
        $wpdb->query( "TRUNCATE TABLE $elements_table" );

        // Clear collections
        $collections_table = $wpdb->prefix . 'vpb_collections';
        $wpdb->query( "TRUNCATE TABLE $collections_table" );

        // Clear product-collection relationships
        $product_collections_table = $wpdb->prefix . 'vpb_product_collections';
        $wpdb->query( "TRUNCATE TABLE $product_collections_table" );

        return true;
    }

    /**
     * Get available color options for admin UI
     *
     * @return array
     */
    public static function get_color_options() {
        return self::$colors;
    }
}
