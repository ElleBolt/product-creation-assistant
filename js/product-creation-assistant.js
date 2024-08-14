document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM fully loaded and parsed');

    // Add New Rule Button
    const addRuleButton = document.getElementById('add-rule');
    if (addRuleButton) {
        addRuleButton.addEventListener('click', function () {
            document.getElementById('new-rule-form').style.display = 'block';
        });
    } else {
        console.error('Add Rule Button not found');
    }

    // Save Rule Button
    const saveRuleButton = document.getElementById('save-rule');
    if (saveRuleButton) {
        saveRuleButton.addEventListener('click', function () {
            // Handle rule saving logic
        });
    }

    // Cancel Button
    const cancelRuleButton = document.getElementById('cancel-rule');
    if (cancelRuleButton) {
        cancelRuleButton.addEventListener('click', function () {
            document.getElementById('new-rule-form').style.display = 'none';
        });
    }

    // Edit Button functionality
    document.querySelectorAll('.edit-rule').forEach(button => {
        button.addEventListener('click', function () {
            const index = this.getAttribute('data-index');
            const rule = pcaRules[index];

            if (rule) {
                // Populate the form with the existing rule data
                document.getElementById('new-rule-form').style.display = 'block';
                document.getElementById('rule-name').value = rule.name;
                // Populate attributes and material IDs
                populateAttributes(rule.attributes);
                document.getElementById('material-ids').value = JSON.stringify(rule.material_ids);
            }
        });
    });

    // Populate Attributes in the Form
    function populateAttributes(attributes) {
        const attributesWrapper = document.querySelector('.attributes-wrapper');
        attributesWrapper.innerHTML = ''; // Clear existing attributes

        for (const attrName in attributes) {
            if (attributes.hasOwnProperty(attrName)) {
                const terms = attributes[attrName];
                let attrTemplate = `
                    <div class="attribute-item">
                        <label>${attrName}:</label>
                        <select name="attributes[${attrName}][]" multiple="multiple" class="wc-enhanced-select">
                `;
                terms.forEach(term => {
                    attrTemplate += `<option value="${term}" selected>${term}</option>`;
                });
                attrTemplate += `</select><button type="button" class="button remove-attribute">Remove</button></div>`;
                attributesWrapper.insertAdjacentHTML('beforeend', attrTemplate);
            }
        }

        // Reinitialize select2 or chosen after dynamically adding elements
        jQuery('.wc-enhanced-select').select2({
            placeholder: "Select terms"
        });
    }

    // Event Listener for the "Add Existing" Dropdown
    document.querySelector('.existing-attribute-dropdown').addEventListener('change', function () {
        const attribute = this.value;
        if (attribute) {
            fetchAttributeTerms(attribute);
        }
    });

    function fetchAttributeTerms(attribute) {
        fetch(`admin-ajax.php?action=get_attribute_terms&attribute_name=${attribute}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.terms.length > 0) {
                    const terms = data.data.terms;
                    let options = '';
                    terms.forEach(term => {
                        options += `<option value="${term.slug}">${term.name}</option>`;
                    });
                    const attributesWrapper = document.querySelector('.attributes-wrapper');
                    attributesWrapper.insertAdjacentHTML('beforeend', `
                        <div class="attribute-item">
                            <label>${attribute}:</label>
                            <select name="attributes[${attribute}][]" multiple="multiple" class="wc-enhanced-select">
                                ${options}
                            </select>
                            <button type="button" class="button remove-attribute">Remove</button>
                        </div>
                    `);
                    jQuery('.wc-enhanced-select').select2({
                        placeholder: "Select terms"
                    });
                }
            });
    }
});
