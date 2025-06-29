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
            .wss-user-info-box {
                border: 1px solid #e0e0e0;
                background-color: #f9f9f9;
                padding: 15px 20px;
                border-radius: 4px;
                margin-bottom: 30px;
            }
            .wss-user-info-box h3 {
                margin-top: 0;
                margin-bottom: 15px;
                font-size: 1.2em;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            .wss-user-info-box p {
                margin: 0 0 8px 0;
                font-size: 1em;
                color: #555;
            }
            .wss-user-info-box strong {
                color: #333;
            }

            /* --- Chat UI --- */
            .wss-chat-container {
                background: #F8F9FA;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
                border: 1px solid #DEE2E6;
                max-height: 500px;
                overflow-y: auto;
            }
            
            .wss-chat-row {
                display: flex;
                margin-bottom: 16px;
                align-items: flex-start;
            }
            
            .wss-chat-row.admin-row {
                flex-direction: row;
            }
            
            .wss-chat-row.user-row {
                flex-direction: row-reverse;
            }
            
            .wss-chat-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                border: 2px solid #DEE2E6;
                flex-shrink: 0;
            }
            
            .admin-row .wss-chat-avatar {
                margin-right: 12px;
                border-color: #4FACF7;
            }
            
            .user-row .wss-chat-avatar {
                margin-left: 12px;
                border-color: #F7C948;
            }
            
            .wss-chat-bubble {
                max-width: 70%;
                padding: 14px 16px;
                border-radius: 16px;
                box-shadow: 0 2px 8px rgba(13, 47, 79, 0.08);
            }
            
            .admin-bubble {
                background: #4FACF7;
                color: #FFFFFF;
                border-bottom-left-radius: 6px;
            }
            
            .user-bubble {
                background: #FFFFFF;
                color: #212529;
                border: 1px solid #DEE2E6;
                border-bottom-right-radius: 6px;
            }
            
            .wss-chat-bubble .sender {
                font-weight: 600;
                font-size: 13px;
                margin-bottom: 6px;
                color: inherit;
                opacity: 0.9;
            }
            
            .wss-chat-bubble .message-content {
                font-size: 14px;
                line-height: 1.5;
                margin-bottom: 8px;
            }
            
            .wss-chat-bubble .message-content p {
                margin: 0 0 8px 0;
            }
            
            .wss-chat-bubble .message-content p:last-child {
                margin-bottom: 0;
            }
            
            .wss-attachments {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 8px;
                padding: 10px;
                margin: 8px 0;
                border: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .user-bubble .wss-attachments {
                background: #F8F9FA;
                border-color: #DEE2E6;
            }
            
            .wss-attachments strong {
                display: block;
                margin-bottom: 6px;
                font-size: 12px;
            }
            
            .wss-attachments a {
                display: inline-block;
                padding: 4px 8px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 12px;
                text-decoration: none;
                font-size: 12px;
                margin: 2px 4px 2px 0;
                color: inherit;
                transition: opacity 0.2s ease;
            }
            
            .wss-attachments a:hover {
                opacity: 0.8;
            }
            
            .user-bubble .wss-attachments a {
                background: #DEE2E6;
                color: #212529;
            }
            
            .wss-chat-bubble .timestamp {
                font-size: 11px;
                opacity: 0.7;
                text-align: right;
                margin-top: 4px;
            }
            
            /* Scrollbar styling */
            .wss-chat-container::-webkit-scrollbar {
                width: 6px;
            }
            
            .wss-chat-container::-webkit-scrollbar-track {
                background: #F8F9FA;
            }
            
            .wss-chat-container::-webkit-scrollbar-thumb {
                background: #DEE2E6;
                border-radius: 3px;
            }
            
            .wss-chat-container::-webkit-scrollbar-thumb:hover {
                background: #4FACF7;
            }
            
            /* Mobile responsive */
            @media (max-width: 768px) {
                .wss-chat-container {
                    padding: 16px;
                }
                
                .wss-chat-bubble {
                    max-width: 80%;
                    padding: 12px 14px;
                }
                
                .wss-chat-avatar {
                    width: 36px;
                    height: 36px;
                }
                
                .admin-row .wss-chat-avatar {
                    margin-right: 10px;
                }
                
                .user-row .wss-chat-avatar {
                    margin-left: 10px;
                }
            }
            
            /* Simple CSS for wss-system-bubble log messages */
            .wss-system-bubble {
                text-align: center;
                padding: 6px 12px;
                margin: 8px auto;
                background-color: #f0f0f1;
                border: 1px solid #c3c4c7;
                border-radius: 12px;
                font-size: 12px;
                color: #646970;
                max-width: 60%;
                display: block;
            }
            
            /* Timestamp styling */
            .wss-system-bubble .timestamp {
                color: #8c8f94;
                font-weight: normal;
            }
            
            /* Responsive for mobile */
            @media (max-width: 768px) {
                .wss-system-bubble {
                    max-width: 80%;
                    padding: 5px 10px;
                    font-size: 11px;
                }
            }
            
            

            /* --- Star Rating System --- */
            .wss-star-rating .star {
                display: inline-block;
                font-size: 2.5em;
                color: #ccc;
                cursor: pointer;
                transition: color 0.2s;
            }
            .wss-star-rating .star:hover,
            .wss-star-rating .star.hover,
            .wss-star-rating .star.rated {
                color: #ffb900;
            }
            .wss-final-rating .star {
                font-size: 1.5em;
                color: #ccc;
            }
            .wss-final-rating .star.rated {
                color: #ffb900;
            }

            /* --- File Upload Enhancements --- */
            .wss-attachment-error {
                color: red;
                font-size: 0.9em;
                margin-top: 5px;
            }

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

            /* === Ticket Form === */
            .wss-form {
                padding: 24px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
                max-width: 100%;
                box-sizing: border-box;
            }

            .wss-form input[type="text"],
            .wss-form select,
            .wss-form textarea,
            .wss-form input[type="file"] {
                width: 100%;
                padding: 10px 12px;
                margin-top: 6px;
                border: 1px solid #ccc;
                border-radius: 6px;
                font-size: 14px;
                box-sizing: border-box;
            }

            .wss-form label {
                font-weight: 600;
                margin-bottom: 4px;
                display: block;
                color: #333;
            }

            .wss-form textarea {
                min-height: 120px;
                resize: vertical;
            }

            .wss-form button[type="submit"] {
                background-color: #0073aa;
                color: white;
                padding: 10px 20px;
                font-weight: 600;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                transition: background 0.3s;
            }

            .wss-form button[type="submit"]:hover {
                background-color: #005f8d;
            }

            .woocommerce-form-row {
                margin-bottom: 20px;
            }

            .form-row-first,
            .form-row-last {
                width: 100%;
                display: block;
            }

            @media (min-width: 768px) {
                .form-row-first,
                .form-row-last {
                    display: inline-block;
                    width: 48%;
                    box-sizing: border-box;
                    vertical-align: top;
                }

                .form-row-first { margin-right: 4%; }
                .form-row-last { margin-right: 0; }
            }

            .wss-attachment-rules {
                font-size: 12px;
                color: #666;
                margin-top: 4px;
            }

            .wss-attachment-error {
                color: red;
                font-size: 13px;
                margin-top: 8px;
            }

            /* === Ticket Table Styling === */
            .ticket-history table.woocommerce-orders-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 14px;
            }

            .ticket-history table.woocommerce-orders-table th,
            .ticket-history table.woocommerce-orders-table td {
                padding: 12px 14px;
                text-align: left;
                border: 1px solid #e0e0e0;
                background-color: #fff;
            }

            .ticket-history table.woocommerce-orders-table th {
                background-color: #f5f5f5;
                font-weight: 600;
            }

            .ticket-history table.woocommerce-orders-table tr:nth-child(even) td {
                background-color: #fafafa;
            }

            .ticket-history a.woocommerce-button.view {
                display: inline-block;
                padding: 6px 12px;
                background-color: #0073aa;
                color: #fff;
                font-weight: 500;
                font-size: 13px;
                border-radius: 4px;
                text-decoration: none;
                transition: background-color 0.3s;
            }

            .ticket-history a.woocommerce-button.view:hover {
                background-color: #005f8d;
            }

            .ticket-history h2 {
                font-size: 20px;
                margin-bottom: 12px;
            }

            @media (max-width: 767px) {
                .ticket-history table.woocommerce-orders-table thead {
                    display: none;
                }

                .ticket-history table.woocommerce-orders-table tr {
                    display: block;
                    margin-bottom: 15px;
                    border: 1px solid #e0e0e0;
                    border-radius: 6px;
                    padding: 10px;
                }

                .ticket-history table.woocommerce-orders-table td {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                    border: none;
                    border-bottom: 1px solid #eee;
                }

                .ticket-history table.woocommerce-orders-table td:last-child {
                    border-bottom: none;
                }

                .ticket-history table.woocommerce-orders-table td:before {
                    content: attr(data-title);
                    font-weight: 600;
                    color: #555;
                }
            }

            /* --- Styled Pagination --- */
            .woocommerce-pagination {
                margin-top: 30px;
                text-align: center;
            }

            .woocommerce-pagination ul.page-numbers {
                display: inline-flex;
                list-style: none;
                margin: 0;
                padding: 0;
                border: 1px solid #ddd;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }

            .woocommerce-pagination .page-numbers li { margin: 0; }

            .woocommerce-pagination .page-numbers a,
            .woocommerce-pagination .page-numbers span {
                display: inline-block;
                padding: 8px 15px;
                text-decoration: none;
                color: #0073aa;
                border-right: 1px solid #ddd;
                background: #fff;
                transition: background-color 0.2s, color 0.2s;
            }

            .woocommerce-pagination .page-numbers li:last-child a,
            .woocommerce-pagination .page-numbers li:last-child span {
                border-right: 0;
            }

            .woocommerce-pagination .page-numbers a:hover {
                background-color: #f0f5fa;
            }

            .woocommerce-pagination .page-numbers span.current {
                background-color: #0073aa;
                color: #fff;
                font-weight: bold;
                cursor: default;
            }

            .woocommerce-pagination .page-numbers .prev,
            .woocommerce-pagination .page-numbers .next {
                font-weight: bold;
                font-size: 1.1em;
            }
        </style>
        <?php
    }
}
