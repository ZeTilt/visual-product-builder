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
     * Get sample elements data
     *
     * @return array
     */
    public static function get_elements() {
        $elements = array();
        $letters  = range( 'A', 'Z' );

        // Blue letters (PNG)
        foreach ( $letters as $index => $letter ) {
            $elements[] = array(
                'name'       => $letter,
                'slug'       => strtolower( $letter ),
                'category'   => 'letter',
                'color'      => 'blue',
                'svg_file'   => VPB_PLUGIN_URL . 'assets/elements/blue/' . $letter . '.png',
                'price'      => 2.00,
                'sort_order' => $index,
                'active'     => 1,
            );
        }

        // Beige letters (JPG)
        foreach ( $letters as $index => $letter ) {
            $elements[] = array(
                'name'       => $letter,
                'slug'       => strtolower( $letter ),
                'category'   => 'letter',
                'color'      => 'beige',
                'svg_file'   => VPB_PLUGIN_URL . 'assets/elements/beige/' . $letter . '.jpg',
                'price'      => 2.00,
                'sort_order' => $index,
                'active'     => 1,
            );
        }

        // Numbers (0-9) - placeholder, using blue letter style
        for ( $i = 0; $i <= 9; $i++ ) {
            $elements[] = array(
                'name'       => (string) $i,
                'slug'       => 'num-' . $i,
                'category'   => 'number',
                'color'      => 'blue',
                'svg_file'   => '', // To be added later
                'price'      => 2.00,
                'sort_order' => $i,
                'active'     => 0, // Inactive until images are provided
            );
        }

        // Sample shapes - placeholder
        $shapes = array(
            array( 'name' => 'Heart', 'slug' => 'heart', 'emoji' => '❤️' ),
            array( 'name' => 'Star', 'slug' => 'star', 'emoji' => '⭐' ),
            array( 'name' => 'Moon', 'slug' => 'moon', 'emoji' => '🌙' ),
            array( 'name' => 'Crown', 'slug' => 'crown', 'emoji' => '👑' ),
            array( 'name' => 'Flower', 'slug' => 'flower', 'emoji' => '🌸' ),
        );

        foreach ( $shapes as $index => $shape ) {
            $elements[] = array(
                'name'       => $shape['name'],
                'slug'       => $shape['slug'],
                'category'   => 'shape',
                'color'      => 'default',
                'svg_file'   => '', // To be added later
                'price'      => 3.00,
                'sort_order' => $index,
                'active'     => 0, // Inactive until images are provided
            );
        }

        return $elements;
    }

    /**
     * Import sample elements into database
     *
     * @return int Number of elements imported
     */
    public static function import() {
        $elements = self::get_elements();
        $imported = 0;

        foreach ( $elements as $element ) {
            // Skip if no image
            if ( empty( $element['svg_file'] ) ) {
                continue;
            }

            // Check if element already exists (same name + color)
            global $wpdb;
            $table    = $wpdb->prefix . 'vpb_elements';
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table WHERE name = %s AND color = %s",
                    $element['name'],
                    $element['color']
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
        $table = $wpdb->prefix . 'vpb_elements';
        return $wpdb->query( "TRUNCATE TABLE $table" ) !== false;
    }
}
