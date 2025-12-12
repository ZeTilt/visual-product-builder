<?php
/**
 * Page Bibliothèque d'éléments Admin
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
    <h1>
        Bibliothèque d'éléments
        <button type="button" class="page-title-action" id="vpb-add-element">
            Ajouter un élément
        </button>
    </h1>

    <div class="vpb-elements-grid">
        <?php if ( empty( $elements ) ) : ?>
            <p class="vpb-no-elements">
                Aucun élément trouvé. Cliquez sur "Ajouter un élément" pour créer votre premier élément.
            </p>
        <?php else : ?>
            <!-- Barre d'actions en masse -->
            <div class="vpb-bulk-actions" style="display: none;">
                <span class="vpb-selected-count">0 sélectionné(s)</span>
                <button type="button" class="button" id="vpb-bulk-price">
                    Modifier le prix
                </button>
                <button type="button" class="button" id="vpb-bulk-deselect">
                    Désélectionner tout
                </button>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="vpb-select-all" title="Tout sélectionner">
                        </th>
                        <th style="width: 60px;">Aperçu</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Couleur</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $elements as $element ) : ?>
                        <tr data-id="<?php echo esc_attr( $element['id'] ); ?>" data-price="<?php echo esc_attr( $element['price'] ); ?>">
                            <td>
                                <input type="checkbox" class="vpb-element-checkbox" value="<?php echo esc_attr( $element['id'] ); ?>">
                            </td>
                            <td>
                                <img src="<?php echo esc_url( $element['svg_file'] ); ?>"
                                     alt="<?php echo esc_attr( $element['name'] ); ?>"
                                     style="width: 40px; height: 40px; object-fit: contain;">
                            </td>
                            <td><strong><?php echo esc_html( $element['name'] ); ?></strong></td>
                            <td><?php echo esc_html( ucfirst( $element['category'] ) ); ?></td>
                            <td><?php echo esc_html( ucfirst( $element['color'] ) ); ?></td>
                            <td class="vpb-price-cell"><?php echo wc_price( $element['price'] ); ?></td>
                            <td>
                                <?php if ( $element['active'] ) : ?>
                                    <span class="vpb-status vpb-status-active">Actif</span>
                                <?php else : ?>
                                    <span class="vpb-status vpb-status-inactive">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button vpb-edit-element" data-id="<?php echo esc_attr( $element['id'] ); ?>">
                                    Modifier
                                </button>
                                <button type="button" class="button vpb-delete-element" data-id="<?php echo esc_attr( $element['id'] ); ?>">
                                    Suppr.
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Élément -->
<div id="vpb-element-modal" class="vpb-modal" style="display: none;">
    <div class="vpb-modal-content">
        <div class="vpb-modal-header">
            <h2 id="vpb-modal-title">Ajouter un élément</h2>
            <button type="button" class="vpb-modal-close">&times;</button>
        </div>
        <form id="vpb-element-form">
            <input type="hidden" name="id" id="vpb-element-id" value="0">

            <div class="vpb-form-row">
                <label for="vpb-element-name">Nom *</label>
                <input type="text" id="vpb-element-name" name="name" required>
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-slug">Slug</label>
                <input type="text" id="vpb-element-slug" name="slug" placeholder="Généré automatiquement">
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-category">Catégorie</label>
                <select id="vpb-element-category" name="category">
                    <option value="letter">Lettre</option>
                    <option value="number">Chiffre</option>
                    <option value="shape">Forme</option>
                </select>
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-color">Couleur</label>
                <input type="text" id="vpb-element-color" name="color" placeholder="bleu, rose, beige...">
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-svg">Image *</label>
                <div class="vpb-image-field">
                    <input type="text" id="vpb-element-svg" name="svg_file" required>
                    <button type="button" class="button vpb-select-image">Choisir</button>
                </div>
                <p class="description">PNG, JPG ou SVG. Taille recommandée : 100x100px avec fond transparent.</p>
                <div id="vpb-svg-preview" class="vpb-image-preview"></div>
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-price">Prix (€)</label>
                <input type="number" id="vpb-element-price" name="price" step="0.01" min="0" value="0">
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-order">Ordre d'affichage</label>
                <input type="number" id="vpb-element-order" name="sort_order" min="0" value="0">
            </div>

            <div class="vpb-form-row">
                <label>
                    <input type="checkbox" id="vpb-element-active" name="active" checked>
                    Actif
                </label>
            </div>

            <div class="vpb-form-actions">
                <button type="button" class="button vpb-modal-close">Annuler</button>
                <button type="submit" class="button button-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Prix en masse -->
<div id="vpb-bulk-price-modal" class="vpb-modal" style="display: none;">
    <div class="vpb-modal-content" style="width: 400px;">
        <div class="vpb-modal-header">
            <h2>Modifier le prix</h2>
            <button type="button" class="vpb-modal-close">&times;</button>
        </div>
        <form id="vpb-bulk-price-form">
            <p class="vpb-bulk-info"></p>

            <div class="vpb-form-row">
                <label>
                    <input type="radio" name="price_mode" value="set" checked>
                    Définir le prix à
                </label>
                <div class="vpb-price-input-row">
                    <input type="number" id="vpb-bulk-price-value" step="0.01" min="0" value="0"> €
                </div>
            </div>

            <div class="vpb-form-row">
                <label>
                    <input type="radio" name="price_mode" value="add">
                    Ajouter au prix actuel
                </label>
            </div>

            <div class="vpb-form-row">
                <label>
                    <input type="radio" name="price_mode" value="subtract">
                    Soustraire du prix actuel
                </label>
            </div>

            <div class="vpb-form-actions">
                <button type="button" class="button vpb-modal-close">Annuler</button>
                <button type="submit" class="button button-primary">Appliquer</button>
            </div>
        </form>
    </div>
</div>

<style>
.vpb-bulk-actions {
    background: #f0f0f1;
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.vpb-selected-count {
    font-weight: 600;
    color: #2271b1;
}
.vpb-bulk-info {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 10px 15px;
    margin: 0 0 15px 0;
}
.vpb-price-input-row {
    margin-top: 5px;
    margin-left: 24px;
}
.vpb-price-input-row input {
    width: 100px;
}
.vpb-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}
.vpb-status-active {
    background: #d4edda;
    color: #155724;
}
.vpb-status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.vpb-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.vpb-modal-content {
    background: #fff;
    width: 500px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    border-radius: 4px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}
.vpb-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}
.vpb-modal-header h2 {
    margin: 0;
}
.vpb-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
#vpb-element-form {
    padding: 20px;
}
.vpb-form-row {
    margin-bottom: 15px;
}
.vpb-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}
.vpb-form-row input[type="text"],
.vpb-form-row input[type="number"],
.vpb-form-row select {
    width: 100%;
}
.vpb-image-field {
    display: flex;
    gap: 10px;
}
.vpb-image-field input {
    flex: 1;
}
.vpb-image-preview {
    margin-top: 10px;
}
.vpb-image-preview img {
    max-width: 100px;
    max-height: 100px;
    border: 1px solid #ddd;
    padding: 5px;
    background: #f9f9f9;
    border-radius: 4px;
}
.vpb-form-row .description {
    color: #666;
    font-style: italic;
    margin-top: 5px;
}
.vpb-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}
</style>
