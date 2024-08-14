document.addEventListener('DOMContentLoaded', function () {
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

    // Show the New Rule form when clicking "Add New Rule"
    addRuleButton.addEventListener('click', function () {
        newRuleForm.style.display = 'block';
    });

    // Hide the New Rule form when clicking "Cancel"
    cancelRuleButton.addEventListener('click', function () {
        newRuleForm.style.display = 'none';
    });

    // Save the rule when clicking "Save Rule"
    saveRuleButton.addEventListener('click', function () {
        const ruleNameInput = newRuleForm.querySelector('input[name="rule_name"]');
        const materialIdsTextarea = newRuleForm.querySelector('textarea[name="material_ids"]');

        const ruleName = ruleNameInput.value;
        const materialIds = materialIdsTextarea.value;

        // Create a new row for the rule
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>${ruleName}</td>
            <td>
                <button type="button" class="button edit-rule">Edit</button>
                <button type="button" class="button delete-rule">Delete</button>
            </td>
        `;

        // Append the new rule to the table
        rulesWrapper.appendChild(newRow);

        // Clear the form
        ruleNameInput.value = '';
        materialIdsTextarea.value = '';

        // Hide the form
        newRuleForm.style.display = 'none';
    });

    // Delete a rule
    rulesWrapper.addEventListener('click', function (e) {
        if (e.target.classList.contains('delete-rule')) {
            const row = e.target.closest('tr');
            row.remove();
        }
    });

    // Initialize Chosen.js on select fields within the form
    document.querySelectorAll('.existing-attribute-dropdown').forEach(function (selectBox) {
        selectBox.chosen({
            width: '100%',
            placeholder_text_multiple: productCreationAssistant.selectTerms,
            no_results_text: productCreationAssistant.noTermsFound
        }).on('chosen:showing_dropdown', function () {
            if (!selectBox.dataset.loaded) {
                fetch(`${adminUrl}?action=get_attribute_terms&attribute_name=${selectBox.value}`)
                    .then(response => response.json())
                    .then(data => {
                        let options = '';
                        if (data.success && data.data.terms.length > 0) {
                            options = data.data.terms.map(term => `<option value="${term.slug}">${term.name}</option>`).join('');
                        } else {
                            options = `<option value="">${productCreationAssistant.noTermsFound}</option>`;
                        }
                        selectBox.innerHTML = '<option value=""></option>' + options;
                        selectBox.dispatchEvent(new Event("chosen:updated"));
                        selectBox.dataset.loaded = true;
                    })
                    .catch(error => {
                        console.error('Error fetching terms:', error);
                    });
            }
        });
    });
});
