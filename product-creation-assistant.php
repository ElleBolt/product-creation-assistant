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

    // Enqueue the custom JavaScript file
    wp_enqueue_script('pca-js', plugin_dir_url(__FILE__) . 'js/product-creation-assistant.js', array('jquery'), null, true);

    // Pass PHP data to JavaScript
    $saved_rules = get_option('pca_rules', []);
    wp_localize_script('pca-js', 'pcaRules', $saved_rules);
}
add_action('admin_enqueue_scripts', 'pca_enqueue_scripts');

function pca_enqueue_admin_styles() {
    // Enqueue WooCommerce admin styles
    wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css');
    
    // Enqueue select2 for enhanced selects
    wp_enqueue_script('select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array('jquery'), '4.0.3', true);
    wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css', array(), '4.0.3');

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
        
        <!-- Rule Listing -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Rule Name', 'product-creation-assistant'); ?></th>
                    <th><?php _e('Actions', 'product-creation-assistant'); ?></th>
                </tr>
            </thead>
            <tbody id="rules-wrapper">
                <?php if (!empty($saved_rules)): ?>
                    <?php foreach ($saved_rules as $index => $rule): ?>
                        <tr>
                            <td><?php echo esc_html($rule['name']); ?></td>
                            <td>
                                <button type="button" class="button edit-rule" data-index="<?php echo $index; ?>"><?php _e('Edit', 'product-creation-assistant'); ?></button>
                                <button type="button" class="button delete-rule" data-index="<?php echo $index; ?>"><?php _e('Delete', 'product-creation-assistant'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <button type="button" id="add-rule" class="button button-primary"><?php _e('Add New Rule', 'product-creation-assistant'); ?></button>

        <!-- New Rule Form -->
        <div id="new-rule-form" style="display:none;">
            <h2><?php _e('New Rule', 'product-creation-assistant'); ?></h2>
            <label><?php _e('Rule Name:', 'product-creation-assistant'); ?></label>
            <input type="text" name="rule_name" value="" />

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
            <textarea name="material_ids"></textarea>

            <button type="button" id="save-rule" class="button button-primary"><?php _e('Save Rule', 'product-creation-assistant'); ?></button>
            <button type="button" id="cancel-rule" class="button"><?php _e('Cancel', 'product-creation-assistant'); ?></button>
        </div>
    </div>
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
        <?php if ($selected_attributes): ?>
            <?php foreach ($selected_attributes as $attribute_name => $terms): ?>
                <div class="attribute-item">
                    <label><?php echo esc_html($attribute_name); ?>:</label>
                    <select name="pca_rules[<?php echo $index; ?>][attributes][<?php echo esc_attr($attribute_name); ?>][]" multiple="multiple" class="wc-enhanced-select">
                        <?php foreach ($terms as $term_slug): ?>
                            <option value="<?php echo esc_attr($term_slug); ?>" selected><?php echo esc_html(get_term_by('slug', $term_slug, wc_attribute_taxonomy_name($attribute_name))->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button remove-attribute"><?php _e('Remove', 'product-creation-assistant'); ?></button>
                </div>
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
    // Ensure that $input is an array
    if (!is_array($input)) {
        $input = []; // Initialize as an empty array if it's not already an array
    }

    $sanitized_rules = [];

    foreach ($input as $rule) {
        // Check if 'name' and 'material_ids' exist and are strings
        if (isset($rule['name']) && is_string($rule['name'])) {
            $sanitized_rule['name'] = sanitize_text_field($rule['name']);
        } else {
            $sanitized_rule['name'] = ''; // Default to empty if not set or not a string
        }

        if (isset($rule['material_ids']) && is_string($rule['material_ids'])) {
            $sanitized_rule['material_ids'] = wp_kses_post($rule['material_ids']);
        } else {
            $sanitized_rule['material_ids'] = ''; // Default to empty if not set or not a string
        }

        // Handle attributes
        if (isset($rule['attributes']) && is_array($rule['attributes'])) {
            foreach ($rule['attributes'] as $attribute_name => $terms) {
                if (is_array($terms)) {
                    $sanitized_rule['attributes'][$attribute_name] = array_map('sanitize_text_field', $terms);
                } elseif (is_string($terms)) {
                    $sanitized_rule['attributes'][$attribute_name] = array_map('sanitize_text_field', explode(',', $terms));
                } else {
                    $sanitized_rule['attributes'][$attribute_name] = [];
                }
            }
        } else {
            $sanitized_rule['attributes'] = []; // Default to empty if not set or not an array
        }

        $sanitized_rules[] = $sanitized_rule;
    }

    return $sanitized_rules;
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
        wp_send_json_error(array('message' => __('No attribute specified', 'product-creation-assistant');)
    }
}
add_action('wp_ajax_get_attribute_terms', 'pca_get_attribute_terms');

// Function to handle the deletion of a rule via AJAX
function pca_delete_rule() {
    if (isset($_POST['index'])) {
        $index = intval($_POST['index']);
        $rules = get_option('pca_rules', []);

        if (isset($rules[$index])) {
            unset($rules[$index]);
            // Reindex the array to ensure indexes are sequential
            $rules = array_values($rules);
            update_option('pca_rules', $rules);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Rule not found.'));
        }
    } else {
        wp_send_json_error(array('message' => 'Invalid request.'));
    }
}
add_action('wp_ajax_delete_pca_rule', 'pca_delete_rule');
