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
        $user = get_user_by( 'email', $email );

        if ( ! $user ) {
            wp_send_json_error( [ 'message' => __( 'Usuário não encontrado.', 'llrp' ) ] );
        }

        $code = (string) wp_rand( 100000, 999999 );
        $hash = wp_hash_password( $code );
        $expiration = time() + ( 5 * MINUTE_IN_SECONDS );
        $message = sprintf( __( 'Seu código de login para %s é: %s', 'llrp' ), get_bloginfo('name'), $code );

        update_user_meta( $user->ID, '_llrp_login_code_hash', $hash );
        update_user_meta( $user->ID, '_llrp_login_code_expiration', $expiration );

        $whatsapp_enabled = get_option( 'llrp_whatsapp_enabled' );

        if ( $whatsapp_enabled && function_exists( 'joinotify_send_whatsapp_message_text' ) ) {
            $sender_phone = get_option( 'llrp_whatsapp_sender_phone' );
            $receiver_phone = get_user_meta( $user->ID, 'billing_phone', true );

            if ( $sender_phone && $receiver_phone ) {
                $response = joinotify_send_whatsapp_message_text( $sender_phone, $receiver_phone, $message );
                if ( $response === 200 ) {
                    wp_send_json_success( [ 'message' => __( 'Enviamos o código para o seu WhatsApp.', 'llrp' ) ] );
                    return; // Exit after successful WhatsApp send
                }
            }
        }

        // Fallback to email
        $subject = __( 'Seu código de login', 'llrp' );
        if ( ! wp_mail( $email, $subject, $message ) ) {
            wp_send_json_error( [ 'message' => __( 'Não foi possível enviar o código de login.', 'llrp' ) ] );
        }

        // If WhatsApp was enabled but failed, send a specific message.
        if ($whatsapp_enabled) {
            wp_send_json_success( [ 'message' => __( 'Não foi possível enviar para o WhatsApp. Verifique o número cadastrado. O código foi enviado para o seu e-mail.', 'llrp' ) ] );
        } else {
            wp_send_json_success( [ 'message' => __( 'Enviamos o código para o seu e-mail.', 'llrp' ) ] );
        }
    }

    public static function ajax_code_login() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $code  = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );
        $user = get_user_by( 'email', $email );

        if ( ! $user ) {
            wp_send_json_error( [ 'message' => __( 'Usuário não encontrado.', 'llrp' ) ] );
        }

        $hash = get_user_meta( $user->ID, '_llrp_login_code_hash', true );
        $expiration = get_user_meta( $user->ID, '_llrp_login_code_expiration', true );

        if ( empty( $hash ) || empty( $expiration ) || time() > $expiration ) {
            wp_send_json_error( [ 'message' => __( 'Código inválido ou expirado. Por favor, solicite um novo.', 'llrp' ) ] );
        }

        if ( ! wp_check_password( $code, $hash, $user->ID ) ) {
            wp_send_json_error( [ 'message' => __( 'O código de login está incorreto.', 'llrp' ) ] );
        }

        delete_user_meta( $user->ID, '_llrp_login_code_hash' );
        delete_user_meta( $user->ID, '_llrp_login_code_expiration' );
        wp_set_current_user( $user->ID, $user->user_login );
        wp_set_auth_cookie( $user->ID, true );

        wp_send_json_success( [ 'redirect' => wc_get_checkout_url() ] );
    }
}
Llrp_Ajax::init();
