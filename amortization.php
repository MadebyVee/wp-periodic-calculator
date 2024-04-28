<?php
// rvc-amortization-settings.php

// Add the Amortization settings page
function rvc_amortization_settings_page() {
    ?>
    <div class="wrap">
        <form method="post" action="options.php">
            <?php
            settings_fields('rvc-amortization-settings');
            do_settings_sections('rvc-amortization');
            ?>
            <table class="form-table">
                <tr>
                    <th>Year of Vehicule</th>
                    <th>Length (Months)</th>
                </tr>
                <?php
                $years = range(2025, 2006);
                foreach ($years as $year) {
                    $length = get_option('rvc_financing_amortization_length_' . $year);
                    ?>
                    <tr>
                        <td><?php echo $year; ?></td>
                        <td><input type="number" name="rvc_financing_amortization_length_<?php echo $year; ?>" value="<?php echo $length; ?>" min="1"></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register and define the Amortization settings
add_action('admin_init', 'rvc_amortization_settings');
function rvc_amortization_settings() {
    register_setting('rvc-amortization-settings', 'rvc_financing_amortization_years', array(
        'type' => 'array',
        'sanitize_callback' => 'rvc_amortization_sanitize_years',
        'default' => array()
    ));

    // Register the lengths for each year
    $years = range(2025, 2006);
    foreach ($years as $year) {
        register_setting('rvc-amortization-settings', 'rvc_financing_amortization_length_' . $year, array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0 // Set a default value of 0
        ));
    }

    add_settings_section('rvc_amortization_main', 'Amortization Settings', 'rvc_amortization_section_text', 'rvc-amortization');
}

function rvc_amortization_section_text() {
    echo '<p>Enter your Amortization length in months.</p>';
}

// Sanitize years input
function rvc_amortization_sanitize_years($input) {
    return json_decode($input, true);
}

// Save the amortization length values
function rvc_save_amortization_lengths() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // No need to do anything here, settings API automatically handles saving
    }
}

add_action('admin_init', 'rvc_save_amortization_lengths');


