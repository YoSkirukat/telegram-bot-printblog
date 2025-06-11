<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Telegram Bot Configuration
 define( 'TBA_BOT_TOKEN', get_option( 'tba_bot_token', '' ) );
 define( 'TBA_BOT_USERNAME', get_option( 'tba_bot_username', '' ) );
 define( 'TBA_WEBHOOK_URL', rest_url( 'tba/v1/webhook' ) );
?> 