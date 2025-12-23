<?php
/**
 * Configurator Template
 *
 * @package VisualProductBuilder
 * @var WC_Product $product
 * @var array $elements
 * @var array $colors
 * @var array $product_collections
 * @var int $limit
 * @var string $support_image
 */

defined( 'ABSPATH' ) || exit;

// Check if editing from cart
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for loading cart item.
$vpb_edit_key = isset( $_GET['vpb_edit'] ) ? sanitize_text_field( wp_unslash( $_GET['vpb_edit'] ) ) : '';
?>

<div class="vpb-configurator"
     data-product-id="<?php echo esc_attr( $product_id ); ?>"
     data-limit="<?php echo esc_attr( $limit ); ?>"
     data-base-price="<?php echo esc_attr( $product->get_price() ); ?>"
     data-support-image="<?php echo esc_url( $support_image ); ?>"
     <?php if ( $vpb_edit_key ) : ?>data-edit-cart-key="<?php echo esc_attr( $vpb_edit_key ); ?>"<?php endif; ?>>

    <!-- Preview area -->
    <div class="vpb-preview-section">
        <div class="vpb-preview-container<?php echo $support_image ? ' has-support-image' : ''; ?>">
            <?php if ( $support_image ) : ?>
                <img src="<?php echo esc_url( $support_image ); ?>" alt="<?php esc_attr_e( 'Support', 'visual-product-builder' ); ?>" class="vpb-support-image">
            <?php endif; ?>
            <div class="vpb-preview-canvas <?php echo $support_image ? 'vpb-overlay-mode' : ''; ?>" id="vpb-preview"
                 role="region" aria-label="<?php esc_attr_e( 'Design preview', 'visual-product-builder' ); ?>" aria-live="polite">
                <?php if ( ! $support_image ) : ?>
                    <div class="vpb-preview-placeholder">
                        <?php esc_html_e( 'Your design will appear here', 'visual-product-builder' ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Controls -->
        <div class="vpb-controls">
            <button type="button" class="vpb-btn-icon" id="vpb-undo" disabled
                    title="<?php esc_attr_e( 'Undo', 'visual-product-builder' ); ?>"
                    aria-label="<?php esc_attr_e( 'Undo last action', 'visual-product-builder' ); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 10h10a5 5 0 0 1 5 5v2M3 10l5-5M3 10l5 5"/></svg>
            </button>
            <button type="button" class="vpb-btn-icon" id="vpb-reset"
                    title="<?php esc_attr_e( 'Start over', 'visual-product-builder' ); ?>"
                    aria-label="<?php esc_attr_e( 'Clear all and start over', 'visual-product-builder' ); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            </button>
        </div>
    </div>

    <!-- Configuration panel -->
    <div class="vpb-config-section">
        <!-- Status -->
        <div class="vpb-status-bar">
            <span class="vpb-counter">
                <span id="vpb-count">0</span> / <?php echo esc_html( $limit ); ?>
            </span>
            <span class="vpb-price-display">
                <?php esc_html_e( 'Total:', 'visual-product-builder' ); ?> <strong id="vpb-total-price"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></strong>
            </span>
        </div>

        <?php if ( ! empty( $product_collections ) && count( $product_collections ) > 1 ) : ?>
            <!-- Collection tabs (thumbnails) -->
            <div class="vpb-collection-tabs vpb-collection-tabs-thumbnails" role="tablist" aria-label="<?php esc_attr_e( 'Filter elements by collection', 'visual-product-builder' ); ?>">
                <button type="button"
                        class="vpb-collection-tab vpb-collection-tab-all active"
                        data-collection="all"
                        title="<?php esc_attr_e( 'All collections', 'visual-product-builder' ); ?>"
                        aria-label="<?php esc_attr_e( 'Show all collections', 'visual-product-builder' ); ?>"
                        aria-pressed="true">
                    <span class="vpb-tab-all-icon" aria-hidden="true"><?php esc_html_e( 'All', 'visual-product-builder' ); ?></span>
                </button>
                <?php foreach ( $product_collections as $collection ) : ?>
                    <?php
                    // Get thumbnail: collection thumbnail or first element image
                    $tab_image = $collection->thumbnail_url;
                    if ( empty( $tab_image ) ) {
                        $first_elements = VPB_Collection::get_elements( $collection->id, array( 'limit' => 1 ) );
                        if ( ! empty( $first_elements ) ) {
                            $tab_image = $first_elements[0]->svg_file;
                        }
                    }
                    ?>
                    <?php
                    /* translators: %s: collection name */
                    $aria_filter_label = sprintf( __( 'Filter by collection: %s', 'visual-product-builder' ), $collection->name );
                    ?>
                    <button type="button"
                            class="vpb-collection-tab vpb-collection-tab-thumb"
                            data-collection="<?php echo esc_attr( $collection->id ); ?>"
                            style="--collection-color: <?php echo esc_attr( $collection->color_hex ); ?>; background-color: <?php echo esc_attr( $collection->color_hex ); ?>;"
                            title="<?php echo esc_attr( $collection->name ); ?>"
                            aria-label="<?php echo esc_attr( $aria_filter_label ); ?>"
                            aria-pressed="false">
                        <?php if ( $tab_image ) : ?>
                            <img src="<?php echo esc_url( $tab_image ); ?>" alt="" aria-hidden="true">
                        <?php else : ?>
                            <span class="vpb-tab-placeholder" aria-hidden="true"></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php elseif ( ! empty( $all_collections ) && count( $all_collections ) > 1 ) : ?>
            <!-- Collection filter dropdown (when no specific collections assigned to product) -->
            <div class="vpb-collection-filter">
                <label for="vpb-collection-select" class="screen-reader-text"><?php esc_html_e( 'Filter by collection', 'visual-product-builder' ); ?></label>
                <select id="vpb-collection-select" class="vpb-collection-dropdown">
                    <option value="all"><?php esc_html_e( 'All collections', 'visual-product-builder' ); ?></option>
                    <?php foreach ( $all_collections as $collection ) : ?>
                        <option value="<?php echo esc_attr( $collection->id ); ?>" data-color="<?php echo esc_attr( $collection->color_hex ); ?>">
                            <?php echo esc_html( $collection->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Grille d'éléments -->
        <div class="vpb-elements-container" role="region" aria-label="<?php esc_attr_e( 'Available elements', 'visual-product-builder' ); ?>">
            <?php foreach ( $elements as $category => $category_elements ) : ?>
                <div class="vpb-elements-grid" data-category="<?php echo esc_attr( $category ); ?>">
                    <?php foreach ( $category_elements as $element ) : ?>
                        <?php
                        $color_hex = ! empty( $element['color_hex'] ) ? $element['color_hex'] : '#4F9ED9';
                        $is_svg    = ! empty( $element['svg_file'] ) && strtolower( pathinfo( $element['svg_file'], PATHINFO_EXTENSION ) ) === 'svg';
                        /* translators: 1: element name, 2: element color, 3: element price */
                        $aria_add_label = sprintf( __( 'Add %1$s (%2$s) - %3$s', 'visual-product-builder' ), $element['name'], ucfirst( $element['color'] ), wp_strip_all_tags( wc_price( $element['price'] ) ) );
                        ?>
                        <button type="button"
                                class="vpb-element-btn"
                                data-id="<?php echo esc_attr( $element['id'] ); ?>"
                                data-name="<?php echo esc_attr( $element['name'] ); ?>"
                                data-color="<?php echo esc_attr( $element['color'] ); ?>"
                                data-color-hex="<?php echo esc_attr( $color_hex ); ?>"
                                data-collection="<?php echo esc_attr( $element['collection_id'] ?? '' ); ?>"
                                data-price="<?php echo esc_attr( $element['price'] ); ?>"
                                data-svg="<?php echo esc_url( $element['svg_file'] ); ?>"
                                data-is-svg="<?php echo $is_svg ? 'true' : 'false'; ?>"
                                style="<?php echo $is_svg ? '--element-color: ' . esc_attr( $color_hex ) . ';' : ''; ?>"
                                title="<?php echo esc_attr( $element['name'] . ' (' . ucfirst( $element['color'] ) . ')' ); ?>"
                                aria-label="<?php echo esc_attr( $aria_add_label ); ?>">
                            <img src="<?php echo esc_url( $element['svg_file'] ); ?>"
                                 alt="" aria-hidden="true">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add to cart form -->
        <form id="vpb-add-to-cart-form" method="post">
            <input type="hidden" name="vpb_configuration" id="vpb-configuration-input" value="">
            <input type="hidden" name="vpb_image_data" id="vpb-image-input" value="">
            <?php wp_nonce_field( 'vpb_add_to_cart', 'vpb_nonce' ); ?>
            <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>">
            <?php if ( $vpb_edit_key ) : ?>
                <input type="hidden" name="vpb_edit_cart_key" id="vpb-edit-cart-key" value="<?php echo esc_attr( $vpb_edit_key ); ?>">
            <?php endif; ?>

            <button type="submit" class="vpb-btn vpb-btn-primary vpb-add-to-cart" disabled>
                <?php if ( $vpb_edit_key ) : ?>
                    <?php esc_html_e( 'Update cart', 'visual-product-builder' ); ?> - <span id="vpb-cart-price"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
                <?php else : ?>
                    <?php esc_html_e( 'Add to cart', 'visual-product-builder' ); ?> - <span id="vpb-cart-price"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
                <?php endif; ?>
            </button>
        </form>
    </div>

    <!-- Toast container -->
    <div id="vpb-toast-container"></div>

    <?php if ( vpb_is_free() ) : ?>
        <!-- Powered by branding (FREE tier) -->
        <div class="vpb-branding">
            <a href="https://alre-web.bzh/" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e( 'Powered by Visual Product Builder', 'visual-product-builder' ); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
