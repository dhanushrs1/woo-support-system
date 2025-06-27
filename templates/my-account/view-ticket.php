<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$current_user_id = get_current_user_id();
$tickets_table = $wpdb->prefix . 'support_tickets';
$replies_table = $wpdb->prefix . 'support_ticket_replies';
$attachments_table = $wpdb->prefix . 'support_ticket_attachments';

$ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets_table} WHERE id = %d AND user_id = %d", $ticket_id, $current_user_id ) );

if ( ! $ticket ) { echo '<div class="woocommerce-error">' . __( 'Sorry, this ticket could not be found.', 'woo-support-system' ) . '</div>'; return; }

$replies = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$replies_table} WHERE ticket_id = %d ORDER BY created_at ASC", $ticket_id));
$settings = get_option('wss_general_settings', []);
$allow_attachments = isset($settings['allow_attachments']) && $settings['allow_attachments'];
$max_files = absint($settings['max_files_per_upload'] ?? 3);
$max_size_mb = absint($settings['max_file_size'] ?? 5);
$allowed_types = esc_attr($settings['allowed_file_types'] ?? 'jpg,jpeg,png,pdf');
$attachments_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $attachments_table)) === $attachments_table;
?>
<div class="wss-ticket-view">
    <h3><?php printf( __('Ticket #%s: %s', 'woo-support-system'), esc_html($ticket->id), esc_html($ticket->subject) ); ?></h3>
    <div class="wss-ticket-meta">
        <p><strong><?php _e('Status:', 'woo-support-system'); ?></strong> <span class="wss-status-<?php echo esc_attr( strtolower( str_replace(' ', '-', $ticket->status) ) ); ?>"><?php echo esc_html( wss_get_status_name($ticket->status) ); ?></span></p>
        <?php if (isset($ticket->priority)): ?>
        <p><strong><?php _e('Priority:', 'woo-support-system'); ?></strong> <span class="wss-priority-<?php echo esc_attr( strtolower($ticket->priority) ); ?>"><?php echo esc_html( $ticket->priority ); ?></span></p>
        <?php endif; ?>
    </div>

    <div class="wss-chat-container">
        <?php foreach( $replies as $reply ) :
            $user = get_user_by('id', $reply->user_id);
            $sender_name = $reply->is_admin_reply ? __('Store Support', 'woo-support-system') : ($user ? $user->display_name : __('You', 'woo-support-system'));
            $avatar_url = get_avatar_url($reply->user_id);
            $attachments = [];
            if ($attachments_table_exists) {
                $attachments = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$attachments_table} WHERE reply_id = %d", $reply->id));
            }
            ?>
            <div class="wss-chat-row <?php echo $reply->is_admin_reply ? 'admin-row' : 'user-row'; ?>">
                <img src="<?php echo esc_url($avatar_url); ?>" class="wss-chat-avatar" alt="Avatar">
                <div class="wss-chat-bubble <?php echo $reply->is_admin_reply ? 'admin-bubble' : 'user-bubble'; ?>">
                    <div class="sender"><?php echo esc_html($sender_name); ?></div>
                    <div class="message-content"><?php echo wpautop( wp_kses_post( $reply->message ) ); ?></div>
                    <?php if (!empty($attachments)): ?>
                    <div class="wss-attachments">
                        <strong><?php _e('Attachments:', 'woo-support-system'); ?></strong><br>
                        <?php foreach($attachments as $attachment): ?>
                            <a href="<?php echo esc_url($attachment->file_url); ?>" target="_blank" rel="noopener noreferrer">📎 <?php echo esc_html($attachment->file_name); ?></a><br>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="timestamp"><?php echo esc_html( date_i18n( 'M j, g:i a', strtotime( $reply->created_at ) ) ); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ( $ticket->status !== 'Closed' && $ticket->status !== 'Cancelled' ) : ?>
        <hr style="margin: 40px 0;">
        <h4><?php _e('Post a Reply', 'woo-support-system'); ?></h4>
        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="wss-form" id="wss-reply-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="wss_new_reply"><input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
            <?php wp_nonce_field( 'wss_new_reply_nonce', 'wss_nonce' ); ?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <textarea class="woocommerce-Textarea woocommerce-Input--textarea input-text" name="wss_message" rows="6" required placeholder="<?php _e('Type your message here...', 'woo-support-system'); ?>"></textarea>
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
                <div id="wss-attachment-errors-reply" class="wss-attachment-error"></div>
            </p>
            <?php endif; ?>
            <p>
                <button type="submit" class="woocommerce-Button button" name="wss_submit_reply"><?php _e('Submit Reply', 'woo-support-system'); ?></button>
                <span class="wss-spinner" style="display: none;"></span>
            </p>
        </form>
    <?php else: ?>
        <div class="woocommerce-info" style="margin-top: 20px;"><?php printf( __('This ticket was %s and can no longer be updated.', 'woo-support-system'), strtolower(wss_get_status_name($ticket->status)) ); ?></div>
        
        <?php if ($ticket->status === 'Closed' && !isset($ticket->rating)): ?>
        <div class="wss-rating-section" style="margin-top: 30px; padding: 20px; border: 1px solid #e0e0e0; background: #f9f9f9; text-align: center;">
            <h4><?php _e('How would you rate our support?', 'woo-support-system'); ?></h4>
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="wss-rating-form">
                <input type="hidden" name="action" value="wss_rate_ticket">
                <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket->id); ?>">
                <input type="hidden" name="rating" id="wss-rating-value" value="0">
                <?php wp_nonce_field('wss_rate_ticket_nonce', 'wss_nonce'); ?>
                <div class="wss-star-rating">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>
                <button type="submit" class="woocommerce-Button button" style="margin-top: 15px;"><?php _e('Submit Rating', 'woo-support-system'); ?></button>
            </form>
        </div>
        <?php elseif (isset($ticket->rating) && !is_null($ticket->rating)): ?>
            <div class="woocommerce-message" style="margin-top: 20px; text-align:center;">
                <?php _e('Thank you for your feedback!', 'woo-support-system'); ?><br>
                <div class="wss-final-rating" title="<?php printf(__('%d out of 5 stars', 'woo-support-system'), $ticket->rating); ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php if ($i <= $ticket->rating) echo 'rated'; ?>">★</span>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <p style="margin-top: 20px;"><a href="<?php echo esc_url( wc_get_account_endpoint_url('support-tickets') ); ?>" class="button">&larr; <?php _e('Back to All Tickets', 'woo-support-system'); ?></a></p>
