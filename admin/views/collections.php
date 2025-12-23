<?php
/**
 * Admin Collections Page
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap vpb-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Collections', 'visual-product-builder' ); ?></h1>
    <button type="button" class="page-title-action" id="vpb-add-collection"><?php esc_html_e( 'Add Collection', 'visual-product-builder' ); ?></button>
    <?php if ( ! empty( $collections ) ) : ?>
        <button type="button" class="page-title-action vpb-danger-action" id="vpb-purge-collections"><?php esc_html_e( 'Purge All', 'visual-product-builder' ); ?></button>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php if ( empty( $collections ) ) : ?>
        <div class="vpb-empty-state">
            <p><?php esc_html_e( 'No collections have been created yet.', 'visual-product-builder' ); ?></p>
            <p><?php esc_html_e( 'Collections allow you to organize your elements (letters, numbers, symbols) and assign them to products.', 'visual-product-builder' ); ?></p>
            <button type="button" class="button button-primary" id="vpb-add-collection-empty"><?php esc_html_e( 'Create Your First Collection', 'visual-product-builder' ); ?></button>
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
                    <?php $is_sample = ! empty( $collection->is_sample ); ?>
                    <div class="vpb-collection-card-body">
                        <h3>
                            <?php echo esc_html( $collection->name ); ?>
                            <?php if ( $is_sample ) : ?>
                                <span class="vpb-badge vpb-badge-sample"><?php esc_html_e( 'Sample', 'visual-product-builder' ); ?></span>
                            <?php endif; ?>
                        </h3>
                        <p class="vpb-collection-meta">
                            <span class="vpb-badge">
                                <?php
                                /* translators: %d: number of elements */
                                echo esc_html( sprintf( _n( '%d element', '%d elements', $element_count, 'visual-product-builder' ), $element_count ) );
                                ?>
                            </span>
                            <?php if ( ! $collection->active ) : ?>
                                <span class="vpb-badge vpb-badge-inactive"><?php esc_html_e( 'Inactive', 'visual-product-builder' ); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if ( $collection->description ) : ?>
                            <p class="vpb-collection-description"><?php echo esc_html( $collection->description ); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="vpb-collection-card-footer">
                        <button type="button" class="button button-primary vpb-import-elements"
                                data-id="<?php echo esc_attr( $collection->id ); ?>"
                                data-name="<?php echo esc_attr( $collection->name ); ?>"
                                data-color="<?php echo esc_attr( $collection->color_hex ); ?>">
                            <span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import', 'visual-product-builder' ); ?>
                        </button>
                        <button type="button" class="button vpb-edit-collection" data-id="<?php echo esc_attr( $collection->id ); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=vpb-elements&collection=' . $collection->id ) ); ?>" class="button">
                            <span class="dashicons dashicons-visibility"></span>
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
            <h2 id="vpb-collection-modal-title"><?php esc_html_e( 'Add Collection', 'visual-product-builder' ); ?></h2>
            <button type="button" class="vpb-modal-close">&times;</button>
        </div>
        <div class="vpb-modal-body">
            <form id="vpb-collection-form">
                <input type="hidden" name="id" id="vpb-collection-id" value="">

                <div class="vpb-form-row">
                    <label for="vpb-collection-name"><?php esc_html_e( 'Name', 'visual-product-builder' ); ?> *</label>
                    <input type="text" id="vpb-collection-name" name="name" required>
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-collection-slug"><?php esc_html_e( 'Slug', 'visual-product-builder' ); ?></label>
                    <input type="text" id="vpb-collection-slug" name="slug" placeholder="<?php esc_attr_e( 'auto-generated', 'visual-product-builder' ); ?>">
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-collection-description"><?php esc_html_e( 'Description', 'visual-product-builder' ); ?></label>
                    <textarea id="vpb-collection-description" name="description" rows="3"></textarea>
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-collection-color"><?php esc_html_e( 'Color', 'visual-product-builder' ); ?></label>
                    <div class="vpb-color-picker-wrapper">
                        <input type="color" id="vpb-collection-color" name="color_hex" value="#4F9ED9">
                        <input type="text" id="vpb-collection-color-text" placeholder="#4F9ED9" maxlength="7">
                    </div>
                    <p class="description"><?php esc_html_e( 'Collection theme color', 'visual-product-builder' ); ?></p>
                </div>

                <div class="vpb-form-row">
                    <label><?php esc_html_e( 'Thumbnail', 'visual-product-builder' ); ?></label>
                    <div class="vpb-image-upload">
                        <div id="vpb-collection-thumbnail-preview" class="vpb-image-preview"></div>
                        <input type="hidden" id="vpb-collection-thumbnail" name="thumbnail_url" value="">
                        <button type="button" class="button" id="vpb-collection-thumbnail-btn"><?php esc_html_e( 'Choose Image', 'visual-product-builder' ); ?></button>
                        <button type="button" class="button vpb-remove-image" id="vpb-collection-thumbnail-remove" style="display: none;"><?php esc_html_e( 'Remove', 'visual-product-builder' ); ?></button>
                    </div>
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-collection-order"><?php esc_html_e( 'Display Order', 'visual-product-builder' ); ?></label>
                    <input type="number" id="vpb-collection-order" name="sort_order" value="0" min="0">
                </div>

                <div class="vpb-form-row">
                    <label>
                        <input type="checkbox" name="active" id="vpb-collection-active" checked>
                        <?php esc_html_e( 'Active Collection', 'visual-product-builder' ); ?>
                    </label>
                </div>
            </form>
        </div>
        <div class="vpb-modal-footer">
            <button type="button" class="button vpb-modal-close"><?php esc_html_e( 'Cancel', 'visual-product-builder' ); ?></button>
            <button type="button" class="button button-primary" id="vpb-save-collection"><?php esc_html_e( 'Save', 'visual-product-builder' ); ?></button>
        </div>
    </div>
