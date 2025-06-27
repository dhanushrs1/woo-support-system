<?php
/**
 * Admin View: Single Ticket
 *
 * This template is used for displaying a single ticket conversation and details in the admin dashboard.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( !current_user_can('manage_woocommerce') ) { wp_die( __('You do not have sufficient permissions to access this page.') ); }

// Database and object setup
global $wpdb;
$tickets_table = $wpdb->prefix . 'support_tickets';
$replies_table = $wpdb->prefix . 'support_ticket_replies';
$history_table = $wpdb->prefix . 'support_ticket_history';
$attachments_table = $wpdb->prefix . 'support_ticket_attachments';
$ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets_table} WHERE id = %d", $ticket_id ) );

// Exit if the ticket doesn't exist.
if ( ! $ticket ) {
    echo '<div class="error"><p>' . __( 'Sorry, this ticket could not be found.', 'woo-support-system' ) . '</p></div>';
    return;
}

// Automatically update status from Open to Processing when an admin first views it.
if ( $ticket->status === 'Open' ) {
    $wpdb->update( $tickets_table, ['status' => 'Processing'], ['id' => $ticket_id] );
    wss_add_ticket_history_note($ticket_id, "Status changed from 'Open' to 'Processing'.");
    $ticket->status = 'Processing'; // Update the object for immediate display
}

// Get all conversation items (replies and history) and sort them by date.
$customer = get_user_by('id', $ticket->user_id);
$timeline = array_merge(
    (array) $wpdb->get_results($wpdb->prepare("SELECT id, user_id, message, created_at, is_admin_reply, 'reply' as type FROM {$replies_table} WHERE ticket_id = %d", $ticket_id)),
    (array) $wpdb->get_results($wpdb->prepare("SELECT id, user_id, note as message, created_at, 'history' as type FROM {$history_table} WHERE ticket_id = %d", $ticket_id))
);
usort($timeline, function($a, $b) { return strtotime($a->created_at) - strtotime($b->created_at); });

// Get settings for attachments
$settings = get_option('wss_general_settings', []);
$allow_attachments = isset($settings['allow_attachments']) && $settings['allow_attachments'];
$max_files = absint($settings['max_files_per_upload'] ?? 3);
$max_size_mb = absint($settings['max_file_size'] ?? 5);
$allowed_types = esc_attr($settings['allowed_file_types'] ?? 'jpg,jpeg,png,pdf');
$attachments_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $attachments_table)) === $attachments_table;
?>
<div class="wrap">
    <h1>
        <?php printf( __('Ticket #%s: %s', 'woo-support-system'), esc_html($ticket->id), esc_html($ticket->subject) ); ?>
        <?php if (isset($ticket->rating) && !is_null($ticket->rating)): ?>
            <span class="wss-rating-stars" style="color:#f5a623; font-size: 18px; vertical-align: middle;" title="Customer Rating: <?php echo $ticket->rating; ?>/5">
                <?php echo str_repeat('★', $ticket->rating) . str_repeat('☆', 5 - $ticket->rating); ?>
            </span>
        <?php endif; ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=wss-tickets-dashboard'); ?>" class="page-title-action">&larr; <?php _e('Back to All Tickets', 'woo-support-system'); ?></a>
    
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            
            <div id="post-body-content">
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Conversation', 'woo-support-system'); ?></span></h2>
                    <div class="inside"><div class="wss-chat-container">
                        <?php foreach( $timeline as $item ) :
                            if ($item->type === 'reply') {
                                $user = get_user_by('id', $item->user_id);
                                $sender_name = $item->is_admin_reply ? __('You (Support)', 'woo-support-system') : ($user ? $user->display_name : __('Customer', 'woo-support-system'));
                                $avatar_url = get_avatar_url($item->user_id);
                                $attachments = [];
                                if ($attachments_table_exists) {
                                    $attachments = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$attachments_table} WHERE reply_id = %d", $item->id));
                                }
                                ?>
                                <div class="wss-chat-row <?php echo $item->is_admin_reply ? 'admin-row' : 'user-row'; ?>">
                                    <img src="<?php echo esc_url($avatar_url); ?>" class="wss-chat-avatar" alt="Avatar">
                                    <div class="wss-chat-bubble <?php echo $item->is_admin_reply ? 'admin-bubble' : 'user-bubble'; ?>">
                                        <div class="sender"><?php echo esc_html($sender_name); ?></div>
                                        <div class="message-content"><?php echo wpautop( wp_kses_post( $item->message ) ); ?></div>
                                        <?php if (!empty($attachments)): ?>
                                        <div class="wss-attachments" style="margin-top: 10px;">
                                            <strong><?php _e('Attachments:', 'woo-support-system'); ?></strong><br>
                                            <?php foreach($attachments as $attachment): ?>
                                                <a href="<?php echo esc_url($attachment->file_url); ?>" target="_blank" rel="noopener noreferrer">📎 <?php echo esc_html($attachment->file_name); ?></a><br>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="timestamp"><?php echo esc_html( date_i18n( 'M j, g:i a', strtotime( $item->created_at ) ) ); ?></div>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="wss-system-bubble"><?php echo esc_html( $item->message ); ?> - <span class="timestamp"><?php echo esc_html( date_i18n( 'M j, g:i a', strtotime( $item->created_at ) ) ); ?></span></div>
                            <?php }
                        endforeach; ?>
                    </div></div>
                </div>
                
                <?php if ( $ticket->status !== 'Closed' && $ticket->status !== 'Cancelled' ) : ?>
                <div class="postbox">
                     <h2 class="hndle"><span><?php _e('Post a Reply', 'woo-support-system'); ?></span></h2>
                     <div class="inside">
                        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" id="wss-admin-reply-form" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="wss_admin_reply"><input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
                            <?php wp_nonce_field( 'wss_admin_reply_nonce', 'wss_nonce' ); ?>
                            <textarea name="wss_message" rows="8" style="width:100%;" required placeholder="<?php _e('Type your reply here...', 'woo-support-system'); ?>"></textarea>
                            
                            <?php if ($allow_attachments): ?>
                            <p style="margin-top: 10px;">
                                <label for="wss_attachments"><?php _e('Add Attachments', 'woo-support-system'); ?></label><br>
                                <input type="file" name="wss_attachments[]" id="wss_attachments" multiple>
                                <div id="wss-attachment-errors-admin" class="wss-attachment-error"></div>
                            </p>
                            <?php endif; ?>
                            
                            <div id="major-publishing-actions">
                                <div id="publishing-action">
                                    <button type="submit" class="button button-primary button-large"><?php _e('Submit Reply', 'woo-support-system'); ?></button>
                                    <span class="wss-spinner" style="display: none;"></span>
                                </div>
                                <div class="clear"></div>
                            </div>
                        </form>
                     </div>
                </div>
                <?php endif; ?>
            </div>

            <div id="postbox-container-1" class="postbox-container">
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Ticket Details', 'woo-support-system'); ?></span></h2>
                    <div class="inside">
                        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                            <input type="hidden" name="action" value="wss_update_status">
                            <input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
                            <?php wp_nonce_field( 'wss_update_status_nonce', 'wss_nonce' ); ?>
                            
                            <p>
                                <label for="wss_status"><strong><?php _e('Status:', 'woo-support-system'); ?></strong></label><br>
                                <select name="wss_status" id="wss_status" style="width: 100%; margin-top: 5px;">
                                    <?php $all_statuses = ['Open', 'Processing', 'Awaiting Admin Reply', 'Awaiting Customer Reply', 'Closed', 'Cancelled'];
                                    foreach ($all_statuses as $status) : ?>
                                        <option value="<?php echo esc_attr($status); ?>" <?php selected($ticket->status, $status); ?>><?php echo esc_html(wss_get_status_name($status)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </p>

                            <?php if (isset($ticket->priority)): ?>
                            <p>
                                <label for="wss_priority"><strong><?php _e('Priority:', 'woo-support-system'); ?></strong></label><br>
                                <select name="wss_priority" id="wss_priority" style="width: 100%; margin-top: 5px;">
                                    <?php $all_priorities = ['Low', 'Normal', 'High', 'Urgent'];
                                    foreach ($all_priorities as $priority) : ?>
                                        <option value="<?php echo esc_attr($priority); ?>" <?php selected($ticket->priority, $priority); ?>><?php echo esc_html($priority); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                            <?php endif; ?>

                            <div id="major-publishing-actions" style="margin-top:10px;">
                                <button type="submit" class="button button-primary"><?php _e('Update', 'woo-support-system'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Customer Details', 'woo-support-system'); ?></span></h2>
                    <div class="inside">
                        <p><strong><?php _e('Name:', 'woo-support-system'); ?></strong><br><a href="<?php echo get_edit_user_link($customer->ID); ?>"><?php echo esc_html($customer->display_name); ?></a></p>
                        <p><strong><?php _e('Email:', 'woo-support-system'); ?></strong><br><?php echo esc_html($customer->user_email); ?></p>
                        <?php if ($phone = get_user_meta($customer->ID, 'billing_phone', true)) : ?><p><strong><?php _e('Phone:', 'woo-support-system'); ?></strong><br><?php echo esc_html($phone); ?></p><?php endif; ?>
                        <?php if ($ticket->order_id && ($order = wc_get_order($ticket->order_id))) : ?><p><strong><?php _e('Related Order:', 'woo-support-system'); ?></strong><br><a href="<?php echo $order->get_edit_order_url(); ?>">#<?php echo esc_html($order->get_order_number()); ?></a></p><?php endif; ?>
                    </div>
                </div>
            </div></div></div></div><?php
// Script for file upload validation, only if attachments are allowed and ticket is open.
if ($allow_attachments && $ticket->status !== 'Closed' && $ticket->status !== 'Cancelled' ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wss-admin-reply-form');
    if (form) {
        const fileInput = form.querySelector('#wss_attachments');
        const errorContainer = form.querySelector('#wss-attachment-errors-admin');
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
                this.value = '';
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

            if(hasError) {
                this.value = '';
            }
        });

        form.addEventListener('submit', function() {
            if (fileInput.files.length > 0 && spinner.style.display !== 'inline-block') {
                submitButton.disabled = true;
                spinner.style.display = 'inline-block';
            }
        });
    }
});
</script>
<?php endif; // This is the closing endif for the script block. It was missing before. ?>