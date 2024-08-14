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
    // Your dynamic form code goes here
}

// Sanitize the input before saving
function pca_sanitize_rules($input) {
    // Your sanitization code goes here
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
