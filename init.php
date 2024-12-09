<?php
/**
 * Plugin Name: XPRSS - Periodic Payments
 * Description: Calculate periodic payments, cost of borrowing & loan calculator
 * Version: 1.5
 * Author: Vlad Xprss
 */

defined('ABSPATH') or die('No script kiddies please!');

// Include the required files
require_once(plugin_dir_path(__FILE__) . 'functions.php');
require_once(plugin_dir_path(__FILE__) . 'settings.php');
require_once(plugin_dir_path(__FILE__) . 'amortization.php');
require_once(plugin_dir_path(__FILE__) . 'metas.php');
require_once(plugin_dir_path(__FILE__) . 'rvtypes.php');

// Setup menu for plugin settings
function rvc_financing_setup_menu() {
    add_menu_page(
        'Periodic Payment Settings', // Page title
        'Periodic Payment Settings', // Menu title
        'edit_others_posts', // Capability
        'rvc-financing', // Menu slug
        'rvc_financing_init', // Callback function
        'dashicons-money-alt' // Icon
    );

    // Add submenu item for amortization settings
    add_submenu_page(
        'rvc-financing', // Parent slug
        'Term & Amortization', // Page title
        'Term & Amortization', // Menu title
        'edit_others_posts', // Capability
        'rvc-amortization', // Menu slug
        'rvc_amortization_settings_page' // Callback function
    );

    // Add submenu item for meta settings
    add_submenu_page(
        'rvc-financing', // Parent slug
        'Meta Settings', // Page title
        'Meta Settings', // Menu title
        'edit_others_posts', // Capability
        'rvc-meta-settings', // Menu slug
        'rvc_meta_settings_init' // Callback function
    );

    // Add submenu item for RV Types settings
    add_submenu_page(
        'rvc-financing', // Parent slug
        'RV Types Settings', // Page title
        'RV Types', // Menu title
        'edit_others_posts', // Capability
        'rvc-rv-types', // Menu slug
        'rvc_rv_types_settings_page' // Callback function
    );

    // Add submenu item for plugin settings
    add_submenu_page(
        'rvc-financing', // Parent slug
        'Plugin Settings', // Page title
        'Settings', // Menu title
        'edit_others_posts', // Capability
        'rvc-financing-settings', // Menu slug
        'rvc_financing_init' // Callback function
    );
}
add_action('admin_menu', 'rvc_financing_setup_menu');


/**
 * Create the financing-related tables
 */
