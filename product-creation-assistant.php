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
    wp_enqueue_script('pca-js', plugin_dir_url(__FILE__) . 'js/product-creation-assistant.js', array('jquery', 'chosen-js'), null, true);

    // Pass PHP data to JavaScript
    wp_localize_script('pca-js', 'pcaRules', pca_get_all_rules());

    // Pass localized strings and admin URL to JavaScript
    wp_localize_script('pca-js', 'productCreationAssistant', array(
        'searching' => __('Searching...', 'product-creation-assistant'),
        'remove' => __('Remove', 'product-creation-assistant'),
        'noTermsFound' => __('No terms found', 'product-creation-assistant'),
        'selectTerms' => __('Select terms', 'product-creation-assistant'),
        'adminUrl' => admin_url('admin-ajax.php'),
    ));
}
add_action('admin_enqueue_scripts', 'pca_enqueue_scripts');

function pca_enqueue_admin_styles() {
    // Enqueue WooCommerce admin styles
    wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css');
    
    // Enqueue select2 for enhanced selects
    wp_enqueue_script('select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array('jquery'), '4.0.3', true);
    wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css', array(), '4.0.3');

    // Enqueue custom CSS for the Product Creation Assistant
    wp_enqueue_style('pca-css', plugin_dir_url(__FILE__) . 'css/product-creation-assistant.css', array(), null);
}
add_action('admin_enqueue_scripts', 'pca_enqueue_admin_styles');

// Function to get all rules stored as separate rows in the options table
function pca_get_all_rules() {
    global $wpdb;
    $prefix = $wpdb->prefix . 'options';
    $rules = $wpdb->get_results("SELECT option_name, option_value FROM $prefix WHERE option_name LIKE 'pca_rule_%'");

    $decoded_rules = [];
    foreach ($rules as $rule) {
        $rule_id = str_replace('pca_rule_', '', $rule->option_name);
        $decoded_rules[$rule_id] = maybe_unserialize($rule->option_value);
        $decoded_rules[$rule_id]['id'] = $rule_id;  // Add the rule ID to the array
    }

    return $decoded_rules;
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
function pca_render_settings_page() {
    // Retrieve saved rules
    $saved_rules = pca_get_all_rules();

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
                            <button type="button" class="button edit-rule" data-index="<?php echo esc_attr($index); ?>" data-id="<?php echo esc_attr($rule['id']); ?>"><?php _e('Edit', 'product-creation-assistant'); ?></button>
                            <button type="button" class="button delete-rule" data-index="<?php echo esc_attr($index); ?>" data-id="<?php echo esc_attr($rule['id']); ?>"><?php _e('Delete', 'product-creation-assistant'); ?></button>
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

// Function to handle the deletion of a rule via AJAX
function pca_delete_rule() {
    if (isset($_POST['rule_id'])) {
        $rule_id = sanitize_text_field($_POST['rule_id']);
        delete_option($rule_id);
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => 'Invalid request.'));
    }
}
add_action('wp_ajax_delete_pca_rule', 'pca_delete_rule');

// Function to handle saving a new rule via AJAX
function pca_save_rule() {
    if (isset($_POST['rule_name'], $_POST['attributes'])) {
        $rule_name = sanitize_text_field($_POST['rule_name']);
        $material_ids = sanitize_text_field($_POST['material_ids']);
        $attributes = [];

        foreach ($_POST['attributes'] as $attribute_name => $terms) {
            $terms_with_labels = [];
            foreach ($terms as $term_slug) {
                $term = get_term_by('slug', $term_slug, wc_attribute_taxonomy_name($attribute_name));
                if ($term) {
                    $terms_with_labels[] = [
                        'slug' => $term->slug,
                        'name' => $term->name
                    ];
                }
            }
            $attributes[$attribute_name] = $terms_with_labels;
        }
        
        $rule_data = [
            'name' => $rule_name,
            'material_ids' => $material_ids,
            'attributes' => $attributes,
        ];

        // Check if we are editing an existing rule
        if (isset($_POST['rule_id']) && !empty($_POST['rule_id'])) {
            $rule_id = sanitize_text_field($_POST['rule_id']);
            update_option("pca_rule_$rule_id", $rule_data);
        } else {
            // Generate a unique ID for the new rule
            $rule_id = uniqid();
            add_option("pca_rule_$rule_id", $rule_data);
        }

        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Invalid data.']);
    }
}
add_action('wp_ajax_save_pca_rule', 'pca_save_rule');
