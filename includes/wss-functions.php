<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the count of open tickets for the admin menu bubble.
 */
function wss_get_open_ticket_count() {
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$wpdb->prefix}support_tickets WHERE status = %s OR status = %s", 'Open', 'Awaiting Admin Reply'));
    return (int) $count;
}

/**
 * Get a translatable, human-readable status name.
 * @param string $status The status slug.
 * @return string The display name for the status.
 */
function wss_get_status_name( $status ) {
    $statuses = [
        'Open' => __('Open', 'woo-support-system'), 
        'Processing' => __('Processing', 'woo-support-system'),
        'Awaiting Admin Reply' => __('Awaiting Admin Reply', 'woo-support-system'), 
        'Awaiting Customer Reply' => __('Awaiting Customer Reply', 'woo-support-system'),
        'Closed' => __('Closed', 'woo-support-system'), 
        'Cancelled' => __('Cancelled', 'woo-support-system'),
    ];
    return $statuses[$status] ?? $status;
}

/**
 * Load a template file.
 */
function wss_get_template($template_name, $args = []) {
    $template_path = WSS_PLUGIN_PATH . 'templates/' . $template_name;
    if (file_exists($template_path)) { 
        extract($args); 
        include $template_path; 
    }
}

/**
 * Adds a note to the ticket's activity log.
 */
function wss_add_ticket_history_note($ticket_id, $note, $user_id = null) {
    global $wpdb;
    if ($user_id === null) $user_id = get_current_user_id();
    $wpdb->insert("{$wpdb->prefix}support_ticket_history", [
        'ticket_id' => $ticket_id, 'user_id' => $user_id, 'note' => $note,
        'created_at' => current_time('mysql', 1)
    ]);
}

/**
 * Deletes a ticket and all associated data.
 * @param int $ticket_id The ID of the ticket to delete.
 */
function wss_delete_ticket($ticket_id) {
    global $wpdb;
    $ticket_id = absint($ticket_id);
    if (!$ticket_id) return false;

    $tickets_table = $wpdb->prefix . 'support_tickets';
    $replies_table = $wpdb->prefix . 'support_ticket_replies';
    $history_table = $wpdb->prefix . 'support_ticket_history';
    $attachments_table = $wpdb->prefix . 'support_ticket_attachments';

    // Delete associated data first
    if ($wpdb->get_var("SHOW TABLES LIKE '{$attachments_table}'") === $attachments_table) {
        $wpdb->delete($attachments_table, ['ticket_id' => $ticket_id], ['%d']);
    }
    $wpdb->delete($replies_table, ['ticket_id' => $ticket_id], ['%d']);
    $wpdb->delete($history_table, ['ticket_id' => $ticket_id], ['%d']);
    
    // Finally, delete the main ticket record
    return $wpdb->delete($tickets_table, ['id' => $ticket_id], ['%d']) !== false;
}

/**
 * Closes a ticket.
 * @param int $ticket_id The ID of the ticket to close.
 */
function wss_close_ticket($ticket_id) {
    global $wpdb;
    $ticket_id = absint($ticket_id);
    if (!$ticket_id) return false;
    
    $result = $wpdb->update(
        "{$wpdb->prefix}support_tickets", 
        ['status' => 'Closed', 'last_updated' => current_time('mysql', 1)], 
        ['id' => $ticket_id]
    );

    if ($result !== false) {
        wss_add_ticket_history_note($ticket_id, 'Ticket closed via bulk action.');
        return true;
    }

    return false;
}

/**
 * Handles file uploads for tickets and replies.
 *
 * @param int $ticket_id
 * @param int $reply_id
 * @param string $file_input_name The name of the file input in the form.
 * @return array An array of error messages, if any.
 */
