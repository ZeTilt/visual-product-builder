<?php
/**
 * Configurator Shortcode Template
 *
 * @package VisualProductBuilder
 * @var WC_Product $product
 * @var array $elements
 * @var array $colors
 * @var int $limit
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="vpb-configurator"
     data-product-id="<?php echo esc_attr( $product_id ); ?>"
     data-limit="<?php echo esc_attr( $limit ); ?>"
     data-base-price="<?php echo esc_attr( $product->get_price() ); ?>">

    <!-- Preview Area -->
    <div class="vpb-preview-section">
        <div class="vpb-preview-container">
            <div class="vpb-preview-canvas" id="vpb-preview">
                <!-- Elements will be rendered here -->
                <div class="vpb-preview-placeholder">
                    <?php esc_html_e( 'Your design will appear here', 'visual-product-builder' ); ?>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="vpb-controls">
            <button type="button" class="vpb-btn vpb-btn-secondary" id="vpb-undo" disabled>
                <?php esc_html_e( 'Undo last', 'visual-product-builder' ); ?>
            </button>
            <button type="button" class="vpb-btn vpb-btn-secondary" id="vpb-reset">
                <?php esc_html_e( 'Start over', 'visual-product-builder' ); ?>
            </button>
        </div>
    </div>

    <!-- Configuration Panel -->
    <div class="vpb-config-section">
        <!-- Status -->
        <div class="vpb-status-bar">
            <span class="vpb-counter">
                <span id="vpb-count">0</span> / <span id="vpb-limit"><?php echo esc_html( $limit ); ?></span>
                <?php esc_html_e( 'elements', 'visual-product-builder' ); ?>
            </span>
            <span class="vpb-price-display">
                <?php esc_html_e( 'Total:', 'visual-product-builder' ); ?>
                <strong id="vpb-total-price"><?php echo wc_price( $product->get_price() ); ?></strong>
            </span>
        </div>

        <!-- Color Filter -->
        <?php if ( count( $colors ) > 1 ) : ?>
            <div class="vpb-color-filter">
                <span class="vpb-filter-label"><?php esc_html_e( 'Color:', 'visual-product-builder' ); ?></span>
                <div class="vpb-color-tabs">
                    <button type="button" class="vpb-color-tab active" data-color="all">
                        <?php esc_html_e( 'All', 'visual-product-builder' ); ?>
                    </button>
                    <?php foreach ( $colors as $color ) : ?>
                        <button type="button" class="vpb-color-tab" data-color="<?php echo esc_attr( $color ); ?>">
                            <?php echo esc_html( ucfirst( $color ) ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Element Selection -->
        <div class="vpb-element-selector">
            <!-- Element Grid -->
            <div class="vpb-elements-container">
                <?php foreach ( $elements as $category => $category_elements ) : ?>
                    <div class="vpb-elements-grid" data-category="<?php echo esc_attr( $category ); ?>">
                        <?php foreach ( $category_elements as $element ) : ?>
                            <button type="button"
                                    class="vpb-element-btn"
                                    data-id="<?php echo esc_attr( $element['id'] ); ?>"
                                    data-name="<?php echo esc_attr( $element['name'] ); ?>"
                                    data-color="<?php echo esc_attr( $element['color'] ); ?>"
                                    data-price="<?php echo esc_attr( $element['price'] ); ?>"
                                    data-svg="<?php echo esc_attr( $element['svg_file'] ); ?>"
                                    title="<?php echo esc_attr( $element['name'] . ' (' . ucfirst( $element['color'] ) . ')' ); ?>">
                                <img src="<?php echo esc_url( $element['svg_file'] ); ?>"
                                     alt="<?php echo esc_attr( $element['name'] ); ?>">
                                <span class="vpb-element-name"><?php echo esc_html( $element['name'] ); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Add to Cart Form -->
        <form id="vpb-add-to-cart-form" method="post">
            <input type="hidden" name="vpb_configuration" id="vpb-configuration-input" value="">
            <input type="hidden" name="vpb_image_data" id="vpb-image-input" value="">
            <?php wp_nonce_field( 'vpb_add_to_cart', 'vpb_nonce' ); ?>
            <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>">

            <button type="submit" class="vpb-btn vpb-btn-primary vpb-add-to-cart" disabled>
                <?php esc_html_e( 'Add to Cart', 'visual-product-builder' ); ?> -
                <span id="vpb-cart-price"><?php echo wc_price( $product->get_price() ); ?></span>
            </button>
        </form>
    </div>

    <!-- Toast Container -->
    <div id="vpb-toast-container"></div>
</div>