</div>

<?php if ($allow_attachments && $ticket->status !== 'Closed' && $ticket->status !== 'Cancelled' ): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wss-reply-form');
    if(form) {
        const fileInput = form.querySelector('#wss_attachments');
        const errorContainer = form.querySelector('#wss-attachment-errors-reply');
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
<?php endif; ?>

<?php if ($ticket->status === 'Closed' && !isset($ticket->rating)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const starContainer = document.querySelector('.wss-star-rating');
    if (starContainer) {
        const stars = starContainer.querySelectorAll('.star');
        const ratingInput = document.getElementById('wss-rating-value');
        const ratingForm = document.getElementById('wss-rating-form');

        const resetStars = () => {
            const selectedValue = parseInt(ratingInput.value, 10);
            stars.forEach(star => {
                if (parseInt(star.dataset.value, 10) <= selectedValue) {
                    star.classList.add('rated');
                } else {
                    star.classList.remove('rated');
                }
            });
        };

        stars.forEach(star => {
            star.addEventListener('click', function() {
                ratingInput.value = this.dataset.value;
                resetStars();
            });

            star.addEventListener('mouseover', function() {
                const hoverValue = parseInt(this.dataset.value, 10);
                stars.forEach(s => {
                    s.classList.remove('rated');
                    if (parseInt(s.dataset.value, 10) <= hoverValue) {
                        s.classList.add('hover');
                    } else {
                        s.classList.remove('hover');
                    }
                });
            });
        });

        starContainer.addEventListener('mouseleave', function() {
            stars.forEach(star => star.classList.remove('hover'));
            resetStars();
        });

        ratingForm.addEventListener('submit', function(e) {
            if (ratingInput.value == '0') {
                e.preventDefault();
                alert('<?php _e("Please select a star rating before submitting.", "woo-support-system"); ?>');
            }
        });
    }
});
</script>
<?php endif; ?>