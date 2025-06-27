<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WSS_Admin_Menu {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_notices', [ $this, 'show_admin_notices' ] );
        add_action( 'admin_head', [ $this, 'print_admin_assets' ] );
    }

    public function add_admin_menu() {
        $open_ticket_count = wss_get_open_ticket_count();
        $bubble = $open_ticket_count > 0 ? sprintf( ' <span class="update-plugins count-%d"><span class="plugin-count">%d</span></span>', $open_ticket_count, $open_ticket_count ) : '';
        
        add_menu_page( 
            __('Support Tickets', 'woo-support-system'), 
            __('Support Tickets', 'woo-support-system') . $bubble, 
            'manage_woocommerce', 
            'wss-tickets-dashboard', 
            [ $this, 'render_dashboard_page' ], 
            'dashicons-format-chat', 
            56 
        );
        
        add_submenu_page( 'wss-tickets-dashboard', __('All Tickets', 'woo-support-system'), __('All Tickets', 'woo-support-system'), 'manage_woocommerce', 'wss-tickets-dashboard' );
        add_submenu_page( 'wss-tickets-dashboard', __('Settings', 'woo-support-system'), __('Settings', 'woo-support-system'), 'manage_options', 'wss-settings', [ $this, 'render_settings_page' ] );
    }

    public function show_admin_notices() {
        if ( !isset($_GET['page']) || strpos($_GET['page'], 'wss-') === false ) return;
        
        settings_errors('wss_admin_notices');
        
        if ( $_GET['page'] === 'wss-settings' && isset($_GET['settings-updated']) && $_GET['settings-updated'] ) { 
            echo '<div id="message" class="updated notice is-dismissible"><p><strong>' . __('Settings saved.', 'woo-support-system') . '</strong></p></div>'; 
        }
        
        if ( isset($_GET['message']) ) {
            $messages = [
                'ticket_closed' => ['class' => 'updated', 'text' => __('Ticket successfully closed.', 'woo-support-system')],
                'ticket_deleted' => ['class' => 'updated', 'text' => __('Ticket successfully deleted.', 'woo-support-system')],
            ];
            $message_key = sanitize_key($_GET['message']);
            if (array_key_exists($message_key, $messages)) {
                $notice = $messages[$message_key];
                echo '<div class="notice ' . esc_attr($notice['class']) . ' is-dismissible"><p>' . esc_html($notice['text']) . '</p></div>';
            }
        }
    }

    public function render_dashboard_page() {
        if ( isset($_GET['action']) && $_GET['action'] === 'view_ticket' && isset($_GET['ticket_id']) ) {
            wss_get_template('admin/view-ticket.php', ['ticket_id' => absint($_GET['ticket_id'])]);
        } else {
            $list_table = new WSS_Ticket_List_Table();
            $list_table->prepare_items();
            ?>
            <div class="wrap wss-admin-wrap">
                <h1 class="wp-heading-inline"><?php _e('Support Dashboard', 'woo-support-system'); ?></h1>
                <a href="?page=wss-settings" class="page-title-action"><?php _e('Settings', 'woo-support-system'); ?></a>
                <hr class="wp-header-end">
                
                <?php $this->render_dashboard_stats(); ?>

                <div id="poststuff">
                    <?php $this->render_enhanced_filters(); ?>
                    <form method="post">
                        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                        <?php
                            $list_table->search_box('Search Tickets', 'search_id');
                            $list_table->display();
                        ?>
                    </form>
                </div>
            </div>
            <?php
        }
    }

    private function render_dashboard_stats() {
        $stats = $this->get_ticket_stats();
        ?>
        <div class="wss-stats-container">
            <div class="wss-stat-box wss-stat-open">
                <div class="wss-stat-number"><?php echo esc_html($stats['open']); ?></div>
                <div class="wss-stat-label"><?php _e('Open Tickets', 'woo-support-system'); ?></div>
            </div>
            <div class="wss-stat-box wss-stat-pending">
                <div class="wss-stat-number"><?php echo esc_html($stats['pending']); ?></div>
                <div class="wss-stat-label"><?php _e('Pending', 'woo-support-system'); ?></div>
            </div>
            <div class="wss-stat-box wss-stat-closed">
                <div class="wss-stat-number"><?php echo esc_html($stats['closed']); ?></div>
                <div class="wss-stat-label"><?php _e('Closed', 'woo-support-system'); ?></div>
            </div>
            <div class="wss-stat-box wss-stat-today">
                <div class="wss-stat-number"><?php echo esc_html($stats['today']); ?></div>
                <div class="wss-stat-label"><?php _e('Today', 'woo-support-system'); ?></div>
            </div>
        </div>
        <?php
    }

    private function render_enhanced_filters() {
        $current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $current_priority = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : '';
        $current_date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $current_date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        ?>
        <div class="wss-enhanced-filters" style="margin-bottom: 15px;">
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <div class="wss-filter-form">
                    <select name="status" id="wss-status-filter">
                        <option value=""><?php _e('All Statuses', 'woo-support-system'); ?></option>
                        <?php
                        $all_statuses = ['Open', 'Processing', 'Awaiting Admin Reply', 'Awaiting Customer Reply', 'Closed', 'Cancelled'];
                        foreach ($all_statuses as $status) {
                            $slug = strtolower(str_replace(' ', '-', $status));
                            echo '<option value="' . esc_attr($slug) . '" ' . selected($current_status, $slug, false) . '>' . esc_html(wss_get_status_name($status)) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <select name="priority" id="wss-priority-filter">
                        <option value=""><?php _e('All Priorities', 'woo-support-system'); ?></option>
                        <?php
                        $all_priorities = ['Low', 'Normal', 'High', 'Urgent'];
                        foreach ($all_priorities as $priority) {
                            $slug = strtolower($priority);
                            echo '<option value="' . esc_attr($slug) . '" ' . selected($current_priority, $slug, false) . '>' . esc_html($priority) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <input type="date" name="date_from" value="<?php echo esc_attr($current_date_from); ?>" placeholder="yyyy-mm-dd">
                    <input type="date" name="date_to" value="<?php echo esc_attr($current_date_to); ?>" placeholder="yyyy-mm-dd">
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'woo-support-system'); ?>">
                    <a href="?page=wss-tickets-dashboard" class="button"><?php _e('Reset', 'woo-support-system'); ?></a>
                </div>
            </form>
        </div>
        <?php
    }
    
    private function get_ticket_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'support_tickets';
        return [
            'open' => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'Open' OR status = 'Awaiting Admin Reply'"),
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'Processing' OR status = 'Awaiting Customer Reply'"),
            'closed' => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'Closed' OR status = 'Cancelled'"),
            'today' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", date('Y-m-d'))),
        ];
    }

    public function render_settings_page() {
        $active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_key($_GET[ 'tab' ]) : 'general';
        ?>
        <div class="wrap">
            <h1><?php _e('Support System Settings', 'woo-support-system'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=wss-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'woo-support-system'); ?></a>
                <a href="?page=wss-settings&tab=emails" class="nav-tab <?php echo $active_tab == 'emails' ? 'nav-tab-active' : ''; ?>"><?php _e('Emails', 'woo-support-system'); ?></a>
                <a href="?page=wss-settings&tab=automation" class="nav-tab <?php echo $active_tab == 'automation' ? 'nav-tab-active' : ''; ?>"><?php _e('Automation', 'woo-support-system'); ?></a>
                <a href="?page=wss-settings&tab=data" class="nav-tab <?php echo $active_tab == 'data' ? 'nav-tab-active' : ''; ?>"><?php _e('Data Management', 'woo-support-system'); ?></a>
            </h2>
            <form method="post" action="options.php">
            <?php
                if( $active_tab == 'general' ) { settings_fields( 'wss_general_settings_group' ); do_settings_sections( 'wss_general_settings' ); }
                if( $active_tab == 'emails' ) { settings_fields( 'wss_email_settings_group' ); do_settings_sections( 'wss_email_settings' ); }
                if( $active_tab == 'automation' ) { settings_fields( 'wss_automation_settings_group' ); do_settings_sections( 'wss_automation_settings' ); }
                if( $active_tab == 'data' ) { settings_fields( 'wss_data_settings_group' ); do_settings_sections( 'wss_data_settings' ); }
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        // ### GENERAL TAB ###
        register_setting( 'wss_general_settings_group', 'wss_general_settings' );
        
        add_settings_section( 'wss_general_section_pagination', __('Pagination Controls', 'woo-support-system'), null, 'wss_general_settings' );
        add_settings_field( 'admin_tickets_per_page', __('Admin Tickets Per Page', 'woo-support-system'), [ $this, 'render_field' ], 'wss_general_settings', 'wss_general_section_pagination', ['id' => 'admin_tickets_per_page', 'type' => 'number', 'group' => 'wss_general_settings', 'default' => 20] );
        add_settings_field( 'tickets_per_page', __('Customer Tickets Per Page', 'woo-support-system'), [ $this, 'render_field' ], 'wss_general_settings', 'wss_general_section_pagination', ['id' => 'tickets_per_page', 'type' => 'number', 'group' => 'wss_general_settings', 'default' => 10] );
        
        add_settings_section( 'wss_general_section_attachments', __('File Attachment Rules', 'woo-support-system'), null, 'wss_general_settings' );
        add_settings_field( 'allow_attachments', __('Allow File Attachments', 'woo-support-system'), [ $this, 'render_field' ], 'wss_general_settings', 'wss_general_section_attachments', ['id' => 'allow_attachments', 'type' => 'checkbox', 'group' => 'wss_general_settings', 'default' => 0] );
        add_settings_field( 'max_files_per_upload', __('Max Files Per Upload', 'woo-support-system'), [ $this, 'render_field' ], 'wss_general_settings', 'wss_general_section_attachments', ['id' => 'max_files_per_upload', 'type' => 'number', 'group' => 'wss_general_settings', 'default' => 3] );
        add_settings_field( 'allowed_file_types', __('Allowed File Types', 'woo-support-system'), [ $this, 'render_field' ], 'wss_general_settings', 'wss_general_section_attachments', ['id' => 'allowed_file_types', 'type' => 'text', 'group' => 'wss_general_settings', 'default' => 'jpg,jpeg,png,pdf'] );
        add_settings_field( 'max_file_size', __('Maximum File Size (MB)', 'woo-support-system'), [ $this, 'render_field' ], 'wss_general_settings', 'wss_general_section_attachments', ['id' => 'max_file_size', 'type' => 'number', 'group' => 'wss_general_settings', 'default' => 5] );

        add_settings_section( 'wss_general_section_storage', __('Attachment Storage Method', 'woo-support-system'), null, 'wss_general_settings' );
        add_settings_field( 'external_storage_api_key', __('External Storage API Key (imgbb.com)', 'woo-support-system'), [ $this, 'render_field' ], 'wss_general_settings', 'wss_general_section_storage', ['id' => 'external_storage_api_key', 'type' => 'text', 'group' => 'wss_general_settings'] );
        add_settings_field( 'local_storage_fallback', __('Enable Local Storage', 'woo-support-system'), [ $this, 'render_field' ], 'wss_general_settings', 'wss_general_section_storage', ['id' => 'local_storage_fallback', 'type' => 'checkbox', 'group' => 'wss_general_settings', 'desc' => 'If no API key is provided, files will be uploaded to the local WordPress media library. <strong style="color:red;">(Disabled by default)</strong>', 'default' => 0] );


        // ### EMAILS TAB (CONSOLIDATED) ###
        register_setting( 'wss_email_settings_group', 'wss_email_settings' );
        
        add_settings_section( 'wss_email_section_delivery', __('Email Delivery', 'woo-support-system'), null, 'wss_email_settings' );
        add_settings_field( 'admin_notification_recipients', __('Admin Notification Email(s)', 'woo-support-system'), [ $this, 'render_field' ], 'wss_email_settings', 'wss_email_section_delivery', ['id' => 'admin_notification_recipients', 'type' => 'text', 'group' => 'wss_email_settings', 'default' => get_option('admin_email')] );
        add_settings_field( 'admin_notification', __('Notify Admin on New Ticket', 'woo-support-system'), [ $this, 'render_field' ], 'wss_email_settings', 'wss_email_section_delivery', ['id' => 'admin_notification', 'type' => 'checkbox', 'group' => 'wss_email_settings', 'default' => 1] );
        add_settings_field( 'customer_notification', __('Notify Customers on Ticket Updates', 'woo-support-system'), [ $this, 'render_field' ], 'wss_email_settings', 'wss_email_section_delivery', ['id' => 'customer_notification', 'type' => 'checkbox', 'group' => 'wss_email_settings', 'default' => 1] );
        
        add_settings_section( 'wss_email_section_sender', __('Sender Details', 'woo-support-system'), null, 'wss_email_settings' );
        add_settings_field( 'from_name', __('"From" Name', 'woo-support-system'), [ $this, 'render_field' ], 'wss_email_settings', 'wss_email_section_sender', ['id' => 'from_name', 'type' => 'text', 'group' => 'wss_email_settings', 'default' => get_bloginfo('name')] );
        add_settings_field( 'from_email', __('"From" Email', 'woo-support-system'), [ $this, 'render_field' ], 'wss_email_settings', 'wss_email_section_sender', ['id' => 'from_email', 'type' => 'email', 'group' => 'wss_email_settings', 'default' => get_option('admin_email')] );

        add_settings_section( 'wss_email_section_template', __('Template Customization', 'woo-support-system'), null, 'wss_email_settings' );
        add_settings_field( 'logo', __('Email Logo', 'woo-support-system'), [$this, 'render_field'], 'wss_email_settings', 'wss_email_section_template', ['id' => 'logo', 'type' => 'logo', 'group' => 'wss_email_settings']);
        add_settings_field( 'header_color', __('Header Color', 'woo-support-system'), [$this, 'render_field'], 'wss_email_settings', 'wss_email_section_template', ['id' => 'header_color', 'type' => 'color', 'group' => 'wss_email_settings', 'default' => '#005a9c']);
        add_settings_field( 'footer_text', __('Footer Text', 'woo-support-system'), [$this, 'render_field'], 'wss_email_settings', 'wss_email_section_template', ['id' => 'footer_text', 'type' => 'textarea', 'group' => 'wss_email_settings', 'default' => sprintf(__('Copyright &copy; %s %s', 'woo-support-system'), date('Y'), get_bloginfo('name'))]);


        // ### AUTOMATION TAB ###
        register_setting( 'wss_automation_settings_group', 'wss_automation_settings' );
        add_settings_section( 'wss_automation_section_main', __('Ticket Automation', 'woo-support-system'), null, 'wss_automation_settings' );
        add_settings_field( 'auto_close_tickets', __('Auto-close Inactive Tickets', 'woo-support-system'), [ $this, 'render_field' ], 'wss_automation_settings', 'wss_automation_section_main', ['id' => 'auto_close_tickets', 'type' => 'number', 'group' => 'wss_automation_settings', 'desc' => 'Automatically close tickets that have been awaiting a customer reply for this many days. Set to 0 to disable.', 'default' => '0'] );


        // ### DATA MANAGEMENT TAB ###
        register_setting( 'wss_data_settings_group', 'wss_data_settings' );
        add_settings_section( 'wss_data_section', null, null, 'wss_data_settings' );
        add_settings_field( 'delete_on_uninstall', __('Delete Data on Uninstall', 'woo-support-system'), [ $this, 'render_field' ], 'wss_data_settings', 'wss_data_section', ['id' => 'delete_on_uninstall', 'type' => 'checkbox', 'group' => 'wss_data_settings', 'desc' => '<span style="color:red; font-weight:bold;">Warning:</span> Permanently delete all data when the plugin is deleted.'] );
    }

    public function render_field( $args ) {
        $group = $args['group'];
        $id = $args['id'];
        $options = get_option($group, []);
        $value = isset($options[$id]) ? $options[$id] : (isset($args['default']) ? $args['default'] : '');
        $name = "{$group}[{$id}]";
        
        switch ($args['type']) {
            case 'checkbox': 
                echo "<label><input type='checkbox' name='{$name}' value='1' " . checked(1, $value, false) . " />"; 
                if(!empty($args['desc'])) echo " " . $args['desc'] . "</label>";
                break;
            case 'number': 
                echo "<input type='number' name='{$name}' value='" . esc_attr($value) . "' class='small-text' min='0' />"; 
                if(!empty($args['desc'])) echo "<p class='description'>{$args['desc']}</p>";
                break;
            case 'logo':
                echo "<div><input type='text' id='wss_email_template_logo' name='{$name}' value='" . esc_attr($value) . "' class='regular-text'/> ";
                echo "<input type='button' id='upload_logo_button' class='button-secondary' value='Upload Logo'/></div>";
                echo "<div id='logo_preview' style='margin-top:10px;'>";
                if ($value) echo "<img src='".esc_url($value)."' style='max-width:200px; height:auto; border:1px solid #ddd; padding: 5px; background: #fff;'/>";
                echo '</div>'; 
                break;
            case 'color': 
                echo "<input type='text' name='{$name}' value='" . esc_attr($value) . "' class='wss-color-picker' />"; 
                break;
            case 'textarea': 
                echo "<textarea name='{$name}' rows='4' class='large-text'>" . esc_textarea($value) . "</textarea>";
                if(!empty($args['desc'])) echo "<p class='description'>{$args['desc']}</p>";
                break;
            default: 
                echo "<input type='{$args['type']}' name='{$name}' value='" . esc_attr($value) . "' class='regular-text' />";
                 if(!empty($args['desc'])) echo "<p class='description'>{$args['desc']}</p>";
        }
    }

    public function print_admin_assets() {
        if ( !isset($_GET['page']) || strpos($_GET['page'], 'wss-') === false ) return;
        
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        ?>
        <style type="text/css">
            .wss-admin-wrap .page-title-action { top: 0; }
            .wss-stats-container { display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
            .wss-stat-box { background: #fff; border: 1px solid #e2e4e7; border-radius: 4px; padding: 20px; text-align: center; flex: 1; min-width: 150px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
            .wss-stat-number { font-size: 2.5em; font-weight: 600; line-height: 1.1; margin-bottom: 5px; }
            .wss-stat-label { font-size: 13px; color: #50575e; text-transform: uppercase; letter-spacing: 0.5px; }
            .wss-stat-open .wss-stat-number { color: #d63638; }
            .wss-stat-pending .wss-stat-number { color: #f5a623; }
            .wss-stat-closed .wss-stat-number { color: #46b450; }
            .wss-stat-today .wss-stat-number { color: #0073aa; }
            .wss-filter-form { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function($){
                if(typeof wp.media !== 'undefined'){
                    var uploader;
                    $('#upload_logo_button').on('click', function(e) {
                        e.preventDefault();
                        if (uploader) {
                            uploader.open();
                            return;
                        }
                        uploader = wp.media({
                            title: 'Choose Logo', button: { text: 'Choose Logo' }, multiple: false
                        });
                        uploader.on('select', function() {
                            var attachment = uploader.state().get('selection').first().toJSON();
                            $('#wss_email_template_logo').val(attachment.url);
                            $('#logo_preview').html('<img src="'+attachment.url+'" style="max-width:200px;"/>');
                        });
                        uploader.open();
                    });
                }
                if(typeof $.fn.wpColorPicker !== 'undefined'){
                    $('.wss-color-picker').wpColorPicker();
                }
            });
        </script>
        <?php
    }
}