<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WSS_Ticket_Handler {
    public function __construct() {
        // Customer facing actions
        add_action( 'admin_post_wss_new_ticket', [ $this, 'handle_new_ticket' ] );
        add_action( 'admin_post_nopriv_wss_new_ticket', [ $this, 'handle_new_ticket' ] );
        add_action( 'admin_post_wss_new_reply', [ $this, 'handle_new_reply' ] );
        add_action( 'admin_post_nopriv_wss_new_reply', [ $this, 'handle_new_reply' ] );
        add_action( 'admin_post_wss_rate_ticket', [ $this, 'handle_rate_ticket' ] );
        add_action( 'admin_post_nopriv_wss_rate_ticket', [ $this, 'handle_rate_ticket' ] );
        
        // Admin facing actions
        add_action( 'admin_post_wss_admin_reply', [ $this, 'handle_admin_reply' ] );
        add_action( 'admin_post_wss_update_status', [ $this, 'handle_update_status' ] );
        add_action( 'admin_action_wss_close_ticket', [ $this, 'handle_close_ticket_from_list' ] );
        add_action( 'admin_action_wss_delete_ticket', [ $this, 'handle_delete_ticket_from_list' ] );
        add_action( 'admin_post_wss_send_test_email', [ $this, 'handle_send_test_email' ] );
    }

    /**
     * Handles the creation of a new support ticket from the front-end.
     */
    public function handle_new_ticket() {
        if (!isset($_POST['wss_nonce']) || !wp_verify_nonce($_POST['wss_nonce'], 'wss_new_ticket_nonce') || !is_user_logged_in()) { 
            wp_die('Security check failed!'); 
        }
        
        global $wpdb; 
        $user_id = get_current_user_id();
        
        $priority = 'Normal';
        $allowed_priorities = ['Low', 'Normal', 'High', 'Urgent'];
        if (isset($_POST['wss_priority']) && in_array($_POST['wss_priority'], $allowed_priorities)) {
            $priority = sanitize_text_field($_POST['wss_priority']);
        }

        $wpdb->insert("{$wpdb->prefix}support_tickets", [
            'user_id'       => $user_id, 
            'order_id'      => isset($_POST['wss_order_id']) && !empty($_POST['wss_order_id']) ? absint($_POST['wss_order_id']) : null, 
            'subject'       => sanitize_text_field($_POST['wss_subject']), 
            'status'        => 'Open', 
            'priority'      => $priority,
            'created_at'    => current_time('mysql', 1), 
            'last_updated'  => current_time('mysql', 1)
        ]);
        
        $ticket_id = $wpdb->insert_id; 
        if (!$ticket_id) { 
            wp_safe_redirect( add_query_arg('error', 'db_error', wc_get_account_endpoint_url('support-tickets')) ); 
            exit; 
        }
        
        $reply_id = $wpdb->insert("{$wpdb->prefix}support_ticket_replies", [
            'ticket_id'      => $ticket_id, 
            'user_id'        => $user_id, 
            'message'        => wp_kses_post($_POST['wss_message']), 
            'created_at'     => current_time('mysql', 1), 
            'is_admin_reply' => 0
        ]);
        
        // Handle file uploads
        $upload_errors = wss_handle_file_uploads($ticket_id, $wpdb->insert_id);
        if (!empty($upload_errors)) {
            // Optionally, handle errors - for now, we just proceed
        }
        
        wss_add_ticket_history_note($ticket_id, 'Ticket created with priority: ' . $priority, $user_id); 
        WSS_Emails::send_new_ticket_notifications($ticket_id);
        
        wp_safe_redirect( add_query_arg('success', 'ticket_created', wc_get_account_endpoint_url('view-ticket') . $ticket_id) ); 
        exit;
    }
    
    /**
     * Handles a new reply from the customer.
     */
    public function handle_new_reply() {
        if (!isset($_POST['wss_nonce']) || !wp_verify_nonce($_POST['wss_nonce'], 'wss_new_reply_nonce') || !is_user_logged_in()) { wp_die('Security check failed!'); }
        global $wpdb; 
        $ticket_id = absint($_POST['ticket_id']);
        if (get_current_user_id() != $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}support_tickets WHERE id = %d", $ticket_id))) { wp_die('Permission denied.'); }
        
        $this->update_ticket_status($ticket_id, 'Awaiting Admin Reply');
        
        $wpdb->insert("{$wpdb->prefix}support_ticket_replies", [
            'ticket_id' => $ticket_id, 
            'user_id' => get_current_user_id(), 
            'message' => wp_kses_post($_POST['wss_message']), 
            'created_at' => current_time('mysql', 1), 
            'is_admin_reply' => 0
        ]);
        $reply_id = $wpdb->insert_id;
        
        // Handle file uploads
        wss_handle_file_uploads($ticket_id, $reply_id);
        
        wss_add_ticket_history_note($ticket_id, 'Customer replied.');
        WSS_Emails::send_new_reply_notifications($ticket_id, false);
        wp_safe_redirect( add_query_arg('success', 'reply_sent', wc_get_account_endpoint_url('view-ticket') . $ticket_id) ); 
        exit;
    }

    /**
     * Handles a new reply from an admin.
     */
    public function handle_admin_reply() {
        if (!isset($_POST['wss_nonce']) || !wp_verify_nonce($_POST['wss_nonce'], 'wss_admin_reply_nonce') || !current_user_can('manage_woocommerce')) { wp_die('Security check failed!'); }
        global $wpdb; 
        $ticket_id = absint($_POST['ticket_id']);
        $this->update_ticket_status($ticket_id, 'Awaiting Customer Reply');
        
        $wpdb->insert("{$wpdb->prefix}support_ticket_replies", [
            'ticket_id' => $ticket_id, 
            'user_id' => get_current_user_id(), 
            'message' => wp_kses_post($_POST['wss_message']), 
            'created_at' => current_time('mysql', 1), 
            'is_admin_reply' => 1
        ]);
        $reply_id = $wpdb->insert_id;

        // Handle file uploads
        wss_handle_file_uploads($ticket_id, $reply_id);

        wss_add_ticket_history_note($ticket_id, 'Admin replied.');
        WSS_Emails::send_new_reply_notifications($ticket_id, true);
        wp_safe_redirect( add_query_arg(['success' => 'reply_sent', 'action' => 'view_ticket', 'ticket_id' => $ticket_id], admin_url('admin.php?page=wss-tickets-dashboard')) ); 
        exit;
    }
    
    // ... (rest of the functions remain the same) ...

    public function handle_rate_ticket() {
        if (!isset($_POST['wss_nonce']) || !wp_verify_nonce($_POST['wss_nonce'], 'wss_rate_ticket_nonce') || !is_user_logged_in()) {
            wp_die('Security check failed!');
        }
        global $wpdb;
        $ticket_id = absint($_POST['ticket_id']);
        $rating = isset($_POST['rating']) ? absint($_POST['rating']) : 0;
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT user_id, rating FROM {$wpdb->prefix}support_tickets WHERE id = %d", $ticket_id));
        if (!$ticket || get_current_user_id() != $ticket->user_id || !is_null($ticket->rating)) {
            wp_die('Permission denied or ticket already rated.');
        }
        if ($rating >= 1 && $rating <= 5) {
            $wpdb->update("{$wpdb->prefix}support_tickets", ['rating' => $rating], ['id' => $ticket_id]);
            wss_add_ticket_history_note($ticket_id, "Customer rated the support with {$rating}/5 stars.");
        }
        wp_safe_redirect(add_query_arg('success', 'rating_received', wc_get_account_endpoint_url('view-ticket') . $ticket_id));
        exit;
    }

    public function handle_update_status() {
        if (!isset($_POST['wss_nonce']) || !wp_verify_nonce($_POST['wss_nonce'], 'wss_update_status_nonce') || !current_user_can('manage_woocommerce')) { 
            wp_die('Security check failed!'); 
        }
        global $wpdb;
        $ticket_id = absint($_POST['ticket_id']);
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT status, priority FROM {$wpdb->prefix}support_tickets WHERE id = %d", $ticket_id));
        $new_status = sanitize_text_field($_POST['wss_status']);
        if ($ticket->status !== $new_status) {
            $this->update_ticket_status($ticket_id, $new_status, sprintf("Status manually changed from '%s' to '%s'.", wss_get_status_name($ticket->status), wss_get_status_name($new_status)));
        }
        if (isset($ticket->priority)) {
            $new_priority = sanitize_text_field($_POST['wss_priority']);
            if ($ticket->priority !== $new_priority) {
                $wpdb->update("{$wpdb->prefix}support_tickets", ['priority' => $new_priority], ['id' => $ticket_id]);
                wss_add_ticket_history_note($ticket_id, sprintf("Priority changed from '%s' to '%s'.", $ticket->priority, $new_priority));
            }
        }
        wp_safe_redirect( add_query_arg(['success' => 'status_updated', 'action' => 'view_ticket', 'ticket_id' => $ticket_id], admin_url('admin.php?page=wss-tickets-dashboard')) ); 
        exit;
    }

    private function update_ticket_status($ticket_id, $new_status, $history_note = '') {
        global $wpdb; 
        $wpdb->update("{$wpdb->prefix}support_tickets", ['status' => $new_status, 'last_updated' => current_time('mysql', 1)], ['id' => $ticket_id]);
        if (!empty($history_note)) { 
            wss_add_ticket_history_note($ticket_id, $history_note); 
        }
    }

    public function handle_delete_ticket_from_list() {
        $ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wss_delete_ticket_' . $ticket_id) || !current_user_can('manage_woocommerce')) {
            wp_safe_redirect( add_query_arg('message', 'delete_failed', admin_url('admin.php?page=wss-tickets-dashboard')) );
            exit;
        }
        wss_delete_ticket($ticket_id);
        wp_safe_redirect( add_query_arg('message', 'ticket_deleted', admin_url('admin.php?page=wss-tickets-dashboard')) );
        exit;
    }

    public function handle_send_test_email() {
        if (!isset($_POST['wss_test_nonce']) || !wp_verify_nonce($_POST['wss_test_nonce'], 'wss_send_test_email_nonce') || !current_user_can('manage_options')) { wp_die('Security check failed!'); }
        WSS_Emails::send_test_email();
        wp_safe_redirect( add_query_arg('message', 'test_sent', admin_url('admin.php?page=wss-settings&tab=email_template')) );
        exit;
    }
    
    public function handle_close_ticket_from_list() {
        $ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wss_close_ticket_' . $ticket_id) || !current_user_can('manage_woocommerce')) { wp_safe_redirect( add_query_arg('message', 'close_failed', admin_url('admin.php?page=wss-tickets-dashboard')) ); exit; }
        $this->update_ticket_status($ticket_id, 'Closed', 'Ticket closed via action link.');
        wp_safe_redirect( add_query_arg('message', 'ticket_closed', admin_url('admin.php?page=wss-tickets-dashboard')) ); exit;
    }
}