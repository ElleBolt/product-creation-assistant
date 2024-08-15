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
            var taxonomy = $(this).data('taxonomy');
            var newTerm = prompt('Enter new term:');
            if (newTerm) {
                // Simulate AJAX term creation for the demo
                var option = new Option(newTerm, newTerm, true, true);
                row.find('select[name="pca_attributes_values[' + taxonomy + '][]\"]').append(option).trigger('change');
            }
        });

        // Handle visibility and variation checkboxes
        $('.pca_attributes_visibility, .pca_attributes_variation').off('change').on('change', function () {
            var checkbox = $(this);
            checkbox.val(checkbox.is(':checked') ? 1 : 0);
        });

        // Reinitialize sortable after adding a new row
        $('#pca-attributes-wrapper').sortable('refresh');
    }

    // Initialize any existing rows (in case there are some when the page loads)
    handleNewAttributeRow();
});
