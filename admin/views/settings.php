<?php
/**
 * Page Réglages Admin
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
    <h1>Visual Product Builder - Réglages</h1>

    <div class="vpb-admin-header">
        <p>Configurez votre Visual Product Builder.</p>
    </div>

    <div class="vpb-admin-content">
        <div class="vpb-card">
            <h2>Démarrage rapide</h2>
            <ol>
                <li>Ajoutez des éléments à votre bibliothèque (menu Éléments)</li>
                <li>Ajoutez le shortcode sur votre page produit :
                    <code>[vpb_configurator product_id="123" limit="10"]</code>
                </li>
                <li>Personnalisez l'apparence via le CSS</li>
            </ol>
        </div>

        <div class="vpb-card">
            <h2>Données d'exemple</h2>
            <p>Importez des éléments d'exemple (lettres A-Z en bleu et beige) pour démarrer rapidement.</p>
            <?php if ( VPB_Sample_Data::is_imported() ) : ?>
                <p class="vpb-notice vpb-notice-info">
                    Les données d'exemple ont déjà été importées.
                </p>
            <?php endif; ?>
            <p>
                <button type="button" class="button button-primary" id="vpb-import-sample-data">
                    Importer les données d'exemple
                </button>
                <span id="vpb-import-status"></span>
            </p>
        </div>

        <div class="vpb-card">
            <h2>Paramètres du shortcode</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Paramètre</th>
                        <th>Description</th>
                        <th>Défaut</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>product_id</code></td>
                        <td>ID du produit WooCommerce (auto-détecté sur les pages produit)</td>
                        <td>0</td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td>Nombre maximum d'éléments autorisés</td>
                        <td>10</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="vpb-card">
            <h2>Statut système</h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td>Version du plugin</td>
                        <td><strong><?php echo esc_html( VPB_VERSION ); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Version WooCommerce</td>
                        <td><strong><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : 'N/A' ); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Version PHP</td>
                        <td><strong><?php echo esc_html( phpversion() ); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Éléments dans la bibliothèque</td>
                        <td><strong><?php echo esc_html( count( VPB_Library::get_elements() ) ); ?></strong></td>
                    </tr>
                </tbody>
            </table>
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
</style>
