<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Llrp_Ajax {
    
     /**
      * Safe logging function - silent operation for security
      */
     private static function safe_log($message, $data = null) {
         // Silent operation for security - no logs
         return;
     }
     
     /**
      * Valida captcha (Turnstile ou reCAPTCHA)
      * @return bool|WP_Error True se válido, WP_Error se inválido
      */
     private static function validate_captcha() {
         $captcha_type = get_option( 'llrp_captcha_type', 'none' );
         
         // Se não está configurado, retorna sucesso
         if ( $captcha_type === 'none' || empty( $captcha_type ) ) {
             return true;
         }
         
         // Verifica se o token foi enviado
         $captcha_token = sanitize_text_field( wp_unslash( $_POST['captcha_token'] ?? '' ) );
         
         if ( empty( $captcha_token ) ) {
             return new WP_Error( 'captcha_missing', __( 'Por favor, complete a verificação de segurança.', 'llrp' ) );
         }
         
         // Valida de acordo com o tipo
         if ( $captcha_type === 'turnstile' ) {
             return self::validate_turnstile( $captcha_token );
         } elseif ( in_array( $captcha_type, ['recaptcha_v2_checkbox', 'recaptcha_v2_invisible', 'recaptcha_v3'] ) ) {
             return self::validate_recaptcha( $captcha_token, $captcha_type );
         }
         
         return true;
     }
     
     /**
      * Valida Cloudflare Turnstile
      */
     private static function validate_turnstile( $token ) {
         $secret_key = get_option( 'llrp_turnstile_secret_key' );
         
         if ( empty( $secret_key ) ) {
             return new WP_Error( 'captcha_config', __( 'Turnstile não está configurado corretamente.', 'llrp' ) );
         }
         
         $response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
             'body' => [
                 'secret' => $secret_key,
                 'response' => $token,
                 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
             ],
             'timeout' => 15,
         ] );
         
         if ( is_wp_error( $response ) ) {
             return new WP_Error( 'captcha_error', __( 'Erro ao validar captcha. Tente novamente.', 'llrp' ) );
         }
         
         $body = json_decode( wp_remote_retrieve_body( $response ), true );
         
         if ( empty( $body['success'] ) ) {
             return new WP_Error( 'captcha_failed', __( 'Verificação de segurança falhou. Tente novamente.', 'llrp' ) );
         }
         
         return true;
     }
     
     /**
      * Valida Google reCAPTCHA
      */
     private static function validate_recaptcha( $token, $type ) {
         $secret_key = get_option( 'llrp_recaptcha_secret_key' );
         
         if ( empty( $secret_key ) ) {
             return new WP_Error( 'captcha_config', __( 'Erro de configuração: reCAPTCHA Secret Key não está configurada.', 'llrp' ) );
         }
         
         $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
             'body' => [
                 'secret' => $secret_key,
                 'response' => $token,
                 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
             ],
             'timeout' => 15,
         ] );
         
         if ( is_wp_error( $response ) ) {
             return new WP_Error( 'captcha_error', __( 'Erro ao validar captcha. Tente novamente.', 'llrp' ) );
         }
         
         $body = json_decode( wp_remote_retrieve_body( $response ), true );
         
         if ( empty( $body['success'] ) ) {
             $error_codes = isset( $body['error-codes'] ) ? $body['error-codes'] : ['unknown'];
             $error_string = implode( ', ', $error_codes );
             
             // Mensagens mais específicas baseadas no código de erro
             $error_messages = [
                 'missing-input-secret' => __( 'Erro de configuração: Secret key não fornecida', 'llrp' ),
                 'invalid-input-secret' => __( 'Erro de configuração: Secret key inválida', 'llrp' ),
                 'missing-input-response' => __( 'Token do captcha não recebido', 'llrp' ),
                 'invalid-input-response' => __( 'Token do captcha inválido ou expirado', 'llrp' ),
                 'bad-request' => __( 'Requisição malformada', 'llrp' ),
                 'timeout-or-duplicate' => __( 'Token expirado ou já usado', 'llrp' ),
             ];
             
             $user_message = __( 'Verificação de segurança falhou.', 'llrp' );
             if ( isset( $error_codes[0] ) && isset( $error_messages[ $error_codes[0] ] ) ) {
                 $user_message = $error_messages[ $error_codes[0] ];
             }
             
             // Em desenvolvimento, mostra o código de erro
             if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                 $user_message .= ' [' . $error_string . ']';
             }
             
             return new WP_Error( 'captcha_failed', $user_message );
         }
         
         // Para reCAPTCHA v3, verifica o score
         if ( $type === 'recaptcha_v3' ) {
             $min_score = floatval( get_option( 'llrp_recaptcha_v3_score', 0.5 ) );
             $score = floatval( $body['score'] ?? 0 );
             
             if ( $score < $min_score ) {
                 $message = __( 'Verificação de segurança falhou. Score muito baixo.', 'llrp' );
                 if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                     $message .= ' [Score: ' . $score . ' / Mínimo: ' . $min_score . ']';
                 }
                 return new WP_Error( 'captcha_score_low', $message );
             }
         }
         
         return true;
     }
    
    public static function init() {
        // Hooks para usuários não logados
        add_action( 'wp_ajax_nopriv_llrp_check_user', [ __CLASS__, 'ajax_check_user' ] );
        add_action( 'wp_ajax_nopriv_llrp_send_login_code', [ __CLASS__, 'ajax_send_login_code' ] );
        add_action( 'wp_ajax_nopriv_llrp_code_login', [ __CLASS__, 'ajax_code_login' ] );
        add_action( 'wp_ajax_nopriv_llrp_login_with_password', [ __CLASS__, 'ajax_login_with_password' ] );
        add_action( 'wp_ajax_nopriv_llrp_register', [ __CLASS__, 'ajax_register' ] );
        add_action( 'wp_ajax_nopriv_llrp_lostpassword', [ __CLASS__, 'ajax_lostpassword' ] );
        add_action( 'wp_ajax_nopriv_llrp_google_login', [ __CLASS__, 'ajax_google_login' ] );
        add_action( 'wp_ajax_nopriv_llrp_facebook_login', [ __CLASS__, 'ajax_facebook_login' ] );
        add_action( 'wp_ajax_nopriv_llrp_check_login_status', [ __CLASS__, 'ajax_check_login_status' ] );
        
        // Hooks para usuários logados também (para verificação de status)
        add_action( 'wp_ajax_llrp_check_login_status', [ __CLASS__, 'ajax_check_login_status' ] );
        add_action( 'wp_ajax_nopriv_llrp_refresh_nonce', [ __CLASS__, 'ajax_refresh_nonce' ] );
        add_action( 'wp_ajax_llrp_refresh_nonce', [ __CLASS__, 'ajax_refresh_nonce' ] );
        
        // CRITICAL: Direct checkout autofill endpoint
        add_action( 'wp_ajax_llrp_get_checkout_user_data', [ __CLASS__, 'ajax_get_checkout_user_data' ] );
    }

    public static function ajax_check_user() {
        // Verificação de nonce mais flexível
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'llrp_nonce' ) ) {
            if ( ! wp_verify_nonce( $nonce, 'woocommerce-process_checkout' ) ) {
                self::safe_log( 'LLRP: Nonce verification failed for check_user. Nonce: ' . $nonce );
                wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
            }
        }
        
        // Valida captcha
        $captcha_validation = self::validate_captcha();
        if ( is_wp_error( $captcha_validation ) ) {
            wp_send_json_error( [ 'message' => $captcha_validation->get_error_message() ] );
        }
        
        $identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) );
        if ( empty( $identifier ) ) {
            wp_send_json_error( [ 'message' => __( 'Por favor, preencha o campo.', 'llrp' ) ] );
        }

        $user = self::get_user_by_identifier( $identifier );

        if ( $user ) {
            // Verificar status da senha
            $password_status = Llrp_Password_Expiration::check_password_status( $user->ID );
            
            $response_data = [
                'exists'   => true,
                'username' => $user->display_name ?: $user->user_login,
                'email'    => $user->user_email,
                'avatar'   => get_avatar_url( $user->ID, [ 'size' => 140 ] ),
                'has_phone' => !empty(get_user_meta($user->ID, 'billing_phone', true)),
                'password_expired' => $password_status['expired'],
                'password_warning' => $password_status['warning'],
            ];
            
            if ( $password_status['warning'] && isset( $password_status['days_until_expiration'] ) ) {
                $response_data['password_warning_days'] = $password_status['days_until_expiration'];
            }
            
            if ( $password_status['expired'] ) {
                if ( $password_status['reason'] === 'time' ) {
                    $response_data['password_expired_reason'] = __( 'Sua senha expirou e precisa ser trocada.', 'llrp' );
                } elseif ( $password_status['reason'] === 'inactivity' ) {
                    $response_data['password_expired_reason'] = __( 'Sua senha expirou por inatividade e precisa ser trocada.', 'llrp' );
                } elseif ( $password_status['reason'] === 'imported' ) {
                    $response_data['password_expired_reason'] = __( 'Por segurança, você precisa criar uma nova senha antes de continuar.', 'llrp' );
                }
            }
            
            wp_send_json_success( $response_data );
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
        // Verificação de nonce mais flexível
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'llrp_nonce' ) ) {
            if ( ! wp_verify_nonce( $nonce, 'woocommerce-process_checkout' ) ) {
                self::safe_log( 'LLRP: Nonce verification failed for send_login_code. Nonce: ' . $nonce );
                wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
            }
        }
        
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
        
        // Primeira mensagem WhatsApp: Explicação sobre o código
        $whatsapp_message_1 = sprintf( 
            "🔐 *Código de Login*\n\n" .
            "Segue seu código para efetuar login em *%s*, seu código é válido por 5 minutos",
            get_bloginfo('name')
        );
        
        // Segunda mensagem WhatsApp: Apenas o código (para facilitar cópia)
        $whatsapp_message_2 = $code;

        update_user_meta( $user->ID, '_llrp_login_code_hash', $hash );
        update_user_meta( $user->ID, '_llrp_login_code_expiration', $expiration );

        $whatsapp_enabled = get_option( 'llrp_whatsapp_enabled' );
        $whatsapp_interactive = get_option( 'llrp_whatsapp_interactive_buttons' );
        if ( $whatsapp_enabled && function_exists( 'joinotify_send_whatsapp_message_text' ) ) {
            $sender_phone = get_option( 'llrp_whatsapp_sender_phone' );
            $receiver_phone = get_user_meta( $user->ID, 'billing_phone', true );
            if ( $sender_phone && $receiver_phone ) {
                // Tenta enviar com botão de copiar código (duas mensagens separadas)
                if ( $whatsapp_interactive && function_exists( 'joinotify_send_whatsapp_copy_code' ) ) {
                    // Primeira mensagem: Explicação
                    $response1 = joinotify_send_whatsapp_message_text( 
                        $sender_phone, 
                        $receiver_phone, 
                        $whatsapp_message_1
                    );
                    
                    // Segunda mensagem: Código com botão de copiar
                    if ( $response1 === 201 ) {
                        // Pequeno delay para garantir ordem das mensagens
                        usleep(500000); // 0.5 segundo
                        
                        $response2 = joinotify_send_whatsapp_copy_code( 
                            $sender_phone, 
                            $receiver_phone, 
                            $whatsapp_message_2,
                            $code
                        );
                        
                        if ( $response2 === 201 ) {
                            wp_send_json_success( [
                                'message' => __( 'Enviamos o código para o seu WhatsApp.', 'llrp' ),
                                'delivery_method' => 'whatsapp',
                            ] );
                            return;
                        }
                    }
                }
                
                // Tenta enviar com botão interativo genérico (duas mensagens separadas)
                if ( $whatsapp_interactive && function_exists( 'joinotify_send_whatsapp_interactive_message' ) ) {
                    // Primeira mensagem: Explicação
                    $response1 = joinotify_send_whatsapp_message_text( 
                        $sender_phone, 
                        $receiver_phone, 
                        $whatsapp_message_1
                    );
                    
                    // Segunda mensagem: Código com botão interativo
                    if ( $response1 === 201 ) {
                        // Pequeno delay para garantir ordem das mensagens
                        usleep(500000); // 0.5 segundo
                        
                        $buttons = [
                            [
                                'type' => 'copy_code',
                                'text' => 'Copiar código',
                                'code' => $code
                            ]
                        ];
                        
                        $response2 = joinotify_send_whatsapp_interactive_message( 
                            $sender_phone, 
                            $receiver_phone, 
                            $whatsapp_message_2,
                            $buttons
                        );
                        
                        if ( $response2 === 201 ) {
                            wp_send_json_success( [
                                'message' => __( 'Enviamos o código para o seu WhatsApp.', 'llrp' ),
                                'delivery_method' => 'whatsapp',
                            ] );
                            return;
                        }
                    }
                }
                
                // Fallback para mensagem normal se botões não estiverem disponíveis (duas mensagens separadas)
                // Primeira mensagem: Explicação
                $response1 = joinotify_send_whatsapp_message_text( $sender_phone, $receiver_phone, $whatsapp_message_1 );
                if ( $response1 === 201 ) {
                    // Pequeno delay para garantir ordem das mensagens
                    usleep(500000); // 0.5 segundo
                    
                    // Segunda mensagem: Apenas o código
                    $response2 = joinotify_send_whatsapp_message_text( $sender_phone, $receiver_phone, $whatsapp_message_2 );
                    if ( $response2 === 201 ) {
                        wp_send_json_success( [
                            'message' => __( 'Enviamos o código para o seu WhatsApp.', 'llrp' ),
                            'delivery_method' => 'whatsapp',
                        ] );
                        return;
                    }
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
        // Verificação de nonce mais flexível
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'llrp_nonce' ) ) {
            if ( ! wp_verify_nonce( $nonce, 'woocommerce-process_checkout' ) ) {
                self::safe_log( 'LLRP: Nonce verification failed for code_login. Nonce: ' . $nonce );
                wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
            }
        }
        
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
        // Authentication process (sensitive details only in debug mode)
        self::safe_log('🛒 LLRP: About to authenticate user');
        
        wp_set_current_user( $user->ID, $user->user_login );
        wp_set_auth_cookie( $user->ID, true );
        
        // Atualizar data do último login
        update_user_meta( $user->ID, '_llrp_last_login', time() );
        
        self::safe_log('🛒 LLRP: User authenticated successfully');
        
        // Verificar se a senha expirou
        $password_status = Llrp_Password_Expiration::check_password_status( $user->ID );

        // Trigger cart fragments update for Fluid Checkout compatibility
        self::trigger_cart_fragments_update();

        // CRITICAL: Mark this as popup login to prevent force autofill conflict
        if ( ! session_id() && ! headers_sent() ) {
            @session_start();
        }
        if ( session_id() ) {
            $_SESSION['llrp_popup_login_timestamp'] = time();
        }
        
        // SMART REDIRECT: Based on referrer or current context
        $redirect_url = self::get_smart_redirect_url();
        
        $response_data = [ 
            'redirect' => $redirect_url,
            'user_logged_in' => true,
            'cart_fragments' => self::get_cart_fragments(),
            'user_data' => self::get_user_checkout_data($user->ID),
            'password_expired' => $password_status['expired'],
        ];
        
        // Se a senha expirou, adicionar informações extras
        if ( $password_status['expired'] ) {
            if ( $password_status['reason'] === 'time' ) {
                $response_data['password_expired_message'] = __( 'Sua senha expirou. Você será redirecionado para trocar sua senha.', 'llrp' );
            } elseif ( $password_status['reason'] === 'inactivity' ) {
                $response_data['password_expired_message'] = __( 'Sua senha expirou por inatividade. Você será redirecionado para trocar sua senha.', 'llrp' );
            } elseif ( $password_status['reason'] === 'imported' ) {
                $response_data['password_expired_message'] = __( 'Por segurança, você precisa criar uma nova senha. Você será redirecionado.', 'llrp' );
            }
            // Redirecionar para a página de editar conta
            $response_data['redirect'] = wc_get_account_endpoint_url( 'edit-account' );
        }
        
        wp_send_json_success( $response_data );
    }

    public static function ajax_login_with_password() {
        // Verificação de nonce mais flexível
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'llrp_nonce' ) ) {
            if ( ! wp_verify_nonce( $nonce, 'woocommerce-process_checkout' ) ) {
                self::safe_log( 'LLRP: Nonce verification failed for login_with_password. Nonce: ' . $nonce );
                wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
            }
        }
        
        // Valida captcha
        $captcha_validation = self::validate_captcha();
        if ( is_wp_error( $captcha_validation ) ) {
            wp_send_json_error( [ 'message' => $captcha_validation->get_error_message() ] );
        }
        
        $identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) );
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

        $user = self::get_user_by_identifier($identifier);

        if( !$user ) {
            wp_send_json_error([ 'message' => __( 'Credenciais inválidas.', 'llrp' ) ]);
        }

        // Password login process (sensitive details only in debug mode)
        self::safe_log('🛒 LLRP: About to login with password');

        $creds = [
            'user_login'    => $user->user_login,
            'user_password' => $password,
            'remember'      => true,
        ];
        $user_signon = wp_signon( $creds, is_ssl() );
        if ( is_wp_error( $user_signon ) ) {
            wp_send_json_error([ 'message' => __( 'Credenciais inválidas.', 'llrp' ) ]);
        }
        
        // Atualizar data do último login
        update_user_meta( $user_signon->ID, '_llrp_last_login', time() );
        
        self::safe_log('🛒 LLRP: Password login successful');
        
        // Verificar se a senha expirou
        $password_status = Llrp_Password_Expiration::check_password_status( $user_signon->ID );

        // Trigger cart fragments update for Fluid Checkout compatibility
        self::trigger_cart_fragments_update();

        // CRITICAL: Mark this as popup login to prevent force autofill conflict
        if ( ! session_id() && ! headers_sent() ) {
            @session_start();
        }
        if ( session_id() ) {
            $_SESSION['llrp_popup_login_timestamp'] = time();
        }
        
        // SMART REDIRECT: Based on referrer or current context
        $redirect_url = self::get_smart_redirect_url();
        
        $response_data = [ 
            'redirect' => $redirect_url,
            'user_logged_in' => true,
            'cart_fragments' => self::get_cart_fragments(),
            'user_data' => self::get_user_checkout_data($user_signon->ID),
            'password_expired' => $password_status['expired'],
        ];
        
        // Se a senha expirou, adicionar informações extras
        if ( $password_status['expired'] ) {
            if ( $password_status['reason'] === 'time' ) {
                $response_data['password_expired_message'] = __( 'Sua senha expirou. Você será redirecionado para trocar sua senha.', 'llrp' );
            } elseif ( $password_status['reason'] === 'inactivity' ) {
                $response_data['password_expired_message'] = __( 'Sua senha expirou por inatividade. Você será redirecionado para trocar sua senha.', 'llrp' );
            } elseif ( $password_status['reason'] === 'imported' ) {
                $response_data['password_expired_message'] = __( 'Por segurança, você precisa criar uma nova senha. Você será redirecionado.', 'llrp' );
            }
            // Redirecionar para a página de editar conta
            $response_data['redirect'] = wc_get_account_endpoint_url( 'edit-account' );
        }
        
        wp_send_json_success( $response_data );
    }

    public static function ajax_register() {
        // Direct WordPress user creation without any nonce validation
        if ( ! self::validate_direct_registration_request() ) {
            self::safe_log( 'LLRP: Direct registration validation failed. IP: ' . self::get_client_ip() );
            wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
        }
        
        // Valida captcha
        $captcha_validation = self::validate_captcha();
        if ( is_wp_error( $captcha_validation ) ) {
            wp_send_json_error( [ 'message' => $captcha_validation->get_error_message() ] );
        }
        
        // Temporarily disable any potential nonce checks from other plugins
        add_filter( 'wp_verify_nonce', '__return_true', 999, 2 );
        
        // Ensure we're in the right context
        if ( ! defined( 'DOING_AJAX' ) ) {
            define( 'DOING_AJAX', true );
        }
        
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

        // Use direct WordPress user creation to avoid any nonce dependencies
        try {
            // Validate email and password
            if ( ! is_email( $email ) ) {
                wp_send_json_error([ 'message' => __( 'Por favor, insira um endereço de e-mail válido.', 'llrp' ) ]);
            }

            if ( email_exists( $email ) ) {
                wp_send_json_error([ 'message' => __( 'Uma conta já está registrada com seu endereço de e-mail. Faça login.', 'llrp' ) ]);
            }

            if ( empty( $password ) ) {
                wp_send_json_error([ 'message' => __( 'Por favor, insira uma senha válida.', 'llrp' ) ]);
            }

            if ( strlen( $password ) < 8 ) {
                wp_send_json_error([ 'message' => __( 'A senha deve ter pelo menos 8 caracteres.', 'llrp' ) ]);
            }

            // Generate unique username from email
            $username = sanitize_user( current( explode( '@', $email ) ), true );
            $append = 1;
            $original_username = $username;
            
            while ( username_exists( $username ) ) {
                $username = $original_username . $append;
                $append++;
            }

            // Create user directly with WordPress functions (no nonce validation)
            $user_id = wp_create_user( $username, $password, $email );
            
            if ( is_wp_error( $user_id ) ) {
                self::safe_log( 'LLRP: wp_create_user error: ' . $user_id->get_error_message() );
                wp_send_json_error([ 'message' => $user_id->get_error_message() ]);
            }

            // Set user role to customer for WooCommerce compatibility
            $user = new WP_User( $user_id );
            $user->set_role( 'customer' );

            // Add WooCommerce customer meta data
            update_user_meta( $user_id, 'billing_email', $email );
            update_user_meta( $user_id, 'first_name', '' );
            update_user_meta( $user_id, 'last_name', '' );
            
            // Add CPF/CNPJ if provided
            if ( ! is_email( $identifier ) ) {
                $sanitized_identifier = preg_replace( '/[^0-9]/', '', $identifier );
                if ( strlen( $sanitized_identifier ) === 11 && self::is_cpf_valid( $sanitized_identifier ) ) {
                    update_user_meta( $user_id, 'billing_cpf', $sanitized_identifier );
                } elseif ( strlen( $sanitized_identifier ) === 14 && self::is_cnpj_valid( $sanitized_identifier ) ) {
                    update_user_meta( $user_id, 'billing_cnpj', $sanitized_identifier );
                }
            }
            
            // Definir data da última troca de senha e último login
            update_user_meta( $user_id, '_llrp_last_password_change', time() );
            update_user_meta( $user_id, '_llrp_last_login', time() );

            // Trigger WooCommerce customer registration hooks manually
            do_action( 'woocommerce_created_customer', $user_id, array( 'user_login' => $username, 'user_email' => $email ), $password );

        } catch ( Exception $e ) {
            self::safe_log( 'LLRP: Registration error: ' . $e->getMessage() );
            
            if ( email_exists( $email ) ) {
                wp_send_json_error([ 'message' => __( 'Seu usuário foi criado, mas um plugin de terceiros causou um erro. Por favor, tente fazer o login.', 'llrp' ) ]);
            } else {
                wp_send_json_error([ 'message' => __( 'Ocorreu um erro durante o registro. Tente novamente.', 'llrp' ) ]);
            }
        }

        // Setting auth cookie for new user (sensitive details only in debug mode)
        self::safe_log('🛒 LLRP: About to set auth cookie for new user');
        
        // Use WooCommerce's native login method
        wc_set_customer_auth_cookie( $user_id );
        
        self::safe_log('🛒 LLRP: Auth cookie set for new user');

        // Trigger cart fragments update for Fluid Checkout compatibility
        self::trigger_cart_fragments_update();

        // Remove the temporary nonce filter
        remove_filter( 'wp_verify_nonce', '__return_true', 999 );

        wp_send_json_success([ 
            'redirect' => wc_get_checkout_url() . '#reload',
            'user_logged_in' => true,
            'cart_fragments' => self::get_cart_fragments(),
            'user_data' => self::get_user_checkout_data($user_id)
        ]);
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

    /**
     * Handle Google login via OAuth
     */
    public static function ajax_google_login() {
        // Debug logging
        self::safe_log( 'LLRP: Google login attempt started' );
        self::safe_log( 'LLRP: POST data: ' . print_r( $_POST, true ) );
        
        // More flexible nonce verification with debug
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        self::safe_log( 'LLRP: Received nonce: ' . $nonce );
        self::safe_log( 'LLRP: Current user ID: ' . get_current_user_id() );
        
        // Try to verify nonce with fallback
        $nonce_valid = wp_verify_nonce( $nonce, 'llrp_nonce' );
        self::safe_log( 'LLRP: Nonce validation result: ' . ( $nonce_valid ? 'VALID' : 'INVALID' ) );
        
        // Verificar nonce - se falhar, verificar se é uma requisição válida
        if ( ! $nonce_valid ) {
            self::safe_log( 'LLRP: Nonce verification failed - checking alternative validations' );
            
            // Verificar se é uma requisição AJAX válida
            if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
                self::safe_log( 'LLRP: Not an AJAX request' );
                wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
            }
            
            // Verificar se tem dados necessários
            if ( empty( $_POST['user_info'] ) && empty( $_POST['id_token'] ) ) {
                self::safe_log( 'LLRP: Missing required data' );
                wp_send_json_error( [ 'message' => __( 'Dados inválidos recebidos.', 'llrp' ) ] );
            }
            
            // Verificar se o usuário não está logado (para evitar ataques)
            if ( is_user_logged_in() ) {
                self::safe_log( 'LLRP: User already logged in, nonce validation required' );
                wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
            }
            
            self::safe_log( 'LLRP: Alternative validations passed - proceeding' );
        } else {
            self::safe_log( 'LLRP: Nonce verification passed' );
        }
        
        // Check for new user_info format first
        $user_info_raw = sanitize_text_field( wp_unslash( $_POST['user_info'] ?? '' ) );
        $id_token = sanitize_text_field( wp_unslash( $_POST['id_token'] ?? '' ) );
        
        self::safe_log( 'LLRP: Raw user_info: ' . $user_info_raw );
        self::safe_log( 'LLRP: ID token: ' . $id_token );
        
        if ( empty( $user_info_raw ) && empty( $id_token ) ) {
            self::safe_log( 'LLRP: Both user_info and id_token are empty' );
            wp_send_json_error( [ 'message' => __( 'Dados do Google inválidos.', 'llrp' ) ] );
        }

        // Verificar se o login com Google está habilitado
        $google_enabled = get_option( 'llrp_google_login_enabled' );
        self::safe_log( 'LLRP: Google login enabled: ' . ( $google_enabled ? 'YES' : 'NO' ) );
        
        if ( ! $google_enabled ) {
            self::safe_log( 'LLRP: Google login not enabled' );
            wp_send_json_error( [ 'message' => __( 'Login com Google não está habilitado.', 'llrp' ) ] );
        }

        $client_id = get_option( 'llrp_google_client_id' );
        self::safe_log( 'LLRP: Google client ID: ' . $client_id );
        
        if ( empty( $client_id ) ) {
            self::safe_log( 'LLRP: Google client ID is empty' );
            wp_send_json_error( [ 'message' => __( 'Configuração do Google não encontrada.', 'llrp' ) ] );
        }

        $data = null;

        // Handle new user_info format
        if ( ! empty( $user_info_raw ) ) {
            self::safe_log( 'LLRP: Processing user_info format' );
            $user_info = json_decode( $user_info_raw, true );
            self::safe_log( 'LLRP: Decoded user_info: ' . print_r( $user_info, true ) );
            
            if ( ! $user_info || ! isset( $user_info['email'] ) || ! isset( $user_info['verified_email'] ) ) {
                self::safe_log( 'LLRP: Invalid user_info data structure' );
                wp_send_json_error( [ 'message' => __( 'Dados do Google inválidos.', 'llrp' ) ] );
            }

            if ( ! $user_info['verified_email'] ) {
                self::safe_log( 'LLRP: Email not verified by Google' );
                wp_send_json_error( [ 'message' => __( 'E-mail do Google não verificado.', 'llrp' ) ] );
            }

            $data = [
                'email'      => $user_info['email'],
                'given_name' => $user_info['given_name'] ?? '',
                'family_name' => $user_info['family_name'] ?? '',
                'name'       => $user_info['name'] ?? '',
                'picture'    => $user_info['picture'] ?? ''
            ];
            
            self::safe_log( 'LLRP: Prepared user data: ' . print_r( $data, true ) );
        }
        // Handle legacy id_token format
        else if ( ! empty( $id_token ) ) {
            // Verificar o token com a API do Google
            $response = wp_remote_get( 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $id_token );
            
            if ( is_wp_error( $response ) ) {
                wp_send_json_error( [ 'message' => __( 'Erro ao verificar token do Google.', 'llrp' ) ] );
            }

            $body = wp_remote_retrieve_body( $response );
            $token_data = json_decode( $body, true );

            if ( ! isset( $token_data['aud'] ) || $token_data['aud'] !== $client_id ) {
                wp_send_json_error( [ 'message' => __( 'Token do Google inválido.', 'llrp' ) ] );
            }

            if ( ! isset( $token_data['email'] ) || ! isset( $token_data['email_verified'] ) || $token_data['email_verified'] !== 'true' ) {
                wp_send_json_error( [ 'message' => __( 'E-mail do Google não verificado.', 'llrp' ) ] );
            }

            $data = $token_data;
        }

        if ( ! $data ) {
            wp_send_json_error( [ 'message' => __( 'Erro ao processar dados do Google.', 'llrp' ) ] );
        }

        $user_data = [
            'email'      => sanitize_email( $data['email'] ),
            'first_name' => sanitize_text_field( $data['given_name'] ?? '' ),
            'last_name'  => sanitize_text_field( $data['family_name'] ?? '' ),
            'name'       => sanitize_text_field( $data['name'] ?? '' ),
            'picture'    => esc_url_raw( $data['picture'] ?? '' ),
            'provider'   => 'google'
        ];

        self::safe_log( 'LLRP: Final user_data to process: ' . print_r( $user_data, true ) );
        self::safe_log( 'LLRP: Calling process_social_login...' );
        
        $user_id = self::process_social_login( $user_data );
        
        self::safe_log( 'LLRP: process_social_login returned: ' . print_r( $user_id, true ) );
        
        if ( is_wp_error( $user_id ) ) {
            self::safe_log( 'LLRP: process_social_login error: ' . $user_id->get_error_message() );
            wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
        }

        self::safe_log( 'LLRP: Setting auth cookie for user ID: ' . $user_id );
        wc_set_customer_auth_cookie( $user_id );
        
        // Trigger cart fragments update for Fluid Checkout compatibility
        self::trigger_cart_fragments_update();
        
        // Smart redirect: account page if coming from account, checkout if from cart
        $redirect_url = wc_get_checkout_url(); // Default to checkout
        if ( isset( $_POST['from_account'] ) && $_POST['from_account'] === '1' ) {
            $redirect_url = wc_get_account_endpoint_url( 'dashboard' );
        }
        
        // Add #reload to checkout URLs for proper state refresh
        if (strpos($redirect_url, 'checkout') !== false || strpos($redirect_url, 'finalizar-compra') !== false) {
            if (strpos($redirect_url, '#') === false) {
                $redirect_url .= '#reload';
            }
        }
        
        self::safe_log( 'LLRP: Sending success response with redirect: ' . $redirect_url );
        wp_send_json_success( [ 
            'redirect' => $redirect_url,
            'user_logged_in' => true,
            'cart_fragments' => self::get_cart_fragments(),
            'user_data' => self::get_user_checkout_data($user_id)
        ] );
    }

    /**
     * Handle Facebook login via OAuth
     */
    public static function ajax_facebook_login() {
        // More flexible nonce verification with debug
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'llrp_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
        }
        
        $access_token = sanitize_text_field( wp_unslash( $_POST['access_token'] ?? '' ) );
        
        if ( empty( $access_token ) ) {
            wp_send_json_error( [ 'message' => __( 'Token do Facebook inválido.', 'llrp' ) ] );
        }

        // Verificar se o login com Facebook está habilitado
        if ( ! get_option( 'llrp_facebook_login_enabled' ) ) {
            wp_send_json_error( [ 'message' => __( 'Login com Facebook não está habilitado.', 'llrp' ) ] );
        }

        $app_id = get_option( 'llrp_facebook_app_id' );
        $app_secret = get_option( 'llrp_facebook_app_secret' );
        
        if ( empty( $app_id ) || empty( $app_secret ) ) {
            wp_send_json_error( [ 'message' => __( 'Configuração do Facebook não encontrada.', 'llrp' ) ] );
        }

        // Verificar o token com a API do Facebook
        $verify_url = sprintf(
            'https://graph.facebook.com/me?access_token=%s&fields=id,email,first_name,last_name,name,picture',
            $access_token
        );
        
        $response = wp_remote_get( $verify_url );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => __( 'Erro ao verificar token do Facebook.', 'llrp' ) ] );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Token do Facebook inválido.', 'llrp' ) ] );
        }

        if ( ! isset( $data['email'] ) ) {
            wp_send_json_error( [ 'message' => __( 'E-mail não fornecido pelo Facebook.', 'llrp' ) ] );
        }

        $user_data = [
            'email'      => sanitize_email( $data['email'] ),
            'first_name' => sanitize_text_field( $data['first_name'] ?? '' ),
            'last_name'  => sanitize_text_field( $data['last_name'] ?? '' ),
            'name'       => sanitize_text_field( $data['name'] ?? '' ),
            'picture'    => esc_url_raw( $data['picture']['data']['url'] ?? '' ),
            'provider'   => 'facebook',
            'social_id'  => sanitize_text_field( $data['id'] ?? '' )
        ];

        $user_id = self::process_social_login( $user_data );
        
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
        }

        wc_set_customer_auth_cookie( $user_id );
        
        // Trigger cart fragments update for Fluid Checkout compatibility
        self::trigger_cart_fragments_update();
        
        // Smart redirect: account page if coming from account, checkout if from cart
        $redirect_url = wc_get_checkout_url(); // Default to checkout
        if ( isset( $_POST['from_account'] ) && $_POST['from_account'] === '1' ) {
            $redirect_url = wc_get_account_endpoint_url( 'dashboard' );
        }
        
        // Add #reload to checkout URLs for proper state refresh
        if (strpos($redirect_url, 'checkout') !== false || strpos($redirect_url, 'finalizar-compra') !== false) {
            if (strpos($redirect_url, '#') === false) {
                $redirect_url .= '#reload';
            }
        }
        
        wp_send_json_success( [ 
            'redirect' => $redirect_url,
            'user_logged_in' => true,
            'cart_fragments' => self::get_cart_fragments(),
            'user_data' => self::get_user_checkout_data($user_id)
        ] );
    }

    /**
     * Process social login data and create/login user
     */
    private static function process_social_login( $user_data ) {
        self::safe_log( 'LLRP: process_social_login started with data: ' . print_r( $user_data, true ) );
        
        $email = $user_data['email'];
        $provider = $user_data['provider'];
        
        self::safe_log( 'LLRP: Looking for existing user with email: ' . $email );
        
        // Verificar se o usuário já existe
        $user = get_user_by( 'email', $email );
        
        if ( $user ) {
            self::safe_log( 'LLRP: Existing user found with ID: ' . $user->ID );
            // Atualizar meta dados do provedor social
            update_user_meta( $user->ID, '_llrp_social_provider', $provider );
            if ( ! empty( $user_data['social_id'] ) ) {
                update_user_meta( $user->ID, '_llrp_' . $provider . '_id', $user_data['social_id'] );
            }
            
            // Atualizar data do último login
            update_user_meta( $user->ID, '_llrp_last_login', time() );
            
            self::safe_log( 'LLRP: Returning existing user ID: ' . $user->ID );
            return $user->ID;
        }
        
        self::safe_log( 'LLRP: No existing user found, creating new user' );
        
        // Criar novo usuário
        $username = $email;
        $password = wp_generate_password();
        
        self::safe_log( 'LLRP: Creating new user with email: ' . $email );
        
        try {
            // Usar wp_create_user ao invés de wc_create_new_customer para evitar problemas de nonce
            $user_id = wp_create_user( $username, $password, $email );
            
            self::safe_log( 'LLRP: wp_create_user result: ' . print_r( $user_id, true ) );
            
            if ( is_wp_error( $user_id ) ) {
                self::safe_log( 'LLRP: wp_create_user error: ' . $user_id->get_error_message() );
                return $user_id;
            }
            
            self::safe_log( 'LLRP: New user created successfully with ID: ' . $user_id );
            
            // Definir role como customer do WooCommerce
            $user = new WP_User( $user_id );
            $user->set_role( 'customer' );
            
            // Atualizar dados do usuário
            if ( ! empty( $user_data['first_name'] ) ) {
                update_user_meta( $user_id, 'first_name', $user_data['first_name'] );
                update_user_meta( $user_id, 'billing_first_name', $user_data['first_name'] );
            }
            
            if ( ! empty( $user_data['last_name'] ) ) {
                update_user_meta( $user_id, 'last_name', $user_data['last_name'] );
                update_user_meta( $user_id, 'billing_last_name', $user_data['last_name'] );
            }
            
            if ( ! empty( $user_data['name'] ) ) {
                wp_update_user( [
                    'ID' => $user_id,
                    'display_name' => $user_data['name']
                ] );
            }
            
            // Salvar dados do provedor social
            update_user_meta( $user_id, '_llrp_social_provider', $provider );
            update_user_meta( $user_id, '_llrp_social_login', 1 );
            
            if ( ! empty( $user_data['social_id'] ) ) {
                update_user_meta( $user_id, '_llrp_' . $provider . '_id', $user_data['social_id'] );
            }
            
            if ( ! empty( $user_data['picture'] ) ) {
                update_user_meta( $user_id, '_llrp_' . $provider . '_picture', $user_data['picture'] );
            }
            
            // Definir data da última troca de senha e último login
            update_user_meta( $user_id, '_llrp_last_password_change', time() );
            update_user_meta( $user_id, '_llrp_last_login', time() );
            
            return $user_id;
            
        } catch ( Exception $e ) {
            return new WP_Error( 'social_login_error', $e->getMessage() );
        }
    }

    /**
     * Trigger cart fragments update for modern WooCommerce compatibility
     */
    private static function trigger_cart_fragments_update() {
        // Force WooCommerce to refresh cart fragments
        if ( function_exists( 'WC' ) && WC()->cart ) {
            // Clear cart cache
            WC()->cart->get_cart_contents_count();
            
            // Trigger cart fragments refresh (legacy)
            do_action( 'woocommerce_cart_updated' );
            
            // Trigger for Interactivity API compatibility
            if ( function_exists( 'wc_get_cart_fragments' ) ) {
                // Force cart fragments refresh for Interactivity API
                do_action( 'wp_ajax_woocommerce_get_refreshed_fragments' );
                do_action( 'wp_ajax_nopriv_woocommerce_get_refreshed_fragments' );
            }
            
            // If Fluid Checkout is active, trigger its specific hooks
            if ( class_exists( 'FluidCheckout' ) ) {
                do_action( 'fluidcheckout_cart_updated' );
            }
        }
    }

    /**
     * Get cart fragments for AJAX response (Interactivity API compatible)
     */
    private static function get_cart_fragments() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return [];
        }

        // Use WooCommerce native function if available (Interactivity API)
        if ( function_exists( 'wc_get_cart_fragments' ) ) {
            $cart_fragments = wc_get_cart_fragments();
        } else {
            // Fallback to legacy method
            $cart_fragments = apply_filters( 'woocommerce_add_to_cart_fragments', [] );
        }
        
        // Add user login state to fragments (non-intrusive)
        if ( ! isset( $cart_fragments['.llrp-user-state'] ) ) {
            $cart_fragments['.llrp-user-state'] = is_user_logged_in() ? 'logged-in' : 'logged-out';
        }
        
        // Add Fluid Checkout specific fragments if available
        if ( class_exists( 'FluidCheckout' ) ) {
            $cart_fragments = apply_filters( 'fluidcheckout_cart_fragments', $cart_fragments );
        }

        return $cart_fragments;
    }

    /**
     * Refresh nonce when needed
     */
    public static function ajax_refresh_nonce() {
        wp_send_json_success( [
            'nonce' => wp_create_nonce( 'llrp_nonce' ),
            'timestamp' => time()
        ] );
    }

    /**
     * Check login status dynamically via AJAX
     */
    public static function ajax_check_login_status() {
        // Verificação de nonce mais flexível
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'llrp_nonce' ) ) {
            if ( ! wp_verify_nonce( $nonce, 'woocommerce-process_checkout' ) ) {
                self::safe_log( 'LLRP: Nonce verification failed for check_login_status. Nonce: ' . $nonce );
                wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
            }
        }
        
        $is_logged_in = is_user_logged_in();
        $checkout_url = wc_get_checkout_url();
        
        wp_send_json_success( [
            'is_logged_in' => $is_logged_in,
            'checkout_url' => $checkout_url,
            'user_id' => get_current_user_id(),
        ] );
    }

    /**
     * CRITICAL: Get checkout user data for direct login autofill
     */
    public static function ajax_get_checkout_user_data() {
        // Verificação de nonce
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'llrp_nonce' ) ) {
            self::safe_log( 'LLRP: Nonce verification failed for get_checkout_user_data' );
            wp_send_json_error( [ 'message' => __( 'Erro de segurança. Recarregue a página e tente novamente.', 'llrp' ) ] );
        }
        
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Usuário não está logado.', 'llrp' ) ] );
        }
        
        $user_id = get_current_user_id();
        // User data request logged (user ID removed for security)
        
        // Get user data
        $user_data = self::get_user_checkout_data( $user_id );
        
        if ( empty( $user_data ) ) {
            self::safe_log( '🔄 LLRP: No user data found for autofill' );
            wp_send_json_error( [ 'message' => __( 'Dados do usuário não encontrados.', 'llrp' ) ] );
        }
        
        // Checkout user data sent (data removed from logs for security)
        
        wp_send_json_success( $user_data );
    }

    /**
     * Direct validation without any nonce dependency
     */
    private static function validate_direct_registration_request() {
        // Minimal validation - no nonce, no complex checks
        
        // 1. Basic POST request check
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            return false;
        }
        
        // 2. Check action
        if ( empty( $_POST['action'] ) || $_POST['action'] !== 'llrp_register' ) {
            return false;
        }
        
        // 3. Check required fields exist
        if ( empty( $_POST['identifier'] ) || empty( $_POST['password'] ) ) {
            return false;
        }
        
        // 4. Simple rate limiting
        $ip = self::get_client_ip();
        $transient_key = 'llrp_reg_' . md5( $ip );
        $attempts = get_transient( $transient_key );
        
        if ( $attempts && $attempts >= 5 ) {
            self::safe_log( 'LLRP: Registration rate limit exceeded for IP: ' . $ip );
            return false;
        }
        
        // Increment attempt counter
        set_transient( $transient_key, ( $attempts ? $attempts + 1 : 1 ), 300 ); // 5 minutes
        
        return true;
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user data for checkout form auto-fill
     */
    /**
     * CRITICAL: Smart redirect URL based on context with #reload for checkout refresh
     */
    private static function get_smart_redirect_url() {
        // Check HTTP_REFERER to understand where the user came from
        $referer = wp_get_referer();
        $current_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        self::safe_log('🔄 LLRP: Smart redirect - Referer: ' . $referer . ' | Current: ' . $current_url);
        
        // If user is coming from cart page, redirect to checkout with #reload
        if ($referer && (strpos($referer, '/cart') !== false || strpos($referer, '/carrinho') !== false)) {
            self::safe_log('🔄 LLRP: User came from cart, redirecting to checkout with #reload');
            return wc_get_checkout_url() . '#reload';
        }
        
        // If user is already on checkout page, add #reload to refresh properly
        if ($referer && (strpos($referer, '/checkout') !== false || strpos($referer, '/finalizar-compra') !== false)) {
            self::safe_log('🔄 LLRP: User is on checkout, adding #reload to preserve and refresh state');
            // Remove existing fragment and add #reload
            $clean_url = strtok($referer, '#');
            return $clean_url . '#reload';
        }
        
        // Check current URL context
        if (strpos($current_url, '/checkout') !== false || strpos($current_url, '/finalizar-compra') !== false) {
            self::safe_log('🔄 LLRP: Current URL is checkout, adding #reload');
            return wc_get_checkout_url() . '#reload';
        }
        
        // Default: redirect to checkout with #reload
        self::safe_log('🔄 LLRP: Default redirect to checkout with #reload');
        return wc_get_checkout_url() . '#reload';
    }

    private static function get_user_checkout_data($user_id) {
        if (!$user_id) {
            return [];
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return [];
        }
        
        // CRITICAL: Email must be the same for both account_email and billing_email
        $user_email = $user->user_email;
        $billing_email = get_user_meta($user_id, 'billing_email', true) ?: $user_email;
        
        // Always ensure both email fields have the same value
        $final_email = $billing_email ?: $user_email;
        
        // Collect all user data for checkout form
        $user_data = [
            // CRITICAL: Both email fields must have identical values
            'email' => $final_email,
            'account_email' => $final_email,
            'billing_email' => $final_email,
            
            'first_name' => get_user_meta($user_id, 'first_name', true),
            'last_name' => get_user_meta($user_id, 'last_name', true),
            'billing_first_name' => get_user_meta($user_id, 'billing_first_name', true),
            'billing_last_name' => get_user_meta($user_id, 'billing_last_name', true),
            'billing_phone' => get_user_meta($user_id, 'billing_phone', true),
            'billing_address_1' => get_user_meta($user_id, 'billing_address_1', true),
            'billing_address_2' => get_user_meta($user_id, 'billing_address_2', true),
            'billing_city' => get_user_meta($user_id, 'billing_city', true),
            'billing_state' => get_user_meta($user_id, 'billing_state', true),
            'billing_postcode' => get_user_meta($user_id, 'billing_postcode', true),
            'billing_country' => get_user_meta($user_id, 'billing_country', true) ?: 'BR',
            'billing_cpf' => get_user_meta($user_id, 'billing_cpf', true),
            'billing_cnpj' => get_user_meta($user_id, 'billing_cnpj', true),
            
            // Brazilian Market plugin compatibility
            'billing_number' => get_user_meta($user_id, 'billing_number', true),
            'billing_neighborhood' => get_user_meta($user_id, 'billing_neighborhood', true),
            'billing_cellphone' => get_user_meta($user_id, 'billing_cellphone', true),
            'billing_birthdate' => get_user_meta($user_id, 'billing_birthdate', true),
            'billing_sex' => get_user_meta($user_id, 'billing_sex', true),
            'billing_company_cnpj' => get_user_meta($user_id, 'billing_company_cnpj', true),
            'billing_ie' => get_user_meta($user_id, 'billing_ie', true),
            'billing_rg' => get_user_meta($user_id, 'billing_rg', true),
            'shipping_first_name' => get_user_meta($user_id, 'shipping_first_name', true),
            'shipping_last_name' => get_user_meta($user_id, 'shipping_last_name', true),
            'shipping_address_1' => get_user_meta($user_id, 'shipping_address_1', true),
            'shipping_address_2' => get_user_meta($user_id, 'shipping_address_2', true),
            'shipping_city' => get_user_meta($user_id, 'shipping_city', true),
            'shipping_state' => get_user_meta($user_id, 'shipping_state', true),
            'shipping_postcode' => get_user_meta($user_id, 'shipping_postcode', true),
            'shipping_country' => get_user_meta($user_id, 'shipping_country', true) ?: 'BR'
        ];
        
        // Log the email synchronization
        // Email sync completed (details removed for security)
        
        // Remove empty values
        $user_data = array_filter($user_data, function($value) {
            return !empty($value);
        });
        
        return $user_data;
    }

    /**
     * Check if Fluid Checkout is active
     */
    private static function is_fluid_checkout_active() {
        return class_exists( 'FluidCheckout' ) || 
               function_exists( 'fluidcheckout_is_fluid_checkout' ) ||
               ( defined( 'FLUIDCHECKOUT_VERSION' ) && FLUIDCHECKOUT_VERSION );
    }
}
Llrp_Ajax::init();
