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
?>

<div class="vpb-configurator"
     data-product-id="<?php echo esc_attr( $product_id ); ?>"
     data-limit="<?php echo esc_attr( $limit ); ?>"
     data-base-price="<?php echo esc_attr( $product->get_price() ); ?>"
     data-support-image="<?php echo esc_url( $support_image ); ?>">

    <!-- Preview area -->
    <div class="vpb-preview-section">
        <div class="vpb-preview-container<?php echo $support_image ? ' has-support-image' : ''; ?>">
            <?php if ( $support_image ) : ?>
                <img src="<?php echo esc_url( $support_image ); ?>" alt="<?php esc_attr_e( 'Support', 'visual-product-builder' ); ?>" class="vpb-support-image">
            <?php endif; ?>
            <div class="vpb-preview-canvas <?php echo $support_image ? 'vpb-overlay-mode' : ''; ?>" id="vpb-preview">
                <?php if ( ! $support_image ) : ?>
                    <div class="vpb-preview-placeholder">
                        <?php esc_html_e( 'Your design will appear here', 'visual-product-builder' ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Controls -->
        <div class="vpb-controls">
            <button type="button" class="vpb-btn-icon" id="vpb-undo" disabled title="<?php esc_attr_e( 'Undo', 'visual-product-builder' ); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10h10a5 5 0 0 1 5 5v2M3 10l5-5M3 10l5 5"/></svg>
            </button>
            <button type="button" class="vpb-btn-icon" id="vpb-reset" title="<?php esc_attr_e( 'Start over', 'visual-product-builder' ); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
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
                <?php esc_html_e( 'Total:', 'visual-product-builder' ); ?> <strong id="vpb-total-price"><?php echo wc_price( $product->get_price() ); ?></strong>
            </span>
        </div>

        <?php if ( ! empty( $product_collections ) && count( $product_collections ) > 1 ) : ?>
            <!-- Collection tabs (thumbnails) -->
            <div class="vpb-collection-tabs vpb-collection-tabs-thumbnails">
                <button type="button"
                        class="vpb-collection-tab vpb-collection-tab-all active"
                        data-collection="all"
                        title="<?php esc_attr_e( 'All collections', 'visual-product-builder' ); ?>">
                    <span class="vpb-tab-all-icon"><?php esc_html_e( 'All', 'visual-product-builder' ); ?></span>
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
                    <button type="button"
                            class="vpb-collection-tab vpb-collection-tab-thumb"
                            data-collection="<?php echo esc_attr( $collection->id ); ?>"
                            style="--collection-color: <?php echo esc_attr( $collection->color_hex ); ?>; background-color: <?php echo esc_attr( $collection->color_hex ); ?>;"
                            title="<?php echo esc_attr( $collection->name ); ?>">
                        <?php if ( $tab_image ) : ?>
                            <img src="<?php echo esc_url( $tab_image ); ?>" alt="<?php echo esc_attr( $collection->name ); ?>">
                        <?php else : ?>
                            <span class="vpb-tab-placeholder"></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Grille d'éléments -->
        <div class="vpb-elements-container">
            <?php foreach ( $elements as $category => $category_elements ) : ?>
                <div class="vpb-elements-grid" data-category="<?php echo esc_attr( $category ); ?>">
                    <?php foreach ( $category_elements as $element ) : ?>
                        <?php
                        $color_hex = ! empty( $element['color_hex'] ) ? $element['color_hex'] : '#4F9ED9';
                        $is_svg    = ! empty( $element['svg_file'] ) && strtolower( pathinfo( $element['svg_file'], PATHINFO_EXTENSION ) ) === 'svg';
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
                                title="<?php echo esc_attr( $element['name'] . ' (' . ucfirst( $element['color'] ) . ')' ); ?>">
                            <img src="<?php echo esc_url( $element['svg_file'] ); ?>"
                                 alt="<?php echo esc_attr( $element['name'] ); ?>">
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

            <button type="submit" class="vpb-btn vpb-btn-primary vpb-add-to-cart" disabled>
                <?php esc_html_e( 'Add to cart', 'visual-product-builder' ); ?> - <span id="vpb-cart-price"><?php echo wc_price( $product->get_price() ); ?></span>
            </button>
        </form>
    </div>

    <!-- Toast container -->
    <div id="vpb-toast-container"></div>
</div>
