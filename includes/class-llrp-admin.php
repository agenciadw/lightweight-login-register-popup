<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Llrp_Admin {
    public static function init() {
        add_action( 'admin_menu',            [ __CLASS__, 'add_settings_page'    ] );
        add_action( 'admin_init',            [ __CLASS__, 'register_settings'    ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
    }

    /**
     * Enqueue admin assets (CSS, JS, color picker)
     */
    public static function enqueue_admin_assets( $hook ) {
        if ( 'woocommerce_page_llrp-settings' !== $hook ) {
            return;
        }
        
        // WordPress color picker
        wp_enqueue_style( 'wp-color-picker' );
        
        // Admin CSS
        wp_enqueue_style(
            'llrp-admin-css',
            LLRP_PLUGIN_URL . 'assets/css/llrp-admin.css',
            [],
            LLRP_VERSION
        );
        
        // Admin JS
        wp_enqueue_script(
            'llrp-admin-js',
            LLRP_PLUGIN_URL . 'assets/js/llrp-admin.js',
            [ 'wp-color-picker', 'jquery' ],
            LLRP_VERSION,
            true
        );
    }

    /**
     * Add submenu under WooCommerce
     */
    public static function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Login Popup', 'llrp' ),
            __( 'Login Popup', 'llrp' ),
            'manage_woocommerce',
            'llrp-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    /**
     * Register plugin settings
     */
    public static function register_settings() {
        $settings = [
            // General
            'popup_width'               => 'absint',
            
            // Texts
            'header_email'              => 'sanitize_text_field',
            'text_email'                => 'sanitize_text_field',
            'placeholder_email'         => 'sanitize_text_field',
            'button_email'              => 'sanitize_text_field',
            'text_login'                => 'sanitize_text_field',
            'placeholder_password'      => 'sanitize_text_field',
            'text_remember'             => 'sanitize_text_field',
            'button_login'              => 'sanitize_text_field',
            'header_register'           => 'sanitize_text_field',
            'text_register'             => 'sanitize_text_field',
            'placeholder_register'      => 'sanitize_text_field',
            'button_register'           => 'sanitize_text_field',
            
            // Colors
            'color_bg'                  => 'sanitize_hex_color',
            'color_header_bg'           => 'sanitize_hex_color',
            'color_text'                => 'sanitize_hex_color',
            'color_overlay'             => [ __CLASS__, 'sanitize_rgba_or_hex' ],
            'color_link'                => 'sanitize_hex_color',
            'color_link_hover'          => 'sanitize_hex_color',
            'color_btn_bg'              => 'sanitize_hex_color',
            'color_btn_bg_hover'        => 'sanitize_hex_color',
            'color_btn_border'          => 'sanitize_hex_color',
            'color_btn_border_hover'    => 'sanitize_hex_color',
            'color_btn_text'            => 'sanitize_hex_color',
            'color_btn_text_hover'      => 'sanitize_hex_color',
            'color_btn_skip_bg'         => 'sanitize_hex_color',
            'color_btn_skip_bg_hover'   => 'sanitize_hex_color',
            'color_btn_skip_border'     => 'sanitize_hex_color',
            'color_btn_skip_border_hover' => 'sanitize_hex_color',
            'color_btn_skip_text'       => 'sanitize_hex_color',
            'color_btn_skip_text_hover' => 'sanitize_hex_color',
            'color_btn_google_bg'       => 'sanitize_hex_color',
            'color_btn_google_bg_hover' => 'sanitize_hex_color',
            'color_btn_google_border'   => 'sanitize_hex_color',
            'color_btn_google_border_hover' => 'sanitize_hex_color',
            'color_btn_google_text'     => 'sanitize_hex_color',
            'color_btn_google_text_hover' => 'sanitize_hex_color',
            'color_btn_facebook_bg'     => 'sanitize_hex_color',
            'color_btn_facebook_bg_hover' => 'sanitize_hex_color',
            'color_btn_facebook_border' => 'sanitize_hex_color',
            'color_btn_facebook_border_hover' => 'sanitize_hex_color',
            'color_btn_facebook_text'   => 'sanitize_hex_color',
            'color_btn_facebook_text_hover' => 'sanitize_hex_color',
            'color_btn_code_bg'         => 'sanitize_hex_color',
            'color_btn_code_bg_hover'   => 'sanitize_hex_color',
            'color_btn_code_border'     => 'sanitize_hex_color',
            'color_btn_code_border_hover' => 'sanitize_hex_color',
            'color_btn_code_text'       => 'sanitize_hex_color',
            'color_btn_code_text_hover' => 'sanitize_hex_color',
            
            // Social Login
            'google_login_enabled'      => 'absint',
            'google_client_id'          => 'sanitize_text_field',
            'google_client_secret'      => 'sanitize_text_field',
            'facebook_login_enabled'    => 'absint',
            'facebook_app_id'           => 'sanitize_text_field',
            'facebook_app_secret'       => 'sanitize_text_field',
            
            // Captcha
            'captcha_type'              => 'sanitize_text_field',
            'turnstile_site_key'        => 'sanitize_text_field',
            'turnstile_secret_key'      => 'sanitize_text_field',
            'recaptcha_site_key'        => 'sanitize_text_field',
            'recaptcha_secret_key'      => 'sanitize_text_field',
            'recaptcha_v3_score'        => 'floatval',
            
            // Advanced
            'cpf_login_enabled'         => 'absint',
            'cnpj_login_enabled'        => 'absint',
            'whatsapp_enabled'          => 'absint',
            'whatsapp_sender_phone'     => 'sanitize_text_field',
            'whatsapp_interactive_buttons' => 'absint',
            
            // Password Expiration
            'password_expiration_enabled'     => 'absint',
            'password_expiration_days'        => 'absint',
            'password_expiration_inactivity_enabled' => 'absint',
            'password_expiration_inactivity_days'    => 'absint',
            'password_force_imported_users'   => 'absint',
        ];

        foreach ( $settings as $field => $sanitize_callback ) {
            register_setting( 'llrp_options', 'llrp_' . $field, [
                'sanitize_callback' => $sanitize_callback,
            ] );
        }
    }

    /**
     * Render the settings page HTML
     */
    public static function render_settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        $guest_checkout = llrp_is_guest_checkout_enabled();
        ?>
        <div class="wrap llrp-admin-wrap">
            <h1 class="llrp-admin-title">
                <span class="dashicons dashicons-lock"></span>
                <?php esc_html_e( 'Lightweight Login & Register Popup', 'llrp' ); ?>
                <span class="llrp-version">v<?php echo LLRP_VERSION; ?></span>
            </h1>
            
            <!-- Status Notice -->
            <?php if ( $guest_checkout ) : ?>
                <div class="notice notice-info llrp-notice">
                    <div class="llrp-notice-icon">
                        <span class="dashicons dashicons-info"></span>
                    </div>
                    <div class="llrp-notice-content">
                        <strong><?php esc_html_e( 'Checkout de Convidado Ativo', 'llrp' ); ?></strong>
                        <p><?php esc_html_e( 'O checkout de convidado está habilitado. O plugin permite que usuários finalizem compras sem login.', 'llrp' ); ?></p>
                    </div>
                </div>
            <?php else : ?>
                <div class="notice notice-warning llrp-notice">
                    <div class="llrp-notice-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="llrp-notice-content">
                        <strong><?php esc_html_e( 'Checkout de Convidado Inativo', 'llrp' ); ?></strong>
                        <p><?php esc_html_e( 'O plugin interceptará os botões de checkout para solicitar login/registro.', 'llrp' ); ?></p>
                        <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout' ); ?>" class="button button-small">
                            <?php esc_html_e( 'Configurar WooCommerce', 'llrp' ); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Tabs Navigation -->
            <nav class="llrp-nav-tabs">
                <a href="?page=llrp-settings&tab=general" class="llrp-nav-tab <?php echo $active_tab === 'general' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e( 'Geral', 'llrp' ); ?>
                </a>
                <a href="?page=llrp-settings&tab=texts" class="llrp-nav-tab <?php echo $active_tab === 'texts' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-edit"></span>
                    <?php esc_html_e( 'Textos', 'llrp' ); ?>
                </a>
                <a href="?page=llrp-settings&tab=colors" class="llrp-nav-tab <?php echo $active_tab === 'colors' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-art"></span>
                    <?php esc_html_e( 'Cores', 'llrp' ); ?>
                </a>
                <a href="?page=llrp-settings&tab=social" class="llrp-nav-tab <?php echo $active_tab === 'social' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-share"></span>
                    <?php esc_html_e( 'Login Social', 'llrp' ); ?>
                </a>
                <a href="?page=llrp-settings&tab=captcha" class="llrp-nav-tab <?php echo $active_tab === 'captcha' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-shield"></span>
                    <?php esc_html_e( 'Captcha', 'llrp' ); ?>
                </a>
                <a href="?page=llrp-settings&tab=advanced" class="llrp-nav-tab <?php echo $active_tab === 'advanced' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e( 'Avançado', 'llrp' ); ?>
                </a>
            </nav>
            
            <!-- Tab Content -->
            <form method="post" action="options.php" class="llrp-admin-form">
                <?php settings_fields( 'llrp_options' ); ?>
                
                <!-- Hidden fields para preservar valores de outras abas -->
                <?php self::render_hidden_fields( $active_tab ); ?>
                
                <div class="llrp-tab-content">
                    <?php
                    switch ( $active_tab ) {
                        case 'general':
                            self::render_general_tab();
                            break;
                        case 'texts':
                            self::render_texts_tab();
                            break;
                        case 'colors':
                            self::render_colors_tab();
                            break;
                        case 'social':
                            self::render_social_tab();
                            break;
                        case 'captcha':
                            self::render_captcha_tab();
                            break;
                        case 'advanced':
                            self::render_advanced_tab();
                            break;
                        default:
                            self::render_general_tab();
                    }
                    ?>
                </div>
                
                <div class="llrp-save-bar">
                    <?php submit_button( __( 'Salvar Alterações', 'llrp' ), 'primary large', 'submit', false ); ?>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render hidden fields to preserve values from other tabs
     */
    private static function render_hidden_fields( $active_tab ) {
        // Define todos os campos por aba
        $tabs_fields = [
            'general' => [ 'popup_width' ],
            'texts' => [ 
                'header_email', 'text_email', 'placeholder_email', 'button_email',
                'text_login', 'placeholder_password', 'text_remember', 'button_login',
                'header_register', 'text_register', 'placeholder_register', 'button_register'
            ],
            'colors' => [
                'color_bg', 'color_overlay', 'color_header_bg', 'color_text',
                'color_link', 'color_link_hover', 'color_btn_bg', 'color_btn_bg_hover',
                'color_btn_border', 'color_btn_border_hover', 'color_btn_text', 'color_btn_text_hover',
                'color_btn_skip_bg', 'color_btn_skip_bg_hover', 'color_btn_skip_border',
                'color_btn_skip_border_hover', 'color_btn_skip_text', 'color_btn_skip_text_hover',
                'color_btn_google_bg', 'color_btn_google_bg_hover', 'color_btn_google_border',
                'color_btn_google_border_hover', 'color_btn_google_text', 'color_btn_google_text_hover',
                'color_btn_facebook_bg', 'color_btn_facebook_bg_hover', 'color_btn_facebook_border',
                'color_btn_facebook_border_hover', 'color_btn_facebook_text', 'color_btn_facebook_text_hover',
                'color_btn_code_bg', 'color_btn_code_bg_hover', 'color_btn_code_border',
                'color_btn_code_border_hover', 'color_btn_code_text', 'color_btn_code_text_hover'
            ],
            'social' => [
                'google_login_enabled', 'google_client_id', 'google_client_secret',
                'facebook_login_enabled', 'facebook_app_id', 'facebook_app_secret'
            ],
            'captcha' => [
                'captcha_type', 'turnstile_site_key', 'turnstile_secret_key',
                'recaptcha_site_key', 'recaptcha_secret_key', 'recaptcha_v3_score'
            ],
            'advanced' => [
                'cpf_login_enabled', 'cnpj_login_enabled',
                'whatsapp_enabled', 'whatsapp_sender_phone', 'whatsapp_interactive_buttons',
                'password_expiration_enabled', 'password_expiration_days',
                'password_expiration_inactivity_enabled', 'password_expiration_inactivity_days',
                'password_force_imported_users'
            ]
        ];
        
        // Renderiza campos hidden para todas as abas EXCETO a ativa
        foreach ( $tabs_fields as $tab => $fields ) {
            if ( $tab === $active_tab ) {
                continue; // Pula a aba ativa
            }
            
            foreach ( $fields as $field ) {
                $value = get_option( 'llrp_' . $field );
                
                // Para checkboxes, sempre envia o valor (1 ou 0)
                if ( in_array( $field, [
                    'google_login_enabled', 'facebook_login_enabled',
                    'cpf_login_enabled', 'cnpj_login_enabled',
                    'whatsapp_enabled', 'whatsapp_interactive_buttons',
                    'password_expiration_enabled', 'password_expiration_inactivity_enabled',
                    'password_force_imported_users'
                ] ) ) {
                    echo '<input type="hidden" name="llrp_' . esc_attr( $field ) . '" value="' . esc_attr( $value ? 1 : 0 ) . '" />';
                } else {
                    echo '<input type="hidden" name="llrp_' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '" />';
                }
            }
        }
    }
    
    /**
     * Tab: General Settings
     */
    private static function render_general_tab() {
        ?>
        <div class="llrp-card">
            <div class="llrp-card-header">
                <h2><?php esc_html_e( 'Configurações Gerais', 'llrp' ); ?></h2>
                <p><?php esc_html_e( 'Configure as opções básicas do popup de login', 'llrp' ); ?></p>
            </div>
            <div class="llrp-card-body">
                <div class="llrp-field">
                    <label for="llrp_popup_width">
                        <?php esc_html_e( 'Largura do Popup (px)', 'llrp' ); ?>
                        <span class="llrp-tooltip" data-tip="<?php esc_attr_e( 'Largura máxima do popup em pixels. Padrão: 590px', 'llrp' ); ?>">?</span>
                    </label>
                    <input type="number" 
                           id="llrp_popup_width" 
                           name="llrp_popup_width" 
                           value="<?php echo esc_attr( get_option( 'llrp_popup_width', 590 ) ); ?>" 
                           min="300" 
                           max="1200"
                           class="llrp-input-medium" />
                    <p class="description"><?php esc_html_e( 'Define a largura máxima do popup. Valores entre 300px e 1200px.', 'llrp' ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: Text Customization
     */
    private static function render_texts_tab() {
        ?>
        <div class="llrp-card">
            <div class="llrp-card-header">
                <h2><?php esc_html_e( 'Personalização de Textos', 'llrp' ); ?></h2>
                <p><?php esc_html_e( 'Customize todos os textos exibidos no popup', 'llrp' ); ?></p>
            </div>
            <div class="llrp-card-body">
                <div class="llrp-field-group">
                    <h3 class="llrp-field-group-title"><?php esc_html_e( 'Tela de E-mail', 'llrp' ); ?></h3>
                    
                    <div class="llrp-field">
                        <label for="llrp_header_email"><?php esc_html_e( 'Título', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_header_email" 
                               name="llrp_header_email" 
                               value="<?php echo esc_attr( get_option( 'llrp_header_email', __( 'Finalize o pedido', 'llrp' ) ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="<?php esc_attr_e( 'Finalize o pedido', 'llrp' ); ?>" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_text_email"><?php esc_html_e( 'Texto Descritivo', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_text_email" 
                               name="llrp_text_email" 
                               value="<?php echo esc_attr( get_option( 'llrp_text_email', __( 'Digite seu e-mail abaixo para continuar', 'llrp' ) ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="<?php esc_attr_e( 'Digite seu e-mail abaixo para continuar', 'llrp' ); ?>" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_placeholder_email"><?php esc_html_e( 'Placeholder do Campo', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_placeholder_email" 
                               name="llrp_placeholder_email" 
                               value="<?php echo esc_attr( get_option( 'llrp_placeholder_email', __( 'Insira seu e-mail', 'llrp' ) ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="<?php esc_attr_e( 'Insira seu e-mail', 'llrp' ); ?>" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_button_email"><?php esc_html_e( 'Texto do Botão', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_button_email" 
                               name="llrp_button_email" 
                               value="<?php echo esc_attr( get_option( 'llrp_button_email', __( 'Continuar', 'llrp' ) ) ); ?>" 
                               class="llrp-input-medium"
                               placeholder="<?php esc_attr_e( 'Continuar', 'llrp' ); ?>" />
                    </div>
                </div>
                
                <div class="llrp-field-group">
                    <h3 class="llrp-field-group-title"><?php esc_html_e( 'Tela de Login', 'llrp' ); ?></h3>
                    
                    <div class="llrp-field">
                        <label for="llrp_text_login"><?php esc_html_e( 'Texto Descritivo', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_text_login" 
                               name="llrp_text_login" 
                               value="<?php echo esc_attr( get_option( 'llrp_text_login', __( 'Digite sua senha para continuar a compra.', 'llrp' ) ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="<?php esc_attr_e( 'Digite sua senha para continuar a compra.', 'llrp' ); ?>" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_placeholder_password"><?php esc_html_e( 'Placeholder da Senha', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_placeholder_password" 
                               name="llrp_placeholder_password" 
                               value="<?php echo esc_attr( get_option( 'llrp_placeholder_password', __( 'Digite sua senha aqui', 'llrp' ) ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="<?php esc_attr_e( 'Digite sua senha aqui', 'llrp' ); ?>" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_text_remember"><?php esc_html_e( 'Texto "Lembrar-me"', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_text_remember" 
                               name="llrp_text_remember" 
                               value="<?php echo esc_attr( get_option( 'llrp_text_remember', __( 'Lembrar meu acesso', 'llrp' ) ) ); ?>" 
                               class="llrp-input-medium"
                               placeholder="<?php esc_attr_e( 'Lembrar meu acesso', 'llrp' ); ?>" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_button_login"><?php esc_html_e( 'Texto do Botão', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_button_login" 
                               name="llrp_button_login" 
                               value="<?php echo esc_attr( get_option( 'llrp_button_login', __( 'Acessar', 'llrp' ) ) ); ?>" 
                               class="llrp-input-medium"
                               placeholder="<?php esc_attr_e( 'Acessar', 'llrp' ); ?>" />
                    </div>
                </div>
                
                <div class="llrp-field-group">
                    <h3 class="llrp-field-group-title"><?php esc_html_e( 'Tela de Registro', 'llrp' ); ?></h3>
                    
                    <div class="llrp-field">
                        <label for="llrp_header_register"><?php esc_html_e( 'Título', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_header_register" 
                               name="llrp_header_register" 
                               value="<?php echo esc_attr( get_option( 'llrp_header_register', __( 'Novo por aqui? Crie sua conta!', 'llrp' ) ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="<?php esc_attr_e( 'Novo por aqui? Crie sua conta!', 'llrp' ); ?>" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_text_register"><?php esc_html_e( 'Texto Descritivo', 'llrp' ); ?></label>
                        <textarea id="llrp_text_register" 
                                  name="llrp_text_register" 
                                  class="llrp-textarea"
                                  rows="3"
                                  placeholder="<?php esc_attr_e( 'Você ainda não tem uma conta. Não se preocupe, você pode criar e finalizar sua compra.', 'llrp' ); ?>"><?php echo esc_textarea( get_option( 'llrp_text_register', __( 'Você ainda não tem uma conta. Não se preocupe, você pode criar e finalizar sua compra.', 'llrp' ) ) ); ?></textarea>
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_placeholder_register"><?php esc_html_e( 'Placeholder da Senha', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_placeholder_register" 
                               name="llrp_placeholder_register" 
                               value="<?php echo esc_attr( get_option( 'llrp_placeholder_register', __( 'Insira uma senha para sua conta', 'llrp' ) ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="<?php esc_attr_e( 'Insira uma senha para sua conta', 'llrp' ); ?>" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_button_register"><?php esc_html_e( 'Texto do Botão', 'llrp' ); ?></label>
                        <input type="text" 
                               id="llrp_button_register" 
                               name="llrp_button_register" 
                               value="<?php echo esc_attr( get_option( 'llrp_button_register', __( 'Cadastrar e finalizar compra', 'llrp' ) ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="<?php esc_attr_e( 'Cadastrar e finalizar compra', 'llrp' ); ?>" />
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: Color Customization
     */
    private static function render_colors_tab() {
        ?>
        <div class="llrp-card">
            <div class="llrp-card-header">
                <h2><?php esc_html_e( 'Personalização de Cores', 'llrp' ); ?></h2>
                <p><?php esc_html_e( 'Customize as cores do popup para combinar com sua marca', 'llrp' ); ?></p>
            </div>
            <div class="llrp-card-body">
                <div class="llrp-field-group">
                    <h3 class="llrp-field-group-title"><?php esc_html_e( 'Cores Gerais', 'llrp' ); ?></h3>
                    <div class="llrp-color-grid">
                        <?php
                        self::render_color_field( 'color_overlay', __( 'Overlay (Fundo Escuro)', 'llrp' ), 'rgba(0,0,0,0.5)', __( 'Aceita rgba() ou hex. Ex: rgba(0,0,0,0.5)', 'llrp' ) );
                        self::render_color_field( 'color_bg', __( 'Fundo do Popup', 'llrp' ), '#ffffff' );
                        self::render_color_field( 'color_header_bg', __( 'Fundo do Cabeçalho', 'llrp' ), '#ffffff' );
                        self::render_color_field( 'color_text', __( 'Cor do Texto', 'llrp' ), '#1a1a1a' );
                        self::render_color_field( 'color_link', __( 'Cor dos Links', 'llrp' ), '#791b0a' );
                        self::render_color_field( 'color_link_hover', __( 'Cor dos Links (Hover)', 'llrp' ), '#686868' );
                        ?>
                    </div>
                </div>
                
                <div class="llrp-field-group">
                    <h3 class="llrp-field-group-title"><?php esc_html_e( 'Botão Continuar', 'llrp' ); ?></h3>
                    <p class="description" style="margin-top: -10px; margin-bottom: 15px;"><?php esc_html_e( 'Botão principal "Continuar" na tela inicial', 'llrp' ); ?></p>
                    <div class="llrp-color-grid">
                        <?php
                        self::render_color_field( 'color_btn_bg', __( 'Fundo', 'llrp' ), '#385b02' );
                        self::render_color_field( 'color_btn_bg_hover', __( 'Fundo (Hover)', 'llrp' ), '#91b381' );
                        self::render_color_field( 'color_btn_border', __( 'Borda', 'llrp' ), '#385b02' );
                        self::render_color_field( 'color_btn_border_hover', __( 'Borda (Hover)', 'llrp' ), '#91b381' );
                        self::render_color_field( 'color_btn_text', __( 'Texto', 'llrp' ), '#ffffff' );
                        self::render_color_field( 'color_btn_text_hover', __( 'Texto (Hover)', 'llrp' ), '#ffffff' );
                        ?>
                    </div>
                </div>
                
                <div class="llrp-field-group">
                    <h3 class="llrp-field-group-title"><?php esc_html_e( 'Botão Pular para Checkout', 'llrp' ); ?></h3>
                    <p class="description" style="margin-top: -10px; margin-bottom: 15px;"><?php esc_html_e( 'Botão "Pular para o checkout" (checkout de convidado)', 'llrp' ); ?></p>
                    <div class="llrp-color-grid">
                        <?php
                        self::render_color_field( 'color_btn_skip_bg', __( 'Fundo', 'llrp' ), '#6c757d' );
                        self::render_color_field( 'color_btn_skip_bg_hover', __( 'Fundo (Hover)', 'llrp' ), '#5a6268' );
                        self::render_color_field( 'color_btn_skip_border', __( 'Borda', 'llrp' ), '#6c757d' );
                        self::render_color_field( 'color_btn_skip_border_hover', __( 'Borda (Hover)', 'llrp' ), '#5a6268' );
                        self::render_color_field( 'color_btn_skip_text', __( 'Texto', 'llrp' ), '#ffffff' );
                        self::render_color_field( 'color_btn_skip_text_hover', __( 'Texto (Hover)', 'llrp' ), '#ffffff' );
                        ?>
                    </div>
                </div>
                
                <div class="llrp-field-group">
                    <h3 class="llrp-field-group-title"><?php esc_html_e( 'Botão Google', 'llrp' ); ?></h3>
                    <p class="description" style="margin-top: -10px; margin-bottom: 15px;"><?php esc_html_e( 'Botão "Continuar com Google"', 'llrp' ); ?></p>
                    <div class="llrp-color-grid">
                        <?php
                        self::render_color_field( 'color_btn_google_bg', __( 'Fundo', 'llrp' ), '#ffffff' );
                        self::render_color_field( 'color_btn_google_bg_hover', __( 'Fundo (Hover)', 'llrp' ), '#f8f9fa' );
                        self::render_color_field( 'color_btn_google_border', __( 'Borda', 'llrp' ), '#dadce0' );
                        self::render_color_field( 'color_btn_google_border_hover', __( 'Borda (Hover)', 'llrp' ), '#d2d4d8' );
                        self::render_color_field( 'color_btn_google_text', __( 'Texto', 'llrp' ), '#3c4043' );
                        self::render_color_field( 'color_btn_google_text_hover', __( 'Texto (Hover)', 'llrp' ), '#202124' );
                        ?>
                    </div>
                </div>
                
                <div class="llrp-field-group">
                    <h3 class="llrp-field-group-title"><?php esc_html_e( 'Botão Facebook', 'llrp' ); ?></h3>
                    <p class="description" style="margin-top: -10px; margin-bottom: 15px;"><?php esc_html_e( 'Botão "Continuar com Facebook"', 'llrp' ); ?></p>
                    <div class="llrp-color-grid">
                        <?php
                        self::render_color_field( 'color_btn_facebook_bg', __( 'Fundo', 'llrp' ), '#1877f2' );
                        self::render_color_field( 'color_btn_facebook_bg_hover', __( 'Fundo (Hover)', 'llrp' ), '#166fe5' );
                        self::render_color_field( 'color_btn_facebook_border', __( 'Borda', 'llrp' ), '#1877f2' );
                        self::render_color_field( 'color_btn_facebook_border_hover', __( 'Borda (Hover)', 'llrp' ), '#166fe5' );
                        self::render_color_field( 'color_btn_facebook_text', __( 'Texto', 'llrp' ), '#ffffff' );
                        self::render_color_field( 'color_btn_facebook_text_hover', __( 'Texto (Hover)', 'llrp' ), '#ffffff' );
                        ?>
                    </div>
                </div>
                
                <div class="llrp-field-group">
                    <h3 class="llrp-field-group-title"><?php esc_html_e( 'Botão de Código (WhatsApp/E-mail)', 'llrp' ); ?></h3>
                    <p class="description" style="margin-top: -10px; margin-bottom: 15px;"><?php esc_html_e( 'Botões de envio de código por WhatsApp ou E-mail', 'llrp' ); ?></p>
                    <div class="llrp-color-grid">
                        <?php
                        self::render_color_field( 'color_btn_code_bg', __( 'Fundo', 'llrp' ), '#2271b1' );
                        self::render_color_field( 'color_btn_code_bg_hover', __( 'Fundo (Hover)', 'llrp' ), '#1e639a' );
                        self::render_color_field( 'color_btn_code_border', __( 'Borda', 'llrp' ), '#2271b1' );
                        self::render_color_field( 'color_btn_code_border_hover', __( 'Borda (Hover)', 'llrp' ), '#1e639a' );
                        self::render_color_field( 'color_btn_code_text', __( 'Texto', 'llrp' ), '#ffffff' );
                        self::render_color_field( 'color_btn_code_text_hover', __( 'Texto (Hover)', 'llrp' ), '#ffffff' );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Helper: Render color field
     */
    private static function render_color_field( $field, $label, $default = '', $description = '' ) {
        $value = get_option( 'llrp_' . $field, $default );
        $is_overlay = $field === 'color_overlay';
        ?>
        <div class="llrp-color-field-wrapper">
            <label for="llrp_<?php echo esc_attr( $field ); ?>" class="llrp-color-label">
                <?php echo esc_html( $label ); ?>
            </label>
            <div class="llrp-color-input-wrapper">
                <input type="text" 
                       id="llrp_<?php echo esc_attr( $field ); ?>" 
                       name="llrp_<?php echo esc_attr( $field ); ?>" 
                       value="<?php echo esc_attr( $value ); ?>" 
                       class="<?php echo $is_overlay ? 'llrp-overlay-color' : 'llrp-color-picker'; ?>" 
                       data-default-color="<?php echo esc_attr( $default ); ?>" />
                <span class="llrp-color-preview" style="background-color: <?php echo esc_attr( $value ); ?>"></span>
            </div>
            <?php if ( $description ) : ?>
                <p class="description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Tab: Social Login
     */
    private static function render_social_tab() {
        $google_enabled = get_option( 'llrp_google_login_enabled' );
        $facebook_enabled = get_option( 'llrp_facebook_login_enabled' );
        ?>
        <div class="llrp-cards-row">
            <!-- Google Login -->
            <div class="llrp-card llrp-card-half">
                <div class="llrp-card-header">
                    <div class="llrp-card-title-with-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        <h2><?php esc_html_e( 'Login com Google', 'llrp' ); ?></h2>
                    </div>
                    <label class="llrp-switch">
                        <input type="hidden" name="llrp_google_login_enabled" value="0" />
                        <input type="checkbox" name="llrp_google_login_enabled" value="1" <?php checked( $google_enabled, 1 ); ?> />
                        <span class="llrp-switch-slider"></span>
                    </label>
                </div>
                <div class="llrp-card-body">
                    <div class="llrp-field">
                        <label for="llrp_google_client_id">
                            <?php esc_html_e( 'Client ID', 'llrp' ); ?>
                            <span class="llrp-required">*</span>
                        </label>
                        <input type="text" 
                               id="llrp_google_client_id" 
                               name="llrp_google_client_id" 
                               value="<?php echo esc_attr( get_option( 'llrp_google_client_id' ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="123456789-abc.apps.googleusercontent.com" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_google_client_secret">
                            <?php esc_html_e( 'Client Secret', 'llrp' ); ?>
                            <span class="llrp-required">*</span>
                        </label>
                        <input type="password" 
                               id="llrp_google_client_secret" 
                               name="llrp_google_client_secret" 
                               value="<?php echo esc_attr( get_option( 'llrp_google_client_secret' ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="••••••••••••••••" />
                    </div>
                    
                    <div class="llrp-help-box">
                        <p><strong><?php esc_html_e( 'Como obter:', 'llrp' ); ?></strong></p>
                        <ol>
                            <li><?php esc_html_e( 'Acesse o Google Cloud Console', 'llrp' ); ?></li>
                            <li><?php esc_html_e( 'Crie um projeto OAuth 2.0', 'llrp' ); ?></li>
                            <li><?php esc_html_e( 'Configure as URIs de redirecionamento', 'llrp' ); ?></li>
                        </ol>
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="llrp-link-external">
                            <?php esc_html_e( 'Ir para o Google Cloud Console', 'llrp' ); ?>
                            <span class="dashicons dashicons-external"></span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Facebook Login -->
            <div class="llrp-card llrp-card-half">
                <div class="llrp-card-header">
                    <div class="llrp-card-title-with-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/>
                        </svg>
                        <h2><?php esc_html_e( 'Login com Facebook', 'llrp' ); ?></h2>
                    </div>
                    <label class="llrp-switch">
                        <input type="hidden" name="llrp_facebook_login_enabled" value="0" />
                        <input type="checkbox" name="llrp_facebook_login_enabled" value="1" <?php checked( $facebook_enabled, 1 ); ?> />
                        <span class="llrp-switch-slider"></span>
                    </label>
                </div>
                <div class="llrp-card-body">
                    <div class="llrp-field">
                        <label for="llrp_facebook_app_id">
                            <?php esc_html_e( 'App ID', 'llrp' ); ?>
                            <span class="llrp-required">*</span>
                        </label>
                        <input type="text" 
                               id="llrp_facebook_app_id" 
                               name="llrp_facebook_app_id" 
                               value="<?php echo esc_attr( get_option( 'llrp_facebook_app_id' ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="123456789012345" />
                    </div>
                    
                    <div class="llrp-field">
                        <label for="llrp_facebook_app_secret">
                            <?php esc_html_e( 'App Secret', 'llrp' ); ?>
                            <span class="llrp-required">*</span>
                        </label>
                        <input type="password" 
                               id="llrp_facebook_app_secret" 
                               name="llrp_facebook_app_secret" 
                               value="<?php echo esc_attr( get_option( 'llrp_facebook_app_secret' ) ); ?>" 
                               class="llrp-input-large"
                               placeholder="••••••••••••••••" />
                    </div>
                    
                    <div class="llrp-help-box">
                        <p><strong><?php esc_html_e( 'Como obter:', 'llrp' ); ?></strong></p>
                        <ol>
                            <li><?php esc_html_e( 'Acesse o Facebook Developers', 'llrp' ); ?></li>
                            <li><?php esc_html_e( 'Crie um novo aplicativo', 'llrp' ); ?></li>
                            <li><?php esc_html_e( 'Configure o Facebook Login', 'llrp' ); ?></li>
                        </ol>
                        <a href="https://developers.facebook.com/apps/" target="_blank" class="llrp-link-external">
                            <?php esc_html_e( 'Ir para o Facebook Developers', 'llrp' ); ?>
                            <span class="dashicons dashicons-external"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: Captcha Settings
     */
    private static function render_captcha_tab() {
        $captcha_type = get_option( 'llrp_captcha_type', 'none' );
        ?>
        <div class="llrp-card">
            <div class="llrp-card-header">
                <h2><?php esc_html_e( 'Proteção Anti-Spam', 'llrp' ); ?></h2>
                <p><?php esc_html_e( 'Proteja seu site contra bots e spam com captcha', 'llrp' ); ?></p>
            </div>
            <div class="llrp-card-body">
                <div class="llrp-field">
                    <label for="llrp_captcha_type">
                        <?php esc_html_e( 'Tipo de Captcha', 'llrp' ); ?>
                        <span class="llrp-tooltip" data-tip="<?php esc_attr_e( 'Selecione o tipo de proteção contra bots', 'llrp' ); ?>">?</span>
                    </label>
                    <select id="llrp_captcha_type" name="llrp_captcha_type" class="llrp-select-large">
                        <option value="none" <?php selected( $captcha_type, 'none' ); ?>><?php esc_html_e( '🚫 Nenhum (Desabilitado)', 'llrp' ); ?></option>
                        <option value="turnstile" <?php selected( $captcha_type, 'turnstile' ); ?>><?php esc_html_e( '☁️ Cloudflare Turnstile (Recomendado)', 'llrp' ); ?></option>
                        <option value="recaptcha_v2_checkbox" <?php selected( $captcha_type, 'recaptcha_v2_checkbox' ); ?>><?php esc_html_e( '✅ reCAPTCHA v2 (Checkbox)', 'llrp' ); ?></option>
                        <option value="recaptcha_v2_invisible" <?php selected( $captcha_type, 'recaptcha_v2_invisible' ); ?>><?php esc_html_e( '👻 reCAPTCHA v2 (Invisível)', 'llrp' ); ?></option>
                        <option value="recaptcha_v3" <?php selected( $captcha_type, 'recaptcha_v3' ); ?>><?php esc_html_e( '🤖 reCAPTCHA v3 (Score)', 'llrp' ); ?></option>
                    </select>
                    <?php if ( $captcha_type !== 'none' ): ?>
                        <p class="description" style="margin-top: 10px;">
                            <button type="button" class="button button-secondary" id="llrp-test-captcha-btn">
                                🔍 <?php esc_html_e( 'Testar Configuração', 'llrp' ); ?>
                            </button>
                        </p>
                        <div id="llrp-captcha-test-result" style="display:none; margin-top: 15px; padding: 15px; border-radius: 4px;"></div>
                    <?php endif; ?>
                </div>
                
                <!-- Turnstile -->
                <div class="llrp-captcha-section" id="llrp-turnstile-section" style="display: <?php echo $captcha_type === 'turnstile' ? 'block' : 'none'; ?>;">
                    <div class="llrp-field-group">
                        <h3 class="llrp-field-group-title">☁️ <?php esc_html_e( 'Cloudflare Turnstile', 'llrp' ); ?></h3>
                        
                        <div class="llrp-field">
                            <label for="llrp_turnstile_site_key"><?php esc_html_e( 'Site Key', 'llrp' ); ?> <span class="llrp-required">*</span></label>
                            <input type="text" 
                                   id="llrp_turnstile_site_key" 
                                   name="llrp_turnstile_site_key" 
                                   value="<?php echo esc_attr( get_option( 'llrp_turnstile_site_key' ) ); ?>" 
                                   class="llrp-input-large"
                                   placeholder="0x4AAAAAAA..." />
                        </div>
                        
                        <div class="llrp-field">
                            <label for="llrp_turnstile_secret_key"><?php esc_html_e( 'Secret Key', 'llrp' ); ?> <span class="llrp-required">*</span></label>
                            <input type="password" 
                                   id="llrp_turnstile_secret_key" 
                                   name="llrp_turnstile_secret_key" 
                                   value="<?php echo esc_attr( get_option( 'llrp_turnstile_secret_key' ) ); ?>" 
                                   class="llrp-input-large"
                                   placeholder="0x4AAAAAAA..." />
                        </div>
                        
                        <div class="llrp-help-box">
                            <p><strong>✅ <?php esc_html_e( 'Vantagens do Turnstile:', 'llrp' ); ?></strong></p>
                            <ul>
                                <li><?php esc_html_e( 'Gratuito e ilimitado', 'llrp' ); ?></li>
                                <li><?php esc_html_e( 'Melhor experiência do usuário', 'llrp' ); ?></li>
                                <li><?php esc_html_e( 'Mais rápido que reCAPTCHA', 'llrp' ); ?></li>
                            </ul>
                            <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" class="llrp-link-external">
                                <?php esc_html_e( 'Obter Chaves no Cloudflare', 'llrp' ); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- reCAPTCHA -->
                <div class="llrp-captcha-section" id="llrp-recaptcha-section" style="display: <?php echo in_array( $captcha_type, ['recaptcha_v2_checkbox', 'recaptcha_v2_invisible', 'recaptcha_v3'] ) ? 'block' : 'none'; ?>;">
                    <div class="llrp-field-group">
                        <h3 class="llrp-field-group-title">🤖 <?php esc_html_e( 'Google reCAPTCHA', 'llrp' ); ?></h3>
                        
                        <div class="llrp-field">
                            <label for="llrp_recaptcha_site_key"><?php esc_html_e( 'Site Key', 'llrp' ); ?> <span class="llrp-required">*</span></label>
                            <input type="text" 
                                   id="llrp_recaptcha_site_key" 
                                   name="llrp_recaptcha_site_key" 
                                   value="<?php echo esc_attr( get_option( 'llrp_recaptcha_site_key' ) ); ?>" 
                                   class="llrp-input-large"
                                   placeholder="6Lc..." />
                        </div>
                        
                        <div class="llrp-field">
                            <label for="llrp_recaptcha_secret_key"><?php esc_html_e( 'Secret Key', 'llrp' ); ?> <span class="llrp-required">*</span></label>
                            <input type="password" 
                                   id="llrp_recaptcha_secret_key" 
                                   name="llrp_recaptcha_secret_key" 
                                   value="<?php echo esc_attr( get_option( 'llrp_recaptcha_secret_key' ) ); ?>" 
                                   class="llrp-input-large"
                                   placeholder="6Lc..." />
                        </div>
                        
                        <div class="llrp-field" id="llrp-recaptcha-v3-score" style="display: <?php echo $captcha_type === 'recaptcha_v3' ? 'block' : 'none'; ?>;">
                            <label for="llrp_recaptcha_v3_score">
                                <?php esc_html_e( 'Score Mínimo (apenas v3)', 'llrp' ); ?>
                                <span class="llrp-tooltip" data-tip="<?php esc_attr_e( 'Score entre 0.0 (bot) e 1.0 (humano). Recomendado: 0.5', 'llrp' ); ?>">?</span>
                            </label>
                            <input type="number" 
                                   id="llrp_recaptcha_v3_score" 
                                   name="llrp_recaptcha_v3_score" 
                                   value="<?php echo esc_attr( get_option( 'llrp_recaptcha_v3_score', '0.5' ) ); ?>" 
                                   min="0" 
                                   max="1" 
                                   step="0.1"
                                   class="llrp-input-small" />
                            <p class="description"><?php esc_html_e( 'Quanto maior o score, mais rigorosa a validação.', 'llrp' ); ?></p>
                        </div>
                        
                        <div class="llrp-help-box">
                            <a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="llrp-link-external">
                                <?php esc_html_e( 'Obter Chaves no Google reCAPTCHA', 'llrp' ); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: Advanced Settings
     */
    private static function render_advanced_tab() {
        ?>
        <div class="llrp-card">
            <div class="llrp-card-header">
                <h2><?php esc_html_e( 'Login com Documentos Brasileiros', 'llrp' ); ?></h2>
                <p><?php esc_html_e( 'Permita login usando CPF ou CNPJ além do e-mail', 'llrp' ); ?></p>
            </div>
            <div class="llrp-card-body">
                <div class="llrp-toggle-field">
                    <div class="llrp-toggle-content">
                        <label for="llrp_cpf_login_enabled">
                            <strong><?php esc_html_e( 'Login com CPF', 'llrp' ); ?></strong>
                            <p class="description"><?php esc_html_e( 'Permite que usuários façam login usando o CPF', 'llrp' ); ?></p>
                        </label>
                    </div>
                    <label class="llrp-switch">
                        <input type="hidden" name="llrp_cpf_login_enabled" value="0" />
                        <input type="checkbox" id="llrp_cpf_login_enabled" name="llrp_cpf_login_enabled" value="1" <?php checked( get_option( 'llrp_cpf_login_enabled' ), 1 ); ?> />
                        <span class="llrp-switch-slider"></span>
                    </label>
                </div>
                
                <div class="llrp-toggle-field">
                    <div class="llrp-toggle-content">
                        <label for="llrp_cnpj_login_enabled">
                            <strong><?php esc_html_e( 'Login com CNPJ', 'llrp' ); ?></strong>
                            <p class="description"><?php esc_html_e( 'Permite que usuários façam login usando o CNPJ', 'llrp' ); ?></p>
                        </label>
                    </div>
                    <label class="llrp-switch">
                        <input type="hidden" name="llrp_cnpj_login_enabled" value="0" />
                        <input type="checkbox" id="llrp_cnpj_login_enabled" name="llrp_cnpj_login_enabled" value="1" <?php checked( get_option( 'llrp_cnpj_login_enabled' ), 1 ); ?> />
                        <span class="llrp-switch-slider"></span>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="llrp-card">
            <div class="llrp-card-header">
                <h2><?php esc_html_e( 'Integração com WhatsApp', 'llrp' ); ?></h2>
                <p><?php esc_html_e( 'Envie códigos de login via WhatsApp (requer plugin Joinotify)', 'llrp' ); ?></p>
            </div>
            <div class="llrp-card-body">
                <?php if ( ! function_exists( 'joinotify_send_whatsapp_message_text' ) ) : ?>
                    <div class="llrp-warning-box">
                        <span class="dashicons dashicons-warning"></span>
                        <div>
                            <strong><?php esc_html_e( 'Plugin Joinotify não encontrado', 'llrp' ); ?></strong>
                            <p><?php esc_html_e( 'Para usar esta funcionalidade, instale e ative o plugin Joinotify.', 'llrp' ); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="llrp-toggle-field">
                    <div class="llrp-toggle-content">
                        <label for="llrp_whatsapp_enabled">
                            <strong><?php esc_html_e( 'Habilitar WhatsApp', 'llrp' ); ?></strong>
                            <p class="description"><?php esc_html_e( 'Envia códigos de login via WhatsApp em vez de e-mail', 'llrp' ); ?></p>
                        </label>
                    </div>
                    <label class="llrp-switch">
                        <input type="hidden" name="llrp_whatsapp_enabled" value="0" />
                        <input type="checkbox" id="llrp_whatsapp_enabled" name="llrp_whatsapp_enabled" value="1" <?php checked( get_option( 'llrp_whatsapp_enabled' ), 1 ); ?> />
                        <span class="llrp-switch-slider"></span>
                    </label>
                </div>
                
                <div class="llrp-field">
                    <label for="llrp_whatsapp_sender_phone">
                        <?php esc_html_e( 'Número de Telefone Remetente', 'llrp' ); ?>
                    </label>
                    <input type="text" 
                           id="llrp_whatsapp_sender_phone" 
                           name="llrp_whatsapp_sender_phone" 
                           value="<?php echo esc_attr( get_option( 'llrp_whatsapp_sender_phone' ) ); ?>" 
                           class="llrp-input-medium"
                           placeholder="5511999999999" />
                    <p class="description"><?php esc_html_e( 'Formato: código do país + DDD + número (ex: 5511999999999)', 'llrp' ); ?></p>
                </div>
                
                <div class="llrp-toggle-field">
                    <div class="llrp-toggle-content">
                        <label for="llrp_whatsapp_interactive_buttons">
                            <strong><?php esc_html_e( 'Botões Interativos', 'llrp' ); ?></strong>
                            <p class="description"><?php esc_html_e( 'Adiciona botões como "Copiar código" na mensagem', 'llrp' ); ?></p>
                        </label>
                    </div>
                    <label class="llrp-switch">
                        <input type="hidden" name="llrp_whatsapp_interactive_buttons" value="0" />
                        <input type="checkbox" id="llrp_whatsapp_interactive_buttons" name="llrp_whatsapp_interactive_buttons" value="1" <?php checked( get_option( 'llrp_whatsapp_interactive_buttons' ), 1 ); ?> />
                        <span class="llrp-switch-slider"></span>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="llrp-card">
            <div class="llrp-card-header">
                <h2><?php esc_html_e( 'Expiração de Senha', 'llrp' ); ?></h2>
                <p><?php esc_html_e( 'Forçar usuários a trocar a senha periodicamente para maior segurança', 'llrp' ); ?></p>
            </div>
            <div class="llrp-card-body">
                <div class="llrp-toggle-field">
                    <div class="llrp-toggle-content">
                        <label for="llrp_password_expiration_enabled">
                            <strong><?php esc_html_e( 'Habilitar Expiração de Senha', 'llrp' ); ?></strong>
                            <p class="description"><?php esc_html_e( 'Força os usuários a trocar a senha após um período definido', 'llrp' ); ?></p>
                        </label>
                    </div>
                    <label class="llrp-switch">
                        <input type="hidden" name="llrp_password_expiration_enabled" value="0" />
                        <input type="checkbox" id="llrp_password_expiration_enabled" name="llrp_password_expiration_enabled" value="1" <?php checked( get_option( 'llrp_password_expiration_enabled' ), 1 ); ?> />
                        <span class="llrp-switch-slider"></span>
                    </label>
                </div>
                
                <div class="llrp-field" id="llrp_password_expiration_days_field" style="display: <?php echo get_option( 'llrp_password_expiration_enabled' ) ? 'block' : 'none'; ?>;">
                    <label for="llrp_password_expiration_days">
                        <?php esc_html_e( 'Prazo para Troca de Senha (dias)', 'llrp' ); ?>
                    </label>
                    <input type="number" 
                           id="llrp_password_expiration_days" 
                           name="llrp_password_expiration_days" 
                           value="<?php echo esc_attr( get_option( 'llrp_password_expiration_days', 90 ) ); ?>" 
                           min="1" 
                           max="365"
                           class="llrp-input-medium" />
                    <p class="description"><?php esc_html_e( 'Número de dias até a senha expirar e precisar ser trocada. Padrão: 90 dias', 'llrp' ); ?></p>
                </div>
                
                <div class="llrp-toggle-field" style="margin-top: 20px;">
                    <div class="llrp-toggle-content">
                        <label for="llrp_password_expiration_inactivity_enabled">
                            <strong><?php esc_html_e( 'Forçar Troca por Inatividade', 'llrp' ); ?></strong>
                            <p class="description"><?php esc_html_e( 'Força troca de senha quando o usuário não faz login há muito tempo', 'llrp' ); ?></p>
                        </label>
                    </div>
                    <label class="llrp-switch">
                        <input type="hidden" name="llrp_password_expiration_inactivity_enabled" value="0" />
                        <input type="checkbox" id="llrp_password_expiration_inactivity_enabled" name="llrp_password_expiration_inactivity_enabled" value="1" <?php checked( get_option( 'llrp_password_expiration_inactivity_enabled' ), 1 ); ?> />
                        <span class="llrp-switch-slider"></span>
                    </label>
                </div>
                
                <div class="llrp-field" id="llrp_password_expiration_inactivity_days_field" style="display: <?php echo get_option( 'llrp_password_expiration_inactivity_enabled' ) ? 'block' : 'none'; ?>;">
                    <label for="llrp_password_expiration_inactivity_days">
                        <?php esc_html_e( 'Dias de Inatividade para Forçar Troca', 'llrp' ); ?>
                    </label>
                    <input type="number" 
                           id="llrp_password_expiration_inactivity_days" 
                           name="llrp_password_expiration_inactivity_days" 
                           value="<?php echo esc_attr( get_option( 'llrp_password_expiration_inactivity_days', 30 ) ); ?>" 
                           min="1" 
                           max="365"
                           class="llrp-input-medium" />
                    <p class="description"><?php esc_html_e( 'Forçar troca de senha se o usuário não fizer login nos últimos X dias. Padrão: 30 dias', 'llrp' ); ?></p>
                </div>
                
                <div class="llrp-toggle-field" style="margin-top: 20px;">
                    <div class="llrp-toggle-content">
                        <label for="llrp_password_force_imported_users">
                            <strong><?php esc_html_e( 'Forçar Troca para Usuários Importados', 'llrp' ); ?></strong>
                            <p class="description"><?php esc_html_e( 'Força troca de senha no primeiro login para usuários que foram importados de outras plataformas', 'llrp' ); ?></p>
                        </label>
                    </div>
                    <label class="llrp-switch">
                        <input type="hidden" name="llrp_password_force_imported_users" value="0" />
                        <input type="checkbox" id="llrp_password_force_imported_users" name="llrp_password_force_imported_users" value="1" <?php checked( get_option( 'llrp_password_force_imported_users' ), 1 ); ?> />
                        <span class="llrp-switch-slider"></span>
                    </label>
                </div>
                
                <div class="llrp-help-box" style="margin-top: 20px;">
                    <p><strong>ℹ️ <?php esc_html_e( 'Como funciona:', 'llrp' ); ?></strong></p>
                    <ul style="margin-left: 20px;">
                        <li><?php esc_html_e( 'O sistema verifica automaticamente na tela de login, checkout e minha conta', 'llrp' ); ?></li>
                        <li><?php esc_html_e( 'Avisos são exibidos antes da senha expirar (7 dias antes)', 'llrp' ); ?></li>
                        <li><?php esc_html_e( 'Quando a senha expira, o usuário é obrigado a trocar antes de continuar', 'llrp' ); ?></li>
                        <li><?php esc_html_e( 'A data da última troca é registrada automaticamente', 'llrp' ); ?></li>
                        <li><?php esc_html_e( 'Usuários importados sem data de troca registrada serão forçados a trocar no primeiro login (se habilitado)', 'llrp' ); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php
    }

    /**
     * Sanitize rgba or hex color
     */
    public static function sanitize_rgba_or_hex( $color ) {
        if ( empty( $color ) ) {
            return '';
        }
        if ( strpos( trim( $color ), 'rgba' ) === 0 ) {
            if ( preg_match( '/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([0-9.]{1,3})\s*\)$/', $color, $matches ) ) {
                if ( $matches[1] >= 0 && $matches[1] <= 255 && $matches[2] >= 0 && $matches[2] <= 255 && $matches[3] >= 0 && $matches[3] <= 255 && $matches[4] >= 0 && $matches[4] <= 1 ) {
                    return $color;
                }
            }
            return '';
        }
        if ( preg_match( '/^#([a-fA-F0-9]{8})$/', $color ) ) {
            return $color;
        }
        return sanitize_hex_color( $color );
    }
    
    /**
     * Render captcha test diagnostic
     */
    private static function render_captcha_test() {
        $captcha_type = get_option( 'llrp_captcha_type', 'none' );
        ?>
        <div class="notice notice-info" style="margin-top: 20px; padding: 20px;">
            <h3 style="margin-top: 0;">🔍 Teste de Configuração do Captcha</h3>
            
            <table class="widefat" style="background: white; margin: 15px 0;">
                <tbody>
                    <tr>
                        <th style="width: 200px; padding: 12px;">Tipo Selecionado</th>
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html( $captcha_type ); ?></strong>
                            <?php if ( $captcha_type === 'none' ): ?>
                                <span style="color: #999;"> (Captcha desativado)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <?php if ( strpos( $captcha_type, 'recaptcha' ) !== false ): ?>
                        <tr>
                            <th style="padding: 12px;">Site Key</th>
                            <td style="padding: 12px;">
                                <?php 
                                $site_key = get_option( 'llrp_recaptcha_site_key' );
                                if ( !empty( $site_key ) ) {
                                    echo '✅ Configurada (' . esc_html( strlen( $site_key ) ) . ' caracteres)<br>';
                                    echo '<code style="font-size: 11px;">' . esc_html( substr( $site_key, 0, 20 ) ) . '...</code>';
                                } else {
                                    echo '❌ <span style="color: #d63638;">NÃO CONFIGURADA</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="padding: 12px;">Secret Key</th>
                            <td style="padding: 12px;">
                                <?php 
                                $secret_key = get_option( 'llrp_recaptcha_secret_key' );
                                if ( !empty( $secret_key ) ) {
                                    echo '✅ Configurada (' . esc_html( strlen( $secret_key ) ) . ' caracteres)<br>';
                                    echo '<code style="font-size: 11px;">' . esc_html( substr( $secret_key, 0, 20 ) ) . '...</code>';
                                } else {
                                    echo '❌ <span style="color: #d63638;">NÃO CONFIGURADA</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php if ( $captcha_type === 'recaptcha_v3' ): ?>
                        <tr>
                            <th style="padding: 12px;">Score Mínimo</th>
                            <td style="padding: 12px;">
                                <?php echo esc_html( get_option( 'llrp_recaptcha_v3_score', '0.5' ) ); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ( $captcha_type === 'turnstile' ): ?>
                        <tr>
                            <th style="padding: 12px;">Site Key</th>
                            <td style="padding: 12px;">
                                <?php 
                                $site_key = get_option( 'llrp_turnstile_site_key' );
                                if ( !empty( $site_key ) ) {
                                    echo '✅ Configurada (' . esc_html( strlen( $site_key ) ) . ' caracteres)';
                                } else {
                                    echo '❌ <span style="color: #d63638;">NÃO CONFIGURADA</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="padding: 12px;">Secret Key</th>
                            <td style="padding: 12px;">
                                <?php 
                                $secret_key = get_option( 'llrp_turnstile_secret_key' );
                                if ( !empty( $secret_key ) ) {
                                    echo '✅ Configurada (' . esc_html( strlen( $secret_key ) ) . ' caracteres)';
                                } else {
                                    echo '❌ <span style="color: #d63638;">NÃO CONFIGURADA</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <tr>
                        <th style="padding: 12px;">WP_DEBUG</th>
                        <td style="padding: 12px;">
                            <?php 
                            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                                echo '✅ Ativado (erros serão exibidos com detalhes)';
                            } else {
                                echo '❌ Desativado (recomendado ativar para debug)';
                            }
                            ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th style="padding: 12px;">Arquivo de Log</th>
                        <td style="padding: 12px;">
                            <?php 
                            $log_file = WP_CONTENT_DIR . '/debug.log';
                            if ( file_exists( $log_file ) ) {
                                echo '✅ Existe (verifique em wp-content/debug.log)';
                            } else {
                                echo '⚠️ Ainda não criado (será criado no primeiro erro)';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-top: 15px;">
                <h4 style="margin-top: 0;">📋 Próximos Passos:</h4>
                <ol style="margin-left: 20px;">
                    <li>Verifique se todas as chaves estão configuradas ✅</li>
                    <li>Salve as alterações se fez mudanças</li>
                    <li>Abra o frontend e teste o login/registro</li>
                    <li>Abra o Console do navegador (F12) e veja se há erros</li>
                    <li>Verifique o arquivo <code>wp-content/debug.log</code> após o teste</li>
                    <li>Procure por linhas que começam com "LLRP"</li>
                </ol>
            </div>
            
            <a href="?page=llrp-settings&tab=captcha" class="button button-primary" style="margin-top: 15px;">
                ← Voltar para Configurações
            </a>
        </div>
        <?php
        return;
    }
}

Llrp_Admin::init();
