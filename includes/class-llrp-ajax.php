<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Llrp_Ajax {
    public static function init() {
        add_action( 'wp_ajax_nopriv_llrp_login',    [ __CLASS__, 'ajax_login' ] );
        add_action( 'wp_ajax_nopriv_llrp_register', [ __CLASS__, 'ajax_register' ] );
        add_action( 'wp_ajax_nopriv_llrp_send_login_code', [ __CLASS__, 'ajax_send_login_code' ] );
        add_action( 'wp_ajax_nopriv_llrp_code_login',      [ __CLASS__, 'ajax_code_login' ] );
    }

    public static function ajax_login() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $creds = [
            'user_login'    => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
            'user_password' => isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '',
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
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

        if ( ! is_email( $email ) ) {
            wp_send_json_error([ 'message' => __( 'E-mail inválido.', 'llrp' ) ]);
        }
        if ( email_exists( $email ) ) {
            wp_send_json_error([ 'message' => __( 'Este e-mail já está registrado.', 'llrp' ) ]);
        }

        // Security: Basic password strength check
        if ( strlen( $password ) < 8 ) {
            wp_send_json_error([ 'message' => __( 'A senha deve ter pelo menos 8 caracteres.', 'llrp' ) ]);
        }

        $user_id = wc_create_new_customer( $email, '', $password );
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error([ 'message' => $user_id->get_error_message() ]);
        }
        wc_set_customer_auth_cookie( $user_id );
        wp_send_json_success([ 'redirect' => wc_get_checkout_url() ]);
    }
    public static function ajax_send_login_code() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );

        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'E-mail inválido.', 'llrp' ) ] );
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            wp_send_json_error( [ 'message' => __( 'Nenhuma conta encontrada para este e-mail.', 'llrp' ) ] );
        }

        // Rate limiting: 5 attempts per hour per email.
        $transient_key = 'llrp_rate_limit_' . md5( $email );
        $attempts = get_transient( $transient_key );

        if ( false === $attempts ) {
            $attempts = 0;
        }

        if ( $attempts >= 5 ) {
            wp_send_json_error( [ 'message' => __( 'Você fez muitas solicitações. Por favor, tente novamente mais tarde.', 'llrp' ) ] );
        }

        set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );

        $code = (string) wp_rand( 100000, 999999 );
        $hash = wp_hash_password( $code );
        $expiration = time() + ( 5 * MINUTE_IN_SECONDS );

        update_user_meta( $user->ID, '_llrp_login_code_hash', $hash );
        update_user_meta( $user->ID, '_llrp_login_code_expiration', $expiration );

        $subject = __( 'Seu código de login', 'llrp' );
        $message = sprintf(
            __( 'Use este código para fazer login: %s. O código expira em 5 minutos.', 'llrp' ),
            $code
        );

        if ( ! wp_mail( $email, $subject, $message ) ) {
            wp_send_json_error( [ 'message' => __( 'Não foi possível enviar o e-mail com o código de login.', 'llrp' ) ] );
        }

        wp_send_json_success( [ 'message' => __( 'Um código de login foi enviado para o seu e-mail.', 'llrp' ) ] );
    }

    public static function ajax_code_login() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );

        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $code  = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );

        if ( ! is_email( $email ) || empty( $code ) ) {
            wp_send_json_error( [ 'message' => __( 'E-mail ou código inválido.', 'llrp' ) ] );
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            wp_send_json_error( [ 'message' => __( 'Nenhuma conta encontrada para este e-mail.', 'llrp' ) ] );
        }

        $hash       = get_user_meta( $user->ID, '_llrp_login_code_hash', true );
        $expiration = get_user_meta( $user->ID, '_llrp_login_code_expiration', true );

        if ( empty( $hash ) || empty( $expiration ) ) {
            wp_send_json_error( [ 'message' => __( 'Nenhum código de login pendente encontrado. Por favor, solicite um novo código.', 'llrp' ) ] );
        }

        if ( time() > $expiration ) {
            delete_user_meta( $user->ID, '_llrp_login_code_hash' );
            delete_user_meta( $user->ID, '_llrp_login_code_expiration' );
            wp_send_json_error( [ 'message' => __( 'O código de login expirou. Por favor, solicite um novo código.', 'llrp' ) ] );
        }

        if ( ! wp_check_password( $code, $hash, $user->ID ) ) {
            wp_send_json_error( [ 'message' => __( 'O código de login está incorreto.', 'llrp' ) ] );
        }

        // Success! Log the user in.
        delete_user_meta( $user->ID, '_llrp_login_code_hash' );
        delete_user_meta( $user->ID, '_llrp_login_code_expiration' );

        wp_set_current_user( $user->ID, $user->user_login );
        wp_set_auth_cookie( $user->ID, true );

        wp_send_json_success( [ 'redirect' => wc_get_checkout_url() ] );
    }
}
Llrp_Ajax::init();
