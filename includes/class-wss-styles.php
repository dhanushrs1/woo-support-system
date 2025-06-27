<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WSS_Styles {
    public function __construct() {
        add_action('wp_head', [ $this, 'add_front_end_styles' ]);
        add_action('admin_head', [ $this, 'add_admin_styles' ]);
    }

    public function add_front_end_styles() { $this->print_styles(); }
    public function add_admin_styles() { $this->print_styles(); }
    private function print_styles() {
        ?>
        <style type="text/css">
            /* --- General Statuses & Admin Bubble --- */
            .wss-status-open, .wss-status-awaiting-admin-reply { color: #f5a623; font-weight: bold; }
            .wss-status-processing { color: #8e44ad; font-weight: bold; }
            .wss-status-awaiting-customer-reply { color: #4a90e2; font-weight: bold; }
            .wss-status-closed { color: #7ed321; font-weight: bold; }
            .wss-status-cancelled { color: #d63638; font-weight: bold; }
            #toplevel_page_wss-tickets-dashboard .wp-menu-name .update-plugins {
                background-color: #d63638 !important; color: #fff !important;
                border-radius: 10px; padding: 1px 6px; font-size: 9px; vertical-align: top; margin-left: 4px;
            }

            /* --- Ticket Meta on User View --- */
            .wss-ticket-meta { margin-bottom: 20px; }
            .wss-ticket-meta p { margin: 5px 0; }
            
            /* --- User Info Box on Front-End --- */
            .wss-user-info-box { border: 1px solid #e0e0e0; background-color: #f9f9f9; padding: 15px 20px; border-radius: 4px; margin-bottom: 30px; }
            .wss-user-info-box h3 { margin-top: 0; margin-bottom: 15px; font-size: 1.2em; border-bottom: 1px solid #eee; padding-bottom: 10px; }
            .wss-user-info-box p { margin: 0 0 8px 0; font-size: 1em; color: #555; }
            .wss-user-info-box strong { color: #333; }

            /* --- Chat UI --- */
            .wss-chat-container { background-color: #f5f7fa; padding: 20px; border-radius: 8px; border: 1px solid #dcdcdc; overflow-y: auto; max-height: 600px; display: flex; flex-direction: column; gap: 1px; }
            .wss-chat-row { display: flex; align-items: flex-end; margin-bottom: 10px; gap: 10px; }
            .wss-chat-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
            .wss-chat-bubble { max-width: 75%; padding: 12px 18px; border-radius: 18px; position: relative; word-wrap: break-word; box-shadow: 0 2px 4px rgba(0,0,0,0.07); }
            .user-row { justify-content: flex-start; }
            .admin-row { justify-content: flex-end; }
            .admin-row .wss-chat-avatar { order: 2; }
            .admin-row .wss-chat-bubble { order: 1; }
            .wss-chat-bubble .sender { font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
            .wss-chat-bubble .message-content p { margin: 0; padding: 0; font-size: 1em; line-height: 1.5; }
            .wss-chat-bubble .timestamp { font-size: 0.75em; color: #999; margin-top: 8px; text-align: right; }
            .user-bubble { background-color: #ffffff; align-self: flex-start; border-top-left-radius: 4px; border: 1px solid #e5e5e5; }
            .user-bubble .sender { color: #005a9c; }
            .admin-bubble { background-color: #e1f5fe; align-self: flex-end; border-top-right-radius: 4px; }
            .admin-bubble .sender { color: #01579b; }
            .admin-bubble .timestamp { color: #5492b4; }
            .wss-system-bubble { align-self: center; background-color: #e9ebee; color: #65676b; padding: 6px 12px; border-radius: 12px; font-size: 0.85em; text-align: center; margin-bottom: 10px; }
            .wss-attachments { margin-top: 10px; font-size: 0.9em; }
            
            /* --- Star Rating System --- */
            .wss-star-rating .star { display: inline-block; font-size: 2.5em; color: #ccc; cursor: pointer; transition: color 0.2s; }
            .wss-star-rating .star:hover, .wss-star-rating .star.hover, .wss-star-rating .star.rated { color: #ffb900; }
            .wss-final-rating .star { font-size: 1.5em; color: #ccc; }
            .wss-final-rating .star.rated { color: #ffb900; }

            /* --- File Upload Enhancements --- */
            .wss-attachment-error { color: red; font-size: 0.9em; margin-top: 5px; }
            .wss-spinner {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid rgba(0,0,0,.1);
                border-radius: 50%;
                border-top-color: #0073aa;
                animation: wss-spin 1s ease-in-out infinite;
                margin-left: 10px;
                vertical-align: middle;
            }
            @keyframes wss-spin {
                to { transform: rotate(360deg); }
            }

            /* --- Styled Pagination --- */
            .woocommerce-pagination { margin-top: 30px; text-align: center; }
            .woocommerce-pagination ul.page-numbers { display: inline-flex; list-style: none; margin: 0; padding: 0; border: 1px solid #ddd; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
            .woocommerce-pagination .page-numbers li { margin: 0; }
            .woocommerce-pagination .page-numbers a, .woocommerce-pagination .page-numbers span { display: inline-block; padding: 8px 15px; text-decoration: none; color: #0073aa; border-right: 1px solid #ddd; background: #fff; transition: background-color 0.2s, color 0.2s; }
            .woocommerce-pagination .page-numbers li:last-child a, .woocommerce-pagination .page-numbers li:last-child span { border-right: 0; }
            .woocommerce-pagination .page-numbers a:hover { background-color: #f0f5fa; }
            .woocommerce-pagination .page-numbers span.current { background-color: #0073aa; color: #fff; font-weight: bold; cursor: default; }
            .woocommerce-pagination .page-numbers .prev, .woocommerce-pagination .page-numbers .next { font-weight: bold; font-size: 1.1em; }

        </style>
        <?php
    }
}