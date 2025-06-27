<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WSS_Email_Templates {

    public static function get_customer_email_html($subject, $content) {
        // FIXED: Now correctly pulls from the single, consolidated email settings group.
        $template_settings = get_option('wss_email_settings', []);
        $logo_url = !empty($template_settings['logo']) ? esc_url($template_settings['logo']) : '';
        $header_color = !empty($template_settings['header_color']) ? esc_attr($template_settings['header_color']) : '#005a9c';
        $footer_text = !empty($template_settings['footer_text']) ? wpautop(wp_kses_post($template_settings['footer_text'])) : '';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>
        <body style="margin:0; padding:0; background-color:#f5f5f5;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-family: Arial, sans-serif;">
                <tr><td align="center" style="padding: 20px;">
                    <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color:#ffffff; border:1px solid #dddddd; border-radius:3px;">
                        <tr><td align="center" style="padding: 20px 0; background-color:<?php echo $header_color; ?>; color:#ffffff; border-radius:3px 3px 0 0;">
                            <?php if ($logo_url): ?>
                                <img src="<?php echo $logo_url; ?>" alt="<?php echo get_bloginfo('name'); ?>" style="max-width:200px; height:auto;"/>
                            <?php else: ?>
                                <h1 style="margin:0;"><?php echo get_bloginfo('name'); ?></h1>
                            <?php endif; ?>
                        </td></tr>
                        <tr><td style="padding: 30px 20px; font-size:16px; line-height:1.6; color:#555555;">
                            <h2 style="color:#333333;"><?php echo esc_html($subject); ?></h2>
                            <?php echo $content; // Already prepared and escaped in caller function ?>
                        </td></tr>
                        <tr><td align="center" style="padding: 20px; background-color:#f0f0f0; color:#888888; font-size:12px; border-top:1px solid #dddddd; border-radius:0 0 3px 3px;">
                            <?php echo $footer_text; ?>
                        </td></tr>
                    </table>
                </td></tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    public static function get_admin_email_html($subject, $content) {
        // A simpler template for admins
        ob_start();
        ?>
        <div style="font-family: Arial, sans-serif; font-size:14px; line-height:1.6; color:#333; border:1px solid #ddd; padding:20px; max-width:700px; margin:20px auto;">
            <h2 style="margin-top:0;"><?php echo esc_html($subject); ?></h2>
            <hr>
            <?php echo $content; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}