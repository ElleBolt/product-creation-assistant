jQuery(document).ready(function ($) {
    console.log("DOM fully loaded and parsed");

    // Add Rule Button Click
    const addRuleBtn = document.getElementById('add-rule');
    const newRuleForm = document.getElementById('new-rule-form');

    if (addRuleBtn && newRuleForm) {
        addRuleBtn.addEventListener('click', function () {
            newRuleForm.style.display = 'block';
        });
    } else {
        console.error("Add Rule Button or New Rule Form not found");
    }

    // Save Rule Button Click
    const saveRuleBtn = document.getElementById('save-rule');
    if (saveRuleBtn) {
        saveRuleBtn.addEventListener('click', function () {
            const ruleName = document.querySelector('[name="rule_name"]').value;
            const materialIds = document.querySelector('[name="material_ids"]').value;

            // Collect attributes
            let attributes = {};
            $('.attributes-wrapper .attribute-item').each(function () {
                const attributeName = $(this).find('label').text().replace(':', '');
                const attributeValues = $(this).find('select').val();
                if (attributeValues && attributeValues.length > 0) {
                    attributes[attributeName] = attributeValues;
                }
            });

            if (!ruleName) {
                alert('Please enter a rule name.');
                return;
            }

            // Send data via AJAX
            $.ajax({
                url: productCreationAssistant.adminUrl,
                method: 'POST',
                data: {
                    action: 'save_pca_rule',
                    rule_name: ruleName,
                    material_ids: materialIds,
                    attributes: attributes,
                },
                success: function (response) {
                    if (response.success) {
                        location.reload(); // Reload the page to see the updated rules list
                    } else {
                        alert('Failed to save the rule. Please try again.');
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                }
            });
        });
    } else {
        console.error("Save Rule Button not found");
    }

    // Cancel Rule Button Click
    const cancelRuleBtn = document.getElementById('cancel-rule');
    if (cancelRuleBtn) {
        cancelRuleBtn.addEventListener('click', function () {
            newRuleForm.style.display = 'none';
            // Clear the form
            document.querySelector('[name="rule_name"]').value = '';
            document.querySelector('[name="material_ids"]').value = '';
            $('.attributes-wrapper').empty(); // Clear any attributes
        });
    } else {
        console.error("Cancel Rule Button not found");
    }

    // Handle Add Existing Attribute Dropdown Change
    $(document).on('change', '.existing-attribute-dropdown', function () {
        const attribute = $(this).val();
        if (attribute) {
            const wrapper = $(this).closest('.attribute-add-wrapper').next('.attributes-wrapper');

            let attrTemplate = `
                <div class="form-field attribute-item">
                    <label>${attribute}:</label>
                    <select name="attributes[${attribute}][]" multiple="multiple" class="wc-enhanced-select deferred-select">
                        <option value="">${productCreationAssistant.searching}</option>
                    </select>
                    <button type="button" class="button remove-attribute">${productCreationAssistant.remove}</button>
                </div>
            `;

            wrapper.append(attrTemplate);

            // Fetch terms via AJAX
            const selectBox = wrapper.find('.deferred-select').last();
            $.ajax({
                url: productCreationAssistant.adminUrl,
                method: 'GET',
                data: {
                    action: 'get_attribute_terms',
                    attribute_name: attribute,
                },
                success: function (response) {
                    let options = '';
                    if (response.success && response.data.terms.length > 0) {
                        options = response.data.terms.map(term => `<option value="${term.slug}">${term.name}</option>`).join('');
                    } else {
                        options = `<option value="">${productCreationAssistant.noTermsFound}</option>`;
                    }
                    selectBox.html(options);
                    selectBox.chosen({
                        width: '100%',
                        placeholder_text_multiple: productCreationAssistant.selectTerms,
                        no_results_text: productCreationAssistant.noTermsFound
                    }).trigger("chosen:updated");
                },
                error: function () {
                    selectBox.html(`<option value="">${productCreationAssistant.noTermsFound}</option>`);
                }
            });
        }
    });

    // Handle Remove Attribute Button Click
    $(document).on('click', '.remove-attribute', function () {
        $(this).closest('.attribute-item').remove();
    });
});
