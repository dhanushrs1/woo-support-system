<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WSS_Ticket_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'ticket',
            'plural'   => 'tickets',
            'ajax'     => false
        ] );
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array
     */
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'subject'      => __('Subject', 'woo-support-system'),
            'customer'     => __('Customer', 'woo-support-system'),
            'status'       => __('Status', 'woo-support-system'),
            'priority'     => __('Priority', 'woo-support-system'),
            'created_at'   => __('Date Created', 'woo-support-system'),
            'last_updated' => __('Last Updated', 'woo-support-system'),
        ];
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array
     */
    protected function get_sortable_columns() {
        return [
            'subject'      => ['subject', false],
            'status'       => ['status', false],
            'priority'     => ['priority', false],
            'created_at'   => ['created_at', false],
            'last_updated' => ['last_updated', true],
        ];
    }
    
    /**
     * Define the bulk actions
     * @return array
     */
    protected function get_bulk_actions() {
        $actions = [
            'wss_bulk_close'  => __( 'Close Selected Tickets', 'woo-support-system' ),
            'wss_bulk_delete' => __( 'Delete Selected Tickets', 'woo-support-system' ),
        ];
        return $actions;
    }

    /**
     * Process the bulk actions
     */
    public function process_bulk_action() {
        // Security check for bulk actions
        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

            // FIXED: Updated sanitization to avoid deprecated constant
            $nonce  = sanitize_text_field( $_POST['_wpnonce'] );
            $action = 'bulk-' . $this->_args['plural'];

            if ( ! wp_verify_nonce( $nonce, $action ) ) {
                wp_die( 'Nope! Security check failed!' );
            }
        }

        $action = $this->current_action();

        if ( $action && isset($_POST['ticket_ids']) ) {
            $ticket_ids = array_map('absint', $_POST['ticket_ids']);
            
            if ( 'wss_bulk_delete' === $action ) {
                foreach ($ticket_ids as $ticket_id) {
                    wss_delete_ticket($ticket_id);
                }
                $this->add_admin_notice( 'tickets_deleted', 'updated' );
            }
            
            if ( 'wss_bulk_close' === $action ) {
                foreach ($ticket_ids as $ticket_id) {
                    wss_close_ticket($ticket_id);
                }
                $this->add_admin_notice( 'tickets_closed', 'updated' );
            }
        }
    }

    function column_subject($item) {
        $view_url = admin_url('admin.php?page=wss-tickets-dashboard&action=view_ticket&ticket_id=' . $item['id']);
        $delete_url = wp_nonce_url(admin_url('admin.php?action=wss_delete_ticket&ticket_id=' . $item['id']), 'wss_delete_ticket_' . $item['id']);
        
        $actions = ['reply' => sprintf('<a href="%s">%s</a>', esc_url($view_url), __('Reply / View', 'woo-support-system'))];
        $actions['delete'] = sprintf('<a href="%s" style="color:#a00;" onclick="return confirm(\'Are you sure you want to PERMANENTLY DELETE this ticket and all its replies?\');">%s</a>', esc_url($delete_url), __('Delete', 'woo-support-system'));
        
        $rating_stars = '';
        if (isset($item['rating']) && !is_null($item['rating'])) {
            $rating_stars = ' <span class="wss-rating-stars" style="color:#f5a623;">' . str_repeat('★', $item['rating']) . str_repeat('☆', 5 - $item['rating']) . '</span>';
        }

        return sprintf('<strong><a class="row-title" href="%s">#%s: %s</a>%s</strong>', esc_url($view_url), esc_html($item['id']), esc_html($item['subject']), $rating_stars) . $this->row_actions($actions);
    }
    
    function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'customer':
                $user = get_user_by('id', $item['user_id']);
                return $user ? sprintf('<a href="%s">%s</a>', get_edit_user_link($user->ID), $user->display_name) : __('Unknown User', 'woo-support-system');
            case 'status':
                return sprintf('<span class="wss-status-%s">%s</span>', esc_attr(strtolower(str_replace(' ', '-', $item['status']))), esc_html(wss_get_status_name($item['status'])));
            case 'priority':
                return isset($item['priority']) ? esc_html($item['priority']) : __('Normal', 'woo-support-system');
            case 'created_at':
            case 'last_updated':
                return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item[$column_name] ) );
            default:
                return print_r( $item, true );
        }
    }

    function column_cb($item) { 
        return sprintf('<input type="checkbox" name="ticket_ids[]" value="%s" />', esc_attr($item['id'])); 
    }

    public function prepare_items() {
        global $wpdb;

        $this->process_bulk_action();

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        
        $settings = get_option('wss_general_settings', ['admin_tickets_per_page' => 20]);
        $per_page = isset($settings['admin_tickets_per_page']) ? absint($settings['admin_tickets_per_page']) : 20;
        if ( $per_page < 1 ) $per_page = 20;

        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Build WHERE clause based on filters
        $where_conditions = ['1=1'];
        if ( !empty($_REQUEST['status']) ) {
            $where_conditions[] = $wpdb->prepare("status = %s", ucwords(str_replace('-', ' ', sanitize_key($_REQUEST['status']))));
        }
        
        $columns = $wpdb->get_col("DESC {$wpdb->prefix}support_tickets", 0);
        if (in_array('priority', $columns) && !empty($_REQUEST['priority'])) {
            $where_conditions[] = $wpdb->prepare("priority = %s", ucwords(sanitize_key($_REQUEST['priority'])));
        }

        // FIXED: Enhanced search functionality
        if ( !empty($_REQUEST['s']) ) {
            $search_term = trim(sanitize_text_field($_REQUEST['s']));
            if (strpos($search_term, '#') === 0) {
                // Search by Ticket ID
                $ticket_id = absint(substr($search_term, 1));
                if ($ticket_id > 0) {
                    $where_conditions[] = $wpdb->prepare("id = %d", $ticket_id);
                }
            } else {
                // Search by Subject Text
                $search = '%' . $wpdb->esc_like($search_term) . '%';
                $where_conditions[] = $wpdb->prepare("subject LIKE %s", $search);
            }
        }

        if ( !empty($_REQUEST['date_from']) ) {
            $where_conditions[] = $wpdb->prepare("DATE(created_at) >= %s", sanitize_text_field($_REQUEST['date_from']));
        }
        if ( !empty($_REQUEST['date_to']) ) {
            $where_conditions[] = $wpdb->prepare("DATE(created_at) <= %s", sanitize_text_field($_REQUEST['date_to']));
        }

        $where_sql = "WHERE " . implode(' AND ', $where_conditions);

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}support_tickets {$where_sql}");

        $orderby = !empty($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns())) ? esc_sql($_REQUEST['orderby']) : 'last_updated';
        $order = !empty($_REQUEST['order']) && in_array(strtoupper($_REQUEST['order']), ['ASC', 'DESC']) ? esc_sql(strtoupper($_REQUEST['order'])) : 'DESC';

        $this->items = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}support_tickets {$where_sql} ORDER BY {$orderby} {$order} LIMIT {$per_page} OFFSET {$offset}",
            ARRAY_A
        );
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }
    
    /**
     * Add admin notices for bulk actions
     */
    private function add_admin_notice( $message_key, $class ) {
        add_settings_error(
            'wss_admin_notices',
            esc_attr( $message_key ),
            $this->get_message_text( $message_key ),
            $class
        );
    }
    
    /**
     * Get message text for notices
     */
    private function get_message_text( $key ) {
        $messages = [
            'tickets_deleted' => __('Selected tickets have been permanently deleted.', 'woo-support-system'),
            'tickets_closed'  => __('Selected tickets have been closed.', 'woo-support-system'),
        ];
        return $messages[$key] ?? 'Action processed.';
    }
}