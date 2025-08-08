<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Llrp_Ajax {
    public static function init() {
        add_action( 'wp_ajax_nopriv_llrp_check_user', [ __CLASS__, 'ajax_check_user' ] );
        add_action( 'wp_ajax_nopriv_llrp_send_code', [ __CLASS__, 'ajax_send_code' ] );
        add_action( 'wp_ajax_nopriv_llrp_verify_code_and_login', [ __CLASS__, 'ajax_verify_code_and_login' ] );
        add_action( 'wp_ajax_nopriv_llrp_login_with_password', [ __CLASS__, 'ajax_login_with_password' ] );
        add_action( 'wp_ajax_nopriv_llrp_register_with_password', [ __CLASS__, 'ajax_register_with_password' ] );
    }

    public static function ajax_check_user() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'E-mail inválido.', 'llrp' ) ] );
        }

        $user = get_user_by( 'email', $email );
        $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

        if ( $user ) {
            $billing_phone = get_user_meta( $user->ID, 'billing_phone', true );
            wp_send_json_success( [
                'exists' => true,
                'has_phone' => ! empty( $billing_phone ),
                'avatar' => get_avatar_url( $user->ID, [ 'size' => 140 ] ),
                'username' => $user->display_name ?: $user->user_login,
            ] );
        } else {
            wp_send_json_success( [
                'exists' => false,
                'has_phone' => ! empty( $phone ),
            ] );
        }
    }

    public static function ajax_send_code() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );

        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $method = sanitize_text_field( wp_unslash( $_POST['method'] ?? 'email' ) );

        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'E-mail inválido.', 'llrp' ) ] );
        }

        // Rate limiting
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
        $message = sprintf( __( 'Seu código de login para %s é: %s. O código expira em 5 minutos.', 'llrp' ), get_bloginfo( 'name' ), $code );

        $user = get_user_by( 'email', $email );
        if ( $user ) {
            update_user_meta( $user->ID, '_llrp_login_code_hash', $hash );
            update_user_meta( $user->ID, '_llrp_login_code_expiration', $expiration );
        } else {
            set_transient( 'llrp_code_hash_' . md5($email), $hash, 5 * MINUTE_IN_SECONDS );
            set_transient( 'llrp_code_expiration_' . md5($email), $expiration, 5 * MINUTE_IN_SECONDS );
        }

        // Send the code
        if ( $method === 'whatsapp' && function_exists( 'joinotify_send_whatsapp_message_text' ) ) {
            $sender_phone = get_option( 'llrp_whatsapp_sender_phone' );
            $receiver_phone = $user ? get_user_meta( $user->ID, 'billing_phone', true ) : $phone;

            if ( $sender_phone && $receiver_phone ) {
                $response = joinotify_send_whatsapp_message_text( $sender_phone, $receiver_phone, $message );
                if ( $response === 200 ) {
                    wp_send_json_success( [ 'message' => __( 'Enviamos o código para o seu WhatsApp.', 'llrp' ) ] );
                }
            }
        }

        // Fallback or default to email
        $subject = __( 'Seu código de login', 'llrp' );
        if ( ! wp_mail( $email, $subject, $message ) ) {
            wp_send_json_error( [ 'message' => __( 'Não foi possível enviar o código de login.', 'llrp' ) ] );
        }
        wp_send_json_success( [ 'message' => __( 'Enviamos o código para o seu e-mail.', 'llrp' ) ] );
    }

    public static function ajax_verify_code_and_login() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );

        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $code  = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );
        $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

        if ( ! is_email( $email ) || empty( $code ) ) {
            wp_send_json_error( [ 'message' => __( 'E-mail ou código inválido.', 'llrp' ) ] );
        }

        $user = get_user_by( 'email', $email );

        if ( $user ) { // Existing user
            $hash = get_user_meta( $user->ID, '_llrp_login_code_hash', true );
            $expiration = get_user_meta( $user->ID, '_llrp_login_code_expiration', true );
        } else { // New user
            $hash = get_transient( 'llrp_code_hash_' . md5($email) );
            $expiration = get_transient( 'llrp_code_expiration_' . md5($email) );
        }

        if ( empty( $hash ) || empty( $expiration ) ) {
            wp_send_json_error( [ 'message' => __( 'Nenhum código de login pendente encontrado. Por favor, solicite um novo código.', 'llrp' ) ] );
        }

        if ( time() > $expiration ) {
            if ($user) {
                delete_user_meta( $user->ID, '_llrp_login_code_hash' );
                delete_user_meta( $user->ID, '_llrp_login_code_expiration' );
            } else {
                delete_transient( 'llrp_code_hash_' . md5($email) );
                delete_transient( 'llrp_code_expiration_' . md5($email) );
            }
            wp_send_json_error( [ 'message' => __( 'O código de login expirou. Por favor, solicite um novo código.', 'llrp' ) ] );
        }

        $user_id_for_check = $user ? $user->ID : 0;
        if ( ! wp_check_password( $code, $hash, $user_id_for_check ) ) {
            wp_send_json_error( [ 'message' => __( 'O código de login está incorreto.', 'llrp' ) ] );
        }

        // Success!
        if ($user) { // Log in existing user
            delete_user_meta( $user->ID, '_llrp_login_code_hash' );
            delete_user_meta( $user->ID, '_llrp_login_code_expiration' );
            wp_set_current_user( $user->ID, $user->user_login );
            wp_set_auth_cookie( $user->ID, true );
        } else { // Register and log in new user
            delete_transient( 'llrp_code_hash_' . md5($email) );
            delete_transient( 'llrp_code_expiration_' . md5($email) );
            $user_id = wc_create_new_customer( $email, '', '' ); // Create user without password
            if ( is_wp_error( $user_id ) ) {
                wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
            }
            if ( ! empty( $phone ) ) {
                update_user_meta( $user_id, 'billing_phone', $phone );
            }
            wc_set_customer_auth_cookie( $user_id );
        }

        wp_send_json_success( [ 'redirect' => wc_get_checkout_url() ] );
    }

    public static function ajax_login_with_password() {
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

    public static function ajax_register_with_password() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
        $phone    = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

        if ( ! is_email( $email ) ) {
            wp_send_json_error([ 'message' => __( 'E-mail inválido.', 'llrp' ) ]);
        }
        if ( email_exists( $email ) ) {
            wp_send_json_error([ 'message' => __( 'Este e-mail já está registrado.', 'llrp' ) ]);
        }
        if ( strlen( $password ) < 8 ) {
            wp_send_json_error([ 'message' => __( 'A senha deve ter pelo menos 8 caracteres.', 'llrp' ) ]);
        }

        $user_id = wc_create_new_customer( $email, '', $password );
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error([ 'message' => $user_id->get_error_message() ]);
        }
        if ( ! empty( $phone ) ) {
            update_user_meta( $user_id, 'billing_phone', $phone );
        }
        wc_set_customer_auth_cookie( $user_id );
        wp_send_json_success([ 'redirect' => wc_get_checkout_url() ]);
    }
}
Llrp_Ajax::init();
