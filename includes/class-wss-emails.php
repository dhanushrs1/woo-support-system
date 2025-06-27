<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WSS_Emails {
    
    /**
     * The main email sending function. It constructs and sends an email
     * using the plugin's settings for professional formatting and deliverability.
     *
     * @param string|array $to      The recipient's email address(es).
     * @param string       $subject The subject of the email.
     * @param string       $message The HTML content of the email.
     */
    private static function send_mail($to, $subject, $message) {
        $email_settings = get_option('wss_email_settings', []);

        // Get the "From" name from settings, or default to the site's title.
        $from_name = !empty($email_settings['from_name']) ? $email_settings['from_name'] : get_bloginfo('name');
        
        // Get the "From" email from settings, or default to the site's admin email.
        $from_email = !empty($email_settings['from_email']) ? $email_settings['from_email'] : get_option('admin_email');
        
        // Construct the email headers for maximum deliverability.
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_name . ' <' . $from_email . '>',
        ];

        // The wp_mail function uses the 'From' header to set the sender.
        // This is the most direct and reliable method.
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Sends notifications when a new ticket is created.
     * It notifies both the customer and the admin(s) based on settings.
     *
     * @param int $ticket_id The ID of the newly created ticket.
     */
    public static function send_new_ticket_notifications($ticket_id) {
        $email_settings = get_option('wss_email_settings', []);
        global $wpdb;
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}support_tickets WHERE id = %d", $ticket_id)); 
        if (!$ticket) return;
        
        $customer = get_user_by('id', $ticket->user_id);
        
        // --- Notification to Customer ---
        // Only send if customer notifications are enabled in settings.
        if ( !empty($email_settings['customer_notification']) ) {
            $subject = sprintf(__('[%s] Support Ticket Created: #%d', 'woo-support-system'), get_bloginfo('name'), $ticket_id);
            $main_content = sprintf(
                __("Hi %s,<br><br>Your support ticket (#%d) has been successfully created. Our team will get back to you shortly.<br><br>You can view the ticket here: <a href='%s'>View Ticket #%s</a>", 'woo-support-system'), 
                $customer->display_name, 
                $ticket_id, 
                esc_url( wc_get_account_endpoint_url('view-ticket') . $ticket_id ), 
                $ticket_id
            );
            $message = WSS_Email_Templates::get_customer_email_html($subject, $main_content);
            self::send_mail($customer->user_email, $subject, $message);
        }

        // --- Notification to Admin ---
        // Only send if admin notifications are enabled in settings.
        if ( !empty($email_settings['admin_notification']) ) {
            // Use the custom recipient list from settings, or fall back to the site admin.
            $admin_email = !empty($email_settings['admin_notification_recipients']) ? $email_settings['admin_notification_recipients'] : get_option('admin_email');
            
            $admin_subject = sprintf(__('[%s] New Support Ticket: #%d from %s', 'woo-support-system'), get_bloginfo('name'), $ticket_id, $customer->display_name);
            $admin_content = sprintf(
                __("A new support ticket (#%d) has been created.<br><br><strong>Customer:</strong> %s<br><strong>Subject:</strong> %s<br><br><a href='%s' style='padding:10px 15px; background-color:#0073aa; color:#fff; text-decoration:none; border-radius:3px;'>View and Reply to Ticket</a>", 'woo-support-system'), 
                $ticket_id, 
                $customer->display_name, 
                $ticket->subject, 
                admin_url('admin.php?page=wss-tickets-dashboard&action=view_ticket&ticket_id=' . $ticket_id)
            );
            $admin_message = WSS_Email_Templates::get_admin_email_html($admin_subject, $admin_content);
            self::send_mail($admin_email, $admin_subject, $admin_message);
        }
    }

    /**
     * Sends notifications when a new reply is posted.
     *
     * @param int  $ticket_id       The ticket ID.
     * @param bool $is_admin_reply  True if the reply is from an admin, false if from a customer.
     */
    public static function send_new_reply_notifications($ticket_id, $is_admin_reply) {
        $email_settings = get_option('wss_email_settings', []);
        global $wpdb;
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}support_tickets WHERE id = %d", $ticket_id));
        if (!$ticket) return;

        if ($is_admin_reply) {
            // --- Notify Customer of Admin's Reply ---
            if (!empty($email_settings['customer_notification'])) {
                $customer = get_user_by('id', $ticket->user_id);
                $subject = sprintf(__('[%s] A reply was posted on your ticket #%d', 'woo-support-system'), get_bloginfo('name'), $ticket_id);
                $main_content = sprintf(__("Hi %s,<br><br>A new reply has been posted to your support ticket. You can view it here: <a href='%s'>View Ticket #%s</a>", 'woo-support-system'), $customer->display_name, esc_url(wc_get_account_endpoint_url('view-ticket') . $ticket_id), $ticket_id);
                $message = WSS_Email_Templates::get_customer_email_html($subject, $main_content);
                self::send_mail($customer->user_email, $subject, $message);
            }
        } else {
            // --- Notify Admin of Customer's Reply ---
            if (!empty($email_settings['admin_notification'])) {
                $customer = get_user_by('id', $ticket->user_id);
                $admin_email = !empty($email_settings['admin_notification_recipients']) ? $email_settings['admin_notification_recipients'] : get_option('admin_email');
                $subject = sprintf(__('[%s] A customer has replied to ticket #%d', 'woo-support-system'), get_bloginfo('name'), $ticket_id);
                $main_content = sprintf(__("A new reply has been posted by %s on ticket #%d.<br><br><a href='%s' style='padding:10px 15px; background-color:#0073aa; color:#fff; text-decoration:none; border-radius:3px;'>View and Reply to Ticket</a>", 'woo-support-system'), $customer->display_name, $ticket_id, admin_url('admin.php?page=wss-tickets-dashboard&action=view_ticket&ticket_id=' . $ticket_id));
                $message = WSS_Email_Templates::get_admin_email_html($subject, $main_content);
                self::send_mail($admin_email, $subject, $message);
            }
        }
    }

    /**
     * Sends a test email to the site admin for previewing the template.
     */
    public static function send_test_email() {
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] Test Email', 'woo-support-system'), get_bloginfo('name'));
        $content = __("This is a test email to preview your custom template settings.<br><br>Your logo, header color, and footer text should appear correctly. If you received this email, your email settings are working!", 'woo-support-system');
        $message = WSS_Email_Templates::get_customer_email_html($subject, $content);
        self::send_mail($admin_email, $subject, $message);
    }
}