</div>

<!-- Import Elements Modal -->
<div id="vpb-import-modal" class="vpb-modal" style="display: none;">
    <div class="vpb-modal-content" style="max-width: 600px;">
        <div class="vpb-modal-header">
            <h2><?php esc_html_e( 'Import Elements into', 'visual-product-builder' ); ?> <span id="vpb-import-collection-name"></span></h2>
            <button type="button" class="vpb-modal-close">&times;</button>
        </div>
        <div class="vpb-modal-body">
            <input type="hidden" id="vpb-import-collection-id" value="">
            <input type="hidden" id="vpb-import-collection-color" value="">

            <div class="vpb-import-dropzone" id="vpb-dropzone">
                <span class="dashicons dashicons-upload" style="font-size: 48px; width: 48px; height: 48px; color: #c3c4c7;"></span>
                <p><?php esc_html_e( 'Drag and drop your images here', 'visual-product-builder' ); ?></p>
                <p><small>SVG, PNG, JPG, GIF, WebP</small></p>
                <button type="button" class="button" id="vpb-select-files"><?php esc_html_e( 'Select Files', 'visual-product-builder' ); ?></button>
                <input type="file" id="vpb-file-input" multiple accept=".svg,.png,.jpg,.jpeg,.gif,.webp" style="display: none;">
            </div>

            <div id="vpb-import-preview" class="vpb-import-preview" style="display: none;">
                <h4><?php esc_html_e( 'Selected Files:', 'visual-product-builder' ); ?> <span id="vpb-file-count">0</span></h4>
                <div id="vpb-file-list" class="vpb-file-list"></div>
            </div>

            <div class="vpb-form-row" style="margin-top: 15px;">
                <label for="vpb-import-category"><?php esc_html_e( 'Category', 'visual-product-builder' ); ?></label>
                <select id="vpb-import-category">
                    <option value="letter"><?php esc_html_e( 'Letter', 'visual-product-builder' ); ?></option>
                    <option value="number"><?php esc_html_e( 'Number', 'visual-product-builder' ); ?></option>
                    <option value="symbol"><?php esc_html_e( 'Symbol', 'visual-product-builder' ); ?></option>
                </select>
            </div>

            <div class="vpb-form-row">
                <label for="vpb-import-price"><?php esc_html_e( 'Price per Element', 'visual-product-builder' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
                <input type="number" id="vpb-import-price" value="0" min="0" step="0.01">
            </div>
        </div>
        <div class="vpb-modal-footer">
            <button type="button" class="button vpb-modal-close"><?php esc_html_e( 'Cancel', 'visual-product-builder' ); ?></button>
            <button type="button" class="button button-primary" id="vpb-start-import" disabled>
                <?php esc_html_e( 'Import', 'visual-product-builder' ); ?> <span id="vpb-import-btn-count"></span>
            </button>
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

.vpb-badge-sample {
    background: #e8f4f8;
    color: #0077b6;
    font-size: 10px;
    vertical-align: middle;
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

/* Import dropzone */
.vpb-import-dropzone {
    border: 2px dashed #c3c4c7;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    background: #f6f7f7;
    transition: all 0.2s;
}

.vpb-import-dropzone.dragover {
    border-color: #2271b1;
    background: #f0f6fc;
}

.vpb-import-dropzone p {
    margin: 10px 0;
    color: #646970;
}

.vpb-file-list {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    background: #fff;
}

.vpb-file-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-bottom: 1px solid #f0f0f1;
    gap: 10px;
}

.vpb-file-item:last-child {
    border-bottom: none;
}

.vpb-file-item .dashicons {
    color: #f0b849;
}

.vpb-file-item .vpb-file-name {
    flex: 1;
    font-family: monospace;
    font-size: 13px;
}

.vpb-file-item .vpb-file-ext {
    background: #f0f0f1;
    color: #646970;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 600;
}

.vpb-file-item .vpb-remove-file {
    color: #b32d2e;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
}

.vpb-file-item .vpb-remove-file:hover {
    color: #d63638;
}

.vpb-import-progress {
    margin-top: 15px;
}

.vpb-progress-bar {
    height: 20px;
    background: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
}

.vpb-progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #135e96);
    transition: width 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
}
</style>

