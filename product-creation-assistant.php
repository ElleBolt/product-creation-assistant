<?php
/**
 * Plugin Name: Product Creation Assistant
 * Description: A plugin to assist with creating WooCommerce product variations based on predefined rules.
 * Version: 1.0
 * Author: Elle Bolt
 * Text Domain: product-creation-assistant
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register the settings, sections, and fields
function pca_register_settings() {
    register_setting( 'pca_options_group', 'pca_rules', 'pca_sanitize_rules' );

    add_settings_section(
        'pca_rules_section', 
        __('Product Creation Rules', 'product-creation-assistant'), 
        'pca_rules_section_callback', // Ensure this function is defined
        'product-creation-assistant'
    );

    add_settings_field(
        'pca_rules_field', 
        __('Define Rules', 'product-creation-assistant'), 
        'pca_rules_field_callback', 
        'product-creation-assistant', 
        'pca_rules_section'
    );
}
add_action( 'admin_init', 'pca_register_settings' );

// Define the section callback function
function pca_rules_section_callback() {
    echo __('Define the rules for creating product variations based on product types, sizes, and colors.', 'product-creation-assistant');
}

// Define the field callback function (replace with the updated dynamic form code provided earlier)
function pca_rules_field_callback() {
    // Retrieve saved rules from the database
    $rules = get_option('pca_rules', []);

    ?>
    <div id="pca-rules-wrapper">
        <button type="button" id="add-rule" class="button button-primary"><?php _e('Add New Rule', 'product-creation-assistant'); ?></button>

        <div id="rules-list">
            <!-- Existing rules will be populated here -->
            <?php if (!empty($rules)): ?>
                <?php foreach ($rules as $index => $rule): ?>
                    <div class="rule-item">
                        <h4><?php echo esc_html($rule['name']); ?></h4>
                        <label><?php _e('Product Name:', 'product-creation-assistant'); ?></label>
                        <input type="text" name="pca_rules[<?php echo $index; ?>][name]" value="<?php echo esc_attr($rule['name']); ?>" />

                        <label><?php _e('Sizes (comma-separated):', 'product-creation-assistant'); ?></label>
                        <input type="text" name="pca_rules[<?php echo $index; ?>][sizes]" value="<?php echo esc_attr(implode(', ', $rule['attributes']['size'])); ?>" />

                        <label><?php _e('Colors (comma-separated):', 'product-creation-assistant'); ?></label>
                        <input type="text" name="pca_rules[<?php echo $index; ?>][colors]" value="<?php echo esc_attr(implode(', ', $rule['attributes']['color'])); ?>" />

                        <label><?php _e('Material IDs (JSON format):', 'product-creation-assistant'); ?></label>
                        <textarea name="pca_rules[<?php echo $index; ?>][material_ids]"><?php echo esc_textarea(json_encode($rule['material_ids'])); ?></textarea>

                        <button type="button" class="button remove-rule"><?php _e('Remove', 'product-creation-assistant'); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded event fired'); // Debugging line

            let ruleIndex = <?php echo count($rules); ?>;

            document.getElementById('add-rule').addEventListener('click', function() {
                console.log('Add New Rule button clicked'); // Debugging line
                let ruleTemplate = `
                    <div class="rule-item">
                        <h4><?php _e('New Rule', 'product-creation-assistant'); ?></h4>
                        <label><?php _e('Product Name:', 'product-creation-assistant'); ?></label>
                        <input type="text" name="pca_rules[` + ruleIndex + `][name]" value="" />

                        <label><?php _e('Sizes (comma-separated):', 'product-creation-assistant'); ?></label>
                        <input type="text" name="pca_rules[` + ruleIndex + `][sizes]" value="" />

                        <label><?php _e('Colors (comma-separated):', 'product-creation-assistant'); ?></label>
                        <input type="text" name="pca_rules[` + ruleIndex + `][colors]" value="" />

                        <label><?php _e('Material IDs (JSON format):', 'product-creation-assistant'); ?></label>
                        <textarea name="pca_rules[` + ruleIndex + `][material_ids]"></textarea>

                        <button type="button" class="button remove-rule"><?php _e('Remove', 'product-creation-assistant'); ?></button>
                    </div>
                `;
                document.getElementById('rules-list').insertAdjacentHTML('beforeend', ruleTemplate);
                ruleIndex++;
            });
        });
    </script>
    <?php
}

// Sanitize the input before saving
function pca_sanitize_rules($input) {
    $sanitized_rules = [];

    foreach ($input as $rule) {
        $sanitized_rule = [];
        $sanitized_rule['name'] = sanitize_text_field($rule['name']);
        $sanitized_rule['attributes']['size'] = array_map('sanitize_text_field', explode(',', $rule['sizes']));
        $sanitized_rule['attributes']['color'] = array_map('sanitize_text_field', explode(',', $rule['colors']));
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

    return $sanitized_rules;
}


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
function pca_render_settings_page(){
    ?>
    <div class="wrap">
        <h1><?php _e('Product Creation Assistant', 'product-creation-assistant'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('pca_options_group');
            do_settings_sections('product-creation-assistant');
            submit_button(__('Save Rules', 'product-creation-assistant'));
            ?>
        </form>
    </div>
    <?php
}
