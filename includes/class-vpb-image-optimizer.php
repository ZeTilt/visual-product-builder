<?php
/**
 * VPB Image Optimizer Class
 *
 * Handles image optimization, WebP generation, and SVG sanitization.
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * VPB_Image_Optimizer class
 */
class VPB_Image_Optimizer {

    /**
     * Constructor
     */
    public function __construct() {
        // Register custom image sizes
        add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ), 20 );

        // Add size names to media library
        add_filter( 'image_size_names_choose', array( $this, 'add_image_size_names' ) );

        // Optimize images on upload
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'optimize_on_upload' ), 10, 2 );

        // Set compression quality
        add_filter( 'wp_editor_set_quality', array( $this, 'set_compression_quality' ), 10, 2 );

        // Allow SVG uploads for admins
        add_filter( 'upload_mimes', array( $this, 'allow_svg_upload' ) );

        // Sanitize SVG on upload
        add_filter( 'wp_handle_upload_prefilter', array( $this, 'sanitize_svg_upload' ) );

        // Fix SVG display in media library
        add_filter( 'wp_prepare_attachment_for_js', array( $this, 'fix_svg_media_display' ), 10, 3 );
    }

    /**
     * Register custom image sizes for VPB elements
     */
    public function register_image_sizes() {
        // Remove default vpb-element size if it exists
        remove_image_size( 'vpb-element' );

        // Add optimized sizes
        add_image_size( 'vpb-element-display', 100, 100, false );   // Frontend display
        add_image_size( 'vpb-element-admin', 150, 150, false );     // Admin preview
        add_image_size( 'vpb-element-2x', 200, 200, false );        // Retina display
    }

    /**
     * Add image size names to media library
     *
     * @param array $sizes Existing sizes.
     * @return array
     */
    public function add_image_size_names( $sizes ) {
        return array_merge( $sizes, array(
            'vpb-element-display' => __( 'VPB Display (100x100)', 'visual-product-builder' ),
            'vpb-element-admin'   => __( 'VPB Admin (150x150)', 'visual-product-builder' ),
            'vpb-element-2x'      => __( 'VPB Retina (200x200)', 'visual-product-builder' ),
        ) );
    }

    /**
     * Set compression quality based on image size
     *
     * @param int    $quality Default quality.
     * @param string $mime_type Image mime type.
     * @return int
     */
    public function set_compression_quality( $quality, $mime_type ) {
        // Higher compression for VPB elements
        if ( doing_filter( 'wp_generate_attachment_metadata' ) ) {
            return 80;
        }
        return $quality;
    }

    /**
     * Optimize images on upload
     *
     * @param array $metadata Attachment metadata.
     * @param int   $attachment_id Attachment ID.
     * @return array
     */
    public function optimize_on_upload( $metadata, $attachment_id ) {
        // Check if WebP is supported
        if ( ! function_exists( 'imagewebp' ) ) {
            return $metadata;
        }

        $file = get_attached_file( $attachment_id );
        if ( ! $file ) {
            return $metadata;
        }

        $mime_type = get_post_mime_type( $attachment_id );

        // Only process images (not SVG)
        if ( ! in_array( $mime_type, array( 'image/jpeg', 'image/png' ), true ) ) {
            return $metadata;
        }

        // Generate WebP for original
        $this->generate_webp( $file, $mime_type );

        // Generate WebP for all sizes
        if ( ! empty( $metadata['sizes'] ) ) {
            $upload_dir = wp_upload_dir();
            $base_dir   = trailingslashit( dirname( $file ) );

            foreach ( $metadata['sizes'] as $size_name => $size_data ) {
                $size_file = $base_dir . $size_data['file'];
                $size_mime = $size_data['mime-type'] ?? $mime_type;

                if ( in_array( $size_mime, array( 'image/jpeg', 'image/png' ), true ) ) {
                    $this->generate_webp( $size_file, $size_mime );
                }
            }
        }

        return $metadata;
    }

    /**
     * Generate WebP version of an image
     *
     * @param string $file Full path to image file.
     * @param string $mime_type Image mime type.
     * @return bool
     */
    private function generate_webp( $file, $mime_type ) {
        if ( ! file_exists( $file ) ) {
            return false;
        }

        $webp_file = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );

        // Skip if WebP already exists
        if ( file_exists( $webp_file ) ) {
            return true;
        }

        // Create image resource
        switch ( $mime_type ) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg( $file );
                break;
            case 'image/png':
                $image = imagecreatefrompng( $file );
                // Preserve transparency
                imagepalettetotruecolor( $image );
                imagealphablending( $image, true );
                imagesavealpha( $image, true );
                break;
            default:
                return false;
        }

        if ( ! $image ) {
            return false;
        }

        // Save as WebP with 80% quality
        $result = imagewebp( $image, $webp_file, 80 );
        imagedestroy( $image );

        return $result;
    }

    /**
     * Allow SVG uploads for administrators
     *
     * @param array $mimes Allowed mime types.
     * @return array
     */
    public function allow_svg_upload( $mimes ) {
        if ( current_user_can( 'manage_options' ) ) {
            $mimes['svg']  = 'image/svg+xml';
            $mimes['svgz'] = 'image/svg+xml';
        }
        return $mimes;
    }

    /**
     * Sanitize SVG on upload
     *
     * @param array $file File data.
     * @return array
     */
    public function sanitize_svg_upload( $file ) {
        if ( ! isset( $file['type'] ) || $file['type'] !== 'image/svg+xml' ) {
            return $file;
        }

        // Only allow admins
        if ( ! current_user_can( 'manage_options' ) ) {
            $file['error'] = __( 'Seuls les administrateurs peuvent uploader des SVG.', 'visual-product-builder' );
            return $file;
        }

        // Read SVG content
        $svg_content = file_get_contents( $file['tmp_name'] );

        if ( ! $svg_content ) {
            $file['error'] = __( 'Impossible de lire le fichier SVG.', 'visual-product-builder' );
            return $file;
        }

        // Sanitize SVG
        $clean_svg = $this->sanitize_svg( $svg_content );

        if ( ! $clean_svg ) {
            $file['error'] = __( 'Le fichier SVG contient du code potentiellement dangereux.', 'visual-product-builder' );
            return $file;
        }

        // Write sanitized content back
        file_put_contents( $file['tmp_name'], $clean_svg );

        return $file;
    }

    /**
     * Sanitize SVG content
     *
     * Removes potentially dangerous elements and attributes.
     *
     * @param string $svg_content SVG content.
     * @return string|false Sanitized SVG or false on failure.
     */
    public function sanitize_svg( $svg_content ) {
        // Dangerous elements to remove
        $dangerous_elements = array(
            'script',
            'use',
            'foreignObject',
            'set',
            'animate',
            'animateMotion',
            'animateTransform',
        );

        // Dangerous attributes to remove
        $dangerous_attributes = array(
            'onload',
            'onclick',
            'onmouseover',
            'onmouseout',
            'onmousemove',
            'onfocus',
            'onblur',
            'onerror',
            'onabort',
            'onchange',
            'onsubmit',
            'onreset',
            'onkeydown',
            'onkeypress',
            'onkeyup',
        );

        // Load SVG as DOMDocument
        $dom = new DOMDocument();
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = true;

        // Suppress errors for malformed SVG
        libxml_use_internal_errors( true );
        $loaded = $dom->loadXML( $svg_content );
        libxml_clear_errors();

        if ( ! $loaded ) {
            return false;
        }

        // Check root element is SVG
        $root = $dom->documentElement;
        if ( ! $root || strtolower( $root->nodeName ) !== 'svg' ) {
            return false;
        }

        // Remove dangerous elements
        foreach ( $dangerous_elements as $tag ) {
            $elements = $dom->getElementsByTagName( $tag );
            // Loop backwards to safely remove
            for ( $i = $elements->length - 1; $i >= 0; $i-- ) {
                $element = $elements->item( $i );
                $element->parentNode->removeChild( $element );
            }
        }

        // Remove dangerous attributes from all elements
        $xpath = new DOMXPath( $dom );
        $all_elements = $xpath->query( '//*' );

        foreach ( $all_elements as $element ) {
            // Remove event handlers
            foreach ( $dangerous_attributes as $attr ) {
                if ( $element->hasAttribute( $attr ) ) {
                    $element->removeAttribute( $attr );
                }
            }

            // Remove href with javascript:
            if ( $element->hasAttribute( 'href' ) ) {
                $href = $element->getAttribute( 'href' );
                if ( preg_match( '/^\s*javascript:/i', $href ) ) {
                    $element->removeAttribute( 'href' );
                }
            }

            // Remove xlink:href with javascript:
            if ( $element->hasAttributeNS( 'http://www.w3.org/1999/xlink', 'href' ) ) {
                $href = $element->getAttributeNS( 'http://www.w3.org/1999/xlink', 'href' );
                if ( preg_match( '/^\s*javascript:/i', $href ) ) {
                    $element->removeAttributeNS( 'http://www.w3.org/1999/xlink', 'href' );
                }
            }
        }

        // Remove XML declaration and doctype
        $output = $dom->saveXML( $dom->documentElement );

        return $output;
    }

    /**
     * Fix SVG display in media library
     *
     * @param array   $response Attachment response.
     * @param WP_Post $attachment Attachment post.
     * @param array   $meta Attachment metadata.
     * @return array
     */
    public function fix_svg_media_display( $response, $attachment, $meta ) {
        if ( $response['mime'] !== 'image/svg+xml' ) {
            return $response;
        }

        // Get SVG dimensions from file
        $svg_path = get_attached_file( $attachment->ID );

        if ( $svg_path && file_exists( $svg_path ) ) {
            $svg_content = file_get_contents( $svg_path );
            $dimensions  = $this->get_svg_dimensions( $svg_content );

            if ( $dimensions ) {
                $response['width']  = $dimensions['width'];
                $response['height'] = $dimensions['height'];

                // Set sizes for media library display
                $response['sizes'] = array(
                    'full' => array(
                        'url'         => $response['url'],
                        'width'       => $dimensions['width'],
                        'height'      => $dimensions['height'],
                        'orientation' => $dimensions['width'] > $dimensions['height'] ? 'landscape' : 'portrait',
                    ),
                );
            }
        }

        return $response;
    }

    /**
     * Get SVG dimensions from content
     *
     * @param string $svg_content SVG content.
     * @return array|false Array with width/height or false.
     */
    public function get_svg_dimensions( $svg_content ) {
        // Try to get viewBox
        if ( preg_match( '/viewBox=["\']([^"\']+)["\']/', $svg_content, $matches ) ) {
            $viewbox = explode( ' ', preg_replace( '/,/', ' ', $matches[1] ) );
            $viewbox = array_map( 'floatval', array_filter( $viewbox, 'strlen' ) );

            if ( count( $viewbox ) >= 4 ) {
                return array(
                    'width'  => $viewbox[2],
                    'height' => $viewbox[3],
                );
            }
        }

        // Try to get width/height attributes
        $width  = 0;
        $height = 0;

        if ( preg_match( '/\swidth=["\']([0-9.]+)/', $svg_content, $matches ) ) {
            $width = floatval( $matches[1] );
        }

        if ( preg_match( '/\sheight=["\']([0-9.]+)/', $svg_content, $matches ) ) {
            $height = floatval( $matches[1] );
        }

        if ( $width > 0 && $height > 0 ) {
            return array(
                'width'  => $width,
                'height' => $height,
            );
        }

        return false;
    }

    /**
     * Get WebP URL for an image
     *
     * @param string $url Original image URL.
     * @return string WebP URL if exists, original otherwise.
     */
    public static function get_webp_url( $url ) {
        if ( empty( $url ) ) {
            return $url;
        }

        // Check if already WebP
        if ( preg_match( '/\.webp$/i', $url ) ) {
            return $url;
        }

        // Generate WebP URL
        $webp_url = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $url );

        // Convert URL to file path
        $upload_dir = wp_upload_dir();
        $webp_path  = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $webp_url );

        // Check if WebP exists
        if ( file_exists( $webp_path ) ) {
            return $webp_url;
        }

        return $url;
    }

    /**
     * Output picture element with WebP support
     *
     * @param int    $attachment_id Attachment ID.
     * @param string $size Image size.
     * @param array  $attrs Additional attributes.
     * @return string HTML output.
     */
    public static function get_picture_element( $attachment_id, $size = 'vpb-element-display', $attrs = array() ) {
        $image_src = wp_get_attachment_image_src( $attachment_id, $size );

        if ( ! $image_src ) {
            return '';
        }

        $url    = $image_src[0];
        $width  = $image_src[1];
        $height = $image_src[2];

        $webp_url = self::get_webp_url( $url );
        $has_webp = $webp_url !== $url;

        $default_attrs = array(
            'class'   => 'vpb-element-image',
            'loading' => 'lazy',
            'alt'     => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
        );

        $attrs = wp_parse_args( $attrs, $default_attrs );

        $attr_string = '';
        foreach ( $attrs as $name => $value ) {
            $attr_string .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
        }

        $html = '<picture>';

        if ( $has_webp ) {
            $html .= sprintf(
                '<source srcset="%s" type="image/webp">',
                esc_url( $webp_url )
            );
        }

        $html .= sprintf(
            '<img src="%s" width="%d" height="%d"%s>',
            esc_url( $url ),
            intval( $width ),
            intval( $height ),
            $attr_string
        );

        $html .= '</picture>';

        return $html;
    }
}
