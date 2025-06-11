<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once TBA_PLUGIN_DIR . 'includes/config.php';

class TBA_Telegram_Bot {
    private $bot_token;
    private $api_url;

    public function __construct() {
        $this->bot_token = TBA_BOT_TOKEN;
        $this->api_url = 'https://api.telegram.org/bot' . $this->bot_token . '/';
    }

    public function set_webhook( $url ) {
        $endpoint = $this->api_url . 'setWebhook';
        $response = wp_remote_post( $endpoint, array(
            'body' => json_encode( array( 'url' => $url ) ),
            'headers' => array( 'Content-Type' => 'application/json' ),
        ) );
        return $this->handle_response( $response );
    }

    public function send_message( $chat_id, $text, $reply_markup = null ) {
        $endpoint = $this->api_url . 'sendMessage';
        $data = array(
            'chat_id' => $chat_id,
            'text' => $text,
        );
        if ( $reply_markup ) {
            $data['reply_markup'] = $reply_markup;
        }
        $response = wp_remote_post( $endpoint, array(
            'body' => json_encode( $data ),
            'headers' => array( 'Content-Type' => 'application/json' ),
        ) );
        return $this->handle_response( $response );
    }

    public function get_updates( $offset = null ) {
        $endpoint = $this->api_url . 'getUpdates';
        $data = array();
        if ( $offset ) {
            $data['offset'] = $offset;
        }
        $response = wp_remote_post( $endpoint, array(
            'body' => json_encode( $data ),
            'headers' => array( 'Content-Type' => 'application/json' ),
        ) );
        return $this->handle_response( $response );
    }

    private function handle_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return array( 'ok' => false, 'description' => $response->get_error_message() );
        }
        $body = wp_remote_retrieve_body( $response );
        return json_decode( $body, true );
    }

    public function get_bot_token() {
        return $this->bot_token;
    }
}