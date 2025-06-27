<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WSS_My_Account {

    public function __construct() {
        // Add endpoint upon WordPress initialization
        add_action( 'init', [ __CLASS__, 'add_endpoints' ] );
        
        // Add menu item to My Account
        add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_item' ] );
        
        // Handle the content for our new endpoints
        add_action( 'woocommerce_account_support-tickets_endpoint', [ $this, 'endpoint_content' ] );
        add_action( 'woocommerce_account_view-ticket_endpoint', [ $this, 'view_ticket_content' ] );
    }

    /**
     * Add custom endpoints.
     * This is now a static function and can be called from anywhere.
     */
    public static function add_endpoints() {
        add_rewrite_endpoint( 'support-tickets', EP_PAGES );
        add_rewrite_endpoint( 'view-ticket', EP_PAGES );
    }

    public function add_menu_item( $items ) {
        // Re-order items to place 'Support Tickets' before 'Logout'
        $logout = $items['customer-logout'];
        unset( $items['customer-logout'] );
        $items['support-tickets'] = __( 'Support Tickets', 'woo-support-system' );
        $items['customer-logout'] = $logout;
        return $items;
    }

    /**
     * Content for the main 'support-tickets' page.
     */
    public function endpoint_content() {
        wss_get_template( 'my-account/support-tickets.php' );
    }

    /**
     * Content for the 'view-ticket' page.
     */
    public function view_ticket_content() {
        global $wp;
        // Get the ticket ID from the URL
        $ticket_id = isset( $wp->query_vars['view-ticket'] ) ? absint( $wp->query_vars['view-ticket'] ) : 0;
        wss_get_template( 'my-account/view-ticket.php', [ 'ticket_id' => $ticket_id ] );
    }
}