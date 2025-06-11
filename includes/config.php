<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Telegram Bot Configuration
 define( 'TBA_BOT_TOKEN', get_option( 'tba_bot_token', '' ) );
 define( 'TBA_BOT_USERNAME', get_option( 'tba_bot_username', '' ) );

// Webhook URL will be defined after WordPress is fully loaded
function tba_define_webhook_url() {
    if ( ! defined( 'TBA_WEBHOOK_URL' ) ) {
        define( 'TBA_WEBHOOK_URL', rest_url( 'tba/v1/webhook' ) );
    }
}
add_action( 'init', 'tba_define_webhook_url' );
?> 