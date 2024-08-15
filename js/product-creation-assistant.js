jQuery(document).ready(function ($) {
    // Add existing attribute row
    $('#pca-add-existing-attribute').on('change', function () {
        var attribute_name = $(this).val();
        var attribute_label = $(this).find('option:selected').text();

        if (attribute_name !== '') {
            var data = {
                action: 'pca_add_existing_attribute_row',
                attribute_name: attribute_name,
                attribute_label: attribute_label,
                security: pca_ajax.security // Use the localized nonce
            };

            $.post(pca_ajax.ajax_url, data, function (response) {
                $('#pca-attributes-wrapper').append(response);
                $('.wc-enhanced-select').select2(); // Reinitialize select2
            });
        }
    });

    // Handle remove attribute row
    $(document).on('click', '.remove_row', function (e) {
        e.preventDefault();
        $(this).closest('.woocommerce_attribute').remove();
    });

    // Handle create new term
    $(document).on('click', '.pca-create-new-term', function (e) {
        e.preventDefault();
        alert('Handle term creation here.');
    });

    // Make attributes sortable
    $('#pca-attributes-wrapper').sortable({
        items: '.woocommerce_attribute',
        cursor: 'move',
        handle: 'h3',
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        forceHelperSize: false,
        helper: 'clone',
        opacity: 0.65,
        placeholder: 'wc-metabox-sortable-placeholder',
        start: function (event, ui) {
            ui.item.css('background-color', '#f6f6f6');
        },
        stop: function (event, ui) {
            ui.item.removeAttr('style');
        }
    });
});
