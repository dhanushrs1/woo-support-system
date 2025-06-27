<?php
/**
 * Fired when the plugin is uninstalled (deleted).
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

$data_settings = get_option('wss_data_settings');

// Only delete data if the user has explicitly checked the box
if ( ! empty($data_settings['delete_on_uninstall']) ) {
    global $wpdb;

    // Define table names
    $tickets_table = $wpdb->prefix . 'support_tickets';
    $replies_table = $wpdb->prefix . 'support_ticket_replies';

    // Drop custom tables
    $wpdb->query( "DROP TABLE IF EXISTS {$replies_table}" );
    $wpdb->query( "DROP TABLE IF EXISTS {$tickets_table}" );

    // Delete options from the options table
    delete_option('wss_email_settings');
    delete_option('wss_general_settings');
    delete_option('wss_data_settings');
}