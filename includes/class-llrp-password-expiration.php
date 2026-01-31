<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Llrp_Password_Expiration {
    
    public static function init() {
        // Hooks para verificar expiração de senha
        add_action( 'wp_login', [ __CLASS__, 'update_last_login' ], 10, 2 );
        add_action( 'user_register', [ __CLASS__, 'set_initial_password_date' ], 10, 1 );
        add_action( 'password_reset', [ __CLASS__, 'update_password_change_date' ], 10, 2 );
        add_action( 'profile_update', [ __CLASS__, 'check_password_change' ], 10, 2 );
        
        // Hook para verificar na página Minha Conta
        add_action( 'woocommerce_before_account_navigation', [ __CLASS__, 'check_password_expiration_my_account' ] );
        
        // Hook para verificar no checkout
        add_action( 'woocommerce_before_checkout_form', [ __CLASS__, 'check_password_expiration_checkout' ] );
        
        // AJAX endpoints
        add_action( 'wp_ajax_llrp_change_expired_password', [ __CLASS__, 'ajax_change_expired_password' ] );
        add_action( 'wp_ajax_llrp_dismiss_password_warning', [ __CLASS__, 'ajax_dismiss_password_warning' ] );
    }
    
    /**
     * Atualiza a data do último login do usuário
     */
    public static function update_last_login( $user_login, $user ) {
        update_user_meta( $user->ID, '_llrp_last_login', time() );
    }
    
    /**
     * Define a data inicial da senha quando o usuário é criado
     */
    public static function set_initial_password_date( $user_id ) {
        // Define a data da última troca de senha como agora
        update_user_meta( $user_id, '_llrp_last_password_change', time() );
    }
    
    /**
     * Atualiza a data da última troca de senha quando o usuário reseta a senha
     */
    public static function update_password_change_date( $user, $new_pass ) {
        update_user_meta( $user->ID, '_llrp_last_password_change', time() );
        // Remove o aviso de senha expirada
        delete_user_meta( $user->ID, '_llrp_password_expired' );
        delete_user_meta( $user->ID, '_llrp_password_warning_dismissed' );
    }
    
    /**
     * Verifica se a senha foi alterada no perfil do usuário
     */
    public static function check_password_change( $user_id, $old_user_data ) {
        $user = get_userdata( $user_id );
        
        // Se a senha foi alterada, atualiza a data
        if ( $user && $old_user_data && $user->user_pass !== $old_user_data->user_pass ) {
            update_user_meta( $user_id, '_llrp_last_password_change', time() );
            delete_user_meta( $user_id, '_llrp_password_expired' );
            delete_user_meta( $user_id, '_llrp_password_warning_dismissed' );
        }
    }
    
    /**
     * Verifica se a senha do usuário expirou
     * 
     * @param int $user_id ID do usuário
     * @return array Array com status de expiração e informações
     */
    public static function check_password_status( $user_id ) {
        if ( ! $user_id ) {
            return [
                'expired' => false,
                'warning' => false,
                'days_until_expiration' => null,
                'reason' => null
            ];
        }
        
        // Verificar se a expiração está habilitada
        $expiration_enabled = get_option( 'llrp_password_expiration_enabled' );
        $inactivity_enabled = get_option( 'llrp_password_expiration_inactivity_enabled' );
        $force_imported_users = get_option( 'llrp_password_force_imported_users' );
        
        if ( ! $expiration_enabled && ! $inactivity_enabled && ! $force_imported_users ) {
            return [
                'expired' => false,
                'warning' => false,
                'days_until_expiration' => null,
                'reason' => null
            ];
        }
        
        $current_time = time();
        $last_password_change = get_user_meta( $user_id, '_llrp_last_password_change', true );
        $last_login = get_user_meta( $user_id, '_llrp_last_login', true );
        
        // Verificar se é usuário importado sem data de troca de senha
        if ( ! $last_password_change && $force_imported_users ) {
            // Usuário importado - forçar troca de senha
            return [
                'expired' => true,
                'warning' => false,
                'days_until_expiration' => null,
                'reason' => 'imported'
            ];
        }
        
        // Se não tem data de última troca, define como agora (usuário antigo)
        if ( ! $last_password_change ) {
            $last_password_change = $current_time;
            update_user_meta( $user_id, '_llrp_last_password_change', $last_password_change );
        }
        
        $result = [
            'expired' => false,
            'warning' => false,
            'days_until_expiration' => null,
            'reason' => null
        ];
        
        // Verificar expiração por tempo
        if ( $expiration_enabled ) {
            $expiration_days = absint( get_option( 'llrp_password_expiration_days', 90 ) );
            $password_age_days = floor( ( $current_time - $last_password_change ) / DAY_IN_SECONDS );
            $days_until_expiration = $expiration_days - $password_age_days;
            
            if ( $days_until_expiration <= 0 ) {
                $result['expired'] = true;
                $result['reason'] = 'time';
                $result['days_overdue'] = abs( $days_until_expiration );
            } elseif ( $days_until_expiration <= 7 ) {
                // Aviso 7 dias antes
                $result['warning'] = true;
                $result['days_until_expiration'] = $days_until_expiration;
            }
        }
        
        // Verificar expiração por inatividade
        if ( $inactivity_enabled && ! $result['expired'] ) {
            $inactivity_days = absint( get_option( 'llrp_password_expiration_inactivity_days', 30 ) );
            
            if ( $last_login ) {
                $inactivity_age_days = floor( ( $current_time - $last_login ) / DAY_IN_SECONDS );
                
                if ( $inactivity_age_days >= $inactivity_days ) {
                    $result['expired'] = true;
                    $result['reason'] = 'inactivity';
                    $result['inactivity_days'] = $inactivity_age_days;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Verifica a expiração de senha na página Minha Conta
     */
    public static function check_password_expiration_my_account() {
        if ( ! is_user_logged_in() ) {
            return;
        }
        
        $user_id = get_current_user_id();
        $status = self::check_password_status( $user_id );
        $warning_dismissed = get_user_meta( $user_id, '_llrp_password_warning_dismissed', true );
        
        // Se a senha expirou, mostrar modal forçando troca
        if ( $status['expired'] ) {
            self::render_password_expiration_modal( $status );
        }
        // Se há aviso e não foi dispensado, mostrar aviso
        elseif ( $status['warning'] && ! $warning_dismissed ) {
            self::render_password_warning_notice( $status );
        }
    }
    
    /**
     * Verifica a expiração de senha no checkout
     */
    public static function check_password_expiration_checkout() {
        if ( ! is_user_logged_in() ) {
            return;
        }
        
        $user_id = get_current_user_id();
        $status = self::check_password_status( $user_id );
        $warning_dismissed = get_user_meta( $user_id, '_llrp_password_warning_dismissed', true );
        
        // Se a senha expirou, mostrar modal forçando troca
        if ( $status['expired'] ) {
            self::render_password_expiration_modal( $status );
        }
        // Se há aviso e não foi dispensado, mostrar aviso
        elseif ( $status['warning'] && ! $warning_dismissed ) {
            self::render_password_warning_notice( $status );
        }
    }
    
    /**
     * Renderiza o modal de senha expirada (bloqueia o usuário)
     */
    private static function render_password_expiration_modal( $status ) {
        $reason_text = '';
        if ( $status['reason'] === 'time' ) {
            $days_overdue = isset( $status['days_overdue'] ) ? $status['days_overdue'] : 0;
            $reason_text = sprintf(
                __( 'Sua senha expirou há %d dias e precisa ser trocada por motivos de segurança.', 'llrp' ),
                $days_overdue
            );
        } elseif ( $status['reason'] === 'inactivity' ) {
            $inactivity_days = isset( $status['inactivity_days'] ) ? $status['inactivity_days'] : 0;
            $reason_text = sprintf(
                __( 'Você não faz login há %d dias. Por motivos de segurança, você precisa trocar sua senha.', 'llrp' ),
                $inactivity_days
            );
        } elseif ( $status['reason'] === 'imported' ) {
            $reason_text = __( 'Para garantir a segurança da sua conta, você precisa criar uma nova senha antes de continuar usando o site.', 'llrp' );
        }
        ?>
        <div id="llrp-password-expiration-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 99999; display: flex; align-items: center; justify-content: center;">
            <div id="llrp-password-expiration-modal" style="background: #fff; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <h2 style="margin-top: 0; color: #d32f2f;">
                    <span style="font-size: 24px;">⚠️</span> <?php esc_html_e( 'Senha Expirada', 'llrp' ); ?>
                </h2>
                <p><?php echo esc_html( $reason_text ); ?></p>
                <p><?php esc_html_e( 'Por favor, crie uma nova senha para continuar.', 'llrp' ); ?></p>
                
                <div id="llrp-password-change-form">
                    <div style="margin-bottom: 15px;">
                        <label for="llrp-current-password" style="display: block; margin-bottom: 5px; font-weight: 600;">
                            <?php esc_html_e( 'Senha Atual', 'llrp' ); ?>
                        </label>
                        <input type="password" id="llrp-current-password" class="llrp-password-input" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required />
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="llrp-new-password" style="display: block; margin-bottom: 5px; font-weight: 600;">
                            <?php esc_html_e( 'Nova Senha', 'llrp' ); ?>
                        </label>
                        <input type="password" id="llrp-new-password" class="llrp-password-input" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required />
                        <small style="color: #666;"><?php esc_html_e( 'Mínimo de 8 caracteres', 'llrp' ); ?></small>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="llrp-confirm-password" style="display: block; margin-bottom: 5px; font-weight: 600;">
                            <?php esc_html_e( 'Confirmar Nova Senha', 'llrp' ); ?>
                        </label>
                        <input type="password" id="llrp-confirm-password" class="llrp-password-input" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required />
                    </div>
                    
                    <div id="llrp-password-change-feedback" style="margin-bottom: 15px; padding: 10px; border-radius: 4px; display: none;"></div>
                    
                    <button id="llrp-change-password-btn" class="button" style="width: 100%; padding: 12px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
                        <?php esc_html_e( 'Trocar Senha', 'llrp' ); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
            body.llrp-modal-open {
                overflow: hidden;
            }
            #llrp-password-expiration-modal input:focus {
                outline: none;
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            #llrp-change-password-btn:hover {
                background: #135e96;
            }
            #llrp-password-change-feedback.error {
                background: #ffebee;
                color: #d32f2f;
                border: 1px solid #d32f2f;
            }
            #llrp-password-change-feedback.success {
                background: #e8f5e8;
                color: #2e7d32;
                border: 1px solid #2e7d32;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Bloqueia o scroll da página
            $('body').addClass('llrp-modal-open');
            
            // Handler para trocar senha
            $('#llrp-change-password-btn').on('click', function() {
                var currentPassword = $('#llrp-current-password').val();
                var newPassword = $('#llrp-new-password').val();
                var confirmPassword = $('#llrp-confirm-password').val();
                var $feedback = $('#llrp-password-change-feedback');
                var $btn = $(this);
                
                // Validações
                if (!currentPassword || !newPassword || !confirmPassword) {
                    $feedback.removeClass('success').addClass('error')
                        .html('<?php esc_html_e( 'Por favor, preencha todos os campos.', 'llrp' ); ?>')
                        .show();
                    return;
                }
                
                if (newPassword.length < 8) {
                    $feedback.removeClass('success').addClass('error')
                        .html('<?php esc_html_e( 'A nova senha deve ter pelo menos 8 caracteres.', 'llrp' ); ?>')
                        .show();
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    $feedback.removeClass('success').addClass('error')
                        .html('<?php esc_html_e( 'As senhas não coincidem.', 'llrp' ); ?>')
                        .show();
                    return;
                }
                
                // Desabilita botão
                $btn.prop('disabled', true).text('<?php esc_html_e( 'Trocando senha...', 'llrp' ); ?>');
                
                // Envia requisição AJAX
                $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                    action: 'llrp_change_expired_password',
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword,
                    nonce: '<?php echo wp_create_nonce( 'llrp_change_password' ); ?>'
                }).done(function(response) {
                    if (response.success) {
                        $feedback.removeClass('error').addClass('success')
                            .html('<?php esc_html_e( 'Senha trocada com sucesso! Recarregando...', 'llrp' ); ?>')
                            .show();
                        
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        $feedback.removeClass('success').addClass('error')
                            .html(response.data.message || '<?php esc_html_e( 'Erro ao trocar senha.', 'llrp' ); ?>')
                            .show();
                        $btn.prop('disabled', false).text('<?php esc_html_e( 'Trocar Senha', 'llrp' ); ?>');
                    }
                }).fail(function() {
                    $feedback.removeClass('success').addClass('error')
                        .html('<?php esc_html_e( 'Erro de conexão. Tente novamente.', 'llrp' ); ?>')
                        .show();
                    $btn.prop('disabled', false).text('<?php esc_html_e( 'Trocar Senha', 'llrp' ); ?>');
                });
            });
            
            // Enter key handler
            $('.llrp-password-input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#llrp-change-password-btn').click();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Renderiza o aviso de senha próxima da expiração
     */
    private static function render_password_warning_notice( $status ) {
        $days = $status['days_until_expiration'];
        $message = sprintf(
            _n(
                'Sua senha expira em %d dia. Por favor, troque sua senha em breve.',
                'Sua senha expira em %d dias. Por favor, troque sua senha em breve.',
                $days,
                'llrp'
            ),
            $days
        );
        ?>
        <div class="woocommerce-info llrp-password-warning-notice" style="position: relative; padding-right: 40px;">
            <span style="font-size: 18px;">⚠️</span> <?php echo esc_html( $message ); ?>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>" class="button" style="margin-left: 10px;">
                <?php esc_html_e( 'Trocar Senha Agora', 'llrp' ); ?>
            </a>
            <button type="button" class="llrp-dismiss-warning" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">
                &times;
            </button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.llrp-dismiss-warning').on('click', function() {
                var $notice = $(this).closest('.llrp-password-warning-notice');
                
                $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                    action: 'llrp_dismiss_password_warning',
                    nonce: '<?php echo wp_create_nonce( 'llrp_dismiss_warning' ); ?>'
                }).always(function() {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Trocar senha expirada
     */
    public static function ajax_change_expired_password() {
        check_ajax_referer( 'llrp_change_password', 'nonce' );
        
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Você precisa estar logado.', 'llrp' ) ] );
        }
        
        $user_id = get_current_user_id();
        $current_password = isset( $_POST['current_password'] ) ? wp_unslash( $_POST['current_password'] ) : '';
        $new_password = isset( $_POST['new_password'] ) ? wp_unslash( $_POST['new_password'] ) : '';
        $confirm_password = isset( $_POST['confirm_password'] ) ? wp_unslash( $_POST['confirm_password'] ) : '';
        
        // Validações
        if ( empty( $current_password ) || empty( $new_password ) || empty( $confirm_password ) ) {
            wp_send_json_error( [ 'message' => __( 'Por favor, preencha todos os campos.', 'llrp' ) ] );
        }
        
        if ( strlen( $new_password ) < 8 ) {
            wp_send_json_error( [ 'message' => __( 'A nova senha deve ter pelo menos 8 caracteres.', 'llrp' ) ] );
        }
        
        if ( $new_password !== $confirm_password ) {
            wp_send_json_error( [ 'message' => __( 'As senhas não coincidem.', 'llrp' ) ] );
        }
        
        // Verificar senha atual
        $user = get_userdata( $user_id );
        if ( ! wp_check_password( $current_password, $user->user_pass, $user_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Senha atual incorreta.', 'llrp' ) ] );
        }
        
        // Trocar senha
        wp_set_password( $new_password, $user_id );
        
        // Atualizar data de troca
        update_user_meta( $user_id, '_llrp_last_password_change', time() );
        delete_user_meta( $user_id, '_llrp_password_expired' );
        delete_user_meta( $user_id, '_llrp_password_warning_dismissed' );
        
        // Re-logar o usuário (necessário após trocar senha)
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );
        
        wp_send_json_success( [ 'message' => __( 'Senha trocada com sucesso!', 'llrp' ) ] );
    }
    
    /**
     * AJAX: Dispensar aviso de senha
     */
    public static function ajax_dismiss_password_warning() {
        check_ajax_referer( 'llrp_dismiss_warning', 'nonce' );
        
        if ( ! is_user_logged_in() ) {
            wp_send_json_error();
        }
        
        $user_id = get_current_user_id();
        update_user_meta( $user_id, '_llrp_password_warning_dismissed', time() );
        
        wp_send_json_success();
    }
}

Llrp_Password_Expiration::init();
