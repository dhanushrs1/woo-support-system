<?php
/**
 * Plugin Name:       WooCommerce Support Ticket System
 * Plugin URI:        https://wponestop.com/
 * Description:       Adds a support ticket system to the WooCommerce My Account page.
 * Version:           3.0.5
 * Author:            Dhanush R S
 * Author URI:        https://wponestop.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-support-system
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WSS_VERSION', '3.0.5' );
define( 'WSS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WSS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSS_PLUGIN_FILE', __FILE__ );

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

function wss_run_plugin() {
    if ( ! class_exists('WooCommerce') ) {
        add_action('admin_notices', 'wss_woocommerce_missing_notice');
        return;
    }
    require_once WSS_PLUGIN_PATH . 'includes/class-woo-support-system.php';
    WooSupportSystem::instance();
    
    // Add DB check after plugin loads
    add_action('admin_init', 'wss_check_db_schema');
}
add_action('plugins_loaded', 'wss_run_plugin');

/**
 * Adds a "Settings" link to the plugins page.
 */
function wss_add_settings_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=wss-settings' ) . '">' . __( 'Settings', 'woo-support-system' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wss_add_settings_link' );


function wss_plugin_activate() {
    if ( ! class_exists('WooCommerce') ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __('This plugin requires WooCommerce to be installed and activated. Please install WooCommerce and try again.', 'woo-support-system'), 'Plugin Activation Error',  ['back_link' => true] );
    }
    require_once WSS_PLUGIN_PATH . 'includes/class-wss-db.php';
    require_once WSS_PLUGIN_PATH . 'includes/class-wss-my-account.php';
    WSS_DB::create_tables();
    WSS_My_Account::add_endpoints();
    flush_rewrite_rules();
}
register_activation_hook( WSS_PLUGIN_FILE, 'wss_plugin_activate' );

function wss_plugin_deactivate() {
    flush_rewrite_rules();
    wp_clear_scheduled_hook('wss_daily_ticket_automation_cron');
}
register_deactivation_hook( WSS_PLUGIN_FILE, 'wss_plugin_deactivate' );

function wss_woocommerce_missing_notice() {
    echo '<div class="error"><p>' . __( 'WooCommerce Support Ticket System is inactive. It requires WooCommerce to be installed and activated.', 'woo-support-system') . '</p></div>';
}

/**
 * Check if the DB schema is up to date and show a notice if not.
 */
function wss_check_db_schema() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'support_tickets';
    $attachments_table_name = $wpdb->prefix . 'support_ticket_attachments';
    
    $needs_update = false;
    
    // Check if the 'priority' column exists.
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
        $priority_column_exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table_name} LIKE %s", 'priority'));
        if (empty($priority_column_exists)) {
            $needs_update = true;
        }
    }

    // Check if the attachments table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $attachments_table_name)) !== $attachments_table_name) {
        $needs_update = true;
    }
    
    if ($needs_update) {
        set_transient('wss_db_update_notice', true, HOUR_IN_SECONDS);
    }
    
    if (get_transient('wss_db_update_notice')) {
        add_action('admin_notices', 'wss_db_update_admin_notice');
    }

    if (isset($_GET['action']) && $_GET['action'] == 'wss_update_db' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'wss_db_update_nonce')) {
        require_once WSS_PLUGIN_PATH . 'includes/class-wss-db.php';
        WSS_DB::create_tables();
        delete_transient('wss_db_update_notice');
        wp_safe_redirect(remove_query_arg(['action', '_wpnonce']));
        exit;
    }
}

/**
 * Display the admin notice for DB update.
 */
function wss_db_update_admin_notice() {
    $update_url = wp_nonce_url(admin_url('?action=wss_update_db'), 'wss_db_update_nonce');
    ?>
    <div class="notice notice-error is-dismissible">
        <p>
            <strong><?php _e('WooCommerce Support System Data Update Required', 'woo-support-system'); ?></strong><br>
            <?php _e('The plugin needs to update your database to support the latest features like ticket priority and file attachments. This is a required one-time action.', 'woo-support-system'); ?>
        </p>
        <p>
            <a href="<?php echo esc_url($update_url); ?>" class="button button-primary"><?php _e('Update Database Now', 'woo-support-system'); ?></a>
        </p>
    </div>
    <?php
}