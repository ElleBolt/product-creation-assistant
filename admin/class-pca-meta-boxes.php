<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PCA_Meta_Boxes {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));
        add_action('save_post', array($this, 'save_product_rule_attributes'));
        add_action('wp_ajax_pca_add_existing_attribute_row', array($this, 'add_existing_attribute_row'));
    }

    public function add_custom_meta_boxes() {
        add_meta_box('pca_product_rule_attributes', __('Product Rule Attributes', 'product-creation-assistant'), array($this, 'product_rule_attributes_meta_box'), 'pca_product_rule', 'normal', 'high');
    }

    public function product_rule_attributes_meta_box($post) {
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
            $this->render_attribute_row($attribute);
        }
        echo '</div>';
    
        echo '</div>'; // end of woocommerce_attributes_wrapper
        echo '</div>'; // end of pca_attributes_box
    }
    
    private function render_attribute_row($attribute = []) {
        $attribute_name = isset($attribute['name']) ? esc_attr($attribute['name']) : '';
        $attribute_values = isset($attribute['values']) ? implode(',', $attribute['values']) : '';
        ?>
        <div class="woocommerce_attribute wc-metabox closed">
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

    public function save_product_rule_attributes($post_id) {
        // Your existing save function code here
    }

    public function add_existing_attribute_row() {
        // Your existing AJAX callback code here
    }
}

new PCA_Meta_Boxes();
