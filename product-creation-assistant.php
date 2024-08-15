<?php
/**
 * Plugin Name: Product Creation Assistant
 * Description: A plugin to assist with creating WooCommerce product variations using a custom post type for rules.
 * Version: 2.1
 * Author: Elle
 * Text Domain: product-creation-assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register Custom Post Type for Product Rules
function pca_register_product_rule_post_type() {
    $labels = array(
        'name'               => _x('Product Rules', 'post type general name', 'product-creation-assistant'),
        'singular_name'      => _x('Product Rule', 'post type singular name', 'product-creation-assistant'),
        'menu_name'          => _x('Product Rules', 'admin menu', 'product-creation-assistant'),
        'name_admin_bar'     => _x('Product Rule', 'add new on admin bar', 'product-creation-assistant'),
        'add_new'            => _x('Add New', 'rule', 'product-creation-assistant'),
        'add_new_item'       => __('Add New Product Rule', 'product-creation-assistant'),
        'new_item'           => __('New Product Rule', 'product-creation-assistant'),
        'edit_item'          => __('Edit Product Rule', 'product-creation-assistant'),
        'view_item'          => __('View Product Rule', 'product-creation-assistant'),
        'all_items'          => __('All Product Rules', 'product-creation-assistant'),
        'search_items'       => __('Search Product Rules', 'product-creation-assistant'),
        'not_found'          => __('No Product Rules found.', 'product-creation-assistant'),
        'not_found_in_trash' => __('No Product Rules found in Trash.', 'product-creation-assistant')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'product-rule'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 25,
        'supports'           => array('title'),
    );

    register_post_type('pca_product_rule', $args);
}
add_action('init', 'pca_register_product_rule_post_type');

// Remove the default editor for the Product Rule post type
function pca_remove_post_type_support() {
    remove_post_type_support('pca_product_rule', 'editor');
}
add_action('init', 'pca_remove_post_type_support');

// Add Custom Meta Boxes
function pca_add_custom_meta_boxes() {
    add_meta_box('pca_product_rule_attributes', __('Product Rule Attributes', 'product-creation-assistant'), 'pca_product_rule_attributes_meta_box', 'pca_product_rule', 'normal', 'high');
}
add_action('add_meta_boxes', 'pca_add_custom_meta_boxes');

// Product Rule Attributes Meta Box
function pca_product_rule_attributes_meta_box($post) {
    // Get existing attributes
    $attributes = get_post_meta($post->ID, '_pca_product_rule_attributes', true) ?: [];

    echo '<div id="pca_attributes_box">';
    echo '<p>' . __('Add or manage attributes for this product rule.', 'product-creation-assistant') . '</p>';
    echo '<div class="woocommerce_attributes_wrapper">';
    
    // New Attribute button
    echo '<a href="' . esc_url(admin_url('edit.php?post_type=product&page=product_attributes')) . '" class="button">' . __('New Attribute', 'product-creation-assistant') . '</a>';
    
    // Add Existing dropdown
    echo '<select id="pca-add-existing-attribute" class="wc-enhanced-select">';
    echo '<option value="">' . __('Add existing', 'product-creation-assistant') . '</option>';
    
    // Get existing attributes from WooCommerce
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    foreach ($attribute_taxonomies as $tax) {
        $tax_name = wc_attribute_taxonomy_name($tax->attribute_name);
        echo '<option value="' . esc_attr($tax_name) . '">' . esc_html($tax->attribute_label) . '</option>';
    }
    
    echo '</select>';

    // Existing attributes section
    echo '<div id="pca-attributes-wrapper" class="woocommerce_attributes_wrapper">';
    foreach ($attributes as $attribute) {
        pca_render_attribute_row($attribute);
    }
    echo '</div>';

    echo '</div>'; // end of woocommerce_attributes_wrapper
}

// Render Attribute Row
function pca_render_attribute_row($attribute = []) {
    $attribute_name = isset($attribute['name']) ? esc_attr($attribute['name']) : '';
    $attribute_values = isset($attribute['values']) ? implode(',', $attribute['values']) : '';
    ?>
    <div class="woocommerce_attribute wc-metabox">
        <h3>
            <strong><?php echo $attribute_name ? esc_html($attribute_name) : __('New Attribute', 'product-creation-assistant'); ?></strong>
            <a href="#" class="remove_row delete"><?php _e('Remove', 'product-creation-assistant'); ?></a>
        </h3>
        <div class="woocommerce_attribute_data wc-metabox-content hidden">
            <table cellpadding="0" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="attribute_name">
                            <input type="text" name="pca_attributes_names[]" value="<?php echo esc_attr($attribute_name); ?>" placeholder="<?php esc_attr_e('Attribute Name', 'product-creation-assistant'); ?>" />
                        </td>
                        <td class="attribute_values">
                            <input type="text" name="pca_attributes_values[]" value="<?php echo esc_attr($attribute_values); ?>" placeholder="<?php esc_attr_e('Values (comma-separated)', 'product-creation-assistant'); ?>" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// Save the Product Rule Attributes
function pca_save_product_rule_attributes($post_id) {
    if (isset($_POST['pca_attributes_names']) && isset($_POST['pca_attributes_values'])) {
        $attributes = [];
        $names = array_map('sanitize_text_field', $_POST['pca_attributes_names']);
        $values = array_map('sanitize_text_field', $_POST['pca_attributes_values']);

        for ($i = 0; $i < count($names); $i++) {
            $attributes[] = [
                'name' => $names[$i],
                'values' => explode(',', $values[$i]),
            ];
        }

        update_post_meta($post_id, '_pca_product_rule_attributes', $attributes);
    }
}
add_action('save_post', 'pca_save_product_rule_attributes');

// Enqueue Scripts and Styles for Admin Interface
function pca_enqueue_admin_scripts() {
    wp_enqueue_style('pca-admin-css', plugin_dir_url(__FILE__) . 'css/product-creation-assistant.css');
    wp_enqueue_script('pca-admin-js', plugin_dir_url(__FILE__) . 'js/product-creation-assistant.js', array('jquery', 'jquery-ui-sortable', 'wp-util'), null, true);
    wp_enqueue_script('wc-enhanced-select');

    // Localize the script with new data
    wp_localize_script('pca-admin-js', 'pca_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('pca_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'pca_enqueue_admin_scripts');

// Add Existing Attribute Row via AJAX
function pca_add_existing_attribute_row() {
    if (!isset($_POST['attribute_name']) || !isset($_POST['attribute_label'])) {
        wp_die(-1);
    }

    $attribute_name = sanitize_text_field(wp_unslash($_POST['attribute_name']));
    $attribute_label = sanitize_text_field(wp_unslash($_POST['attribute_label']));

    // Render the attribute row
    echo pca_render_existing_attribute_row($attribute_name, $attribute_label);

    wp_die();
}
add_action('wp_ajax_pca_add_existing_attribute_row', 'pca_add_existing_attribute_row');

// Render the Existing Attribute Row
function pca_render_existing_attribute_row($attribute_name, $attribute_label) {
    $tax_name = wc_attribute_taxonomy_name($attribute_name);
    $terms = get_terms($tax_name, array('hide_empty' => false));

    ob_start();
    ?>
    <div class="woocommerce_attribute wc-metabox closed">
        <h3>
            <strong><?php echo esc_html($attribute_label); ?></strong>
            <a href="#" class="remove_row delete"><?php _e('Remove', 'product-creation-assistant'); ?></a>
        </h3>
        <div class="woocommerce_attribute_data wc-metabox-content hidden">
            <table cellpadding="0" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="attribute_name">
                            <input type="hidden" name="pca_attributes_names[]" value="<?php echo esc_attr($attribute_name); ?>" />
                            <input type="hidden" name="pca_attributes_labels[]" value="<?php echo esc_attr($attribute_label); ?>" />
                            <input type="text" name="pca_attributes_values[]" value="" placeholder="<?php esc_attr_e('Values (comma-separated)', 'product-creation-assistant'); ?>" />
                        </td>
                        <td class="attribute_visibility">
                            <input type="checkbox" name="pca_attributes_visibility[]" value="1" checked="checked" /> <?php _e('Visible on the product page', 'product-creation-assistant'); ?>
                        </td>
                        <td class="attribute_variation">
                            <input type="checkbox" name="pca_attributes_variation[]" value="1" /> <?php _e('Used for variations', 'product-creation-assistant'); ?>
                        </td>
                        <td class="attribute_terms">
                            <select multiple="multiple" name="pca_attributes_term_values[<?php echo esc_attr($attribute_name); ?>][]" class="wc-enhanced-select">
                                <?php foreach ($terms as $term) : ?>
                                    <option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="button button-secondary pca-create-new-term"><?php _e('Create value', 'product-creation-assistant'); ?></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
