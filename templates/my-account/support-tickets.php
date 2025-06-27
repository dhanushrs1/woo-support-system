<?php
/**
 * My Account > Support Tickets template
 *
 * This template is responsible for displaying the ticket creation form and the user's ticket history.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current user information and settings
$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;
$billing_phone = get_user_meta($current_user_id, 'billing_phone', true);
$settings = get_option('wss_general_settings', []);
$allow_attachments = isset($settings['allow_attachments']) && $settings['allow_attachments'];
$max_files = absint($settings['max_files_per_upload'] ?? 3);
$max_size_mb = absint($settings['max_file_size'] ?? 5);
$allowed_types = esc_attr($settings['allowed_file_types'] ?? 'jpg,jpeg,png,pdf');

// --- ROBUST PAGINATION LOGIC ---
$tickets_per_page = isset($settings['tickets_per_page']) ? absint($settings['tickets_per_page']) : 10;
if ($tickets_per_page <= 0) { $tickets_per_page = 10; }

global $wpdb;
$tickets_table = $wpdb->prefix . 'support_tickets';
$current_page = isset( $_GET['ticket_page'] ) ? absint( $_GET['ticket_page'] ) : 1;
$offset = ( $current_page - 1 ) * $tickets_per_page;

// Get total number of tickets for pagination
$total_tickets = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$tickets_table} WHERE user_id = %d", $current_user_id ) );

// Get paginated tickets for the current user
$tickets = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$tickets_table} WHERE user_id = %d ORDER BY last_updated DESC LIMIT %d OFFSET %d",
    $current_user_id, $tickets_per_page, $offset
) );
?>

<h2><?php _e('Create a New Ticket', 'woo-support-system'); ?></h2>

<div class="wss-user-info-box">
    <h3><?php _e('Your Information', 'woo-support-system'); ?></h3>
    <p><strong><?php _e('Name:', 'woo-support-system'); ?></strong> <?php echo esc_html( $current_user->display_name ); ?></p>
    <p><strong><?php _e('Email:', 'woo-support-system'); ?></strong> <?php echo esc_html( $current_user->user_email ); ?></p>
    <?php if ( $billing_phone ) : ?>
        <p><strong><?php _e('Phone:', 'woo-support-system'); ?></strong> <?php echo esc_html( $billing_phone ); ?></p>
    <?php endif; ?>
</div>

<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="wss-form" id="wss-new-ticket-form" enctype="multipart/form-data">
    <input type="hidden" name="action" value="wss_new_ticket">
    <?php wp_nonce_field( 'wss_new_ticket_nonce', 'wss_nonce' ); ?>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="wss_subject"><?php _e('Subject', 'woo-support-system'); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="wss_subject" id="wss_subject" required>
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
        <label for="wss_order_id"><?php _e('Related Order (Optional)', 'woo-support-system'); ?></label>
        <select name="wss_order_id" id="wss_order_id" class="woocommerce-select">
            <option value=""><?php _e('None', 'woo-support-system'); ?></option>
            <?php
                $customer_orders = wc_get_orders( [ 'customer' => $current_user_id, 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC' ] );
                if ($customer_orders) {
                    foreach ( $customer_orders as $order ) {
                        echo '<option value="' . esc_attr( $order->get_id() ) . '">#' . esc_html( $order->get_order_number() ) . ' - ' . esc_html( $order->get_date_created()->date('F j, Y') ) . '</option>';
                    }
                }
            ?>
        </select>
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
        <label for="wss_priority"><?php _e('Priority', 'woo-support-system'); ?> <span class="required">*</span></label>
        <select name="wss_priority" id="wss_priority" class="woocommerce-select" required>
            <option value="Normal"><?php _e('Normal', 'woo-support-system'); ?></option>
            <option value="Low"><?php _e('Low', 'woo-support-system'); ?></option>
            <option value="High"><?php _e('High', 'woo-support-system'); ?></option>
            <option value="Urgent"><?php _e('Urgent', 'woo-support-system'); ?></option>
        </select>
    </p>
    
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="wss_message"><?php _e('Message', 'woo-support-system'); ?> <span class="required">*</span></label>
        <textarea class="woocommerce-Textarea woocommerce-Input--textarea input-text" name="wss_message" id="wss_message" rows="6" required></textarea>
    </p>
    
    <?php if ($allow_attachments): ?>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="wss_attachments"><?php _e('Add Attachments', 'woo-support-system'); ?></label>
        <input type="file" name="wss_attachments[]" id="wss_attachments" multiple>
        <small class="wss-attachment-rules"><?php 
            printf(
                __('Max files: %d, Max size: %dMB, Allowed types: %s', 'woo-support-system'), 
                $max_files, 
                $max_size_mb, 
                $allowed_types
            ); 
        ?></small>
        <div id="wss-attachment-errors" class="wss-attachment-error"></div>
    </p>
    <?php endif; ?>

    <p>
        <button type="submit" class="woocommerce-Button button" name="wss_submit_ticket" value="<?php _e('Submit Ticket', 'woo-support-system'); ?>"><?php _e('Submit Ticket', 'woo-support-system'); ?></button>
        <span class="wss-spinner" style="display: none;"></span>
    </p>
</form>

<hr style="margin: 40px 0;">

<div class="ticket-history">
    <h2><?php _e('My Support Tickets', 'woo-support-system'); ?></h2>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
            <tr>
                <th class="woocommerce-orders-table__header"><span class="nobr"><?php _e('Ticket ID', 'woo-support-system'); ?></span></th>
                <th class="woocommerce-orders-table__header"><span class="nobr"><?php _e('Subject', 'woo-support-system'); ?></span></th>
                <th class="woocommerce-orders-table__header"><span class="nobr"><?php _e('Status', 'woo-support-system'); ?></span></th>
                <th class="woocommerce-orders-table__header"><span class="nobr"><?php _e('Last Updated', 'woo-support-system'); ?></span></th>
                <th class="woocommerce-orders-table__header"></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty($tickets) ) : ?>
                <?php foreach ( $tickets as $ticket ) : ?>
                    <tr class="woocommerce-orders-table__row order">
                        <td class="woocommerce-orders-table__cell" data-title="<?php _e('Ticket ID', 'woo-support-system'); ?>">#<?php echo esc_html( $ticket->id ); ?></td>
                        <td class="woocommerce-orders-table__cell" data-title="<?php _e('Subject', 'woo-support-system'); ?>"><?php echo esc_html( $ticket->subject ); ?></td>
                        <td class="woocommerce-orders-table__cell" data-title="<?php _e('Status', 'woo-support-system'); ?>"><span class="wss-status-<?php echo esc_attr( strtolower( str_replace(' ', '-', $ticket->status) ) ); ?>"><?php echo esc_html( wss_get_status_name($ticket->status) ); ?></span></td>
                        <td class="woocommerce-orders-table__cell" data-title="<?php _e('Last Updated', 'woo-support-system'); ?>"><time><?php echo esc_html( date_i18n( 'F j, Y, g:i a', strtotime( $ticket->last_updated ) ) ); ?></time></td>
                        <td class="woocommerce-orders-table__cell">
                            <a href="<?php echo esc_url( wc_get_account_endpoint_url('view-ticket') . $ticket->id ); ?>" class="woocommerce-button button view"><?php _e('View', 'woo-support-system'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="5"><?php _e('You have not created any support tickets yet.', 'woo-support-system'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    if ( $total_tickets > $tickets_per_page ) {
        $pagination_args = array(
            'base'         => add_query_arg( 'ticket_page', '%#%', wc_get_account_endpoint_url('support-tickets') ),
            'format'       => '?ticket_page=%#%',
            'total'        => ceil( $total_tickets / $tickets_per_page ),
            'current'      => $current_page,
            'show_all'     => false,
            'end_size'     => 1,
            'mid_size'     => 2,
            'prev_next'    => true,
            'prev_text'    => __('&laquo; Previous', 'woo-support-system'),
            'next_text'    => __('Next &raquo;', 'woo-support-system'),
            'type'         => 'list',
        );
        
        echo '<nav class="woocommerce-pagination">';
        echo paginate_links($pagination_args);
        echo '</nav>';
    }
    ?>
</div>

<?php if ($allow_attachments): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wss-new-ticket-form');
    if (form) {
        const fileInput = form.querySelector('#wss_attachments');
        const errorContainer = form.querySelector('#wss-attachment-errors');
        const submitButton = form.querySelector('button[type="submit"]');
        const spinner = form.querySelector('.wss-spinner');

        const maxFiles = <?php echo $max_files; ?>;
        const maxSize = <?php echo $max_size_mb; ?> * 1024 * 1024;
        const allowedTypes = '<?php echo str_replace(' ', '', $allowed_types); ?>'.split(',');

        fileInput.addEventListener('change', function() {
            errorContainer.innerHTML = '';
            const files = this.files;
            let hasError = false;

            if (files.length > maxFiles) {
                errorContainer.textContent = `You can only upload a maximum of ${maxFiles} files.`;
                this.value = ''; // Clear the invalid selection
                return;
            }

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileExt = file.name.split('.').pop().toLowerCase();

                if (file.size > maxSize) {
                    errorContainer.innerHTML += `<div>Error: "${file.name}" is too large (max ${<?php echo $max_size_mb; ?>}MB).</div>`;
                    hasError = true;
                    continue;
                }

                if (!allowedTypes.includes(fileExt)) {
                    errorContainer.innerHTML += `<div>Error: "${file.name}" has an unsupported file type.</div>`;
                    hasError = true;
                    continue;
                }
            }
            if (hasError) {
                this.value = '';
            }
        });

        form.addEventListener('submit', function(e) {
            if (fileInput.files.length > 0 && spinner.style.display !== 'inline-block') {
                submitButton.disabled = true;
                spinner.style.display = 'inline-block';
            }
        });
    }
});
</script>
<?php endif; ?>