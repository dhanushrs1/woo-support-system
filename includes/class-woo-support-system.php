<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class WooSupportSystem {
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;
    }

    private function __construct() {
        $this->includes();
        new WSS_My_Account();
        new WSS_Admin_Menu();
        new WSS_Ticket_Handler();
        new WSS_Styles();
        // The Analytics class has been removed for stability.
    }
    
    private function includes() {
        require_once WSS_PLUGIN_PATH . 'includes/class-wss-db.php';
        require_once WSS_PLUGIN_PATH . 'includes/wss-functions.php';
        require_once WSS_PLUGIN_PATH . 'includes/class-wss-my-account.php';
        require_once WSS_PLUGIN_PATH . 'includes/class-wss-admin-menu.php';
        require_once WSS_PLUGIN_PATH . 'includes/class-wss-ticket-handler.php';
        require_once WSS_PLUGIN_PATH . 'includes/class-wss-email-templates.php';
        require_once WSS_PLUGIN_PATH . 'includes/class-wss-emails.php';
        require_once WSS_PLUGIN_PATH . 'includes/class-wss-styles.php';
        // The Analytics file is no longer included.
        if ( ! class_exists( 'WP_List_Table' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
        }
        require_once WSS_PLUGIN_PATH . 'includes/wss-list-table.php';
    }
}