function wss_handle_file_uploads($ticket_id, $reply_id, $file_input_name = 'wss_attachments') {
    global $wpdb;
    $errors = [];
    $settings = get_option('wss_general_settings', []);
    $attachments_table = $wpdb->prefix . 'support_ticket_attachments';

    // Defensive check to ensure the attachments table exists.
    if($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $attachments_table)) != $attachments_table) {
        return $errors; // Silently fail if DB is not updated to prevent fatal errors.
    }

    if (!isset($settings['allow_attachments']) || !$settings['allow_attachments'] || empty($_FILES[$file_input_name]['name'][0])) {
        return $errors;
    }
    
    $files = $_FILES[$file_input_name];
    $file_count = count($files['name']);
    $max_files = isset($settings['max_files_per_upload']) ? absint($settings['max_files_per_upload']) : 3;

    if ($file_count > $max_files) {
        $errors[] = sprintf(__('You can upload a maximum of %d files at a time.', 'woo-support-system'), $max_files);
        return $errors;
    }

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $file_name = sanitize_file_name($files['name'][$i]);
        $file_size = $files['size'][$i];
        $file_tmp_name = $files['tmp_name'][$i];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_types = array_map('trim', explode(',', $settings['allowed_file_types'] ?? 'jpg,png,pdf'));
        $max_size_mb = isset($settings['max_file_size']) ? absint($settings['max_file_size']) : 5;
        $max_size_bytes = $max_size_mb * 1024 * 1024;

        if (!in_array($file_ext, $allowed_types)) {
            $errors[] = sprintf(__('File type "%s" is not allowed.', 'woo-support-system'), $file_ext);
            continue;
        }
        if ($file_size > $max_size_bytes) {
            $errors[] = sprintf(__('File "%s" exceeds the maximum size of %d MB.', 'woo-support-system'), $file_name, $max_size_mb);
            continue;
        }

        $api_key = $settings['external_storage_api_key'] ?? '';
        $upload_result_url = '';

        if (!empty($api_key)) {
            $upload_result_url = wss_upload_to_imgbb($api_key, $file_tmp_name, $file_name);
            if (is_wp_error($upload_result_url)) {
                $errors[] = $upload_result_url->get_error_message();
                continue;
            }
        } elseif (isset($settings['local_storage_fallback']) && $settings['local_storage_fallback']) {
            if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
            
            $file_to_upload = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
            $movefile = wp_handle_upload($file_to_upload, ['test_form' => false]);

            if ($movefile && !isset($movefile['error'])) {
                $upload_result_url = $movefile['url'];
            } else {
                $errors[] = 'Error uploading file locally: ' . ($movefile['error'] ?? 'Unknown error');
                continue;
            }
        } else {
            $errors[] = __('File uploads are misconfigured. Please contact an administrator.', 'woo-support-system');
            continue;
        }
        
        if ($upload_result_url) {
            $wpdb->insert(
                $attachments_table,
                [
                    'ticket_id'  => $ticket_id,
                    'reply_id'   => $reply_id,
                    'file_name'  => $file_name,
                    'file_url'   => $upload_result_url,
                    'created_at' => current_time('mysql', 1)
                ]
            );
        }
    }
    return $errors;
}

/**
 * Uploads a file to imgbb.com
 * @param string $api_key
 * @param string $file_path Temporary path of the file
 * @param string $file_name Original name of the file
 * @return string|WP_Error URL on success, WP_Error on failure.
 */
function wss_upload_to_imgbb($api_key, $file_path, $file_name) {
    $url = 'https://api.imgbb.com/1/upload';
    $image_data = base64_encode(file_get_contents($file_path));

    $response = wp_remote_post($url, [
        'body' => [
            'key'   => $api_key,
            'image' => $image_data,
            'name'  => $file_name,
        ],
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('upload_failed', 'Failed to connect to image hosting service.');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['success']) && $body['success'] && isset($body['data']['url'])) {
        return $body['data']['url'];
    } else {
        $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown API error.';
        return new WP_Error('api_error', 'Image Hosting API Error: ' . $error_message);
    }
}