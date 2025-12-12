<?php
/**
 * Admin Elements Library Page
 *
 * @package VisualProductBuilder
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
    <h1>
        <?php esc_html_e( 'Element Library', 'visual-product-builder' ); ?>
        <button type="button" class="page-title-action" id="vpb-add-element">
            <?php esc_html_e( 'Add Element', 'visual-product-builder' ); ?>
        </button>
    </h1>

    <div class="vpb-elements-grid">
        <?php if ( empty( $elements ) ) : ?>
            <p class="vpb-no-elements">
                <?php esc_html_e( 'No elements found. Click "Add Element" to create your first element.', 'visual-product-builder' ); ?>
            </p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;"><?php esc_html_e( 'Preview', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Category', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Color', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Price', 'visual-product-builder' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'visual-product-builder' ); ?></th>
                        <th style="width: 100px;"><?php esc_html_e( 'Actions', 'visual-product-builder' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $elements as $element ) : ?>
                        <tr data-id="<?php echo esc_attr( $element['id'] ); ?>">
                            <td>
                                <img src="<?php echo esc_url( $element['svg_file'] ); ?>"
                                     alt="<?php echo esc_attr( $element['name'] ); ?>"
                                     style="width: 40px; height: 40px; object-fit: contain;">
                            </td>
                            <td><strong><?php echo esc_html( $element['name'] ); ?></strong></td>
                            <td><?php echo esc_html( ucfirst( $element['category'] ) ); ?></td>
                            <td><?php echo esc_html( $element['color'] ); ?></td>
                            <td><?php echo wc_price( $element['price'] ); ?></td>
                            <td>
                                <?php if ( $element['active'] ) : ?>
                                    <span class="vpb-status vpb-status-active"><?php esc_html_e( 'Active', 'visual-product-builder' ); ?></span>
                                <?php else : ?>
                                    <span class="vpb-status vpb-status-inactive"><?php esc_html_e( 'Inactive', 'visual-product-builder' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button vpb-edit-element" data-id="<?php echo esc_attr( $element['id'] ); ?>">
                                    <?php esc_html_e( 'Edit', 'visual-product-builder' ); ?>
                                </button>
                                <button type="button" class="button vpb-delete-element" data-id="<?php echo esc_attr( $element['id'] ); ?>">
                                    <?php esc_html_e( 'Delete', 'visual-product-builder' ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Element Modal -->
<div id="vpb-element-modal" class="vpb-modal" style="display: none;">
    <div class="vpb-modal-content">
        <div class="vpb-modal-header">
            <h2 id="vpb-modal-title"><?php esc_html_e( 'Add Element', 'visual-product-builder' ); ?></h2>
            <button type="button" class="vpb-modal-close">&times;</button>
        </div>
        <form id="vpb-element-form">
            <input type="hidden" name="id" id="vpb-element-id" value="0">

            <div class="vpb-form-row">
                <label for="vpb-element-name"><?php esc_html_e( 'Name', 'visual-product-builder' ); ?> *</label>
                <input type="text" id="vpb-element-name" name="name" required>
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-slug"><?php esc_html_e( 'Slug', 'visual-product-builder' ); ?></label>
                <input type="text" id="vpb-element-slug" name="slug" placeholder="<?php esc_attr_e( 'Auto-generated from name', 'visual-product-builder' ); ?>">
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-category"><?php esc_html_e( 'Category', 'visual-product-builder' ); ?></label>
                <select id="vpb-element-category" name="category">
                    <option value="letter"><?php esc_html_e( 'Letter', 'visual-product-builder' ); ?></option>
                    <option value="number"><?php esc_html_e( 'Number', 'visual-product-builder' ); ?></option>
                    <option value="shape"><?php esc_html_e( 'Shape', 'visual-product-builder' ); ?></option>
                </select>
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-color"><?php esc_html_e( 'Color', 'visual-product-builder' ); ?></label>
                <input type="text" id="vpb-element-color" name="color" placeholder="blue, red, pink...">
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-svg"><?php esc_html_e( 'Image', 'visual-product-builder' ); ?> *</label>
                <div class="vpb-image-field">
                    <input type="text" id="vpb-element-svg" name="svg_file" required>
                    <button type="button" class="button vpb-select-image"><?php esc_html_e( 'Select', 'visual-product-builder' ); ?></button>
                </div>
                <p class="description"><?php esc_html_e( 'PNG, JPG or SVG. Recommended size: 100x100px with transparent background.', 'visual-product-builder' ); ?></p>
                <div id="vpb-svg-preview" class="vpb-image-preview"></div>
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-price"><?php esc_html_e( 'Price', 'visual-product-builder' ); ?></label>
                <input type="number" id="vpb-element-price" name="price" step="0.01" min="0" value="0">
            </div>

            <div class="vpb-form-row">
                <label for="vpb-element-order"><?php esc_html_e( 'Sort Order', 'visual-product-builder' ); ?></label>
                <input type="number" id="vpb-element-order" name="sort_order" min="0" value="0">
            </div>

            <div class="vpb-form-row">
                <label>
                    <input type="checkbox" id="vpb-element-active" name="active" checked>
                    <?php esc_html_e( 'Active', 'visual-product-builder' ); ?>
                </label>
            </div>

            <div class="vpb-form-actions">
                <button type="button" class="button vpb-modal-close"><?php esc_html_e( 'Cancel', 'visual-product-builder' ); ?></button>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Element', 'visual-product-builder' ); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
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
