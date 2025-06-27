<?php
/**
 * Handles the creation of custom database tables and default options on plugin activation.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WSS_DB {
    /**
     * Creates all necessary database tables. This function is run once on plugin activation.
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Table for main support tickets
        $table_tickets = $wpdb->prefix . 'support_tickets';
        $sql_tickets = "CREATE TABLE $table_tickets (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          user_id bigint(20) NOT NULL,
          order_id bigint(20) NULL DEFAULT NULL,
          subject varchar(255) NOT NULL,
          status varchar(30) NOT NULL,
          priority varchar(20) NOT NULL DEFAULT 'Normal',
          rating tinyint(1) NULL DEFAULT NULL,
          created_at datetime NOT NULL,
          last_updated datetime NOT NULL,
          PRIMARY KEY  (id),
          KEY user_id (user_id),
          KEY status (status),
          KEY priority (priority)
        ) $charset_collate;";
        dbDelta( $sql_tickets );

        // Table for ticket replies/messages
        $table_replies = $wpdb->prefix . 'support_ticket_replies';
        $sql_replies = "CREATE TABLE $table_replies (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          ticket_id bigint(20) NOT NULL,
          user_id bigint(20) NOT NULL,
          message longtext NOT NULL,
          created_at datetime NOT NULL,
          is_admin_reply tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY  (id),
          KEY ticket_id (ticket_id)
        ) $charset_collate;";
        dbDelta( $sql_replies );
        
        // NEW: Table for ticket attachments
        $table_attachments = $wpdb->prefix . 'support_ticket_attachments';
        $sql_attachments = "CREATE TABLE $table_attachments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) NOT NULL,
            reply_id bigint(20) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_url varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY ticket_id (ticket_id),
            KEY reply_id (reply_id)
        ) $charset_collate;";
        dbDelta($sql_attachments);

        // Table for the ticket activity log
        $table_history = $wpdb->prefix . 'support_ticket_history';
        $sql_history = "CREATE TABLE $table_history (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          ticket_id bigint(20) NOT NULL,
          user_id bigint(20) NOT NULL,
          note text NOT NULL,
          created_at datetime NOT NULL,
          PRIMARY KEY  (id),
          KEY ticket_id (ticket_id)
        ) $charset_collate;";
        dbDelta( $sql_history );

        // Set up default options for all settings tabs if they don't already exist.
        if ( false === get_option('wss_general_settings') ) {
            add_option('wss_general_settings', [
                'tickets_per_page' => 10,
                'allow_attachments' => 0,
                'max_files_per_upload' => 3,
                'allowed_file_types' => 'jpg,jpeg,png,pdf',
                'max_file_size' => 5,
                'external_storage_api_key' => '',
                'local_storage_fallback' => 0,
            ]);
        }
        if ( false === get_option('wss_email_settings') ) {
            add_option('wss_email_settings', [
                'admin_notification'    => 1,
                'customer_notification' => 1,
                'from_name'             => get_bloginfo('name'),
                'from_email'            => get_option('admin_email')
            ]);
        }
        if ( false === get_option('wss_email_template_settings') ) {
            add_option('wss_email_template_settings', [
                'logo'          => '',
                'header_color'  => '#005a9c',
                'footer_text'   => sprintf(__('Copyright &copy; %s %s', 'woo-support-system'), date('Y'), get_bloginfo('name'))
            ]);
        }
        if ( false === get_option('wss_automation_settings') ) {
            add_option('wss_automation_settings', [
                'auto_close_tickets' => 0,
            ]);
        }
        if ( false === get_option('wss_data_settings') ) {
            add_option('wss_data_settings', []);
        }
    }
}