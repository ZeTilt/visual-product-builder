<?php
/**
 * Admin Collections Page
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap vpb-admin">
    <h1 class="wp-heading-inline">Collections</h1>
    <button type="button" class="page-title-action" id="vpb-add-collection">Ajouter une collection</button>
    <?php if ( ! empty( $collections ) ) : ?>
        <button type="button" class="page-title-action vpb-danger-action" id="vpb-purge-collections">Tout purger</button>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php if ( empty( $collections ) ) : ?>
        <div class="vpb-empty-state">
            <p>Aucune collection n'a été créée.</p>
            <p>Les collections permettent d'organiser vos éléments (lettres, chiffres, symboles) et de les assigner à des produits.</p>
            <button type="button" class="button button-primary" id="vpb-add-collection-empty">Créer votre première collection</button>
        </div>
    <?php else : ?>
        <div class="vpb-collections-grid">
            <?php foreach ( $collections as $collection ) : ?>
                <?php $element_count = VPB_Collection::get_element_count( $collection->id ); ?>
                <div class="vpb-collection-card" data-id="<?php echo esc_attr( $collection->id ); ?>">
                    <div class="vpb-collection-card-header" style="background-color: <?php echo esc_attr( $collection->color_hex ); ?>;">
                        <?php if ( $collection->thumbnail_url ) : ?>
                            <img src="<?php echo esc_url( $collection->thumbnail_url ); ?>" alt="<?php echo esc_attr( $collection->name ); ?>">
                        <?php else : ?>
                            <span class="dashicons dashicons-portfolio"></span>
                        <?php endif; ?>
                    </div>
                    <div class="vpb-collection-card-body">
                        <h3><?php echo esc_html( $collection->name ); ?></h3>
                        <p class="vpb-collection-meta">
                            <span class="vpb-badge"><?php echo esc_html( $element_count ); ?> éléments</span>
                            <?php if ( ! $collection->active ) : ?>
                                <span class="vpb-badge vpb-badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </p>
                        <?php if ( $collection->description ) : ?>
                            <p class="vpb-collection-description"><?php echo esc_html( $collection->description ); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="vpb-collection-card-footer">
                        <button type="button" class="button vpb-edit-collection" data-id="<?php echo esc_attr( $collection->id ); ?>">
                            <span class="dashicons dashicons-edit"></span> Modifier
                        </button>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=vpb-elements&collection=' . $collection->id ) ); ?>" class="button">
                            <span class="dashicons dashicons-visibility"></span> Voir
                        </a>
                        <button type="button" class="button vpb-delete-collection" data-id="<?php echo esc_attr( $collection->id ); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Collection Modal -->
<div id="vpb-collection-modal" class="vpb-modal" style="display: none;">
    <div class="vpb-modal-content">
        <div class="vpb-modal-header">
            <h2 id="vpb-collection-modal-title">Ajouter une collection</h2>
            <button type="button" class="vpb-modal-close">&times;</button>
        </div>
        <div class="vpb-modal-body">
            <form id="vpb-collection-form">
                <input type="hidden" name="id" id="vpb-collection-id" value="">

                <div class="vpb-form-row">
                    <label for="vpb-collection-name">Nom *</label>
                    <input type="text" id="vpb-collection-name" name="name" required>
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-collection-slug">Slug</label>
                    <input type="text" id="vpb-collection-slug" name="slug" placeholder="auto-généré">
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-collection-description">Description</label>
                    <textarea id="vpb-collection-description" name="description" rows="3"></textarea>
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-collection-color">Couleur</label>
                    <div class="vpb-color-picker-wrapper">
                        <input type="color" id="vpb-collection-color" name="color_hex" value="#4F9ED9">
                        <input type="text" id="vpb-collection-color-text" placeholder="#4F9ED9" maxlength="7">
                    </div>
                    <p class="description">Couleur thème de la collection</p>
                </div>

                <div class="vpb-form-row">
                    <label>Miniature</label>
                    <div class="vpb-image-upload">
                        <div id="vpb-collection-thumbnail-preview" class="vpb-image-preview"></div>
                        <input type="hidden" id="vpb-collection-thumbnail" name="thumbnail_url" value="">
                        <button type="button" class="button" id="vpb-collection-thumbnail-btn">Choisir une image</button>
                        <button type="button" class="button vpb-remove-image" id="vpb-collection-thumbnail-remove" style="display: none;">Supprimer</button>
                    </div>
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-collection-order">Ordre d'affichage</label>
                    <input type="number" id="vpb-collection-order" name="sort_order" value="0" min="0">
                </div>

                <div class="vpb-form-row">
                    <label>
                        <input type="checkbox" name="active" id="vpb-collection-active" checked>
                        Collection active
                    </label>
                </div>
            </form>
        </div>
        <div class="vpb-modal-footer">
            <button type="button" class="button vpb-modal-close">Annuler</button>
            <button type="button" class="button button-primary" id="vpb-save-collection">Enregistrer</button>
        </div>
    </div>
</div>

<style>
.vpb-collections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.vpb-collection-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.vpb-collection-card:hover {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.vpb-collection-card-header {
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.vpb-collection-card-header img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}

.vpb-collection-card-header .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    opacity: 0.8;
}

.vpb-collection-card-body {
    padding: 15px;
}

.vpb-collection-card-body h3 {
    margin: 0 0 10px;
    font-size: 16px;
}

.vpb-collection-meta {
    margin: 0 0 10px;
}

.vpb-badge {
    display: inline-block;
    background: #f0f0f1;
    color: #50575e;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 5px;
}

.vpb-badge-inactive {
    background: #fef7f1;
    color: #9a6700;
}

.vpb-collection-description {
    color: #646970;
    font-size: 13px;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vpb-collection-card-footer {
    padding: 10px 15px;
    background: #f6f7f7;
    border-top: 1px solid #c3c4c7;
    display: flex;
    gap: 5px;
}

.vpb-collection-card-footer .button {
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.vpb-collection-card-footer .button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.vpb-collection-card-footer .vpb-delete-collection {
    margin-left: auto;
    color: #b32d2e;
}

.vpb-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-top: 20px;
}

.vpb-color-picker-wrapper {
    display: flex;
    gap: 10px;
    align-items: center;
}

.vpb-color-picker-wrapper input[type="color"] {
    width: 50px;
    height: 36px;
    padding: 0;
    border: 1px solid #8c8f94;
    cursor: pointer;
}

.vpb-color-picker-wrapper input[type="text"] {
    width: 100px;
    font-family: monospace;
}

.vpb-danger-action {
    color: #b32d2e !important;
    border-color: #b32d2e !important;
}

.vpb-danger-action:hover {
    background: #b32d2e !important;
    color: #fff !important;
}

/* Modal */
.vpb-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.vpb-modal-content {
    background: #fff;
    border-radius: 4px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
}

