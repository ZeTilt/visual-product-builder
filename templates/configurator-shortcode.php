<?php
/**
 * Template du Configurateur
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

    <!-- Zone de prévisualisation -->
    <div class="vpb-preview-section">
        <div class="vpb-preview-container">
            <div class="vpb-preview-canvas" id="vpb-preview">
                <div class="vpb-preview-placeholder">
                    Votre création apparaîtra ici
                </div>
            </div>
        </div>

        <!-- Contrôles -->
        <div class="vpb-controls">
            <button type="button" class="vpb-btn vpb-btn-secondary" id="vpb-undo" disabled>
                Annuler
            </button>
            <button type="button" class="vpb-btn vpb-btn-secondary" id="vpb-reset">
                Recommencer
            </button>
        </div>
    </div>

    <!-- Panneau de configuration -->
    <div class="vpb-config-section">
        <!-- Statut -->
        <div class="vpb-status-bar">
            <span class="vpb-counter">
                <span id="vpb-count">0</span> / <?php echo esc_html( $limit ); ?>
            </span>
            <span class="vpb-price-display">
                Total : <strong id="vpb-total-price"><?php echo wc_price( $product->get_price() ); ?></strong>
            </span>
        </div>

        <!-- Filtre par couleur -->
        <?php if ( count( $colors ) > 1 ) : ?>
            <div class="vpb-color-filter">
                <span class="vpb-filter-label">Couleur :</span>
                <div class="vpb-color-tabs">
                    <button type="button" class="vpb-color-tab active" data-color="all">
                        Tout
                    </button>
                    <?php foreach ( $colors as $color ) : ?>
                        <button type="button" class="vpb-color-tab" data-color="<?php echo esc_attr( $color ); ?>">
                            <?php echo esc_html( ucfirst( $color ) ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Grille d'éléments -->
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
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Formulaire d'ajout au panier -->
        <form id="vpb-add-to-cart-form" method="post">
            <input type="hidden" name="vpb_configuration" id="vpb-configuration-input" value="">
            <input type="hidden" name="vpb_image_data" id="vpb-image-input" value="">
            <?php wp_nonce_field( 'vpb_add_to_cart', 'vpb_nonce' ); ?>
            <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>">

            <button type="submit" class="vpb-btn vpb-btn-primary vpb-add-to-cart" disabled>
                Ajouter au panier - <span id="vpb-cart-price"><?php echo wc_price( $product->get_price() ); ?></span>
            </button>
        </form>
    </div>

    <!-- Container des notifications -->
    <div id="vpb-toast-container"></div>
</div>
