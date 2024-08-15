jQuery(document).ready(function ($) {
    console.log("DOM fully loaded and parsed");

    // Delete Rule Button Click
    $(document).on('click', '.delete-rule', function () {
        if (confirm('Are you sure you want to delete this rule?')) {
            const ruleIndex = $(this).data('index');

            $.ajax({
                url: productCreationAssistant.adminUrl,
                method: 'POST',
                data: {
                    action: 'delete_pca_rule',
                    index: ruleIndex,
                },
                success: function (response) {
                    if (response.success) {
                        location.reload(); // Reload the page to see the updated rules list
                    } else {
                        alert('Failed to delete the rule. Please try again.');
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });

    // Add Rule Button Click
    const addRuleBtn = document.getElementById('add-rule');
    const newRuleForm = document.getElementById('new-rule-form');
    let editIndex = null; // To track if we are editing an existing rule

    if (addRuleBtn && newRuleForm) {
        addRuleBtn.addEventListener('click', function () {
            newRuleForm.style.display = 'block';
            document.querySelector('#new-rule-form h2').textContent = 'New Rule';
            editIndex = null; // Reset edit index when adding a new rule
            clearForm();
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
    
            // Check if we're editing an existing rule
            const ruleId = editIndex !== null ? pcaRules[editIndex].id : null;
    
            // Send data via AJAX
            $.ajax({
                url: productCreationAssistant.adminUrl,
                method: 'POST',
                data: {
                    action: 'save_pca_rule',
                    rule_name: ruleName,
                    material_ids: materialIds,
                    attributes: attributes,
                    rule_id: ruleId // Include rule ID if editing
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
    }

    // Cancel Rule Button Click
    const cancelRuleBtn = document.getElementById('cancel-rule');
    if (cancelRuleBtn) {
        cancelRuleBtn.addEventListener('click', function () {
            newRuleForm.style.display = 'none';
            clearForm();
        });
    } else {
        console.error("Cancel Rule Button not found");
    }

    // Edit Rule Button Click
    $(document).on('click', '.edit-rule', function () {
        const ruleId = $(this).data('rule-id');
        const rule = pcaRules[ruleId];
    
        // Update form title
        document.querySelector('#new-rule-form h2').textContent = 'Edit Rule';
    
        // Populate form fields with the existing rule data
        document.querySelector('[name="rule_name"]').value = rule.name;
        document.querySelector('[name="material_ids"]').value = JSON.stringify(rule.material_ids);
    
        // Populate attributes
        populateAttributes(rule.attributes);
    
        // Show the form and set editIndex
        newRuleForm.style.display = 'block';
        editIndex = ruleId; // Use rule ID for editing
    });

    // Function to populate attributes in the form
    function populateAttributes(attributes) {
        $('.attributes-wrapper').empty(); // Clear any existing attributes
    
        if (attributes) {
            $.each(attributes, function (attributeName, terms) {
                const wrapper = $('.attributes-wrapper');
    
                let attrTemplate = `
                    <div class="form-field attribute-item">
                        <label>${attributeName}:</label>
                        <select name="attributes[${attributeName}][]" multiple="multiple" class="wc-enhanced-select deferred-select">
                        </select>
                        <button type="button" class="button remove-attribute">${productCreationAssistant.remove}</button>
                    </div>
                `;
    
                wrapper.append(attrTemplate);
    
                const selectBox = wrapper.find('.deferred-select').last();
    
                // Prepopulate with existing terms
                let options = terms.map(term => `<option value="${term.slug}" selected>${term.label}</option>`).join('');
                selectBox.html(options);
    
                // Initialize Chosen.js with the preloaded options
                selectBox.chosen({
                    width: '100%',
                    placeholder_text_multiple: productCreationAssistant.selectTerms,
                    no_results_text: productCreationAssistant.noTermsFound,
                    search_contains: true
                });
    
                // Bind focus event to load new options via AJAX
                selectBox.one('focus', function () {
                    $.ajax({
                        url: productCreationAssistant.adminUrl,
                        method: 'GET',
                        data: {
                            action: 'get_attribute_terms',
                            attribute_name: attributeName,
                        },
                        success: function (response) {
                            if (response.success && response.data.terms.length > 0) {
                                let newOptions = response.data.terms.map(term => {
                                    if (!selectBox.find(`option[value="${term.slug}"]`).length) {
                                        return `<option value="${term.slug}">${term.name}</option>`;
                                    }
                                }).join('');
    
                                selectBox.append(newOptions);
                                selectBox.trigger("chosen:updated");
                            }
                        },
                        error: function () {
                            console.error('Failed to load new terms');
                        }
                    });
                });
            });
        }
    }

    // Function to clear the form
    function clearForm() {
        document.querySelector('[name="rule_name"]').value = '';
        document.querySelector('[name="material_ids"]').value = '';
        $('.attributes-wrapper').empty(); // Clear any attributes
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
                    </select>
                    <button type="button" class="button remove-attribute">${productCreationAssistant.remove}</button>
                </div>
            `;

            wrapper.append(attrTemplate);

            const selectBox = wrapper.find('.deferred-select').last();

            // Bind focus event to load options when the user focuses on the dropdown
            selectBox.one('focus', function () {
                selectBox.html('<option disabled selected>' + productCreationAssistant.searching + '</option>');
                // Fetch terms via AJAX
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
            });
        }
    });

    // Handle Remove Attribute Button Click
    $(document).on('click', '.remove-attribute', function () {
        $(this).closest('.attribute-item').remove();
    });
});