function create_financing_tables(): void {
    global $wpdb;

    // Set the character set and collation
    $charset_collate = $wpdb->get_charset_collate();

    // Table names
    $amortization_table = $wpdb->prefix . 'rvcpc_amortization';
    $rvtypes_fees_table = $wpdb->prefix . 'rvcpc_rvtypes_fees';
    $metas_table = $wpdb->prefix . 'rvcpc_meta';
    $settings_table = $wpdb->prefix . 'rvcpc_settings';    
    
        // SQL for creating the tables
        $sql = "
        CREATE TABLE $rvtypes_fees_table (
            id INT(11) NOT NULL AUTO_INCREMENT,
            rv_type_name VARCHAR(255) NOT NULL UNIQUE,
            rv_type_fee DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;
    
        CREATE TABLE $metas_table (
            id INT(11) NOT NULL AUTO_INCREMENT,
            meta_key_label VARCHAR(255) NOT NULL,
            meta_key_name VARCHAR(255) NOT NULL,
            post_type VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;
    
        CREATE TABLE $amortization_table (
            id INT(11) NOT NULL AUTO_INCREMENT,
            vehicle_year INT(4) NOT NULL,
            amortization_months INT(11) NOT NULL,
            term_months INT(11) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;
    
        CREATE TABLE $settings_table (
            id INT(11) NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value DECIMAL(10,2) NOT NULL,
            setting_type VARCHAR(255) NOT NULL,
            is_percentage TINYINT(1) DEFAULT 0,
            description VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;
        ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Hook to create tables and initialize settings on plugin activation
 */
function on_plugin_activation(): void {
    global $wpdb;

    // Create the necessary financing tables
    create_financing_tables();

    // Get the current year
    $current_year = date('Y');

    // Tables we will reset
    $tables_to_reset = [
        $wpdb->prefix . 'rvcpc_amortization',
        $wpdb->prefix . 'rvcpc_meta',
        $wpdb->prefix . 'rvcpc_settings',
        $wpdb->prefix . 'rvcpc_rvtypes_fees'
    ];

    // Truncate the tables (deleting all rows)
    foreach ($tables_to_reset as $table) {
        $wpdb->query("TRUNCATE TABLE $table");
    }


    // Define default values for amortization lengths and terms
    $amortization_defaults = [
        $current_year + 1 => ['length' => 240, 'term' => 60], // Future model year
        $current_year => ['length' => 240, 'term' => 60],
        $current_year - 1 => ['length' => 240, 'term' => 60],
        $current_year - 2 => ['length' => 240, 'term' => 60],
        $current_year - 3 => ['length' => 240, 'term' => 60],
        $current_year - 4 => ['length' => 240, 'term' => 60],
        $current_year - 5 => ['length' => 216, 'term' => 60],
        $current_year - 6 => ['length' => 204, 'term' => 60],
        $current_year - 7 => ['length' => 192, 'term' => 60],
        $current_year - 8 => ['length' => 180, 'term' => 60],
        $current_year - 9 => ['length' => 168, 'term' => 60],
        $current_year - 10 => ['length' => 156, 'term' => 60],
        $current_year - 11 => ['length' => 144, 'term' => 60],
        $current_year - 12 => ['length' => 132, 'term' => 60],
        $current_year - 13 => ['length' => 120, 'term' => 60],
        $current_year - 14 => ['length' => 108, 'term' => 60],
        $current_year - 15 => ['length' => 96, 'term' => 60],
        $current_year - 16 => ['length' => 84, 'term' => 60],
        $current_year - 17 => ['length' => 72, 'term' => 60],
        $current_year - 18 => ['length' => 60, 'term' => 60],
        $current_year - 19 => ['length' => 48, 'term' => 60],
        $current_year - 20 => ['length' => 36, 'term' => 48],
        $current_year - 21 => ['length' => 36, 'term' => 36],
        $current_year - 22 => ['length' => 24, 'term' => 24],
        $current_year - 23 => ['length' => 12, 'term' => 12],
        $current_year - 24 => ['length' => 12, 'term' => 12],
        $current_year - 25 => ['length' => 12, 'term' => 12],
        $current_year - 26 => ['length' => 12, 'term' => 12],
    ];
    
    // Insert default values into the amortization table
    foreach ($amortization_defaults as $year => $values) {
        $result = $wpdb->insert($wpdb->prefix . 'rvcpc_amortization', [
            'vehicle_year' => $year,
            'amortization_months' => $values['length'],
            'term_months' => $values['term'],
        ]);
        if ($result === false) {
            error_log("Failed to insert amortization values for year $year");
        }
    }

    // Insert default values into the settings table
    $default_settings = [
        ['setting_key' => 'interest_rate', 'setting_value' => 8.99, 'setting_type' => 'percentage', 'is_percentage' => 1, 'description' => 'Interest rate for financing'],
        ['setting_key' => 'tax_rate', 'setting_value' => 13.00, 'setting_type' => 'percentage', 'is_percentage' => 1, 'description' => 'Tax rate for financing'],
        ['setting_key' => 'loan_fee', 'setting_value' => 0, 'setting_type' => 'fixed', 'is_percentage' => 0, 'description' => 'Loan fee for financing'],
        ['setting_key' => 'taxed_fee', 'setting_value' => 0, 'setting_type' => 'fixed', 'is_percentage' => 0, 'description' => 'Tax inclusive fee for financing'],
    ];

    foreach ($default_settings as $setting) {
        $result = $wpdb->insert($wpdb->prefix . 'rvcpc_settings', $setting);
        if ($result === false) {
            error_log("Failed to insert default setting for key " . $setting['setting_key']);
        }
    }

    // Insert default values into the RV types fees table
    $rv_types_defaults = [
        ['rv_type_name' => 'Class A', 'rv_type_fee' => 0],
        ['rv_type_name' => 'Class B', 'rv_type_fee' => 0],
        ['rv_type_name' => 'Class C', 'rv_type_fee' => 0],
        ['rv_type_name' => 'Travel Trailer', 'rv_type_fee' => 0],
        ['rv_type_name' => 'Fifth Wheel', 'rv_type_fee' => 0],
        ['rv_type_name' => 'Pop-Up Camper', 'rv_type_fee' => 0],
        ['rv_type_name' => 'Truck Camper', 'rv_type_fee' => 0],
    ];

    foreach ($rv_types_defaults as $rv_type) {
        $result = $wpdb->insert($wpdb->prefix . 'rvcpc_rvtypes_fees', $rv_type);
        if ($result === false) {
            error_log("Failed to insert RV type: " . $rv_type['rv_type_name']);
        }
    }

    // Define default meta key values
    $meta_keys_defaults = [
        ['meta_key_label' => 'Regular Price', 'meta_key_name' => 'regular-price', 'post_type' => 'rv-products'],
        ['meta_key_label' => 'Dealer Price', 'meta_key_name' => 'msrp-price', 'post_type' => 'rv-products'],
        ['meta_key_label' => 'Sale Price', 'meta_key_name' => 'reduced-regular-price', 'post_type' => 'rv-products'],
    ];

    // Insert the default meta key settings
    foreach ($meta_keys_defaults as $meta_key_default) {
        $result = $wpdb->insert($wpdb->prefix . 'rvcpc_meta', [
            'meta_key_label' => $meta_key_default['meta_key_label'],
            'meta_key_name' => $meta_key_default['meta_key_name'],
            'post_type' => $meta_key_default['post_type'],
        ]);
        if ($result === false) {
            error_log("Failed to insert meta key: " . $meta_key_default['meta_key_label']);
        }
    }

    // Flush the rewrite rules
    flush_rewrite_rules();
}

// Hook to create tables and initialize settings on plugin activation
register_activation_hook(__FILE__, 'on_plugin_activation');

