/**
 * Visual Product Builder - Admin Scripts
 */
(function($) {
    'use strict';

    // Element modal (initialized after DOM ready)
    let modal, form, bulkModal;

    // Initialize
    $(document).ready(function() {
        // Cache DOM elements after DOM is ready
        modal = $('#vpb-element-modal');
        form = $('#vpb-element-form');
        bulkModal = $('#vpb-bulk-price-modal');

        bindEvents();
        bindBulkEvents();
    });

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Add new element
        $('#vpb-add-element').on('click', function() {
            openModal();
        });

        // Edit element
        $('.vpb-edit-element').on('click', function() {
            const id = $(this).data('id');
            openModal(id);
        });

        // Delete element
        $('.vpb-delete-element').on('click', function() {
            const id = $(this).data('id');
            deleteElement(id);
        });

        // Close modal
        $('.vpb-modal-close').on('click', closeModal);

        // Close modal on overlay click
        modal.on('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Form submit
        form.on('submit', saveElement);

        // Media library for SVG selection
        $('.vpb-select-image').on('click', selectImage);

        // Image preview
        $('#vpb-element-svg').on('change input', updateImagePreview);

        // Import sample data
        $('#vpb-import-sample-data').on('click', importSampleData);
    }

    /**
     * Open modal for add/edit
     */
    function openModal(id = 0) {
        // Reset form
        form[0].reset();
        $('#vpb-element-id').val(0);
        $('#vpb-element-active').prop('checked', true);
        $('#vpb-svg-preview').html('');

        if (id > 0) {
            // Edit mode - populate form from table row
            $('#vpb-modal-title').text('Edit Element');
            $('#vpb-element-id').val(id);

            const row = $(`tr[data-id="${id}"]`);
            // Note: In a real implementation, you'd fetch the full data via AJAX
            // For now, we'll use what's visible in the table
        } else {
            $('#vpb-modal-title').text('Add Element');
        }

        modal.show();
    }

    /**
     * Close modal
     */
    function closeModal() {
        modal.hide();
    }

    /**
     * Save element via AJAX
     */
    function saveElement(e) {
        e.preventDefault();

        const data = {
            action: 'vpb_save_element',
            nonce: vpbAdmin.nonce,
            id: $('#vpb-element-id').val(),
            name: $('#vpb-element-name').val(),
            slug: $('#vpb-element-slug').val(),
            category: $('#vpb-element-category').val(),
            color: $('#vpb-element-color').val(),
            svg_file: $('#vpb-element-svg').val(),
            price: $('#vpb-element-price').val(),
            sort_order: $('#vpb-element-order').val(),
            active: $('#vpb-element-active').is(':checked') ? 1 : 0
        };

        $.post(vpbAdmin.ajaxUrl, data, function(response) {
            if (response.success) {
                alert(vpbAdmin.i18n.saved);
                location.reload();
            } else {
                alert(response.data.message || vpbAdmin.i18n.error);
            }
        }).fail(function() {
            alert(vpbAdmin.i18n.error);
        });
    }

    /**
     * Delete element
     */
    function deleteElement(id) {
        if (!confirm(vpbAdmin.i18n.confirmDelete)) {
            return;
        }

        $.post(vpbAdmin.ajaxUrl, {
            action: 'vpb_delete_element',
            nonce: vpbAdmin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                $(`tr[data-id="${id}"]`).fadeOut(function() {
                    $(this).remove();
                });
            } else {
                alert(response.data.message || vpbAdmin.i18n.error);
            }
        }).fail(function() {
            alert(vpbAdmin.i18n.error);
        });
    }

    /**
     * Open media library for image selection
     */
    function selectImage(e) {
        e.preventDefault();

        const frame = wp.media({
            title: vpbAdmin.i18n.selectImage,
            button: { text: vpbAdmin.i18n.useImage },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            // Use VPB element size (100x100) if available, otherwise use full size
            let url = attachment.url;
            if (attachment.sizes && attachment.sizes['vpb-element']) {
                url = attachment.sizes['vpb-element'].url;
            } else if (attachment.sizes && attachment.sizes.thumbnail) {
                // Fallback to thumbnail if vpb-element not regenerated
                url = attachment.sizes.thumbnail.url;
            }
            $('#vpb-element-svg').val(url).trigger('change');
        });

        frame.open();
    }

    /**
     * Update image preview
     */
    function updateImagePreview() {
        const url = $(this).val();
        const preview = $('#vpb-svg-preview');

        if (url) {
            preview.html(`<img src="${url}" alt="Preview" style="max-width:100px;max-height:100px;">`);
        } else {
            preview.html('');
        }
    }

    /**
     * Import sample data
     */
    function importSampleData() {
        const button = $(this);
        const status = $('#vpb-import-status');

        button.prop('disabled', true);
        status.text('Import en cours...');

        $.post(vpbAdmin.ajaxUrl, {
            action: 'vpb_import_sample_data',
            nonce: vpbAdmin.nonce
        }, function(response) {
            button.prop('disabled', false);
            if (response.success) {
                status.text(response.data.message);
                if (response.data.imported > 0) {
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            } else {
                status.text(response.data.message || vpbAdmin.i18n.error);
            }
        }).fail(function() {
            button.prop('disabled', false);
            status.text(vpbAdmin.i18n.error);
        });
    }

    /**
     * Bind bulk action events
     */
    function bindBulkEvents() {
        // Select all checkbox
        $('#vpb-select-all').on('change', function() {
            const checked = $(this).is(':checked');
            $('.vpb-element-checkbox').prop('checked', checked);
            updateBulkActionsBar();
        });

        // Individual checkboxes
        $(document).on('change', '.vpb-element-checkbox', function() {
            updateBulkActionsBar();
            // Update select all checkbox state
            const total = $('.vpb-element-checkbox').length;
            const selected = $('.vpb-element-checkbox:checked').length;
            $('#vpb-select-all').prop('checked', total === selected);
        });

        // Deselect all
        $('#vpb-bulk-deselect').on('click', function() {
            $('.vpb-element-checkbox, #vpb-select-all').prop('checked', false);
            updateBulkActionsBar();
        });

        // Open bulk price modal
        $('#vpb-bulk-price').on('click', function() {
            const selected = getSelectedIds();
            if (selected.length === 0) return;

            $('.vpb-bulk-info').text(selected.length + ' élément(s) sélectionné(s)');
            $('#vpb-bulk-price-value').val('0');
            $('input[name="price_mode"][value="set"]').prop('checked', true);
            bulkModal.show();
        });

        // Close bulk modal
        bulkModal.find('.vpb-modal-close').on('click', function() {
            bulkModal.hide();
        });

        // Close modal on overlay click
        bulkModal.on('click', function(e) {
            if (e.target === this) bulkModal.hide();
        });

        // Submit bulk price form
        $('#vpb-bulk-price-form').on('submit', function(e) {
            e.preventDefault();
            applyBulkPrice();
        });
    }

    /**
     * Update bulk actions bar visibility
     */
    function updateBulkActionsBar() {
        const selected = $('.vpb-element-checkbox:checked').length;
        const bulkBar = $('.vpb-bulk-actions');

        if (selected > 0) {
            bulkBar.show();
            $('.vpb-selected-count').text(selected + ' sélectionné(s)');
        } else {
            bulkBar.hide();
        }
    }

    /**
     * Get selected element IDs
     */
    function getSelectedIds() {
        return $('.vpb-element-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
    }

    /**
     * Apply bulk price change
     */
    function applyBulkPrice() {
        const ids = getSelectedIds();
        const price = parseFloat($('#vpb-bulk-price-value').val()) || 0;
        const mode = $('input[name="price_mode"]:checked').val();

        if (ids.length === 0) return;

        $.post(vpbAdmin.ajaxUrl, {
            action: 'vpb_bulk_update_price',
            nonce: vpbAdmin.nonce,
            ids: ids,
            price: price,
            mode: mode
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || vpbAdmin.i18n.error);
            }
        }).fail(function() {
            alert(vpbAdmin.i18n.error);
        });
    }

})(jQuery);
