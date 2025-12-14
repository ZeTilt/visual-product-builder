<?php
/**
 * Element Library Admin Page
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;

// Get collections for filter and dropdown
$collections = VPB_Collection::get_collections();
$collection_filter = isset( $_GET['collection'] ) ? absint( $_GET['collection'] ) : 0;

// Filter elements if collection is specified
if ( $collection_filter ) {
    $elements = array_filter( $elements, function( $el ) use ( $collection_filter ) {
        return isset( $el['collection_id'] ) && $el['collection_id'] == $collection_filter;
    });
}
?>

<div class="wrap vpb-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Element Library', 'visual-product-builder' ); ?></h1>
    <button type="button" class="page-title-action" id="vpb-add-element"><?php esc_html_e( 'Add Element', 'visual-product-builder' ); ?></button>
    <button type="button" class="page-title-action" id="vpb-import-sample"><?php esc_html_e( 'Import Sample Data', 'visual-product-builder' ); ?></button>
    <hr class="wp-header-end">

    <!-- Filtres -->
    <div class="vpb-filters">
        <label for="vpb-filter-collection"><?php esc_html_e( 'Collection:', 'visual-product-builder' ); ?></label>
        <select id="vpb-filter-collection">
            <option value=""><?php esc_html_e( 'All collections', 'visual-product-builder' ); ?></option>
            <option value="0" <?php selected( $collection_filter, 0 ); ?>><?php esc_html_e( 'No collection', 'visual-product-builder' ); ?></option>
            <?php foreach ( $collections as $col ) : ?>
                <option value="<?php echo esc_attr( $col->id ); ?>" <?php selected( $collection_filter, $col->id ); ?>>
                    <?php echo esc_html( $col->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="vpb-elements-grid">
        <?php if ( empty( $elements ) ) : ?>
            <div class="vpb-empty-state">
                <p><?php esc_html_e( 'No elements found.', 'visual-product-builder' ); ?></p>
                <?php if ( $collection_filter ) : ?>
                    <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=vpb-elements' ) ); ?>"><?php esc_html_e( 'View all elements', 'visual-product-builder' ); ?></a></p>
                <?php else : ?>
                    <p><?php esc_html_e( 'Click "Add Element" or "Import Sample Data" to get started.', 'visual-product-builder' ); ?></p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <!-- Barre d'actions en masse -->
            <div class="vpb-bulk-actions" style="display: none;">
                <span class="vpb-selected-count">0 <?php esc_html_e( 'selected', 'visual-product-builder' ); ?></span>
                <button type="button" class="button" id="vpb-bulk-price"><?php esc_html_e( 'Edit price', 'visual-product-builder' ); ?></button>
                <button type="button" class="button" id="vpb-bulk-collection"><?php esc_html_e( 'Assign collection', 'visual-product-builder' ); ?></button>
                <button type="button" class="button" id="vpb-bulk-deselect"><?php esc_html_e( 'Deselect', 'visual-product-builder' ); ?></button>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="vpb-select-all" title="<?php esc_attr_e( 'Select all', 'visual-product-builder' ); ?>">
                        </th>
                        <th style="width: 60px;"><?php esc_html_e( 'Preview', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Category', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Collection', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Color', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Price', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'visual-product-builder' ); ?></th>
                        <th style="width: 130px;"><?php esc_html_e( 'Actions', 'visual-product-builder' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $elements as $element ) : ?>
                        <?php
                        $collection_name = '';
                        $collection_color = '';
                        if ( ! empty( $element['collection_id'] ) ) {
                            $el_collection = VPB_Collection::get_collection( $element['collection_id'] );
                            if ( $el_collection ) {
                                $collection_name = $el_collection->name;
                                $collection_color = $el_collection->color_hex;
                            }
                        }
                        ?>
                        <tr data-id="<?php echo esc_attr( $element['id'] ); ?>" data-price="<?php echo esc_attr( $element['price'] ); ?>">
                            <td>
                                <input type="checkbox" class="vpb-element-checkbox" value="<?php echo esc_attr( $element['id'] ); ?>">
                            </td>
                            <td>
                                <div class="vpb-element-preview" style="color: <?php echo esc_attr( $element['color_hex'] ?? '#4F9ED9' ); ?>;">
                                    <img src="<?php echo esc_url( $element['svg_file'] ); ?>"
                                         alt="<?php echo esc_attr( $element['name'] ); ?>"
                                         style="width: 40px; height: 40px; object-fit: contain;">
                                </div>
                            </td>
                            <td><strong><?php echo esc_html( $element['name'] ); ?></strong></td>
                            <td><?php echo esc_html( ucfirst( $element['category'] ) ); ?></td>
                            <td>
                                <?php if ( $collection_name ) : ?>
                                    <span class="vpb-collection-badge" style="border-left: 3px solid <?php echo esc_attr( $collection_color ); ?>;">
                                        <?php echo esc_html( $collection_name ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="vpb-no-collection">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="vpb-color-swatch" style="background: <?php echo esc_attr( $element['color_hex'] ?? '#4F9ED9' ); ?>;"></span>
                                <?php echo esc_html( $element['color'] ?: '—' ); ?>
                            </td>
                            <td class="vpb-price-cell"><?php echo wc_price( $element['price'] ); ?></td>
                            <td>
                                <?php if ( $element['active'] ) : ?>
                                    <span class="vpb-status vpb-status-active"><?php esc_html_e( 'Active', 'visual-product-builder' ); ?></span>
                                <?php else : ?>
                                    <span class="vpb-status vpb-status-inactive"><?php esc_html_e( 'Inactive', 'visual-product-builder' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small vpb-edit-element" data-id="<?php echo esc_attr( $element['id'] ); ?>">
                                    <?php esc_html_e( 'Edit', 'visual-product-builder' ); ?>
                                </button>
                                <button type="button" class="button button-small vpb-delete-element" data-id="<?php echo esc_attr( $element['id'] ); ?>">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
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
            <h2 id="vpb-modal-title"><?php esc_html_e( 'Add Element', 'visual-product-builder' ); ?></h2>
            <button type="button" class="vpb-modal-close">&times;</button>
        </div>
        <div class="vpb-modal-body">
            <form id="vpb-element-form">
                <input type="hidden" name="id" id="vpb-element-id" value="0">

                <div class="vpb-form-row">
                    <label for="vpb-element-name"><?php esc_html_e( 'Name *', 'visual-product-builder' ); ?></label>
                    <input type="text" id="vpb-element-name" name="name" required>
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-element-slug"><?php esc_html_e( 'Slug', 'visual-product-builder' ); ?></label>
                    <input type="text" id="vpb-element-slug" name="slug" placeholder="<?php esc_attr_e( 'Auto-generated', 'visual-product-builder' ); ?>">
                </div>

                <div class="vpb-form-row vpb-form-row-2col">
                    <div>
                        <label for="vpb-element-category"><?php esc_html_e( 'Category', 'visual-product-builder' ); ?></label>
                        <select id="vpb-element-category" name="category">
                            <option value="letter"><?php esc_html_e( 'Letter', 'visual-product-builder' ); ?></option>
                            <option value="number"><?php esc_html_e( 'Number', 'visual-product-builder' ); ?></option>
                            <option value="symbol"><?php esc_html_e( 'Symbol', 'visual-product-builder' ); ?></option>
                        </select>
                    </div>
                    <div>
                        <label for="vpb-element-collection"><?php esc_html_e( 'Collection', 'visual-product-builder' ); ?></label>
                        <select id="vpb-element-collection" name="collection_id">
                            <option value=""><?php esc_html_e( '-- None --', 'visual-product-builder' ); ?></option>
                            <?php foreach ( $collections as $col ) : ?>
                                <option value="<?php echo esc_attr( $col->id ); ?>">
                                    <?php echo esc_html( $col->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="vpb-form-row vpb-form-row-2col">
                    <div>
                        <label for="vpb-element-color"><?php esc_html_e( 'Color name', 'visual-product-builder' ); ?></label>
                        <input type="text" id="vpb-element-color" name="color" placeholder="<?php esc_attr_e( 'blue, pink...', 'visual-product-builder' ); ?>">
                    </div>
                    <div>
                        <label for="vpb-element-color-hex"><?php esc_html_e( 'Color (hex)', 'visual-product-builder' ); ?></label>
                        <div class="vpb-color-picker-wrapper">
                            <input type="color" id="vpb-element-color-picker" value="#4F9ED9">
                            <input type="text" id="vpb-element-color-hex" name="color_hex" value="#4F9ED9" maxlength="7">
                        </div>
                    </div>
                </div>

                <div class="vpb-form-row">
                    <label for="vpb-element-svg"><?php esc_html_e( 'Image *', 'visual-product-builder' ); ?></label>
                    <div class="vpb-image-field">
                        <input type="text" id="vpb-element-svg" name="svg_file" required>
                        <button type="button" class="button vpb-select-image"><?php esc_html_e( 'Choose', 'visual-product-builder' ); ?></button>
                    </div>
                    <p class="description"><?php esc_html_e( 'PNG, JPG or SVG. For SVG, use fill="currentColor" for colorization.', 'visual-product-builder' ); ?></p>
                    <div id="vpb-svg-preview" class="vpb-image-preview"></div>
                </div>

                <div class="vpb-form-row vpb-form-row-2col">
                    <div>
                        <label for="vpb-element-price"><?php esc_html_e( 'Price', 'visual-product-builder' ); ?></label>
                        <input type="number" id="vpb-element-price" name="price" step="0.01" min="0" value="0">
                    </div>
                    <div>
                        <label for="vpb-element-order"><?php esc_html_e( 'Sort order', 'visual-product-builder' ); ?></label>
                        <input type="number" id="vpb-element-order" name="sort_order" min="0" value="0">
                    </div>
                </div>

                <div class="vpb-form-row">
                    <label>
                        <input type="checkbox" id="vpb-element-active" name="active" checked>
                        <?php esc_html_e( 'Active element', 'visual-product-builder' ); ?>
                    </label>
                </div>
            </form>
        </div>
        <div class="vpb-modal-footer">
            <button type="button" class="button vpb-modal-close"><?php esc_html_e( 'Cancel', 'visual-product-builder' ); ?></button>
            <button type="button" class="button button-primary" id="vpb-save-element"><?php esc_html_e( 'Save', 'visual-product-builder' ); ?></button>
        </div>
    </div>
</div>

<!-- Modal Prix en masse -->
<div id="vpb-bulk-price-modal" class="vpb-modal" style="display: none;">
    <div class="vpb-modal-content" style="width: 400px;">
        <div class="vpb-modal-header">
            <h2><?php esc_html_e( 'Edit price', 'visual-product-builder' ); ?></h2>
            <button type="button" class="vpb-modal-close">&times;</button>
        </div>
        <div class="vpb-modal-body">
            <form id="vpb-bulk-price-form">
                <p class="vpb-bulk-info"></p>
                <div class="vpb-form-row">
                    <label><input type="radio" name="price_mode" value="set" checked> <?php esc_html_e( 'Set to', 'visual-product-builder' ); ?></label>
                    <div class="vpb-price-input-row">
                        <input type="number" id="vpb-bulk-price-value" step="0.01" min="0" value="0"> <?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
                    </div>
                </div>
                <div class="vpb-form-row">
                    <label><input type="radio" name="price_mode" value="add"> <?php esc_html_e( 'Add', 'visual-product-builder' ); ?></label>
                </div>
                <div class="vpb-form-row">
                    <label><input type="radio" name="price_mode" value="subtract"> <?php esc_html_e( 'Subtract', 'visual-product-builder' ); ?></label>
                </div>
            </form>
        </div>
        <div class="vpb-modal-footer">
            <button type="button" class="button vpb-modal-close"><?php esc_html_e( 'Cancel', 'visual-product-builder' ); ?></button>
            <button type="button" class="button button-primary" id="vpb-apply-bulk-price"><?php esc_html_e( 'Apply', 'visual-product-builder' ); ?></button>
        </div>
    </div>
</div>

<!-- Modal Collection en masse -->
<div id="vpb-bulk-collection-modal" class="vpb-modal" style="display: none;">
    <div class="vpb-modal-content" style="width: 400px;">
        <div class="vpb-modal-header">
            <h2><?php esc_html_e( 'Assign to collection', 'visual-product-builder' ); ?></h2>
            <button type="button" class="vpb-modal-close">&times;</button>
        </div>
        <div class="vpb-modal-body">
            <p class="vpb-bulk-info"></p>
            <div class="vpb-form-row">
                <label for="vpb-bulk-collection-select"><?php esc_html_e( 'Collection', 'visual-product-builder' ); ?></label>
                <select id="vpb-bulk-collection-select">
                    <option value=""><?php esc_html_e( '-- Remove from all collections --', 'visual-product-builder' ); ?></option>
                    <?php foreach ( $collections as $col ) : ?>
                        <option value="<?php echo esc_attr( $col->id ); ?>">
                            <?php echo esc_html( $col->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="vpb-modal-footer">
            <button type="button" class="button vpb-modal-close"><?php esc_html_e( 'Cancel', 'visual-product-builder' ); ?></button>
            <button type="button" class="button button-primary" id="vpb-apply-bulk-collection"><?php esc_html_e( 'Apply', 'visual-product-builder' ); ?></button>
        </div>
    </div>
</div>

<style>
.vpb-filters {
    background: #fff;
    padding: 15px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin: 20px 0;
}
.vpb-filters label {
    font-weight: 600;
    margin-right: 10px;
}
.vpb-filters select {
    min-width: 200px;
}

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

.vpb-collection-badge {
    background: #f0f0f1;
    padding: 3px 8px;
    font-size: 12px;
    border-radius: 3px;
}
.vpb-no-collection {
    color: #999;
}

.vpb-color-swatch {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    vertical-align: middle;
    margin-right: 5px;
    border: 1px solid rgba(0,0,0,0.1);
}

.vpb-element-preview img {
    filter: drop-shadow(0 0 0 currentColor);
}

.vpb-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}
.vpb-status-active { background: #d4edda; color: #155724; }
.vpb-status-inactive { background: #f8d7da; color: #721c24; }

.vpb-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

/* Modal styles */
.vpb-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.vpb-modal-content {
    background: #fff;
    width: 550px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    border-radius: 4px;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
}
.vpb-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    background: #f6f7f7;
}
.vpb-modal-header h2 { margin: 0; font-size: 18px; }
.vpb-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    color: #666;
}
.vpb-modal-close:hover { color: #d63638; }
.vpb-modal-body { padding: 20px; }
.vpb-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    background: #f6f7f7;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.vpb-form-row { margin-bottom: 15px; }
.vpb-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}
.vpb-form-row input[type="text"],
.vpb-form-row input[type="number"],
.vpb-form-row select,
.vpb-form-row textarea {
    width: 100%;
}
.vpb-form-row-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
.vpb-form-row-2col > div { margin-bottom: 0; }

