<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Llrp_Ajax {
    public static function init() {
        add_action( 'wp_ajax_nopriv_llrp_login',    [ __CLASS__, 'ajax_login' ] );
        add_action( 'wp_ajax_nopriv_llrp_register', [ __CLASS__, 'ajax_register' ] );
    }

    public static function ajax_login() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $creds = [
            'user_login'    => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
            'user_password' => $_POST['password'] ?? '',
            'remember'      => isset( $_POST['remember'] ) && $_POST['remember'] === '1',
        ];
        $user = wp_signon( $creds, is_ssl() );
        if ( is_wp_error( $user ) ) {
            wp_send_json_error([ 'message' => __( 'Credenciais inválidas.', 'llrp' ) ]);
        }
        wp_send_json_success([ 'redirect' => wc_get_checkout_url() ]);
    }

    public static function ajax_register() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $password = $_POST['password'] ?? '';
        if ( ! is_email( $email ) ) {
            wp_send_json_error([ 'message' => __( 'E-mail inválido.', 'llrp' ) ]);
        }
        if ( email_exists( $email ) ) {
            wp_send_json_error([ 'message' => __( 'Este e-mail já está registrado.', 'llrp' ) ]);
        }
        if ( empty( $password ) ) {
            wp_send_json_error([ 'message' => __( 'Insira uma senha válida.', 'llrp' ) ]);
        }
        $user_id = wc_create_new_customer( $email, '', $password );
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error([ 'message' => $user_id->get_error_message() ]);
        }
        wc_set_customer_auth_cookie( $user_id );
        wp_send_json_success([ 'redirect' => wc_get_checkout_url() ]);
    }
}
Llrp_Ajax::init();
