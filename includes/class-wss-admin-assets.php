<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WSS_Admin_Assets {

    public function __construct() {
        add_action( 'admin_head', [ $this, 'print_admin_styles' ] );
    }

    public function print_admin_styles() {
        // Only load on our plugin's pages
        if ( !isset($_GET['page']) || strpos($_GET['page'], 'wss-') === false ) {
            return;
        }
        ?>
        <style type="text/css">
            /* General Layout Fixes */
            .wss-admin-wrap {
                margin-left: -20px; /* Counteract default WP margin */
            }
            #poststuff {
                padding-top: 10px;
            }

            /* Stats Boxes */
            .wss-stats-container {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }
            .wss-stat-box {
                background: #fff;
                border: 1px solid #e2e4e7;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                flex: 1;
                min-width: 150px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .wss-stat-box:hover {
                transform: translateY(-3px);
                box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            }
            .wss-stat-number {
                font-size: 2.5em;
                font-weight: 600;
                line-height: 1.1;
                margin-bottom: 5px;
            }
            .wss-stat-label {
                font-size: 13px;
                color: #50575e;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .wss-stat-open .wss-stat-number { color: #d63638; }
            .wss-stat-pending .wss-stat-number { color: #f5a623; }
            .wss-stat-closed .wss-stat-number { color: #46b450; }
            .wss-stat-today .wss-stat-number { color: #0073aa; }

            /* Filter Bar */
            .wss-enhanced-filters {
                background: #fff;
                border: 1px solid #e2e4e7;
                padding: 15px;
                margin-top: 20px;
                border-radius: 4px;
            }
            .wss-filter-form {
                display: flex;
                gap: 15px;
                align-items: center;
                flex-wrap: wrap;
            }
            .wss-filter-form select,
            .wss-filter-form input[type="date"] {
                min-width: 160px;
            }
        </style>
        <?php
    }
}
