jQuery(document).ready(function ($) {
    // Handle "Add Existing" dropdown change
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
                handleNewAttributeRow(); // Initialize any new interactions for the newly added row
            });
        }
    });

    // Handle remove attribute row
    $(document).on('click', '.remove_row', function (e) {
        e.preventDefault();
        $(this).closest('.woocommerce_attribute').remove();
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

    // Function to handle interactions in a newly added attribute row
    function handleNewAttributeRow() {
        // Reinitialize Select2 for the dropdowns in the new row
        $('.wc-enhanced-select').select2();

        // Handle the add new term button
        $('.add_new_attribute_term').off('click').on('click', function (e) {
            e.preventDefault();
            var row = $(this).closest('.woocommerce_attribute');
            var attributeName = row.find('input[name="pca_attributes_names[]"]').val();
            var newTerm = prompt('Enter new term:');
            if (newTerm) {
                // Add the new term to the select dropdown
                var option = new Option(newTerm, newTerm, true, true);
                row.find('.wc-enhanced-select').append(option).trigger('change');
            }
        });
    }

    // Initialize any existing rows (in case there are some when the page loads)
    handleNewAttributeRow();
});
