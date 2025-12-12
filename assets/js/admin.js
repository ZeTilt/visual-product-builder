/**
 * Visual Product Builder - Admin Scripts
 */
(function($) {
    'use strict';

    // Element modal (initialized after DOM ready)
    let modal, form;

    // Initialize
    $(document).ready(function() {
        // Cache DOM elements after DOM is ready
        modal = $('#vpb-element-modal');
        form = $('#vpb-element-form');

        bindEvents();
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
        status.text('Importing...');

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

})(jQuery);
