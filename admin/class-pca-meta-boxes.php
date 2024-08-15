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
        // Your existing meta box code here
    }

    public function save_product_rule_attributes($post_id) {
        // Your existing save function code here
    }

    public function add_existing_attribute_row() {
        // Your existing AJAX callback code here
    }
}

new PCA_Meta_Boxes();