.vpb-color-picker-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}
.vpb-color-picker-wrapper input[type="color"] {
    width: 40px;
    height: 32px;
    padding: 0;
    border: 1px solid #8c8f94;
    cursor: pointer;
    border-radius: 3px;
}
.vpb-color-picker-wrapper input[type="text"] {
    flex: 1;
    font-family: monospace;
}

.vpb-image-field {
    display: flex;
    gap: 10px;
}
.vpb-image-field input { flex: 1; }
.vpb-image-preview { margin-top: 10px; }
.vpb-image-preview img {
    max-width: 80px;
    max-height: 80px;
    border: 1px solid #ddd;
    padding: 5px;
    background: #f9f9f9;
    border-radius: 4px;
}
.vpb-form-row .description {
    color: #666;
    font-size: 12px;
    margin-top: 5px;
}

.vpb-bulk-info {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 10px 15px;
    margin: 0 0 15px;
}
.vpb-price-input-row {
    margin: 5px 0 0 24px;
}
.vpb-price-input-row input { width: 100px; }
</style>

<script>
jQuery(document).ready(function($) {
    // Collection filter
    $('#vpb-filter-collection').on('change', function() {
        var val = $(this).val();
        var url = '<?php echo esc_url( admin_url( 'admin.php?page=vpb-elements' ) ); ?>';
        if (val !== '') {
            url += '&collection=' + val;
        }
        window.location.href = url;
    });

    // Sync color picker
    $('#vpb-element-color-picker').on('input', function() {
        $('#vpb-element-color-hex').val($(this).val());
    });
    $('#vpb-element-color-hex').on('input', function() {
        var val = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            $('#vpb-element-color-picker').val(val);
        }
    });

    // Modal functions
    function openModal(modalId) {
        $(modalId).fadeIn(200);
    }
    function closeModal() {
        $('.vpb-modal').fadeOut(200);
    }

    $('.vpb-modal-close').on('click', closeModal);
    $('.vpb-modal').on('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Add new element
    $('#vpb-add-element').on('click', function() {
        $('#vpb-element-form')[0].reset();
        $('#vpb-element-id').val(0);
        $('#vpb-modal-title').text(vpbAdmin.i18n.addElement || 'Add Element');
        $('#vpb-svg-preview').empty();
        $('#vpb-element-color-picker').val('#4F9ED9');
        $('#vpb-element-color-hex').val('#4F9ED9');
        openModal('#vpb-element-modal');
    });

    // Edit element
    $('.vpb-edit-element').on('click', function() {
        var id = $(this).data('id');
        var row = $(this).closest('tr');

        // Get element data via AJAX
        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: { action: 'vpb_get_element', nonce: vpbAdmin.nonce, id: id },
            success: function(response) {
                if (response.success) {
                    var el = response.data;
                    $('#vpb-element-id').val(el.id);
                    $('#vpb-element-name').val(el.name);
                    $('#vpb-element-slug').val(el.slug);
                    $('#vpb-element-category').val(el.category);
                    $('#vpb-element-collection').val(el.collection_id || '');
                    $('#vpb-element-color').val(el.color);
                    $('#vpb-element-color-hex').val(el.color_hex || '#4F9ED9');
                    $('#vpb-element-color-picker').val(el.color_hex || '#4F9ED9');
                    $('#vpb-element-svg').val(el.svg_file);
                    $('#vpb-element-price').val(el.price);
                    $('#vpb-element-order').val(el.sort_order);
                    $('#vpb-element-active').prop('checked', el.active == 1);

                    if (el.svg_file) {
                        $('#vpb-svg-preview').html('<img src="' + el.svg_file + '">');
                    }

                    $('#vpb-modal-title').text(vpbAdmin.i18n.editElement || 'Edit Element');
                    openModal('#vpb-element-modal');
                }
            }
        });
    });

    // Save element
    $('#vpb-save-element').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text(vpbAdmin.i18n.saving || 'Saving...');

        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vpb_save_element',
                nonce: vpbAdmin.nonce,
                id: $('#vpb-element-id').val(),
                name: $('#vpb-element-name').val(),
                slug: $('#vpb-element-slug').val(),
                category: $('#vpb-element-category').val(),
                collection_id: $('#vpb-element-collection').val(),
                color: $('#vpb-element-color').val(),
                color_hex: $('#vpb-element-color-hex').val(),
                svg_file: $('#vpb-element-svg').val(),
                price: $('#vpb-element-price').val(),
                sort_order: $('#vpb-element-order').val(),
                active: $('#vpb-element-active').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || vpbAdmin.i18n.error || 'Error');
                    btn.prop('disabled', false).text(vpbAdmin.i18n.save || 'Save');
                }
            },
            error: function() {
                alert(vpbAdmin.i18n.connectionError || 'Connection error');
                btn.prop('disabled', false).text(vpbAdmin.i18n.save || 'Save');
            }
        });
    });

    // Delete element
    $('.vpb-delete-element').on('click', function() {
        if (!confirm(vpbAdmin.i18n.confirmDelete)) return;

        var id = $(this).data('id');
        var row = $(this).closest('tr');

        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: { action: 'vpb_delete_element', nonce: vpbAdmin.nonce, id: id },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() { $(this).remove(); });
                } else {
                    alert(response.data.message || vpbAdmin.i18n.error || 'Error');
                }
            }
        });
    });

    // Image selector
    $('.vpb-select-image').on('click', function(e) {
        e.preventDefault();
        var input = $(this).siblings('input');
        var preview = $('#vpb-svg-preview');

        var frame = wp.media({
            title: vpbAdmin.i18n.selectImage,
            button: { text: vpbAdmin.i18n.useImage },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            input.val(attachment.url);
            preview.html('<img src="' + attachment.url + '">');
        });

        frame.open();
    });

    // Bulk selection
    var selectedIds = [];

    function updateBulkBar() {
        var count = selectedIds.length;
        var selectedText = vpbAdmin.i18n.selected || 'selected';
        $('.vpb-selected-count').text(count + ' ' + selectedText);
        if (count > 0) {
            $('.vpb-bulk-actions').show();
        } else {
            $('.vpb-bulk-actions').hide();
        }
    }

    $('#vpb-select-all').on('change', function() {
        var checked = $(this).is(':checked');
        $('.vpb-element-checkbox').prop('checked', checked);
        selectedIds = checked ? $('.vpb-element-checkbox').map(function() { return $(this).val(); }).get() : [];
        updateBulkBar();
    });

    $(document).on('change', '.vpb-element-checkbox', function() {
        var val = $(this).val();
        if ($(this).is(':checked')) {
            if (selectedIds.indexOf(val) === -1) selectedIds.push(val);
        } else {
            selectedIds = selectedIds.filter(function(id) { return id !== val; });
        }
        updateBulkBar();
    });

    $('#vpb-bulk-deselect').on('click', function() {
        $('.vpb-element-checkbox, #vpb-select-all').prop('checked', false);
        selectedIds = [];
        updateBulkBar();
    });

    // Bulk price
    $('#vpb-bulk-price').on('click', function() {
        var elementsSelectedText = vpbAdmin.i18n.elementsSelected || 'elements selected';
        $('.vpb-bulk-info').text(selectedIds.length + ' ' + elementsSelectedText);
        openModal('#vpb-bulk-price-modal');
    });

    $('#vpb-apply-bulk-price').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vpb_bulk_update_price',
                nonce: vpbAdmin.nonce,
                ids: selectedIds,
                price: $('#vpb-bulk-price-value').val(),
                mode: $('input[name="price_mode"]:checked').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || vpbAdmin.i18n.error || 'Error');
                    btn.prop('disabled', false);
                }
            }
        });
    });

    // Bulk collection
    $('#vpb-bulk-collection').on('click', function() {
        var elementsSelectedText = vpbAdmin.i18n.elementsSelected || 'elements selected';
        $('#vpb-bulk-collection-modal .vpb-bulk-info').text(selectedIds.length + ' ' + elementsSelectedText);
        openModal('#vpb-bulk-collection-modal');
    });

    $('#vpb-apply-bulk-collection').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vpb_bulk_assign_collection',
                nonce: vpbAdmin.nonce,
                ids: selectedIds,
                collection_id: $('#vpb-bulk-collection-select').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || vpbAdmin.i18n.error || 'Error');
                    btn.prop('disabled', false);
                }
            }
        });
    });

    // Import sample data
    $('#vpb-import-sample').on('click', function() {
        if (!confirm(vpbAdmin.i18n.confirmImportSample || 'Import sample data? This will add elements to your library.')) return;

        var btn = $(this);
        btn.prop('disabled', true).text(vpbAdmin.i18n.importing || 'Importing...');

        $.ajax({
            url: vpbAdmin.ajaxUrl,
            type: 'POST',
            data: { action: 'vpb_import_sample_data', nonce: vpbAdmin.nonce },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || vpbAdmin.i18n.error || 'Error');
                    btn.prop('disabled', false).text(vpbAdmin.i18n.importSampleData || 'Import Sample Data');
                }
            }
        });
    });
});
</script>
