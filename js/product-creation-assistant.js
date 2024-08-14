document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM fully loaded and parsed");

    const addRuleButton = document.getElementById('add-rule');
    const newRuleForm = document.getElementById('new-rule-form');
    const saveRuleButton = document.getElementById('save-rule');
    const cancelRuleButton = document.getElementById('cancel-rule');
    const rulesWrapper = document.getElementById('rules-wrapper');

    if (!addRuleButton) {
        console.log("Add Rule Button not found");
        return;
    }

    if (!newRuleForm) {
        console.log("New Rule Form not found");
        return;
    }

    addRuleButton.addEventListener('click', function() {
        newRuleForm.style.display = 'block';
        addRuleButton.style.display = 'none';
    });

    cancelRuleButton.addEventListener('click', function() {
        newRuleForm.style.display = 'none';
        addRuleButton.style.display = 'block';
    });

    // Handle the "Add existing" dropdown selection
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('existing-attribute-dropdown')) {
            let attribute = e.target.value;
            if (attribute) {
                let wrapper = newRuleForm.querySelector('.attributes-wrapper');
                
                // Add the field for the attribute before fetching terms
                let attrTemplate = `
                    <div class="form-field attribute-item">
                        <label>` + attribute + `:</label>
                        <select name="attributes[` + attribute + `][]" multiple="multiple" class="wc-enhanced-select deferred-select">
                            <option value=""><?php _e('Searching...', 'product-creation-assistant'); ?></option>
                        </select>
                        <button type="button" class="button remove-attribute"><?php _e('Remove', 'product-creation-assistant'); ?></button>
                    </div>
                `;
                wrapper.insertAdjacentHTML('beforeend', attrTemplate);

                let selectBox = jQuery(wrapper).find('.deferred-select').last(); // Correcting to treat as jQuery object

                // Initialize chosen.js on the new select element
                selectBox.chosen({
                    width: '100%',
                    placeholder_text_multiple: "<?php _e('Select terms', 'product-creation-assistant'); ?>",
                    no_results_text: "<?php _e('No terms found', 'product-creation-assistant'); ?>"
                }).on('chosen:showing_dropdown', function() {
                    if (!selectBox.data('loaded')) {
                        fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=get_attribute_terms&attribute_name=${attribute}`)
                            .then(response => response.json())
                            .then(data => {
                                let options = '';
                                if (data.success && data.data.terms.length > 0) {
                                    options = data.data.terms.map(term => `<option value="${term.slug}">${term.name}</option>`).join('');
                                } else {
                                    options = '<option value=""><?php _e('No terms found', 'product-creation-assistant'); ?></option>';
                                }
                                selectBox.html('<option value=""></option>' + options).trigger("chosen:updated");
                                selectBox.data('loaded', true);
                            })
                            .catch(error => {
                                console.error('Error fetching terms:', error);
                            });
                    }
                });
            }
        }
    });
});