<script>
jQuery(document).ready(function($) {
    /**
     * JAVASCRIPT STRINGS THAT NEED LOCALIZATION
     *
     * These strings should be passed via wp_localize_script() in the enqueue function.
     * Add to class-vpb-admin.php in the script localization array:
     *
     * wp_localize_script('vpb-admin', 'vpbAdmin', [
     *     'ajaxUrl' => admin_url('admin-ajax.php'),
     *     'nonce' => wp_create_nonce('vpb_admin_nonce'),
     *     'i18n' => [
     *         'addCollection' => __('Add Collection', 'visual-product-builder'),
     *         'editCollection' => __('Edit Collection', 'visual-product-builder'),
     *         'saving' => __('Saving...', 'visual-product-builder'),
     *         'save' => __('Save', 'visual-product-builder'),
     *         'error' => __('Error', 'visual-product-builder'),
     *         'connectionError' => __('Connection Error', 'visual-product-builder'),
     *         'confirmDelete' => __('Do you really want to delete this collection? Elements will not be deleted but will no longer be assigned.', 'visual-product-builder'),
     *         'confirmPurge' => __('Do you really want to DELETE EVERYTHING?\n\n• %d collections\n• All elements\n\nThis action is irreversible.', 'visual-product-builder'),
     *         'deleting' => __('Deleting...', 'visual-product-builder'),
     *         'purgeAll' => __('Purge All', 'visual-product-builder'),
     *         'chooseThumbnail' => __('Choose Thumbnail', 'visual-product-builder'),
     *         'useThisImage' => __('Use This Image', 'visual-product-builder'),
     *         'importing' => __('Importing...', 'visual-product-builder'),
     *         'importInProgress' => __('Import in progress...', 'visual-product-builder'),
     *         'elementsImported' => __('%d element(s) imported', 'visual-product-builder'),
     *         'errors' => __('%d error(s):', 'visual-product-builder'),
     *         'networkError' => __('Network Error', 'visual-product-builder')
     *     ]
     * ]);
     */

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
        $('#vpb-collection-modal-title').text(vpbAdmin.i18n.addCollection);
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

                    $('#vpb-collection-modal-title').text(vpbAdmin.i18n.editCollection);
                    openModal();
                }
            }
        });
    });

    // Save collection
    $('#vpb-save-collection').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text(vpbAdmin.i18n.saving);

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
                    alert(response.data.message || vpbAdmin.i18n.error);
                    btn.prop('disabled', false).text(vpbAdmin.i18n.save);
                }
            },
            error: function() {
                alert(vpbAdmin.i18n.connectionError);
                btn.prop('disabled', false).text(vpbAdmin.i18n.save);
            }
        });
    });

    // Delete collection
    $('.vpb-delete-collection').on('click', function() {
        if (!confirm(vpbAdmin.i18n.confirmDelete)) {
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
                    alert(response.data.message || vpbAdmin.i18n.error);
                }
            }
        });
    });

    // Purge all collections and elements
    $('#vpb-purge-collections').on('click', function() {
        var count = $('.vpb-collection-card').length;
        if (!confirm(vpbAdmin.i18n.confirmPurge.replace('%d', count))) {
            return;
        }

        doPurge(false);
    });

    function doPurge(force) {
        var btn = $('#vpb-purge-collections');
        btn.prop('disabled', true).text(vpbAdmin.i18n.deleting);

        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vpb_purge_collections',
                nonce: vpbAdmin.nonce,
                force: force ? 'true' : 'false'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else if (response.data && response.data.requires_force) {
                    // Pending orders detected - require typing "PURGE" to confirm
                    var userInput = prompt(
                        response.data.message + '\n\n' +
                        vpbAdmin.i18n.forcePurgeConfirm
                    );
                    if (userInput === 'PURGE') {
                        doPurge(true);
                    } else {
                        btn.prop('disabled', false).text(vpbAdmin.i18n.purgeAll);
                        if (userInput !== null) {
                            alert(vpbAdmin.i18n.purgeAborted);
                        }
                    }
                } else {
                    alert(response.data.message || vpbAdmin.i18n.error);
                    btn.prop('disabled', false).text(vpbAdmin.i18n.purgeAll);
                }
            },
            error: function() {
                alert(vpbAdmin.i18n.connectionError);
                btn.prop('disabled', false).text(vpbAdmin.i18n.purgeAll);
            }
        });
    }

    // Thumbnail upload
    $('#vpb-collection-thumbnail-btn').on('click', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: vpbAdmin.i18n.chooseThumbnail,
            button: { text: vpbAdmin.i18n.useThisImage },
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

    // ========================================
    // IMPORT ELEMENTS FUNCTIONALITY
    // ========================================

    var importFiles = [];

    // Open import modal
    $('.vpb-import-elements').on('click', function() {
        var btn = $(this);
        $('#vpb-import-collection-id').val(btn.data('id'));
        $('#vpb-import-collection-name').text(btn.data('name'));
        $('#vpb-import-collection-color').val(btn.data('color'));
        importFiles = [];
        updateFileList();
        $('#vpb-import-modal').fadeIn(200);
    });

    // Close import modal
    $('#vpb-import-modal .vpb-modal-close').on('click', function() {
        $('#vpb-import-modal').fadeOut(200);
    });

    $('#vpb-import-modal').on('click', function(e) {
        if (e.target === this) $(this).fadeOut(200);
    });

    // File selection button
    $('#vpb-select-files').on('click', function() {
        $('#vpb-file-input').click();
    });

    // File input change
    $('#vpb-file-input').on('change', function(e) {
        addFiles(e.target.files);
    });

    // Drag and drop
    var dropzone = $('#vpb-dropzone');

    dropzone.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    dropzone.on('dragleave dragend drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    dropzone.on('drop', function(e) {
        var files = e.originalEvent.dataTransfer.files;
        addFiles(files);
    });

    // Add files to list
    var allowedTypes = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    var allowedExtensions = ['svg', 'png', 'jpg', 'jpeg', 'gif', 'webp'];

    function addFiles(files) {
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var ext = file.name.split('.').pop().toLowerCase();
            if (allowedTypes.indexOf(file.type) !== -1 || allowedExtensions.indexOf(ext) !== -1) {
                // Check if not already added
                var exists = importFiles.some(function(f) { return f.name === file.name; });
                if (!exists) {
                    importFiles.push(file);
                }
            }
        }
        updateFileList();
    }

    // Update file list display
    function updateFileList() {
        var list = $('#vpb-file-list');
        list.empty();

        if (importFiles.length === 0) {
            $('#vpb-import-preview').hide();
            $('#vpb-start-import').prop('disabled', true);
            $('#vpb-import-btn-count').text('');
            return;
        }

        $('#vpb-import-preview').show();
        $('#vpb-file-count').text(importFiles.length);
        $('#vpb-start-import').prop('disabled', false);
        $('#vpb-import-btn-count').text('(' + importFiles.length + ')');

        importFiles.forEach(function(file, index) {
            var name = file.name.replace(/\.(svg|png|jpe?g|gif|webp)$/i, '');
            var ext = file.name.split('.').pop().toLowerCase();
            var icon = ext === 'svg' ? 'dashicons-media-code' : 'dashicons-format-image';
            var item = $('<div class="vpb-file-item">' +
                '<span class="dashicons ' + icon + '"></span>' +
                '<span class="vpb-file-name">' + name + '</span>' +
                '<span class="vpb-file-ext">' + ext.toUpperCase() + '</span>' +
                '<button type="button" class="vpb-remove-file" data-index="' + index + '">' +
                '<span class="dashicons dashicons-no-alt"></span>' +
                '</button>' +
                '</div>');
            list.append(item);
        });
    }

    // Remove file from list
    $(document).on('click', '.vpb-remove-file', function() {
        var index = $(this).data('index');
        importFiles.splice(index, 1);
        updateFileList();
    });

    // Start import
    $('#vpb-start-import').on('click', function() {
        if (importFiles.length === 0) return;

        var btn = $(this);
        btn.prop('disabled', true).text(vpbAdmin.i18n.importing);

        var collectionId = $('#vpb-import-collection-id').val();
        var collectionColor = $('#vpb-import-collection-color').val();
        var category = $('#vpb-import-category').val();
        var price = $('#vpb-import-price').val();

        var completed = 0;
        var total = importFiles.length;
        var errors = [];

        // Show progress
        $('#vpb-import-preview').html(
            '<div class="vpb-import-progress">' +
            '<p>' + vpbAdmin.i18n.importInProgress + ' <span id="vpb-progress-text">0/' + total + '</span></p>' +
            '<div class="vpb-progress-bar"><div class="vpb-progress-bar-fill" style="width: 0%"></div></div>' +
            '</div>'
        );

        // Upload files one by one
        function uploadNext(index) {
            if (index >= importFiles.length) {
                // Done
                var message = vpbAdmin.i18n.elementsImported.replace('%d', completed);
                if (errors.length > 0) {
                    message += '\n' + vpbAdmin.i18n.errors.replace('%d', errors.length) + '\n' + errors.join('\n');
                }
                alert(message);
                location.reload();
                return;
            }

            var file = importFiles[index];
            var formData = new FormData();
            formData.append('action', 'vpb_import_element');
            formData.append('nonce', vpbAdmin.nonce);
            formData.append('file', file);
            formData.append('collection_id', collectionId);
            formData.append('color_hex', collectionColor);
            formData.append('category', category);
            formData.append('price', price);

            $.ajax({
                url: vpbAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        completed++;
                    } else {
                        errors.push(file.name + ': ' + (response.data.message || vpbAdmin.i18n.error));
                    }
                },
                error: function() {
                    errors.push(file.name + ': ' + vpbAdmin.i18n.networkError);
                },
                complete: function() {
                    var progress = Math.round(((index + 1) / total) * 100);
                    $('#vpb-progress-text').text((index + 1) + '/' + total);
                    $('.vpb-progress-bar-fill').css('width', progress + '%').text(progress + '%');
                    uploadNext(index + 1);
                }
            });
        }

        uploadNext(0);
    });
});
</script>