.vpb-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #dcdcde;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vpb-modal-header h2 { margin: 0; font-size: 18px; }

.vpb-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #646970;
    line-height: 1;
}

.vpb-modal-close:hover { color: #d63638; }
.vpb-modal-body { padding: 20px; }

.vpb-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #dcdcde;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.vpb-form-row {
    margin-bottom: 15px;
}

.vpb-form-row label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.vpb-form-row input[type="text"],
.vpb-form-row input[type="number"],
.vpb-form-row select,
.vpb-form-row textarea {
    width: 100%;
}

.vpb-form-row .description {
    color: #646970;
    font-size: 12px;
    margin-top: 4px;
}

.vpb-image-upload {
    display: flex;
    align-items: center;
    gap: 10px;
}

.vpb-image-preview {
    width: 60px;
    height: 60px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f1;
}

.vpb-image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Modal functions
    function openModal() {
        $('#vpb-collection-modal').fadeIn(200);
    }

    function closeModal() {
        $('#vpb-collection-modal').fadeOut(200);
        resetForm();
    }

    function resetForm() {
        $('#vpb-collection-form')[0].reset();
        $('#vpb-collection-id').val('');
        $('#vpb-collection-modal-title').text('Ajouter une collection');
        $('#vpb-collection-thumbnail-preview').empty();
        $('#vpb-collection-thumbnail').val('');
        $('#vpb-collection-thumbnail-remove').hide();
        $('#vpb-collection-color').val('#4F9ED9');
        $('#vpb-collection-color-text').val('#4F9ED9');
    }

    // Sync color inputs
    $('#vpb-collection-color').on('input', function() {
        $('#vpb-collection-color-text').val($(this).val());
    });

    $('#vpb-collection-color-text').on('input', function() {
        var val = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            $('#vpb-collection-color').val(val);
        }
    });

    // Open modal for new collection
    $('#vpb-add-collection, #vpb-add-collection-empty').on('click', function() {
        resetForm();
        openModal();
    });

    // Close modal
    $('.vpb-modal-close').on('click', closeModal);
    $('#vpb-collection-modal').on('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Edit collection
    $('.vpb-edit-collection').on('click', function() {
        var id = $(this).data('id');
        var card = $(this).closest('.vpb-collection-card');

        // Fetch collection data via AJAX or use data attributes
        // For simplicity, we'll make an AJAX call
        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vpb_get_collection',
                nonce: vpbAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    var c = response.data;
                    $('#vpb-collection-id').val(c.id);
                    $('#vpb-collection-name').val(c.name);
                    $('#vpb-collection-slug').val(c.slug);
                    $('#vpb-collection-description').val(c.description);
                    $('#vpb-collection-color').val(c.color_hex);
                    $('#vpb-collection-color-text').val(c.color_hex);
                    $('#vpb-collection-order').val(c.sort_order);
                    $('#vpb-collection-active').prop('checked', c.active == 1);

                    if (c.thumbnail_url) {
                        $('#vpb-collection-thumbnail').val(c.thumbnail_url);
                        $('#vpb-collection-thumbnail-preview').html('<img src="' + c.thumbnail_url + '">');
                        $('#vpb-collection-thumbnail-remove').show();
                    }

                    $('#vpb-collection-modal-title').text('Modifier la collection');
                    openModal();
                }
            }
        });
    });

    // Save collection
    $('#vpb-save-collection').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Enregistrement...');

        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vpb_save_collection',
                nonce: vpbAdmin.nonce,
                id: $('#vpb-collection-id').val(),
                name: $('#vpb-collection-name').val(),
                slug: $('#vpb-collection-slug').val(),
                description: $('#vpb-collection-description').val(),
                color_hex: $('#vpb-collection-color').val(),
                thumbnail_url: $('#vpb-collection-thumbnail').val(),
                sort_order: $('#vpb-collection-order').val(),
                active: $('#vpb-collection-active').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Erreur');
                    btn.prop('disabled', false).text('Enregistrer');
                }
            },
            error: function() {
                alert('Erreur de connexion');
                btn.prop('disabled', false).text('Enregistrer');
            }
        });
    });

    // Delete collection
    $('.vpb-delete-collection').on('click', function() {
        if (!confirm('Voulez-vous vraiment supprimer cette collection ? Les éléments ne seront pas supprimés mais ne seront plus assignés.')) {
            return;
        }

        var id = $(this).data('id');
        var card = $(this).closest('.vpb-collection-card');

        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vpb_delete_collection',
                nonce: vpbAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    card.fadeOut(300, function() {
                        $(this).remove();
                        if ($('.vpb-collection-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.data.message || 'Erreur');
                }
            }
        });
    });

    // Purge all collections and elements
    $('#vpb-purge-collections').on('click', function() {
        var count = $('.vpb-collection-card').length;
        if (!confirm('Voulez-vous vraiment TOUT supprimer ?\n\n• ' + count + ' collections\n• Tous les éléments\n\nCette action est irréversible.')) {
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).text('Suppression...');

        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vpb_purge_collections',
                nonce: vpbAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Erreur');
                    btn.prop('disabled', false).text('Tout supprimer');
                }
            },
            error: function() {
                alert('Erreur de connexion');
                btn.prop('disabled', false).text('Tout supprimer');
            }
        });
    });

    // Thumbnail upload
    $('#vpb-collection-thumbnail-btn').on('click', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: 'Choisir une miniature',
            button: { text: 'Utiliser cette image' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#vpb-collection-thumbnail').val(attachment.url);
            $('#vpb-collection-thumbnail-preview').html('<img src="' + attachment.url + '">');
            $('#vpb-collection-thumbnail-remove').show();
        });

        frame.open();
    });

    // Remove thumbnail
    $('#vpb-collection-thumbnail-remove').on('click', function() {
        $('#vpb-collection-thumbnail').val('');
        $('#vpb-collection-thumbnail-preview').empty();
        $(this).hide();
    });
});
</script>
