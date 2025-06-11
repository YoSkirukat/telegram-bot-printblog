<?php
/*
 * Plugin Name: Telegram Bot Authentication
 * Description: Плагин для интеграции Telegram бота для аутентификации пользователей на сайте WordPress.
 * Version: 0.0.1 Beta
 * Author: Иван Малышев
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
 define( 'TBA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
 define( 'TBA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include configuration file
require_once TBA_PLUGIN_DIR . 'includes/config.php';

// Include necessary files
require_once TBA_PLUGIN_DIR . 'includes/class-tba-telegram-bot.php';
require_once TBA_PLUGIN_DIR . 'includes/class-tba-authentication.php';
require_once TBA_PLUGIN_DIR . 'includes/class-tba-settings.php';

// Initialize the plugin
function tba_init() {
    $telegram_bot = new TBA_Telegram_Bot();
    $auth = new TBA_Authentication( $telegram_bot );
    if ( is_admin() ) {
        $settings = new TBA_Settings();
    }
}
add_action( 'plugins_loaded', 'tba_init' );

// Activation hook
function tba_activate() {
    // Activation code here if needed
}
register_activation_hook( __FILE__, 'tba_activate' );

// Deactivation hook
function tba_deactivate() {
    // Deactivation code here if needed
}
register_deactivation_hook( __FILE__, 'tba_deactivate' );