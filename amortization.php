<?php
// amortization.php

// Add the Amortization settings page
function rvc_amortization_settings_page() {
    // Check if the user has permission to access this page
    if (!current_user_can('edit_others_posts')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    ?>
    <div class="wrap">
        <h2>Amortization Settings</h2>
        <button class="button button-primary" id="reset-button">Reset to Default</button>
        <div id="reset-dialog" style="display: none;">
            <p>Are you sure you want to reset all values to default? This action cannot be undone.</p>
            <button class="button button-primary" id="reset-confirm">Reset</button>
            <button class="button" id="reset-cancel">Cancel</button>
        </div>
        <form method="post">
            <?php
            global $wpdb;
            $amortization_table = $wpdb->prefix . 'rvcpc_amortization';
            $results = $wpdb->get_results("SELECT * FROM $amortization_table ORDER BY vehicle_year DESC");
            ?>
            <table class="form-table" style="width: auto; border-collapse: collapse;">
                <tr>
                    <th style="width: 10ch; padding: 4px;">Year of Vehicle</th>
                    <th style="padding: 4px;">Amortization (months)</th>
                    <th style="padding: 4px;">Term (months)</th>
                </tr>
                <?php
                foreach ($results as $result) {
                    ?>
                    <tr>
                        <td style="padding: 4px;"><?php echo $result->vehicle_year; ?></td>
                        <td style="padding: 4px;"><input type="number" name="amortization_<?php echo $result->vehicle_year; ?>" value="<?php echo $result->amortization_months; ?>" min="12" style="width: 100%;"></td>
                        <td style="padding: 4px;"><input type="number" name="term_<?php echo $result->vehicle_year; ?>" value="<?php echo $result->term_months; ?>" min="12" style="width: 100%;"></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#reset-button').click(function() {
                $('#reset-dialog').show();
            });
            $('#reset-cancel').click(function() {
                $('#reset-dialog').hide();
            });
            $('#reset-confirm').click(function() {
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'reset_amortization_settings'
                    },
                    success: function(response) {
                        location.reload();
                    }
                });
            });
        });
    </script>
    <?php
}

// Save the amortization length values
function rvc_save_amortization_lengths() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        global $wpdb;
        $amortization_table = $wpdb->prefix . 'rvcpc_amortization';
        $results = $wpdb->get_results("SELECT * FROM $amortization_table ORDER BY vehicle_year DESC");
        foreach ($results as $result) {
            $vehicle_year = $result->vehicle_year;
            $amortization_months = isset($_POST['amortization_' . $vehicle_year]) ? absint($_POST['amortization_' . $vehicle_year]) : $result->amortization_months;
            $term_months = isset($_POST['term_' . $vehicle_year]) ? absint($_POST['term_' . $vehicle_year]) : $result->term_months;
            $wpdb->update($amortization_table, array('amortization_months' => $amortization_months, 'term_months' => $term_months), array('vehicle_year' => $vehicle_year));
        }
    }
}

// Reset amortization settings to default
function reset_amortization_settings() {
    global $wpdb;
    $amortization_table = $wpdb->prefix . 'rvcpc_amortization';
    $current_year = date('Y');
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
    $results = $wpdb->get_results("SELECT * FROM $amortization_table ORDER BY vehicle_year DESC");
    foreach ($results as $result) {
        $vehicle_year = $result->vehicle_year;
        $amortization_months = isset($amortization_defaults[$vehicle_year]) ? $amortization_defaults[$vehicle_year]['length'] : 240;
        $term_months = isset($amortization_defaults[$vehicle_year]) ? $amortization_defaults[$vehicle_year]['term'] : 60;
        $wpdb->update($amortization_table, array('amortization_months' => $amortization_months, 'term_months' => $term_months), array('vehicle_year' => $vehicle_year));
    }
    wp_die();
}

add_action('wp_ajax_reset_amortization_settings', 'reset_amortization_settings');
add_action('admin_init', 'rvc_save_amortization_lengths');
?>
