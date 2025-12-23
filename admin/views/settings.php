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
                <li><?php esc_html_e( 'Import sample collections or create your own (Collections menu)', 'visual-product-builder' ); ?></li>
                <li><?php esc_html_e( 'Edit a WooCommerce product and select which collections to enable', 'visual-product-builder' ); ?></li>
                <li><?php esc_html_e( 'Add this shortcode to your product description:', 'visual-product-builder' ); ?>
                    <code>[vpb_configurator]</code>
                </li>
                <li><?php esc_html_e( 'Optional: Customize the limit with', 'visual-product-builder' ); ?> <code>[vpb_configurator limit="15"]</code></li>
            </ol>
        </div>

        <div class="vpb-card">
            <h2><?php esc_html_e( 'Sample Data', 'visual-product-builder' ); ?></h2>
            <p><?php esc_html_e( 'Import sample elements (letters A-Z, numbers 0-9, and symbols in 8 different colors) to get started quickly.', 'visual-product-builder' ); ?></p>
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

        <?php
        $license      = vpb_license();
        $current_plan = $license->get_plan();
        $is_active    = $license->is_active();
        $limits       = $license->get_limits();
        ?>
        <div class="vpb-card vpb-card-license">
            <h2><?php esc_html_e( 'License', 'visual-product-builder' ); ?></h2>

            <div class="vpb-plan-badge vpb-plan-<?php echo esc_attr( $current_plan ); ?>">
                <?php echo esc_html( strtoupper( $current_plan ) ); ?>
            </div>

            <?php if ( $is_active ) : ?>
                <p class="vpb-license-status vpb-license-active">
                    <?php esc_html_e( 'License active', 'visual-product-builder' ); ?>
                    <?php if ( $license->get_expiry_date() ) : ?>
                        <?php
						/* translators: %s: expiry date */
						?><br><small><?php printf( esc_html__( 'Expires: %s', 'visual-product-builder' ), esc_html( $license->get_expiry_date() ) ); ?></small>
                    <?php endif; ?>
                </p>
                <p>
                    <code><?php echo esc_html( $license->get_license_key_masked() ); ?></code>
                </p>
                <p>
                    <button type="button" class="button" id="vpb-deactivate-license">
                        <?php esc_html_e( 'Deactivate License', 'visual-product-builder' ); ?>
                    </button>
                </p>
            <?php else : ?>
                <p><?php esc_html_e( 'Enter your license key to unlock PRO or BUSINESS features.', 'visual-product-builder' ); ?></p>
                <p>
                    <input type="text" id="vpb-license-key" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX" style="width: 100%; max-width: 300px;">
                </p>
                <p>
                    <button type="button" class="button button-primary" id="vpb-activate-license">
                        <?php esc_html_e( 'Activate License', 'visual-product-builder' ); ?>
                    </button>
                    <a href="<?php echo esc_url( VPB_PRICING_URL ); ?>" target="_blank" class="button">
                        <?php esc_html_e( 'Get a License', 'visual-product-builder' ); ?>
                    </a>
                </p>
            <?php endif; ?>

            <div id="vpb-license-message" style="margin-top: 10px;"></div>

            <hr style="margin: 20px 0;">

            <h3><?php esc_html_e( 'Your Limits', 'visual-product-builder' ); ?></h3>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><?php esc_html_e( 'Collections', 'visual-product-builder' ); ?></td>
                        <td><strong><?php echo $limits['collections'] < 0 ? esc_html__( 'Unlimited', 'visual-product-builder' ) : esc_html( $limits['collections'] ); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Elements per collection', 'visual-product-builder' ); ?></td>
                        <td><strong><?php echo $limits['elements_per_collection'] < 0 ? esc_html__( 'Unlimited', 'visual-product-builder' ) : esc_html( $limits['elements_per_collection'] ); ?></strong></td>
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
                    <tr>
                        <td><?php esc_html_e( 'Current plan', 'visual-product-builder' ); ?></td>
                        <td><strong><?php echo esc_html( strtoupper( $current_plan ) ); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="vpb-card vpb-card-full">
            <h2>
                <?php esc_html_e( 'Custom CSS', 'visual-product-builder' ); ?>
                <?php if ( ! vpb_can_use_feature( 'custom_css' ) ) : ?>
                    <span class="vpb-badge vpb-badge-pro">PRO</span>
                <?php endif; ?>
            </h2>
            <?php if ( vpb_can_use_feature( 'custom_css' ) ) : ?>
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
            <?php else : ?>
                <p class="vpb-pro-notice">
                    <?php esc_html_e( 'Custom CSS allows you to personalize the configurator appearance to match your theme.', 'visual-product-builder' ); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url( VPB_PRICING_URL ); ?>" target="_blank" class="button button-primary">
                        <?php esc_html_e( 'Upgrade to PRO', 'visual-product-builder' ); ?>
                    </a>
                </p>
            <?php endif; ?>
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
.vpb-badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 3px;
    margin-left: 8px;
    vertical-align: middle;
}
.vpb-badge-pro {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
}
.vpb-badge-business {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
}
.vpb-pro-notice {
    background: #f0f0ff;
    border: 1px solid #c7d2fe;
    border-radius: 4px;
    padding: 15px;
    color: #4338ca;
}
/* License section */
.vpb-card-license {
    border: 2px solid #6366f1;
}
.vpb-plan-badge {
    display: inline-block;
    padding: 6px 16px;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    border-radius: 20px;
    margin-bottom: 15px;
}
.vpb-plan-free {
    background: #e5e7eb;
    color: #374151;
}
.vpb-plan-pro {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
}
.vpb-plan-business {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
}
.vpb-license-status {
    padding: 8px 12px;
    border-radius: 4px;
    margin: 10px 0;
}
.vpb-license-active {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.vpb-license-message-success {
    background: #d1fae5;
    color: #065f46;
    padding: 10px;
    border-radius: 4px;
}
.vpb-license-message-error {
    background: #fee2e2;
    color: #991b1b;
    padding: 10px;
    border-radius: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var vpbLicenseNonce = '<?php echo esc_js( wp_create_nonce( 'vpb_license_nonce' ) ); ?>';

    // Activate license
    $('#vpb-activate-license').on('click', function() {
        var $btn = $(this);
        var $msg = $('#vpb-license-message');
        var licenseKey = $('#vpb-license-key').val().trim();

        if (!licenseKey) {
            $msg.html('<div class="vpb-license-message-error"><?php echo esc_js( __( 'Please enter a license key.', 'visual-product-builder' ) ); ?></div>');
            return;
        }

        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Activating...', 'visual-product-builder' ) ); ?>');
        $msg.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vpb_activate_license',
                nonce: vpbLicenseNonce,
                license_key: licenseKey
            },
            success: function(response) {
                if (response.success) {
                    $msg.html('<div class="vpb-license-message-success">' + response.data.message + '</div>');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $msg.html('<div class="vpb-license-message-error">' + response.data.message + '</div>');
                    $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Activate License', 'visual-product-builder' ) ); ?>');
                }
            },
            error: function() {
                $msg.html('<div class="vpb-license-message-error"><?php echo esc_js( __( 'Connection error. Please try again.', 'visual-product-builder' ) ); ?></div>');
                $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Activate License', 'visual-product-builder' ) ); ?>');
            }
        });
    });

    // Deactivate license
    $('#vpb-deactivate-license').on('click', function() {
        if (!confirm('<?php echo esc_js( __( 'Are you sure you want to deactivate your license?', 'visual-product-builder' ) ); ?>')) {
            return;
        }

        var $btn = $(this);
        var $msg = $('#vpb-license-message');

        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Deactivating...', 'visual-product-builder' ) ); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vpb_deactivate_license',
                nonce: vpbLicenseNonce
            },
            success: function(response) {
                if (response.success) {
                    $msg.html('<div class="vpb-license-message-success">' + response.data.message + '</div>');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $msg.html('<div class="vpb-license-message-error">' + response.data.message + '</div>');
                    $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Deactivate License', 'visual-product-builder' ) ); ?>');
                }
            },
            error: function() {
                $msg.html('<div class="vpb-license-message-error"><?php echo esc_js( __( 'Connection error. Please try again.', 'visual-product-builder' ) ); ?></div>');
                $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Deactivate License', 'visual-product-builder' ) ); ?>');
            }
        });
    });
});
</script>
