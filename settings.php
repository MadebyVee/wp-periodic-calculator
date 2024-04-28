<?php

function rvc_financing_setup_menu() {
    add_menu_page( 'RVC Financing Settings', 'Financing Settings', 'manage_options', 'rvc-financing', 'rvc_financing_init', 'dashicons-money-alt' );
    // Add submenu item for amortization settings
    add_submenu_page( 'rvc-financing', 'Amortization Settings', 'Amortization', 'manage_options', 'rvc-amortization', 'rvc_amortization_settings_page' );
}
add_action('admin_menu', 'rvc_financing_setup_menu');

// Initialize the plugin settings page content
function rvc_financing_init() {
    ?>
    <div class="wrap">
        <h2>Financing Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('rvc-financing-settings');
            do_settings_sections('rvc-financing');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register and define the settings
add_action('admin_init', 'rvc_financing_settings');
function rvc_financing_settings() {
    register_setting('rvc-financing-settings', 'rvc_financing_apr', array(
        'type' => 'float',
        'sanitize_callback' => 'floatval',
        'default' => 8.99
    ));
    register_setting('rvc-financing-settings', 'rvc_financing_tax', array(
        'type' => 'float',
        'sanitize_callback' => 'floatval',
        'default' => 5.00
    ));
    register_setting('rvc-financing-settings', 'rvc_financing_regular_price_meta', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'regular_price_meta'
    ));
    register_setting('rvc-financing-settings', 'rvc_financing_dealer_price_meta', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'dealer_price_meta'
    ));
    register_setting('rvc-financing-settings', 'rvc_financing_sale_price_meta', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'sale_price_meta'
    ));

    add_settings_section('rvc_financing_main', 'Main Settings', 'rvc_financing_section_text', 'rvc-financing');

    add_settings_field('rvc_financing_apr', 'APR %', 'rvc_financing_setting_apr', 'rvc-financing', 'rvc_financing_main');
    add_settings_field('rvc_financing_tax', 'Tax Rate', 'rvc_financing_setting_tax', 'rvc-financing', 'rvc_financing_main');
    add_settings_field('rvc_financing_regular_price_meta', 'Regular Price Meta', 'rvc_financing_setting_regular_price_meta', 'rvc-financing', 'rvc_financing_main');
    add_settings_field('rvc_financing_dealer_price_meta', 'Dealer Price Meta', 'rvc_financing_setting_dealer_price_meta', 'rvc-financing', 'rvc_financing_main');
    add_settings_field('rvc_financing_sale_price_meta', 'Sale Price Meta', 'rvc_financing_setting_sale_price_meta', 'rvc-financing', 'rvc_financing_main');
}

function rvc_financing_section_text() {
    echo '<p>
    
    Manage pricing and tax settings effortlessly with this plugin.</p>
    <p>
    Set your APR, Tax rates and designate meta fields.
    </p>';
}

function rvc_financing_setting_apr() {
    $apr = esc_attr(get_option('rvc_financing_apr'));
    echo "<input type='number' name='rvc_financing_apr' value='$apr' step='0.01' />";
}

function rvc_financing_setting_tax() {
    $tax = esc_attr(get_option('rvc_financing_tax'));
    echo "<input type='number' name='rvc_financing_tax' value='$tax' step='0.01' />";
}

function rvc_financing_setting_regular_price_meta() {
    $regular = esc_attr(get_option('rvc_financing_regular_price_meta'));
    echo "<input type='text' name='rvc_financing_regular_price_meta' value='$regular' />";
}

function rvc_financing_setting_dealer_price_meta() {
    $dealer = esc_attr(get_option('rvc_financing_dealer_price_meta'));
    echo "<input type='text' name='rvc_financing_dealer_price_meta' value='$dealer' />";
}

function rvc_financing_setting_sale_price_meta() {
    $sale = esc_attr(get_option('rvc_financing_sale_price_meta'));
    echo "<input type='text' name='rvc_financing_sale_price_meta' value='$sale' />";
}
?>
