<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Llrp_Ajax {
    public static function init() {
        add_action( 'wp_ajax_nopriv_llrp_check_user', [ __CLASS__, 'ajax_check_user' ] );
        add_action( 'wp_ajax_nopriv_llrp_send_login_code', [ __CLASS__, 'ajax_send_login_code' ] );
        add_action( 'wp_ajax_nopriv_llrp_code_login', [ __CLASS__, 'ajax_code_login' ] );
        add_action( 'wp_ajax_nopriv_llrp_login_with_password', [ __CLASS__, 'ajax_login_with_password' ] );
        add_action( 'wp_ajax_nopriv_llrp_register', [ __CLASS__, 'ajax_register' ] );
    }

    public static function ajax_check_user() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) );
        if ( empty( $identifier ) ) {
            wp_send_json_error( [ 'message' => __( 'Por favor, preencha o campo.', 'llrp' ) ] );
        }

        $user = self::get_user_by_identifier( $identifier );

        if ( $user ) {
            wp_send_json_success( [
                'exists'   => true,
                'username' => $user->display_name ?: $user->user_login,
                'email'    => $user->user_email,
                'avatar'   => get_avatar_url( $user->ID, [ 'size' => 140 ] ),
                'has_phone' => !empty(get_user_meta($user->ID, 'billing_phone', true)),
            ] );
        } else {
            $is_email = is_email($identifier);
            wp_send_json_success( [
                'exists'      => false,
                'email'       => $is_email ? $identifier : '',
                'needs_email' => !$is_email,
            ] );
        }
    }

    public static function ajax_send_login_code() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) );
        $user = self::get_user_by_identifier($identifier);

        if ( ! $user ) {
            wp_send_json_error( [ 'message' => __( 'Usuário não encontrado.', 'llrp' ) ] );
        }

        $code = (string) wp_rand( 100000, 999999 );
        $hash = wp_hash_password( $code );
        $expiration = time() + ( 5 * MINUTE_IN_SECONDS );
        $message = sprintf( 'Seu código de login para %s é: %s', get_bloginfo('name'), $code );

        update_user_meta( $user->ID, '_llrp_login_code_hash', $hash );
        update_user_meta( $user->ID, '_llrp_login_code_expiration', $expiration );

        $whatsapp_enabled = get_option( 'llrp_whatsapp_enabled' );
        if ( $whatsapp_enabled && function_exists( 'joinotify_send_whatsapp_message_text' ) ) {
            $sender_phone = get_option( 'llrp_whatsapp_sender_phone' );
            $receiver_phone = get_user_meta( $user->ID, 'billing_phone', true );
            if ( $sender_phone && $receiver_phone ) {
                $response = joinotify_send_whatsapp_message_text( $sender_phone, $receiver_phone, $message );
                if ( $response === 201 ) {
                    wp_send_json_success( [
                        'message' => __( 'Enviamos o código para o seu WhatsApp.', 'llrp' ),
                        'delivery_method' => 'whatsapp',
                    ] );
                    return;
                }
            }
        }

        wp_mail( $user->user_email, 'Seu código de login', $message );
        wp_send_json_success( [
            'message' => __( 'Enviamos o código para o seu e-mail.', 'llrp' ),
            'delivery_method' => 'email',
        ] );
    }

    public static function ajax_code_login() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) );
        $code  = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );
        $user = self::get_user_by_identifier($identifier);

        if ( ! $user ) {
            wp_send_json_error( [ 'message' => __( 'Usuário não encontrado.', 'llrp' ) ] );
        }

        $hash = get_user_meta( $user->ID, '_llrp_login_code_hash', true );
        $expiration = get_user_meta( $user->ID, '_llrp_login_code_expiration', true );

        if ( empty( $hash ) || empty( $expiration ) || time() > $expiration ) {
            wp_send_json_error( [ 'message' => __( 'Código inválido ou expirado.', 'llrp' ) ] );
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

    public static function ajax_login_with_password() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) );
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

        $user = self::get_user_by_identifier($identifier);

        if( !$user ) {
            wp_send_json_error([ 'message' => __( 'Credenciais inválidas.', 'llrp' ) ]);
        }

        $creds = [
            'user_login'    => $user->user_login,
            'user_password' => $password,
            'remember'      => true,
        ];
        $user_signon = wp_signon( $creds, is_ssl() );
        if ( is_wp_error( $user_signon ) ) {
            wp_send_json_error([ 'message' => __( 'Credenciais inválidas.', 'llrp' ) ]);
        }
        wp_send_json_success([ 'redirect' => wc_get_checkout_url() ]);
    }

    public static function ajax_register() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) );
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

        if ( empty( $email ) ) {
            $email = $identifier;
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error([ 'message' => __( 'Para se cadastrar, por favor, use um e-mail válido.', 'llrp' ) ]);
        }
        if ( email_exists( $email ) ) {
            wp_send_json_error([ 'message' => __( 'Este e-mail já está registrado.', 'llrp' ) ]);
        }
        if ( strlen( $password ) < 8 ) {
            wp_send_json_error([ 'message' => __( 'A senha deve ter pelo menos 8 caracteres.', 'llrp' ) ]);
        }

        try {
            $user_id = wc_create_new_customer( $email, '', $password );
            if ( is_wp_error( $user_id ) ) {
                wp_send_json_error([ 'message' => $user_id->get_error_message() ]);
            }

            if ( ! is_email( $identifier ) ) {
                $sanitized_identifier = preg_replace( '/[^0-9]/', '', $identifier );
                if ( strlen( $sanitized_identifier ) === 11 ) {
                    update_user_meta( $user_id, 'billing_cpf', $sanitized_identifier );
                } elseif ( strlen( $sanitized_identifier ) === 14 ) {
                    update_user_meta( $user_id, 'billing_cnpj', $sanitized_identifier );
                }
            }
        } catch (Error $e) {
            if ( email_exists( $email ) ) {
                wp_send_json_error([ 'message' => __( 'Seu usuário foi criado, mas um plugin de terceiros causou um erro. Por favor, tente fazer o login.', 'llrp' ) ]);
            } else {
                wp_send_json_error([ 'message' => __( 'Ocorreu um erro desconhecido durante o registro.', 'llrp' ) ]);
            }
        }

        wc_set_customer_auth_cookie( $user_id );
        wp_send_json_success([ 'redirect' => wc_get_checkout_url() ]);
    }

    private static function get_user_by_identifier( $identifier ) {
        if ( is_email( $identifier ) ) {
            return get_user_by( 'email', $identifier );
        }
        $sanitized_identifier = preg_replace( '/[^0-9]/', '', $identifier );
        if (empty($sanitized_identifier)) {
            return null;
        }
        $meta_query = [ 'relation' => 'OR' ];
        $cpf_enabled = get_option( 'llrp_cpf_login_enabled' );
        $cnpj_enabled = get_option( 'llrp_cnpj_login_enabled' );

        if ( $cpf_enabled ) {
            $meta_query[] = [ 'key' => 'billing_cpf', 'value' => $sanitized_identifier, 'compare' => 'LIKE' ];
            $meta_query[] = [ 'key' => 'billing_cpf', 'value' => $identifier, 'compare' => 'LIKE' ];
        }
        if ( $cnpj_enabled ) {
            $meta_query[] = [ 'key' => 'billing_cnpj', 'value' => $sanitized_identifier, 'compare' => 'LIKE' ];
            $meta_query[] = [ 'key' => 'billing_cnpj', 'value' => $identifier, 'compare' => 'LIKE' ];
        }

        if ( count( $meta_query ) === 1 ) {
            return null;
        }

        $user_query = new WP_User_Query( [
            'meta_query' => $meta_query,
            'number' => 1,
        ] );
        $users = $user_query->get_results();
        return ! empty( $users ) ? $users[0] : null;
    }
}
Llrp_Ajax::init();
