<?php
/**
 * Admin Settings Page
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Visual Product Builder - Settings', 'visual-product-builder' ); ?></h1>

    <div class="vpb-admin-header">
        <p><?php esc_html_e( 'Configure your Visual Product Builder.', 'visual-product-builder' ); ?></p>
    </div>

    <div class="vpb-admin-content">
        <div class="vpb-card">
            <h2><?php esc_html_e( 'Quick Start', 'visual-product-builder' ); ?></h2>
            <ol>
                <li><?php esc_html_e( 'Add elements to your library (Elements menu)', 'visual-product-builder' ); ?></li>
                <li><?php esc_html_e( 'Add the shortcode to your product page:', 'visual-product-builder' ); ?>
                    <code>[vpb_configurator product_id="123" limit="10"]</code>
                </li>
                <li><?php esc_html_e( 'Customize the appearance via CSS', 'visual-product-builder' ); ?></li>
            </ol>
        </div>

        <div class="vpb-card">
            <h2><?php esc_html_e( 'Sample Data', 'visual-product-builder' ); ?></h2>
            <p><?php esc_html_e( 'Import sample elements (letters A-Z in blue and beige) to get started quickly.', 'visual-product-builder' ); ?></p>
            <?php if ( VPB_Sample_Data::is_imported() ) : ?>
                <p class="vpb-notice vpb-notice-info">
                    <?php esc_html_e( 'Sample data has already been imported.', 'visual-product-builder' ); ?>
                </p>
            <?php endif; ?>
            <p>
                <button type="button" class="button button-primary" id="vpb-import-sample-data">
                    <?php esc_html_e( 'Import Sample Data', 'visual-product-builder' ); ?>
                </button>
                <span id="vpb-import-status"></span>
            </p>
        </div>

        <div class="vpb-card">
            <h2><?php esc_html_e( 'Shortcode Parameters', 'visual-product-builder' ); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Parameter', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Default', 'visual-product-builder' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>product_id</code></td>
                        <td><?php esc_html_e( 'WooCommerce product ID (auto-detected on product pages)', 'visual-product-builder' ); ?></td>
                        <td>0</td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td><?php esc_html_e( 'Maximum number of elements allowed', 'visual-product-builder' ); ?></td>
                        <td>10</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="vpb-card">
            <h2><?php esc_html_e( 'System Status', 'visual-product-builder' ); ?></h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><?php esc_html_e( 'Plugin version', 'visual-product-builder' ); ?></td>
                        <td><strong><?php echo esc_html( VPB_VERSION ); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'WooCommerce version', 'visual-product-builder' ); ?></td>
                        <td><strong><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : 'N/A' ); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'PHP version', 'visual-product-builder' ); ?></td>
                        <td><strong><?php echo esc_html( phpversion() ); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Elements in library', 'visual-product-builder' ); ?></td>
                        <td><strong><?php echo esc_html( count( VPB_Library::get_elements() ) ); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="vpb-card vpb-card-full">
            <h2><?php esc_html_e( 'Custom CSS', 'visual-product-builder' ); ?></h2>
            <p><?php esc_html_e( 'Add custom CSS to modify the configurator appearance.', 'visual-product-builder' ); ?></p>
            <form method="post" action="">
                <?php wp_nonce_field( 'vpb_save_custom_css', 'vpb_css_nonce' ); ?>
                <textarea name="vpb_custom_css" id="vpb-custom-css" rows="15" class="large-text code"><?php echo esc_textarea( get_option( 'vpb_custom_css', '' ) ); ?></textarea>
                <p class="description">
                    <?php
                    /* translators: %s: CSS example */
                    printf( esc_html__( 'Example: %s', 'visual-product-builder' ), '<code>.vpb-configurator { background: #f5f5f5; }</code>' );
                    ?>
                </p>
                <p style="margin-top: 15px;">
                    <button type="submit" name="vpb_save_css" class="button button-primary"><?php esc_html_e( 'Save CSS', 'visual-product-builder' ); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.vpb-admin-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.vpb-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    border-radius: 4px;
}
.vpb-card.vpb-card-full {
    grid-column: 1 / -1;
}
.vpb-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
.vpb-notice {
    padding: 8px 12px;
    border-radius: 4px;
    margin: 10px 0;
}
.vpb-notice-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
#vpb-import-status {
    margin-left: 10px;
    font-style: italic;
}
#vpb-custom-css {
    font-family: monospace;
    font-size: 13px;
}
</style>
