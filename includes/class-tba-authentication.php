<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class TBA_Authentication {
    private $telegram_bot;

    public function __construct( $telegram_bot ) {
        $this->telegram_bot = $telegram_bot;
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_shortcode( 'telegram_login', array( $this, 'telegram_login_shortcode' ) );
    }

    public function register_rest_routes() {
        register_rest_route( 'tba/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array( $this, 'handle_webhook' ),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( 'tba/v1', '/auth', array(
            'methods' => 'POST',
            'callback' => array( $this, 'handle_auth_request' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'tba-auth', TBA_PLUGIN_URL . 'assets/js/auth.js', array( 'jquery' ), '1.0.0', true );
        wp_localize_script( 'tba-auth', 'tbaAuth', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'rest_url' => rest_url( 'tba/v1/auth' ),
            'nonce' => wp_create_nonce( 'tba_auth_nonce' ),
        ) );
    }

    public function telegram_login_shortcode() {
        if ( is_user_logged_in() ) {
            return '<p>' . __( 'You are already logged in.', 'telegram-bot-auth' ) . '</p>';
        }
        $bot_username = get_option( 'tba_bot_username', '' );
        if ( empty( $bot_username ) ) {
            return '<p>' . __( 'Telegram bot is not configured.', 'telegram-bot-auth' ) . '</p>';
        }
        return '<div id="telegram-login"><a href="https://t.me/' . esc_attr( $bot_username ) . '?start=auth" target="_blank">' . __( 'Login with Telegram', 'telegram-bot-auth' ) . '</a></div>';
    }

    public function handle_webhook( $request ) {
        $update = json_decode( file_get_contents( 'php://input' ), true );
        if ( isset( $update['message'] ) ) {
            $message = $update['message'];
            $chat_id = $message['chat']['id'];
            $text = $message['text'] ?? '';

            if ( strpos( $text, '/start auth' ) === 0 ) {
                $this->start_auth_process( $chat_id );
            } elseif ( strpos( $text, '/start link' ) === 0 ) {
                $this->start_link_process( $chat_id );
            } else {
                $this->handle_user_input( $chat_id, $text );
            }
        }
        return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
    }

    private function start_auth_process( $chat_id ) {
        $message = __( 'Please click the button below to log in to our website.', 'telegram-bot-auth' );
        $reply_markup = json_encode( array(
            'inline_keyboard' => array(
                array(
                    array(
                        'text' => __( 'Login to Website', 'telegram-bot-auth' ),
                        'url' => site_url( '/telegram-auth?chat_id=' . $chat_id ),
                    ),
                ),
            ),
        ) );
        $this->telegram_bot->send_message( $chat_id, $message, $reply_markup );
    }

    private function start_link_process( $chat_id ) {
        $message = __( 'Please click the button below to link your Telegram account to your existing website account.', 'telegram-bot-auth' );
        $reply_markup = json_encode( array(
            'inline_keyboard' => array(
                array(
                    array(
                        'text' => __( 'Link Account', 'telegram-bot-auth' ),
                        'url' => site_url( '/telegram-link?chat_id=' . $chat_id ),
                    ),
                ),
            ),
        ) );
        $this->telegram_bot->send_message( $chat_id, $message, $reply_markup );
    }

    private function handle_user_input( $chat_id, $text ) {
        // Handle user input for email and name if needed
        $user_data = get_transient( 'tba_user_data_' . $chat_id );
        if ( $user_data ) {
            if ( ! isset( $user_data['email'] ) ) {
                $user_data['email'] = sanitize_email( $text );
                $message = __( 'Please enter your name:', 'telegram-bot-auth' );
                $this->telegram_bot->send_message( $chat_id, $message );
            } elseif ( ! isset( $user_data['name'] ) ) {
                $user_data['name'] = sanitize_text_field( $text );
                // Now we have both email and name, proceed with registration
                $this->complete_registration( $chat_id, $user_data );
            }
            set_transient( 'tba_user_data_' . $chat_id, $user_data, HOUR_IN_SECONDS );
        }
    }

    private function complete_registration( $chat_id, $user_data ) {
        $email = $user_data['email'];
        $name = $user_data['name'];
        $telegram_id = $chat_id;

        // Check if user already exists
        $user = get_user_by( 'email', $email );
        if ( $user ) {
            // Update existing user with Telegram ID
            update_user_meta( $user->ID, 'telegram_id', $telegram_id );
            $message = __( 'Your Telegram account has been linked to your existing account.', 'telegram-bot-auth' );
            $this->telegram_bot->send_message( $chat_id, $message );
        } else {
            // Create new user
            $username = 'tg_' . $telegram_id;
            $password = wp_generate_password();
            $user_id = wp_create_user( $username, $password, $email );
            if ( ! is_wp_error( $user_id ) ) {
                wp_update_user( array(
                    'ID' => $user_id,
                    'display_name' => $name,
                    'user_nicename' => sanitize_title( $name ),
                ) );
                update_user_meta( $user_id, 'telegram_id', $telegram_id );
                $message = __( 'Registration successful! You can now log in to the website with your Telegram account.', 'telegram-bot-auth' );
                $this->telegram_bot->send_message( $chat_id, $message );
            } else {
                $message = __( 'Registration failed. Please try again.', 'telegram-bot-auth' );
                $this->telegram_bot->send_message( $chat_id, $message );
            }
        }
        delete_transient( 'tba_user_data_' . $chat_id );
    }

    public function handle_auth_request( $request ) {
        $chat_id = $request->get_param( 'chat_id' );
        if ( empty( $chat_id ) ) {
            return new WP_REST_Response( array( 'error' => __( 'Invalid request.', 'telegram-bot-auth' ) ), 400 );
        }

        $user = $this->get_user_by_telegram_id( $chat_id );
        if ( $user ) {
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID );
            return new WP_REST_Response( array( 'success' => true, 'message' => __( 'Logged in successfully.', 'telegram-bot-auth' ) ), 200 );
        } else {
            // New user, need to collect email and name
            set_transient( 'tba_user_data_' . $chat_id, array(), HOUR_IN_SECONDS );
            $message = __( 'Please enter your email address:', 'telegram-bot-auth' );
            $this->telegram_bot->send_message( $chat_id, $message );
            return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Please complete registration in Telegram.', 'telegram-bot-auth' ) ), 200 );
        }
    }

    private function get_user_by_telegram_id( $telegram_id ) {
        $users = get_users( array(
            'meta_key' => 'telegram_id',
            'meta_value' => $telegram_id,
            'number' => 1,
        ) );
        return ! empty( $users ) ? $users[0] : false;
    }
} 