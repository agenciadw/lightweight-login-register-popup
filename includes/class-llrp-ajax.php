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
        add_action( 'wp_ajax_nopriv_llrp_lostpassword', [ __CLASS__, 'ajax_lostpassword' ] );
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
        
        // Mensagem para e-mail (padrão)
        $email_message = sprintf( 'Seu código de login para %s é: %s', get_bloginfo('name'), $code );
        
        // Mensagem para WhatsApp com botão de copiar
        $whatsapp_message = sprintf( 
            "🔐 *Código de Login*\n\n" .
            "Seu código de login para *%s* é:\n" .
            "`%s`\n\n" .
            "⏰ *Válido por 5 minutos*",
            get_bloginfo('name'),
            $code
        );

        update_user_meta( $user->ID, '_llrp_login_code_hash', $hash );
        update_user_meta( $user->ID, '_llrp_login_code_expiration', $expiration );

        $whatsapp_enabled = get_option( 'llrp_whatsapp_enabled' );
        $whatsapp_interactive = get_option( 'llrp_whatsapp_interactive_buttons' );
        if ( $whatsapp_enabled && function_exists( 'joinotify_send_whatsapp_message_text' ) ) {
            $sender_phone = get_option( 'llrp_whatsapp_sender_phone' );
            $receiver_phone = get_user_meta( $user->ID, 'billing_phone', true );
            if ( $sender_phone && $receiver_phone ) {
                // Tenta enviar com botão de copiar código
                if ( $whatsapp_interactive && function_exists( 'joinotify_send_whatsapp_copy_code' ) ) {
                    $response = joinotify_send_whatsapp_copy_code( 
                        $sender_phone, 
                        $receiver_phone, 
                        $whatsapp_message,
                        $code
                    );
                    
                    if ( $response === 201 ) {
                        wp_send_json_success( [
                            'message' => __( 'Enviamos o código para o seu WhatsApp.', 'llrp' ),
                            'delivery_method' => 'whatsapp',
                        ] );
                        return;
                    }
                }
                
                // Tenta enviar com botão interativo genérico
                if ( $whatsapp_interactive && function_exists( 'joinotify_send_whatsapp_interactive_message' ) ) {
                    $buttons = [
                        [
                            'type' => 'copy_code',
                            'text' => 'Copiar código',
                            'code' => $code
                        ]
                    ];
                    
                    $response = joinotify_send_whatsapp_interactive_message( 
                        $sender_phone, 
                        $receiver_phone, 
                        $whatsapp_message,
                        $buttons
                    );
                    
                    if ( $response === 201 ) {
                        wp_send_json_success( [
                            'message' => __( 'Enviamos o código para o seu WhatsApp.', 'llrp' ),
                            'delivery_method' => 'whatsapp',
                        ] );
                        return;
                    }
                }
                
                // Fallback para mensagem normal se botões não estiverem disponíveis
                $response = joinotify_send_whatsapp_message_text( $sender_phone, $receiver_phone, $whatsapp_message );
                if ( $response === 201 ) {
                    wp_send_json_success( [
                        'message' => __( 'Enviamos o código para o seu WhatsApp.', 'llrp' ),
                        'delivery_method' => 'whatsapp',
                    ] );
                    return;
                }
            }
        }

        wp_mail( $user->user_email, 'Seu código de login', $email_message );
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

        if ( ! is_email( $identifier ) ) {
            $sanitized_identifier = preg_replace( '/[^0-9]/', '', $identifier );
            if ( strlen( $sanitized_identifier ) === 11 && ! self::is_cpf_valid( $sanitized_identifier ) ) {
                wp_send_json_error( [ 'message' => __( 'CPF inválido.', 'llrp' ) ] );
            } elseif ( strlen( $sanitized_identifier ) === 14 && ! self::is_cnpj_valid( $sanitized_identifier ) ) {
                wp_send_json_error( [ 'message' => __( 'CNPJ inválido.', 'llrp' ) ] );
            }
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

    public static function ajax_lostpassword() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'E-mail inválido.', 'llrp' ) ] );
        }
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            wp_send_json_error( [ 'message' => __( 'Nenhuma conta encontrada para esse e-mail.', 'llrp' ) ] );
        }
        $reset_key = get_password_reset_key( $user );
        if ( is_wp_error( $reset_key ) ) {
            wp_send_json_error( [ 'message' => $reset_key->get_error_message() ] );
        }
        if ( class_exists( 'WooCommerce' ) && method_exists( WC(), 'mailer' ) ) {
            $mailer = WC()->mailer();
            $emails = $mailer->get_emails();
            if ( ! empty( $emails['WC_Email_Customer_Reset_Password'] ) ) {
                $reset_email = $emails['WC_Email_Customer_Reset_Password'];
                $reset_email->trigger( $user->user_login, $reset_key );
            }
        }
        wp_send_json_success( [ 'message' => __( 'Enviamos um link de redefinição para o seu e-mail.', 'llrp' ) ] );
    }

    private static function is_cpf_valid( $cpf ) {
        $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
        if ( strlen( $cpf ) != 11 ) {
            return false;
        }
        if ( preg_match( '/(\d)\1{10}/', $cpf ) ) {
            return false;
        }
        for ( $t = 9; $t < 11; $t++ ) {
            for ( $d = 0, $c = 0; $c < $t; $c++ ) {
                $d += $cpf[$c] * ( ( $t + 1 ) - $c );
            }
            $d = ( ( 10 * $d ) % 11 ) % 10;
            if ( $cpf[$c] != $d ) {
                return false;
            }
        }
        return true;
    }

    private static function is_cnpj_valid( $cnpj ) {
        $cnpj = preg_replace( '/[^0-9]/', '', $cnpj );
        if ( strlen( $cnpj ) != 14 ) {
            return false;
        }
        if ( preg_match( '/(\d)\1{13}/', $cnpj ) ) {
            return false;
        }
        for ( $i = 0, $j = 5, $soma = 0; $i < 12; $i++ ) {
            $soma += $cnpj[$i] * $j;
            $j = ( $j == 2 ) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ( $cnpj[12] != ( $resto < 2 ? 0 : 11 - $resto ) ) {
            return false;
        }
        for ( $i = 0, $j = 6, $soma = 0; $i < 13; $i++ ) {
            $soma += $cnpj[$i] * $j;
            $j = ( $j == 2 ) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj[13] == ( $resto < 2 ? 0 : 11 - $resto );
    }
}
Llrp_Ajax::init();
