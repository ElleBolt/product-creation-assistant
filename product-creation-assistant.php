<?php
/**
 * Plugin Name: Product Creation Assistant
 * Description: A simple plugin to assist with creating WooCommerce product variations based on predefined rules.
 * Version: 1.0
 * Author: Elle
 * Text Domain: product-creation-assistant
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue chosen.js for multi-select functionality
function pca_enqueue_scripts() {
    // Load Chosen.js and its CSS from a CDN
    wp_enqueue_script('chosen-js', 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js', array('jquery'), '1.8.7', true);
    wp_enqueue_style('chosen-css', 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css', array(), '1.8.7');
}
add_action('admin_enqueue_scripts', 'pca_enqueue_scripts');

function pca_enqueue_admin_styles() {
    // Enqueue WooCommerce admin styles to make the UI consistent with WooCommerce
    wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css');
    
    // Add custom styles for the Product Creation Assistant UI
    $custom_css = "
        #pca-rules-wrapper .rule-item {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        #pca-rules-wrapper h4 {
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: bold;
        }
        #pca-rules-wrapper label {
            font-weight: 600;
            margin-right: 10px;
            display: inline-block;
            margin-bottom: 5px;
        }
        #pca-rules-wrapper input[type='text'], #pca-rules-wrapper select, #pca-rules-wrapper textarea {
            width: 100%;
            max-width: 500px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        #pca-rules-wrapper .button {
            background: #7f54b3;
            border-color: #7f54b3;
            color: #fff;
            text-decoration: none;
            text-shadow: none;
            border-radius: 4px;
            padding: 8px 12px;
        }
        #pca-rules-wrapper .button:hover {
            background: #6b4896;
            border-color: #6b4896;
        }
        #pca-rules-wrapper .attribute-add-wrapper {
            margin-bottom: 10px;
        }
        #pca-rules-wrapper .remove-rule, #pca-rules-wrapper .remove-attribute {
            background: #e2401c;
            border-color: #e2401c;
            color: #fff;
            padding: 5px 10px;
            margin-top: 5px;
        }
        #pca-rules-wrapper .remove-rule:hover, #pca-rules-wrapper .remove-attribute:hover {
            background: #c22b0a;
            border-color: #c22b0a;
        }
    ";
    wp_add_inline_style('woocommerce_admin_styles', $custom_css);
}
add_action('admin_enqueue_scripts', 'pca_enqueue_admin_styles');

// Add a custom menu page under WooCommerce
function pca_register_custom_menu_page() {
    add_submenu_page(
        'woocommerce',
        __( 'Product Creation Assistant', 'product-creation-assistant' ),
        __( 'Product Creation Assistant', 'product-creation-assistant' ),
        'manage_options',
        'product-creation-assistant',
        'pca_render_settings_page'
    );
}
add_action( 'admin_menu', 'pca_register_custom_menu_page' );

// Render the settings page
function pca_render_settings_page() {
    // Retrieve saved rules from the database
    $saved_rules = get_option('pca_rules', []);

    ?>
    <div class="wrap">
        <h1><?php _e('Product Creation Assistant', 'product-creation-assistant'); ?></h1>
        <form method="post" action="options.php">
            <?php
            // Output security fields for the registered setting "pca_options_group"
            settings_fields('pca_options_group');
            ?>

            <div id="pca-rules-wrapper">
                <?php if (!empty($saved_rules)): ?>
                    <?php foreach ($saved_rules as $index => $rule): ?>
                        <div class="rule-item">
                            <h4><?php echo esc_html($rule['name']); ?></h4>

                            <label><?php _e('Rule Name:', 'product-creation-assistant'); ?></label>
                            <input type="text" name="pca_rules[<?php echo $index; ?>][name]" value="<?php echo esc_attr($rule['name']); ?>" />

                            <label><?php _e('Attributes:', 'product-creation-assistant'); ?></label>
                            <div class="attributes-wrapper">
                                <?php pca_render_attributes_ui($index, $rule['attributes']); ?>
                            </div>

                            <label><?php _e('Material IDs (JSON format):', 'product-creation-assistant'); ?></label>
                            <textarea name="pca_rules[<?php echo $index; ?>][material_ids]"><?php echo esc_textarea(json_encode($rule['material_ids'])); ?></textarea>

                            <button type="button" class="button remove-rule"><?php _e('Remove', 'product-creation-assistant'); ?></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" id="add-rule" class="button button-primary"><?php _e('Add New Rule', 'product-creation-assistant'); ?></button>

            <?php submit_button(__('Save Rules', 'product-creation-assistant')); ?>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let ruleIndex = <?php echo !empty($saved_rules) ? count($saved_rules) : 0; ?>;

            document.getElementById('add-rule').addEventListener('click', function() {
                let ruleTemplate = `
                    <div class="rule-item">
                        <h4><?php _e('New Rule', 'product-creation-assistant'); ?></h4>

                        <label><?php _e('Rule Name:', 'product-creation-assistant'); ?></label>
                        <input type="text" name="pca_rules[` + ruleIndex + `][name]" value="" />

                        <div class="attribute-add-wrapper">
                            <button type="button" class="button add-new-attribute" onclick="window.location.href='<?php echo admin_url('edit.php?post_type=product&page=product_attributes'); ?>';"><?php _e('Add new', 'product-creation-assistant'); ?></button>
                            <select class="existing-attribute-dropdown wc-enhanced-select">
                                <option value=""><?php _e('Add existing', 'product-creation-assistant'); ?></option>
                                <?php
                                $attributes = wc_get_attribute_taxonomies();
                                if ($attributes):
                                    foreach ($attributes as $attribute): ?>
                                        <option value="<?php echo esc_attr($attribute->attribute_name); ?>"><?php echo esc_html($attribute->attribute_label); ?></option>
                                    <?php endforeach;
                                endif;
                                ?>
                            </select>
                        </div>
                        
                        <div class="attributes-wrapper"></div>

                        <label><?php _e('Material IDs (JSON format):', 'product-creation-assistant'); ?></label>
                        <textarea name="pca_rules[` + ruleIndex + `][material_ids]"></textarea>

                        <button type="button" class="button remove-rule"><?php _e('Remove', 'product-creation-assistant'); ?></button>
                    </div>
                `;
                document.getElementById('pca-rules-wrapper').insertAdjacentHTML('beforeend', ruleTemplate);
                ruleIndex++;
            });

            document.getElementById('pca-rules-wrapper').addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-rule')) {
                    e.target.closest('.rule-item').remove();
                } else if (e.target.classList.contains('remove-attribute')) {
                    e.target.closest('.attribute-item').remove();
                }
            });

            document.getElementById('pca-rules-wrapper').addEventListener('change', function(e) {
                if (e.target.classList.contains('existing-attribute-dropdown')) {
                    let attribute = e.target.value;
                    if (attribute) {
                        let wrapper = e.target.closest('.rule-item').querySelector('.attributes-wrapper');
                        
                        // Add the field for the attribute before fetching terms
                        let attrTemplate = `
                            <div class="form-field attribute-item">
                                <label>` + attribute + `:</label>
                                <select name="pca_rules[` + ruleIndex + `][attributes][` + attribute + `][value][]" multiple="multiple" class="wc-enhanced-select">
                                    <option value=""><?php _e('Loading terms...', 'product-creation-assistant'); ?></option>
                                </select>
                                <button type="button" class="button remove-attribute"><?php _e('Remove', 'product-creation-assistant'); ?></button>
                            </div>
                        `;
                        wrapper.insertAdjacentHTML('beforeend', attrTemplate);

                        // Fetch terms via AJAX
                        fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=get_attribute_terms&attribute_name=${attribute}`)
                            .then(response => response.json())
                            .then(data => {
                                let selectBox = wrapper.querySelector('select[name*="[' + attribute + ']"]');
                                let options = '';
                                if (data.success && data.data.terms.length > 0) {
                                    options = data.data.terms.map(term => `<option value="${term.slug}">${term.name}</option>`).join('');
                                } else {
                                    options = '<option value=""><?php _e('No terms found', 'product-creation-assistant'); ?></option>';
                                }
                                selectBox.innerHTML = options;

                                // Apply chosen.js or a similar plugin for multi-select behavior
                                jQuery(selectBox).chosen({
                                    width: '100%',
                                    placeholder_text_multiple: "<?php _e('Select terms', 'product-creation-assistant'); ?>",
                                    no_results_text: "<?php _e('No terms found', 'product-creation-assistant'); ?>"
                                });
                            })
                            .catch(error => {
                                console.error('Error fetching terms:', error);
                            });
                    }
                }
            });

            // Initialize WooCommerce enhanced selects
            jQuery('.wc-enhanced-select').select2({
                placeholder: "<?php _e('Select terms', 'product-creation-assistant'); ?>"
            });
        });
    </script>
    <?php
}

// Render the attribute selector for WooCommerce attributes
function pca_render_attributes_ui($index, $selected_attributes = []) {
    // Get all available attributes in WooCommerce
    $attributes = wc_get_attribute_taxonomies();
    ?>
    <div class="attribute-add-wrapper">
        <button type="button" class="button add-new-attribute"><?php _e('Add new', 'product-creation-assistant'); ?></button>
        <select class="existing-attribute-dropdown">
            <option value=""><?php _e('Add existing', 'product-creation-assistant'); ?></option>
            <?php if ($attributes): ?>
                <?php foreach ($attributes as $attribute): ?>
                    <option value="<?php echo esc_attr($attribute->attribute_name); ?>"><?php echo esc_html($attribute->attribute_label); ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    
    <div class="attributes-wrapper">
        <?php if ($attributes): ?>
            <?php foreach ($attributes as $attribute): ?>
                <?php $attribute_taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name); ?>
                <?php if (!empty($selected_attributes[$attribute_taxonomy])): ?>
                    <div class="attribute-item">
                        <label><?php echo esc_html($attribute->attribute_label); ?>:</label>
                        <input type="text" name="pca_rules[<?php echo $index; ?>][attributes][<?php echo esc_attr($attribute_taxonomy); ?>][value]" value="<?php echo esc_attr(implode('|', $selected_attributes[$attribute_taxonomy]['value'] ?? [])); ?>" placeholder="<?php _e('Enter options separated by |', 'product-creation-assistant'); ?>" />
                        <button type="button" class="button remove-attribute"><?php _e('Remove', 'product-creation-assistant'); ?></button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}


// Register the settings, sections, and fields
function pca_register_settings() {
    // Register a setting for storing the rules
    register_setting( 'pca_options_group', 'pca_rules', 'pca_sanitize_rules' );

    // Add a section for the rules
    add_settings_section(
        'pca_rules_section', 
        __('Product Creation Rules', 'product-creation-assistant'), 
        'pca_rules_section_callback', 
        'product-creation-assistant'
    );

    // Add a field for entering the rules
    add_settings_field(
        'pca_rules_field', 
        __('Define Rules', 'product-creation-assistant'), 
        'pca_rules_field_callback', 
        'product-creation-assistant', 
        'pca_rules_section'
    );
}
add_action( 'admin_init', 'pca_register_settings' );

// Section callback function
function pca_rules_section_callback() {
    echo __('Define the rules for creating product variations based on product types, sizes, and colors.', 'product-creation-assistant');
}

// Field callback function
function pca_rules_field_callback() {
    // Retrieve saved rules from the database
    $rules = get_option('pca_rules', '');
    
    // Decode the JSON string into an array
    if (is_string($rules)) {
        $rules = json_decode($rules, true);
    }

    if (!is_array($rules)) {
        $rules = []; // Ensure $rules is an array
    }

    ?>
    <textarea id="pca_rules" name="pca_rules" rows="10" cols="50" class="large-text"><?php echo esc_textarea(json_encode($rules)); ?></textarea>
    <p class="description"><?php _e('Enter your rules in JSON format.', 'product-creation-assistant'); ?></p>
    <?php
}

// Sanitize the input before saving
function pca_sanitize_rules($input) {
    $sanitized_rules = [];

    foreach ($input as $rule) {
        if (is_array($rule)) {
            $sanitized_rule = [];
            $sanitized_rule['name'] = sanitize_text_field($rule['name']);
            $sanitized_rule['attributes'] = [];

            if (isset($rule['attributes'])) {
                foreach ($rule['attributes'] as $attr => $data) {
                    $sanitized_rule['attributes'][$attr] = [
                        'value' => array_map('sanitize_text_field', explode('|', $data['value'])),
                        'visible' => isset($data['visible']) && $data['visible'] ? true : false,
                        'variation' => isset($data['variation']) && $data['variation'] ? true : false,
                    ];
                }
            }

            $sanitized_rule['material_ids'] = json_decode(wp_kses_post($rule['material_ids']), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                add_settings_error(
                    'pca_rules',
                    'pca_rules_json_error',
                    __('Invalid JSON format for Material IDs.', 'product-creation-assistant'),
                    'error'
                );
                continue;
            }

            $sanitized_rules[] = $sanitized_rule;
        }
    }

    return json_encode($sanitized_rules);
}

// AJAX handler to get terms of a selected attribute
function pca_get_attribute_terms() {
    if (isset($_GET['attribute_name'])) {
        $attribute_name = sanitize_text_field($_GET['attribute_name']);
        $taxonomy = wc_attribute_taxonomy_name($attribute_name);

        if (taxonomy_exists($taxonomy)) {
            $terms = get_terms($taxonomy, array('hide_empty' => false));
            wp_send_json_success(array('terms' => $terms));
        } else {
            wp_send_json_error(array('message' => __('Invalid attribute', 'product-creation-assistant')));
        }
    } else {
        wp_send_json_error(array('message' => __('No attribute specified', 'product-creation-assistant')));
    }
}
add_action('wp_ajax_get_attribute_terms', 'pca_get_attribute_terms');
