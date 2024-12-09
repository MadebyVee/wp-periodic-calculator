<?php
// rvtypes.php

// Initialize the RV types settings page content
function rvc_rv_types_settings_page() {
    // Check if the user has permission to access this page
    if (!current_user_can('edit_others_posts')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    ?>
    <div class="wrap">
        <h2>RV Types Settings</h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php
            wp_nonce_field('rvc-rv-types');
            ?>
            <?php
            global $wpdb;
            $rv_types_table = $wpdb->prefix . 'rvcpc_rvtypes_fees';
            $results = $wpdb->get_results("SELECT * FROM $rv_types_table ORDER BY rv_type_name ASC");
            if ($results) {
                ?>
                <table class="form-table">
                    <tr>
                        <th>RV Type Label</th>
                        <th>RV Type Name</th>
                        <th>Fees</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    foreach ($results as $result) {
                        ?>
                        <tr>
                            <td><?php echo $result->rv_type_name; ?></td>
                            <td><input type="text" name="rv_type_name_<?php echo $result->id; ?>" value="<?php echo $result->rv_type_name; ?>"></td>
                            <td><input type="number" name="rv_type_fees_<?php echo $result->id; ?>" value="<?php echo $result->rv_type_fee; ?>" step="0.01"></td>
                            <td>
                                <button type="button" class="button delete-button" data-id="<?php echo $result->id; ?>">Delete</button>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <?php
            } else {
                echo "No RV types found.";
            }
            ?>
            <?php submit_button('Save Changes'); ?>
            <input type="hidden" name="action" value="rvc_save_rv_types_settings" />
        </form>

        <h3>Add New RV Type</h3>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('rvc-add-new-rv-type'); ?>
            <table class="form-table">
                <tr>
                    <th>RV Type Name</th>
                    <td><input type="text" name="new_rv_type_name" required></td>
                </tr>
                <tr>
                    <th>Fees</th>
                    <td><input type="number" name="new_rv_type_fees" step="0.01" required></td>
                </tr>
            </table>
            <input type="hidden" name="action" value="rvc_add_new_rv_type" />
            <?php submit_button('Add New'); ?>
        </form>
    </div>

    <script type="text/javascript">
        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this RV type?')) {
                    const id = this.getAttribute('data-id');
                    window.location.href = '<?php echo admin_url('admin-post.php?action=rvc_delete_rv_type&id='); ?>' + id;
                }
            });
        });
    </script>
    <?php
}

// Save the RV types settings
function rvc_save_rv_types_settings() {
    global $wpdb;
    $rv_types_table = $wpdb->prefix . 'rvcpc_rvtypes_fees';
    $results = $wpdb->get_results("SELECT * FROM $rv_types_table");
    foreach ($results as $result) {
        $id = $result->id;
        $rv_type_name = isset($_POST['rv_type_name_' . $id]) ? $_POST['rv_type_name_' . $id] : '';
        $rv_type_fees = isset($_POST['rv_type_fees_' . $id]) ? $_POST['rv_type_fees_' . $id] : '';
        $wpdb->update($rv_types_table, array('rv_type_name' => $rv_type_name, 'rv_type_fee' => $rv_type_fees), array('id' => $id));
    }
    wp_redirect(admin_url('admin.php?page=rvc-rv-types'));
    exit;
}
add_action('admin_post_rvc_save_rv_types_settings', 'rvc_save_rv_types_settings');

// Handle the deletion of an RV type
function rvc_delete_rv_type() {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        wp_die('Invalid ID.');
    }
    global $wpdb;
    $rv_types_table = $wpdb->prefix . 'rvcpc_rvtypes_fees';
    $wpdb->delete($rv_types_table, array('id' => intval($_GET['id'])));
    wp_redirect(admin_url('admin.php?page=rvc-rv-types'));
    exit;
}
add_action('admin_post_rvc_delete_rv_type', 'rvc_delete_rv_type');

// Add a new RV type
function rvc_add_new_rv_type() {
    if (!isset($_POST['new_rv_type_name']) || !isset($_POST['new_rv_type_fees'])) {
        wp_die('Invalid data.');
    }

    global $wpdb;
    $rv_types_table = $wpdb->prefix . 'rvcpc_rvtypes_fees';
    $wpdb->insert($rv_types_table, array(
        'rv_type_name' => sanitize_text_field($_POST['new_rv_type_name']),
        'rv_type_fee' => floatval($_POST['new_rv_type_fees'])
    ));

    wp_redirect(admin_url('admin.php?page=rvc-rv-types'));
    exit;
}
add_action('admin_post_rvc_add_new_rv_type', 'rvc_add_new_rv_type');
