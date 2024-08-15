<?php
/**
 * Plugin Name: Product Creation Assistant
 * Description: A plugin to assist with creating WooCommerce product variations using a custom post type for rules.
 * Version: 2.1
 * Author: Elle
 * Text Domain: product-creation-assistant
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'admin/class-pca-post-type.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-pca-meta-boxes.php';

// Instantiate the classes
function pca_enqueue_admin_scripts() {
    wp_enqueue_style('pca-admin-css', plugin_dir_url(__FILE__) . 'admin/css/product-creation-assistant.css');
    wp_enqueue_script('pca-admin-js', plugin_dir_url(__FILE__) . 'admin/js/product-creation-assistant.js', array('jquery', 'jquery-ui-sortable', 'wp-util', 'select2'), null, true);
    wp_enqueue_script('wc-enhanced-select'); // WooCommerce select2 for enhanced dropdowns
    
    // Localize the script with new data
    wp_localize_script('pca-admin-js', 'pca_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('pca_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'pca_enqueue_admin_scripts');
