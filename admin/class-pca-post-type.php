<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PCA_Post_Type {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'remove_post_type_support'));
    }

    public function register_post_type() {
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
            'publicly_queryable' => true,
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

    public function remove_post_type_support() {
        remove_post_type_support('pca_product_rule', 'editor');
    }
}

new PCA_Post_Type();
