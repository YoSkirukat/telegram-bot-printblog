<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class TBA_Settings {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_settings_page() {
        add_options_page(
            __( 'Настройки Telegram Bot Authentication', 'telegram-bot-auth' ),
            __( 'Telegram Bot Auth', 'telegram-bot-auth' ),
            'manage_options',
            'tba-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'tba_settings_group', 'tba_bot_token', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'tba_settings_group', 'tba_bot_username', array( 'sanitize_callback' => 'sanitize_text_field' ) );

        add_settings_section(
            'tba_general_settings',
            __( 'Общие настройки', 'telegram-bot-auth' ),
            array( $this, 'general_settings_section_callback' ),
            'tba-settings'
        );

        add_settings_field(
            'tba_bot_token',
            __( 'Токен Telegram бота', 'telegram-bot-auth' ),
            array( $this, 'bot_token_field_callback' ),
            'tba-settings',
            'tba_general_settings'
        );

        add_settings_field(
            'tba_bot_username',
            __( 'Имя пользователя Telegram бота', 'telegram-bot-auth' ),
            array( $this, 'bot_username_field_callback' ),
            'tba-settings',
            'tba_general_settings'
        );
    }

    public function general_settings_section_callback() {
        echo '<p>' . __( 'Введите данные вашего Telegram бота для интеграции с сайтом.', 'telegram-bot-auth' ) . '</p>';
    }

    public function bot_token_field_callback() {
        $value = get_option( 'tba_bot_token', '' );
        echo '<input type="text" name="tba_bot_token" value="' . esc_attr( $value ) . '" class="regular-text">';
        echo '<p class="description">' . __( 'Введите токен, полученный от @BotFather в Telegram.', 'telegram-bot-auth' ) . '</p>';
    }

    public function bot_username_field_callback() {
        $value = get_option( 'tba_bot_username', '' );
        echo '<input type="text" name="tba_bot_username" value="' . esc_attr( $value ) . '" class="regular-text">';
        echo '<p class="description">' . __( 'Введите имя пользователя бота (без @).', 'telegram-bot-auth' ) . '</p>';
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'tba_settings_group' );
                do_settings_sections( 'tba-settings' );
                submit_button( __( 'Сохранить настройки', 'telegram-bot-auth' ) );
                ?>
            </form>
        </div>
        <?php
    }
} 