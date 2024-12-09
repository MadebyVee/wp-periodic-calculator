<?php
// settings.php

// Initialize the plugin settings page content
function rvc_financing_init() {
    ?>
    <div class="wrap">
        <h2>Periodic Payment Settings</h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php
            wp_nonce_field('rvc-financing-settings');
            ?>
            <table class="form-table">
                <?php
                global $wpdb;
                $settings_table = $wpdb->prefix . 'rvcpc_settings';
                $settings = $wpdb->get_results("SELECT * FROM $settings_table");
                foreach ($settings as $setting) {
                    ?>
                    <tr>
                        <th scope="row"><label for="rvc_financing_<?php echo esc_attr($setting->setting_key); ?>"><?php echo esc_html($setting->setting_key); ?></label></th>
                        <td>
                            <?php if ($setting->setting_type == 'percentage') { ?>
                                <input type="number" id="rvc_financing_<?php echo esc_attr($setting->setting_key); ?>" name="rvc_financing_settings[<?php echo esc_attr($setting->setting_key); ?>]" value="<?php echo esc_attr($setting->setting_value); ?>" step="0.01" />
                            <?php } else { ?>
                                <input type="text" id="rvc_financing_<?php echo esc_attr($setting->setting_key); ?>" name="rvc_financing_settings[<?php echo esc_attr($setting->setting_key); ?>]" value="<?php echo esc_attr($setting->setting_value); ?>" />
                            <?php } ?>
                            <p class="description"><?php echo esc_html($setting->description); ?></p>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <?php submit_button(); ?>
            <input type="hidden" name="action" value="rvc_financing_save_settings" />
        </form>
    </div>
    <?php
}

// Save settings to database
function rvc_financing_save_settings() {
    global $wpdb;
    $settings_table = $wpdb->prefix . 'rvcpc_settings';
    $settings = $wpdb->get_results("SELECT * FROM $settings_table");
    $new_settings = $_POST['rvc_financing_settings'];
    foreach ($settings as $setting) {
        if (isset($new_settings[$setting->setting_key])) {
            $wpdb->update($settings_table, array('setting_value' => $new_settings[$setting->setting_key]), array('setting_key' => $setting->setting_key));
        }
    }
    ?>
    <script>
        window.location.href = '<?php echo admin_url('admin.php?page=rvc-financing-settings'); ?>';
    </script>
    <?php
    exit;
}
add_action('admin_post_rvc_financing_save_settings', 'rvc_financing_save_settings');
