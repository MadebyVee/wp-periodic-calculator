<?php
// metas.php

// Initialize the meta settings page content
function rvc_meta_settings_init() {
    ?>
    <div class="wrap">
        <h2>Meta Settings</h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php
            wp_nonce_field('rvc-meta-settings');
            ?>
            <input type="hidden" name="action" value="rvc_save_meta_settings" />
            <table class="form-table">
                <?php
                global $wpdb;
                $meta_keys = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 'rvcpc_meta');
                foreach ($meta_keys as $meta_key) {
                    ?>
                    <tr>
                        <th scope="row"><label for="meta_key_name_<?php echo esc_attr($meta_key->id); ?>"><?php echo esc_html($meta_key->meta_key_label); ?></label></th>
                        <td>
                            <input type="text" id="meta_key_name_<?php echo esc_attr($meta_key->id); ?>" name="meta_key_name_<?php echo esc_attr($meta_key->id); ?>" value="<?php echo esc_attr($meta_key->meta_key_name); ?>" />
                            <input type="text" id="post_type_<?php echo esc_attr($meta_key->id); ?>" name="post_type_<?php echo esc_attr($meta_key->id); ?>" value="<?php echo esc_attr($meta_key->post_type); ?>" />
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <?php submit_button('Save Changes'); ?>
        </form>
    </div>
    <?php
}

// Save the meta settings
function rvc_save_meta_settings() {
    // Check if the request comes from the correct action
    if (isset($_POST['action']) && $_POST['action'] === 'rvc_save_meta_settings') {
        check_admin_referer('rvc-meta-settings');
        global $wpdb;
        $meta_keys = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 'rvcpc_meta');
        foreach ($meta_keys as $meta_key) {
            $id = $meta_key->id;
            $meta_key_name = isset($_POST['meta_key_name_' . $id]) ? $_POST['meta_key_name_' . $id] : '';
            $post_type = isset($_POST['post_type_' . $id]) ? $_POST['post_type_' . $id] : '';
            $wpdb->update($wpdb->prefix . 'rvcpc_meta', array('meta_key_name' => $meta_key_name, 'post_type' => $post_type), array('id' => $id));
        }

        // Redirect back to the settings page
        wp_redirect(admin_url('admin.php?page=rvc-meta-settings&updated=true'));
        exit;
    }
}

// Add the meta settings page to the main plugin menu
function rvc_add_meta_settings_page() {
    add_submenu_page('rvc-financing', 'Meta Settings', 'Meta Settings', 'edit_others_posts', 'rvc-meta-settings', 'rvc_meta_settings_init');
}

add_action('admin_menu', 'rvc_add_meta_settings_page');
add_action('admin_post_rvc_save_meta_settings', 'rvc_save_meta_settings');
