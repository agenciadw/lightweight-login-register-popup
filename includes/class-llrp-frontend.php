<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Llrp_Frontend {
    
    /**
     * Safe logging function - only logs in debug mode
     */
    private static function safe_log($message, $data = null) {
        // Debug logging disabled for production
        return;
    }
    
    /**
     * Check if WooCommerce Interactivity API is active
     */
    private static function is_interactivity_api_active() {
        // Check if WooCommerce features utility is available
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            // Check if cart/checkout blocks are enabled
            if ( \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'cart_checkout_blocks' ) ) {
                return true;
            }
        }
        
        // Check for specific Interactivity API functions or classes
        if ( function_exists( 'wp_interactivity' ) || class_exists( 'WP_Interactivity_API' ) ) {
            return true;
        }
        
        // Check if blocks are being used for cart/checkout
        if ( function_exists( 'has_block' ) ) {
            global $post;
            if ( $post && ( has_block( 'woocommerce/cart', $post ) || has_block( 'woocommerce/checkout', $post ) ) ) {
                return true;
            }
        }
        
        // Check WooCommerce settings for Interactivity API features
        if ( function_exists( 'wc_get_setting' ) ) {
            // Check if cart fragments are disabled (sign of Interactivity API)
            $cart_fragments_setting = get_option( 'woocommerce_cart_redirect_after_add' );
            if ( $cart_fragments_setting === 'no' && function_exists( 'wc_get_cart_fragments' ) ) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function init() {
        add_action( 'wp_enqueue_scripts',      [ __CLASS__, 'enqueue_assets' ], 20 );
        add_action( 'wp_footer',               [ __CLASS__, 'render_popup_markup' ] );
        add_action( 'wp_ajax_nopriv_llrp_check_email',  [ __CLASS__, 'ajax_check_email' ] );
        add_action( 'wp_ajax_nopriv_llrp_lostpassword', [ __CLASS__, 'ajax_lostpassword' ] );
        add_filter( 'retrieve_password_message', [ __CLASS__, 'custom_retrieve_password_message' ],10, 4);
        
        // Add social login buttons to My Account page
        add_action( 'woocommerce_login_form_end', [ __CLASS__, 'add_social_login_buttons' ] );
        add_action( 'woocommerce_register_form_end', [ __CLASS__, 'add_social_register_buttons' ] );
        
        // Fluid Checkout compatibility hooks
        add_action( 'wp_ajax_llrp_fluid_checkout_login', [ __CLASS__, 'ajax_fluid_checkout_login' ] );
        add_action( 'wp_ajax_nopriv_llrp_fluid_checkout_login', [ __CLASS__, 'ajax_fluid_checkout_login' ] );
        
        // Only add fragments filter if Interactivity API is not active
        if ( ! self::is_interactivity_api_active() ) {
            add_filter( 'woocommerce_add_to_cart_fragments', [ __CLASS__, 'add_fluid_checkout_fragments' ] );
        }
        
        // CRITICAL: WooCommerce direct checkout login hooks (ONLY for direct checkout access)
        add_action( 'wp_login', [ __CLASS__, 'handle_direct_checkout_login' ], 10, 2 );
        add_action( 'user_register', [ __CLASS__, 'handle_direct_checkout_registration' ], 10, 1 );
        add_action( 'woocommerce_checkout_init', [ __CLASS__, 'inject_checkout_autofill_script' ] );
        add_action( 'wp_footer', [ __CLASS__, 'add_checkout_autofill_handler' ] );
        
        // Additional hook to catch when user is already logged in on checkout
        add_action( 'woocommerce_before_checkout_form', [ __CLASS__, 'force_checkout_autofill_if_logged_in' ] );
    }

 public static function enqueue_assets() {
    // Garantir que o WooCommerce esteja ativo
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    
    // 2) verificar se checkout de convidado está habilitado
    $guest_checkout_enabled = llrp_is_guest_checkout_enabled();
    
    // 3) Detecção robusta da página de minha conta (fallback para REQUEST_URI)
    $is_my_account = is_account_page();
    if ( ! $is_my_account && function_exists( 'wc_get_page_id' ) ) {
        global $wp;
        $myaccount_page_id = wc_get_page_id( 'myaccount' );
        if ( $myaccount_page_id && is_page( $myaccount_page_id ) ) {
            $is_my_account = true;
        }
    }
    
    // Fallback adicional: verificar URL se as funções WC falharem
    if ( ! $is_my_account && isset( $_SERVER['REQUEST_URI'] ) ) {
        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        if ( strpos( $request_uri, 'my-account' ) !== false || 
             strpos( $request_uri, 'minha-conta' ) !== false ) {
            $is_my_account = true;
        }
    }
    
    // 4) carregar no carrinho sempre, na minha conta sempre, checkout só se não logado
    // Se checkout de convidado estiver habilitado, não carregar no checkout
    $should_load = is_cart() || 
                   $is_my_account ||
                   ( is_checkout() && ! is_user_logged_in() && ! $guest_checkout_enabled );
    
    if ( ! $should_load ) {
        return;
    }

    // 3) enqueue de estilos e scripts (inclui wc-cart-fragments para evento added_to_cart)
    wp_enqueue_style(  'llrp-frontend', LLRP_PLUGIN_URL . 'assets/css/llrp-style.css', [], LLRP_VERSION );
    
    $script_dependencies = [ 'jquery', 'wc-cart-fragments' ];
    
    // Enqueue Google Sign-In SDK if enabled
    if ( get_option( 'llrp_google_login_enabled' ) && get_option( 'llrp_google_client_id' ) ) {
        wp_enqueue_script( 'google-signin', 'https://accounts.google.com/gsi/client', [], null, true );
        $script_dependencies[] = 'google-signin';
    }
    
    // Enqueue Facebook SDK if enabled
    if ( get_option( 'llrp_facebook_login_enabled' ) && get_option( 'llrp_facebook_app_id' ) ) {
        wp_enqueue_script( 'facebook-sdk', 'https://connect.facebook.net/pt_BR/sdk.js', [], null, true );
        $script_dependencies[] = 'facebook-sdk';
    }
    
    wp_enqueue_script( 'llrp-frontend',
        LLRP_PLUGIN_URL . 'assets/js/llrp-script.js',
        $script_dependencies,
        LLRP_VERSION,
        true
    );

    // 4) passar dados do PHP pro JS
    wp_localize_script( 'llrp-frontend', 'LLRP_Data', [
        'ajax_url'              => admin_url( 'admin-ajax.php' ),
        'nonce'                 => wp_create_nonce( 'llrp_nonce' ),
        'initial_cart_count'    => (int) WC()->cart->get_cart_contents_count(),
        'is_logged_in'          => is_user_logged_in() ? 1 : 0,
        'is_cart_page'          => is_cart() ? 1 : 0,
        'is_checkout_page'      => is_checkout() ? 1 : 0,
        'is_account_page'       => $is_my_account ? 1 : 0,
        'cpf_login_enabled'     => get_option( 'llrp_cpf_login_enabled' ),
        'cnpj_login_enabled'    => get_option( 'llrp_cnpj_login_enabled' ),
        'google_login_enabled'  => get_option( 'llrp_google_login_enabled' ),
        'google_client_id'      => get_option( 'llrp_google_client_id' ),
        'facebook_login_enabled' => get_option( 'llrp_facebook_login_enabled' ),
        'facebook_app_id'       => get_option( 'llrp_facebook_app_id' ),
        'guest_checkout_enabled' => $guest_checkout_enabled ? 1 : 0,
        'debug_mode'            => defined( 'WP_DEBUG' ) && WP_DEBUG ? '1' : '0',
    ] );

        // Enqueue frontend styles and scripts

        // Localize AJAX parameters
        wp_localize_script( 'llrp-frontend', 'llrp_ajax', [
            'url'             => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'llrp_nonce' ),
            'action_check'    => 'llrp_check_email',
            'action_login'    => 'llrp_login',
            'action_register' => 'llrp_register',
            'action_lost'     => 'llrp_lostpassword',
        ] );

        // Dynamic CSS variables
        $bg                = sanitize_text_field( get_option( 'llrp_color_bg', '#ffffff' ) );
        $overlay           = sanitize_text_field( get_option( 'llrp_color_overlay', 'rgba(0,0,0,0.5)' ) );
        $header_bg         = sanitize_text_field( get_option( 'llrp_color_header_bg', '#ffffff' ) );
        $text_col          = sanitize_text_field( get_option( 'llrp_color_text', '#1a1a1a' ) );
        $link_col          = sanitize_text_field( get_option( 'llrp_color_link', '#791b0a' ) );
        $link_h_col        = sanitize_text_field( get_option( 'llrp_color_link_hover', '#686868' ) );
        $btn_bg            = sanitize_text_field( get_option( 'llrp_color_btn_bg', '#385b02' ) );
        $btn_bg_h          = sanitize_text_field( get_option( 'llrp_color_btn_bg_hover', '#91b381' ) );
        $btn_bd            = sanitize_text_field( get_option( 'llrp_color_btn_border', $btn_bg ) );
        $btn_bd_h          = sanitize_text_field( get_option( 'llrp_color_btn_border_hover', $btn_bg_h ) );
        $btn_txt           = sanitize_text_field( get_option( 'llrp_color_btn_text', '#ffffff' ) );
        $btn_txt_h         = sanitize_text_field( get_option( 'llrp_color_btn_text_hover', $btn_txt ) );
        $font_family       = sanitize_text_field( get_option( 'llrp_font_family', 'inherit' ) );
        $font_size_h2      = floatval( get_option( 'llrp_font_size_h2', '1.5' ) );
        $font_size_p       = floatval( get_option( 'llrp_font_size_p', '1' ) );
        $font_size_label   = floatval( get_option( 'llrp_font_size_label', '0.9' ) );
        $font_size_feedback= floatval( get_option( 'llrp_font_size_feedback', '0.85' ) );
        $font_size_input   = floatval( get_option( 'llrp_font_size_input', '1' ) );
        $font_size_button  = floatval( get_option( 'llrp_font_size_button', '1' ) );

        // Build and add inline CSS
        $css  = ".llrp-overlay { background: {$overlay} !important; }";
        $css .= ".llrp-popup {width: 90%;max-width: 590px;background: {$bg} !important; font-family: {$font_family} !important; color: {$text_col} !important; position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; padding: 20px !important; border-radius: 8px !important; box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important; z-index: 9999 !important; }";
        $css .= ".llrp-close { color: #000 !important; font-size: 24px !important; background: none !important; border: none !important; cursor: pointer !important; float:right !important}";
        $css .= ".llrp-close:hover { color: {$btn_bd_h} !important; }";
        $css .= ".llrp-popup h2 { background: {$header_bg} !important; font-size: {$font_size_h2}rem !important; margin-bottom: .5em !important; }";
        $css .= ".llrp-popup p { font-size: {$font_size_p}rem !important; margin-bottom: 1em !important; }";
        $css .= ".llrp-step label { font-size: {$font_size_label}rem !important; margin-bottom: .1em !important; display: block !important; }";
        $css .= ".llrp-feedback { font-size: {$font_size_feedback}rem !important; }";
        $css .= ".llrp-popup input, .llrp-popup input::placeholder { font-size: {$font_size_input}rem !important; }";
        $css .= ".llrp-step button { background: {$btn_bg} !important; color: {$btn_txt} !important; border: 1px solid {$btn_bd} !important; font-size: {$font_size_button}rem !important; padding: 10px !important; cursor: pointer !important; }";
        $css .= ".llrp-step button:hover { background: {$btn_bg_h} !important; border-color: {$btn_bd_h} !important; color: {$btn_txt_h} !important; }";
        $css .= ".llrp-step a { color: {$link_col} !important; text-decoration: none !important; font-size: 14px; }";
        $css .= ".llrp-step a:hover { color: {$link_h_col} !important; }";
        $css .= ".llrp-user-info { display: flex !important; align-items: center !important; margin-bottom: 1em !important; }";
        $css .= ".llrp-avatar { width: 70px !important; height: 70px !important; border-radius: 1px !important; margin-right: 10px !important; }";
        $css .= ".llrp-login-options { display: flex; justify-content: space-between !important; align-items: center !important; margin: 2em 0 !important; }";
        $css .= ".llrp-login-options label { display: inline-flex !important; align-items: center !important; white-space: nowrap !important; }";
        $css .= ".llrp-login-options label input { width: 15px !important; height: 15px !important; margin-right: 8px !important; }";
        $css .= "@media (max-width: 768px) { .llrp-login-options { flex-direction: column !important; align-items: flex-start !important; gap: 10px !important; } .llrp-login-options label { align-items: flex-start !important; justify-content: flex-start !important; text-align: left !important; } }";
        $btn_code_bg      = sanitize_hex_color( get_option( 'llrp_color_btn_code_bg', '#2271b1' ) );
        $btn_code_bg_h    = sanitize_hex_color( get_option( 'llrp_color_btn_code_bg_hover', '#1e639a' ) );
        $btn_code_bd      = sanitize_hex_color( get_option( 'llrp_color_btn_code_border', $btn_code_bg ) );
        $btn_code_bd_h    = sanitize_hex_color( get_option( 'llrp_color_btn_code_border_hover', $btn_code_bg_h ) );
        $btn_code_txt     = sanitize_hex_color( get_option( 'llrp_color_btn_code_text', '#ffffff' ) );
        $btn_code_txt_h   = sanitize_hex_color( get_option( 'llrp_color_btn_code_text_hover', '#ffffff' ) );
        $css .= "#llrp-send-code { background: {$btn_code_bg} !important; color: {$btn_code_txt} !important; border: 1px solid {$btn_code_bd} !important; }";
        $css .= "#llrp-send-code:hover { background: {$btn_code_bg_h} !important; border-color: {$btn_code_bd_h} !important; color: {$btn_code_txt_h} !important; }";
        


        wp_add_inline_style( 'llrp-frontend', $css );
    }

    public static function render_popup_markup() {
        // Do not render for logged-in users
        if ( is_user_logged_in() ) {
            return;
        }

        // Only on cart and checkout pages
        if ( ! is_cart() && ! is_checkout() ) {
            return;
        }
        
        // Se checkout de convidado estiver habilitado, não renderizar popup no checkout
        if ( is_checkout() && llrp_is_guest_checkout_enabled() ) {
            return;
        }
        
        // Prevent multiple instances of the popup
        static $popup_rendered = false;
        if ( $popup_rendered ) {
            return;
        }
        $popup_rendered = true;

        // Prepare step texts
        $h_email  = get_option( 'llrp_header_email' )    ?: __( 'Finalize o pedido', 'llrp' );
        $t_email  = get_option( 'llrp_text_email' )      ?: __( 'Digite seu e-mail abaixo para continuar', 'llrp' );
        $ph_email = get_option( 'llrp_placeholder_email' ) ?: __( 'Insira seu e-mail', 'llrp' );
        $b_email  = get_option( 'llrp_button_email' )    ?: __( 'Continuar', 'llrp' );

        $t_login  = get_option( 'llrp_text_login' )        ?: __( 'Digite sua senha para continuar a compra.', 'llrp' );
        $ph_pass  = get_option( 'llrp_placeholder_password' ) ?: __( 'Digite sua senha aqui', 'llrp' );
        $txt_rem  = get_option( 'llrp_text_remember' )     ?: __( 'Lembrar meu acesso', 'llrp' );
        $b_login  = get_option( 'llrp_button_login' )      ?: __( 'Acessar', 'llrp' );

        $h_reg    = get_option( 'llrp_header_register' )   ?: __( 'Novo por aqui? Crie sua conta!', 'llrp' );
        $t_reg    = get_option( 'llrp_text_register' )     ?: __( 'Você ainda não tem uma conta. Não se preocupe, você pode criar e finalizar sua compra.', 'llrp' );
        $ph_reg   = get_option( 'llrp_placeholder_register' ) ?: __( 'Insira uma senha para sua conta', 'llrp' );
        $b_reg    = get_option( 'llrp_button_register' )   ?: __( 'Cadastrar e finalizar compra', 'llrp' );

        $h_lost   = __( 'Recuperar a senha', 'llrp' );
        $t_lost   = __( 'Sem problemas. Digite seu e-mail e enviaremos instruções para redefinir sua senha.', 'llrp' );
        $ph_lost  = __( 'Insira seu e-mail', 'llrp' );
        $b_lost   = __( 'Enviar nova senha', 'llrp' );
        ?>
        <div id="llrp-overlay" class="llrp-overlay hidden"></div>
        <div id="llrp-popup" class="llrp-popup hidden">
            <button type="button" class="llrp-close">&times;</button>

            <!-- Email Step -->
            <div class="llrp-step llrp-step-email">
                <h2><?php echo esc_html( $h_email ); ?></h2>
                <p><?php echo esc_html( $t_email ); ?></p>
                <?php
                $cpf_enabled = get_option( 'llrp_cpf_login_enabled' );
                $cnpj_enabled = get_option( 'llrp_cnpj_login_enabled' );
                $placeholder_parts = [ __( 'E-mail', 'llrp' ) ];
                if ( $cpf_enabled ) {
                    $placeholder_parts[] = __( 'CPF', 'llrp' );
                }
                if ( $cnpj_enabled ) {
                    $placeholder_parts[] = __( 'CNPJ', 'llrp' );
                }
                $placeholder = implode( ', ', $placeholder_parts );
                ?>
                <input type="text" id="llrp-identifier" placeholder="<?php echo esc_attr( $placeholder ); ?>">
                <button id="llrp-email-submit"><?php echo esc_html( $b_email ); ?></button>
                
                <?php if ( llrp_is_guest_checkout_enabled() ) : ?>
                    <div class="llrp-guest-checkout-option" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                        <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
                            <?php esc_html_e( 'Não quero fazer cadastro', 'llrp' ); ?>
                        </p>
                        <button type="button" id="llrp-skip-to-checkout" class="llrp-skip-button" style="background: #6c757d; color: #fff; border: 1px solid #6c757d; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 14px;">
                            <?php esc_html_e( 'Pular para o checkout', 'llrp' ); ?>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- Social Login Buttons for new users -->
                <?php if ( ( get_option( 'llrp_google_login_enabled' ) && get_option( 'llrp_google_client_id' ) ) || ( get_option( 'llrp_facebook_login_enabled' ) && get_option( 'llrp_facebook_app_id' ) ) ) : ?>
                    <div class="llrp-social-separator">
                        <span><?php esc_html_e( 'ou', 'llrp' ); ?></span>
                    </div>
                    
                    <?php if ( get_option( 'llrp_google_login_enabled' ) && get_option( 'llrp_google_client_id' ) ) : ?>
                        <button id="llrp-google-login-initial" class="llrp-social-button llrp-google-button">
                            <svg class="llrp-social-icon" width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <?php esc_html_e( 'Continuar com Google', 'llrp' ); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ( get_option( 'llrp_facebook_login_enabled' ) && get_option( 'llrp_facebook_app_id' ) ) : ?>
                        <button id="llrp-facebook-login-initial" class="llrp-social-button llrp-facebook-button">
                            <svg class="llrp-social-icon" width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <?php esc_html_e( 'Continuar com Facebook', 'llrp' ); ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="llrp-feedback llrp-feedback-email"></div>
            </div>

            <!-- Login Options Step -->
            <div class="llrp-step llrp-step-login-options hidden">
                <div class="llrp-user-info">
                    <img class="llrp-avatar" src="" alt="avatar" width="70" height="70">
                    <div class="llrp-user-details">
                        <strong class="llrp-user-name"></strong><br>
                        <small class="llrp-user-email"></small><br>
                        <a href="#" class="llrp-back"><?php esc_html_e( 'Não é sua conta? Voltar', 'llrp' ); ?></a>
                    </div>
                </div>
                <p><?php esc_html_e( 'Como você gostaria de fazer login?', 'llrp' ); ?></p>
                <button id="llrp-show-password-login"><?php esc_html_e( 'Login com Senha', 'llrp' ); ?></button>
                <?php
                $whatsapp_enabled = get_option( 'llrp_whatsapp_enabled' ) && get_option( 'llrp_whatsapp_sender_phone' ) && function_exists('joinotify_send_whatsapp_message_text');
                $send_code_button_text = $whatsapp_enabled ? __( 'Receber código por WhatsApp', 'llrp' ) : __( 'Receber código por e-mail', 'llrp' );
                ?>
                <button id="llrp-send-code"><?php echo esc_html( $send_code_button_text ); ?></button>
                
                <!-- Social Login Buttons -->
                <?php if ( get_option( 'llrp_google_login_enabled' ) && get_option( 'llrp_google_client_id' ) ) : ?>
                    <div class="llrp-social-separator">
                        <span><?php esc_html_e( 'ou', 'llrp' ); ?></span>
                    </div>
                    <button id="llrp-google-login" class="llrp-social-button llrp-google-button">
                        <svg class="llrp-social-icon" width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <?php esc_html_e( 'Continuar com Google', 'llrp' ); ?>
                    </button>
                <?php endif; ?>
                
                <?php if ( get_option( 'llrp_facebook_login_enabled' ) && get_option( 'llrp_facebook_app_id' ) ) : ?>
                    <button id="llrp-facebook-login" class="llrp-social-button llrp-facebook-button">
                        <svg class="llrp-social-icon" width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <?php esc_html_e( 'Continuar com Facebook', 'llrp' ); ?>
                    </button>
                <?php endif; ?>
                
                <div class="llrp-feedback llrp-feedback-login-options"></div>
            </div>

            <!-- Login Step -->
            <div class="llrp-step llrp-step-login hidden">
                <h2 class="llrp-login-header"></h2>
                <div class="llrp-user-info">
                    <img class="llrp-avatar" src="" alt="avatar" width="70" height="70">
                    <div class="llrp-user-details">
                        <strong class="llrp-user-name"></strong><br>
                        <small class="llrp-user-email"></small><br>
                        <a href="#" class="llrp-back"><?php esc_html_e( 'Não é sua conta? Voltar', 'llrp' ); ?></a>
                    </div>
                </div>
                <p><?php echo esc_html( $t_login ); ?></p>
                <input type="password" id="llrp-password" placeholder="<?php echo esc_attr( $ph_pass ); ?>">
                <div class="llrp-login-options">
                    <label><input type="checkbox" id="llrp-remember"> <?php echo esc_html( $txt_rem ); ?></label>
                    <a href="#" class="llrp-forgot"><?php esc_html_e( 'Esqueceu sua senha?', 'llrp' ); ?></a>
                </div>
                <button id="llrp-password-submit"><?php echo esc_html( $b_login ); ?></button>
                <div class="llrp-feedback llrp-feedback-login"></div>
            </div>

            <!-- Register Step -->
            <div class="llrp-step llrp-step-register hidden">
                <h2><?php echo esc_html( $h_reg ); ?></h2>
                <p><?php echo esc_html( $t_reg ); ?></p>
                <input type="password" id="llrp-register-password" placeholder="<?php echo esc_attr( $ph_reg ); ?>">
                <button id="llrp-register-submit"><?php echo esc_html( $b_reg ); ?></button>
                <div class="llrp-feedback llrp-feedback-register"></div>
                <p><a href="#" class="llrp-back">&larr; <?php esc_html_e( 'Voltar', 'llrp' ); ?></a></p>
            </div>

            <!-- Email for Registration Step -->
            <div class="llrp-step llrp-step-register-email hidden">
                <h2><?php esc_html_e( 'Qual é o seu e-mail?', 'llrp' ); ?></h2>
                <p><?php esc_html_e( 'Para finalizar seu cadastro, precisamos do seu e-mail.', 'llrp' ); ?></p>
                <input type="email" id="llrp-register-email" placeholder="<?php esc_attr_e( 'Insira seu e-mail', 'llrp' ); ?>">
                <input type="password" id="llrp-register-password-cpf" placeholder="<?php echo esc_attr( $ph_reg ); ?>">
                <button id="llrp-register-cpf-submit"><?php echo esc_html( $b_reg ); ?></button>
                <div class="llrp-feedback llrp-feedback-register-email"></div>
                <p><a href="#" class="llrp-back">&larr; <?php esc_html_e( 'Voltar', 'llrp' ); ?></a></p>
            </div>

            <!-- Code Login Step -->
            <div class="llrp-step llrp-step-code hidden">
                <h2><?php esc_html_e( 'Verifique seu E-mail', 'llrp' ); ?></h2>
                <p><?php esc_html_e( 'Enviamos um código de 6 dígitos para o seu e-mail. Insira-o abaixo para fazer login.', 'llrp' ); ?></p>
                <input type="text" id="llrp-code" placeholder="<?php esc_attr_e( 'Insira o código', 'llrp' ); ?>" autocomplete="one-time-code">
                <button id="llrp-code-submit"><?php esc_html_e( 'Login', 'llrp' ); ?></button>
                <div class="llrp-feedback llrp-feedback-code"></div>
                <p><a href="#" class="llrp-resend-code"><?php esc_html_e( 'Reenviar código', 'llrp' ); ?></a></p>
                <p><a href="#" class="llrp-back-to-options">&larr; <?php esc_html_e( 'Outras opções', 'llrp' ); ?></a></p>
            </div>

            <!-- Lost Password Step -->
            <div class="llrp-step llrp-step-lost hidden">
                <h2><?php echo esc_html( $h_lost ); ?></h2>
                <p><?php echo esc_html( $t_lost ); ?></p>
                <input type="email" id="llrp-lost-email" placeholder="<?php echo esc_attr( $ph_lost ); ?>">
                <button id="llrp-lost-submit"><?php echo esc_html( $b_lost ); ?></button>
                <div class="llrp-feedback llrp-feedback-lost"></div>
                <p><a href="#" class="llrp-back">&larr; <?php esc_html_e( 'Voltar', 'llrp' ); ?></a></p>
            </div>

        </div>
        <?php
    }

    public static function ajax_check_email() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'E-mail inválido.', 'llrp' ) ] );
        }
        $user = get_user_by( 'email', $email );
        if ( $user ) {
            $avatar = get_avatar_url( $user->ID, [ 'size' => 140 ] );
            wp_send_json_success( [
                'exists'   => true,
                'username' => $user->display_name ?: $user->user_login,
                'email'    => $user->user_email,
                'avatar'   => $avatar,
            ] );
        } else {
            wp_send_json_success( [ 'exists' => false ] );
        }
    }

    public static function ajax_lostpassword() {
       check_ajax_referer( 'llrp_nonce', 'nonce' );

    // Sanitiza e valida e-mail
    $email = isset( $_POST['email'] )
           ? sanitize_email( wp_unslash( $_POST['email'] ) )
           : '';
    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'E-mail inválido.', 'llrp' ) ] );
    }

    // Busca usuário
    $user = get_user_by( 'email', $email );
    if ( ! $user ) {
        wp_send_json_error( [ 'message' => __( 'Nenhuma conta encontrada para esse e-mail.', 'llrp' ) ] );
    }

    // Gera (ou recupera) a chave de reset do WP
    $reset_key = get_password_reset_key( $user );
    if ( is_wp_error( $reset_key ) ) {
        wp_send_json_error( [ 'message' => $reset_key->get_error_message() ] );
    }

    // Dispara o e-mail com o template do WooCommerce
    if ( class_exists( 'WooCommerce' ) && method_exists( WC(), 'mailer' ) ) {
        $mailer = WC()->mailer();
        $emails = $mailer->get_emails();

        if ( ! empty( $emails['WC_Email_Customer_Reset_Password'] ) ) {
            /** @var WC_Email_Customer_Reset_Password $reset_email */
            $reset_email = $emails['WC_Email_Customer_Reset_Password'];
            $reset_email->trigger( $user->user_login, $reset_key );
        }
    }

    // Resposta AJAX
    wp_send_json_success( [ 'message' => __( 'Enviamos um link de redefinição para o seu e-mail.', 'llrp' ) ] ); 
    }
    
    /**
     * Add social login buttons to My Account login form
     */
    public static function add_social_login_buttons() {
        if ( is_user_logged_in() ) {
            return;
        }
        
        $google_enabled = get_option( 'llrp_google_login_enabled' );
        $google_client_id = get_option( 'llrp_google_client_id' );
        $facebook_enabled = get_option( 'llrp_facebook_login_enabled' );
        $facebook_app_id = get_option( 'llrp_facebook_app_id' );
        
        $has_social = ( $google_enabled && $google_client_id ) ||
                      ( $facebook_enabled && $facebook_app_id );
        
        if ( ! $has_social ) {
            return;
        }
        ?>
        <div class="llrp-my-account-social-login">
            <div class="llrp-social-separator">
                <span><?php esc_html_e( 'ou', 'llrp' ); ?></span>
            </div>
            
            <?php if ( get_option( 'llrp_google_login_enabled' ) && get_option( 'llrp_google_client_id' ) ) : ?>
                <button type="button" id="llrp-google-login-account" class="llrp-social-button llrp-google-button">
                    <svg class="llrp-social-icon" width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <?php esc_html_e( 'Continuar com Google', 'llrp' ); ?>
                </button>
            <?php endif; ?>
            
            <?php if ( get_option( 'llrp_facebook_login_enabled' ) && get_option( 'llrp_facebook_app_id' ) ) : ?>
                <button type="button" id="llrp-facebook-login-account" class="llrp-social-button llrp-facebook-button">
                    <svg class="llrp-social-icon" width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <?php esc_html_e( 'Continuar com Facebook', 'llrp' ); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Add social login buttons to My Account register form
     */
    public static function add_social_register_buttons() {
        if ( is_user_logged_in() ) {
            return;
        }
        
        $has_social = ( get_option( 'llrp_google_login_enabled' ) && get_option( 'llrp_google_client_id' ) ) ||
                      ( get_option( 'llrp_facebook_login_enabled' ) && get_option( 'llrp_facebook_app_id' ) );
        
        if ( ! $has_social ) {
            return;
        }
        ?>
        <div class="llrp-my-account-social-register">
            <div class="llrp-social-separator">
                <span><?php esc_html_e( 'ou cadastre-se com', 'llrp' ); ?></span>
            </div>
            
            <?php if ( get_option( 'llrp_google_login_enabled' ) && get_option( 'llrp_google_client_id' ) ) : ?>
                <button type="button" id="llrp-google-register-account" class="llrp-social-button llrp-google-button">
                    <svg class="llrp-social-icon" width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <?php esc_html_e( 'Cadastrar com Google', 'llrp' ); ?>
                </button>
            <?php endif; ?>
            
            <?php if ( get_option( 'llrp_facebook_login_enabled' ) && get_option( 'llrp_facebook_app_id' ) ) : ?>
                <button type="button" id="llrp-facebook-register-account" class="llrp-social-button llrp-facebook-button">
                    <svg class="llrp-social-icon" width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <?php esc_html_e( 'Cadastrar com Facebook', 'llrp' ); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX handler for Fluid Checkout login state check
     */
    public static function ajax_fluid_checkout_login() {
        check_ajax_referer( 'llrp_nonce', 'nonce' );
        
        $response = [
            'is_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'user_email' => '',
            'user_name' => '',
        ];
        
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            $response['user_email'] = $user->user_email;
            $response['user_name'] = $user->display_name ?: $user->user_login;
        }
        
        wp_send_json_success( $response );
    }

    /**
     * Add Fluid Checkout specific fragments
     */
    public static function add_fluid_checkout_fragments( $fragments ) {
        // Add user login state fragment
        $fragments['.llrp-user-state'] = is_user_logged_in() ? 'logged-in' : 'logged-out';
        
        // Add Fluid Checkout specific fragments if the plugin is active
        if ( class_exists( 'FluidCheckout' ) ) {
            // Add checkout form state
            $fragments['.fc-checkout-form'] = is_user_logged_in() ? 'user-logged-in' : 'user-logged-out';
            
            // Add cart state
            $fragments['.fc-cart'] = is_user_logged_in() ? 'user-logged-in' : 'user-logged-out';
        }
        
        return $fragments;
    }

    /**
     * CRITICAL: Handle direct login on checkout page (SAFE MODE)
     */
    public static function handle_direct_checkout_login( $user_login, $user ) {
        // Check if this is from our popup (has our specific action)
        $is_our_popup = isset($_POST['action']) && in_array($_POST['action'], [
            'llrp_code_login', 'llrp_login_with_password', 'llrp_google_login', 'llrp_facebook_login'
        ]);
        
        // Only handle if this is NOT from our popup
        if ( $is_our_popup ) {
            self::safe_log('🔑 LLRP: Ignoring login from our popup');
            return;
        }
        
        // Check if we're in a checkout context (direct page OR checkout AJAX)
        $is_checkout_context = is_checkout() || 
                               (isset($_SERVER['HTTP_REFERER']) && 
                                (strpos($_SERVER['HTTP_REFERER'], '/checkout') !== false || 
                                 strpos($_SERVER['HTTP_REFERER'], '/finalizar-compra') !== false));
        
        if ( ! $is_checkout_context ) {
            return;
        }
        
        // Direct checkout login detected (user ID removed for security)
        
        // Store user ID in session for JavaScript to pick up
        if ( ! session_id() && ! headers_sent() ) {
            @session_start();
        }
        $_SESSION['llrp_direct_checkout_login'] = $user->ID;
        $_SESSION['llrp_direct_checkout_login_time'] = time();
        
        self::safe_log('🔑 LLRP: Session stored for direct checkout login');
    }
    
    /**
     * CRITICAL: Handle direct registration on checkout page (SAFE MODE)
     */
    public static function handle_direct_checkout_registration( $user_id ) {
        // Check if this is from our popup (has our specific action)
        $is_our_popup = isset($_POST['action']) && in_array($_POST['action'], [
            'llrp_register', 'llrp_google_login', 'llrp_facebook_login'
        ]);
        
        // Only handle if this is NOT from our popup
        if ( $is_our_popup ) {
            self::safe_log('📝 LLRP: Ignoring registration from our popup');
            return;
        }
        
        // Check if we're in a checkout context (direct page OR checkout AJAX)
        $is_checkout_context = is_checkout() || 
                               (isset($_SERVER['HTTP_REFERER']) && 
                                (strpos($_SERVER['HTTP_REFERER'], '/checkout') !== false || 
                                 strpos($_SERVER['HTTP_REFERER'], '/finalizar-compra') !== false));
        
        if ( ! $is_checkout_context ) {
            return;
        }
        
        // Direct checkout registration detected (user ID removed for security)
        
        // Store user ID in session for JavaScript to pick up
        if ( ! session_id() && ! headers_sent() ) {
            @session_start();
        }
        $_SESSION['llrp_direct_checkout_register'] = $user_id;
        $_SESSION['llrp_direct_checkout_register_time'] = time();
        
        self::safe_log('📝 LLRP: Session stored for direct checkout registration');
    }

    /**
     * CRITICAL: Inject autofill script data into checkout (IMPROVED)
     */
    public static function inject_checkout_autofill_script() {
        // Only run on checkout page and only if user is logged in
        if ( ! is_checkout() || ! is_user_logged_in() ) {
            return;
        }
        
        if ( ! session_id() && ! headers_sent() ) {
            @session_start();
        }
        
        $should_autofill = false;
        $user_id = get_current_user_id();
        $trigger_type = 'page_load';
        
        // Check if user just logged in directly
        if ( isset( $_SESSION['llrp_direct_checkout_login'] ) && 
             isset( $_SESSION['llrp_direct_checkout_login_time'] ) &&
             ( time() - $_SESSION['llrp_direct_checkout_login_time'] ) < 30 ) {
            
            $should_autofill = true;
            $trigger_type = 'direct_login';
            
            // Clear session data
            unset( $_SESSION['llrp_direct_checkout_login'] );
            unset( $_SESSION['llrp_direct_checkout_login_time'] );
        }
        
        // Check if user just registered directly
        if ( isset( $_SESSION['llrp_direct_checkout_register'] ) && 
             isset( $_SESSION['llrp_direct_checkout_register_time'] ) &&
             ( time() - $_SESSION['llrp_direct_checkout_register_time'] ) < 30 ) {
            
            $should_autofill = true;
            $trigger_type = 'direct_register';
            
            // Clear session data
            unset( $_SESSION['llrp_direct_checkout_register'] );
            unset( $_SESSION['llrp_direct_checkout_register_time'] );
        }
        
        if ( $should_autofill ) {
            // Get user data for autofill
            $user_data = self::get_user_checkout_data_static( $user_id );
            
            self::safe_log('🔄 LLRP: Preparing autofill for ' . $trigger_type . ' - User: ' . $user_id);
            
            // Add JavaScript data
            wp_add_inline_script( 'llrp-frontend', '
                jQuery(document).ready(function($) {
                    // Direct checkout autofill triggered
                    
                    // Trigger autofill with user data
                    setTimeout(function() {
                        if (typeof fillCheckoutFormData === "function") {
                            var userData = ' . wp_json_encode( $user_data ) . ';
                            // Autofilling checkout form (data removed from logs for security)
                            fillCheckoutFormData(userData);
                            
                            // Sync email fields for Brazilian Market compatibility
                            if (userData.email && typeof syncEmailFields === "function") {
                                syncEmailFields(userData.email);
                            }
                            
                            // Trigger checkout update
                            $("form.checkout").trigger("update_checkout");
                            
                            // Direct checkout autofill completed
                        }
                    }, 1000);
                });
            ' );
        }
    }
    
    /**
     * CRITICAL: Add checkout autofill handler (SIMPLIFIED AND SAFE)
     */
    public static function add_checkout_autofill_handler() {
        if ( ! is_checkout() ) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Checkout autofill handler initialized
            
            // Simple check on page load - only if user is logged in and form is empty
            setTimeout(function() {
                if (typeof LLRP_Data !== 'undefined' && LLRP_Data.is_logged_in === '1') {
                    var emailField = $('#billing_email');
                    
                    // Check if form is truly empty (not just pre-filled by popup)
                    var isFormReallyEmpty = emailField.length && !emailField.val() && 
                                           $('#billing_first_name').length && !$('#billing_first_name').val();
                    
                    if (isFormReallyEmpty) {
                        // Logged-in user with empty checkout form detected, requesting autofill
                        
                        $.post(LLRP_Data.ajax_url, {
                            action: 'llrp_get_checkout_user_data',
                            nonce: LLRP_Data.nonce
                        }).done(function(response) {
                            if (response.success && response.data) {
                                // Fallback autofill data received, filling form
                                
                                if (typeof fillCheckoutFormData === 'function') {
                                    fillCheckoutFormData(response.data);
                                    
                                    // Force trigger checkout update
                                    $('body').trigger('update_checkout');
                                    
                                    // Also trigger for Brazilian Market plugin
                                    $(document.body).trigger('updated_checkout');
                                    $(document.body).trigger('checkout_updated');
                                }
                                
                                if (response.data.email && typeof syncEmailFields === 'function') {
                                    syncEmailFields(response.data.email);
                                }
                                
                                // Fallback auto-fill completed
                            }
                        }).fail(function() {
                            // Fallback autofill request failed - no problem, continuing normally
                        });
                    } else if (emailField.length && emailField.val()) {
                        // Checkout form already has email, skipping fallback autofill
                    } else {
                        // Checkout form partially filled, skipping fallback autofill
                    }
                }
            }, 3000); // Longer delay to avoid conflict with popup autofill
        });
        </script>
        <?php
    }
    
    /**
     * CRITICAL: Force autofill when logged-in user accesses checkout page (ONLY for direct access)
     */
    public static function force_checkout_autofill_if_logged_in() {
        if ( ! is_user_logged_in() ) {
            return;
        }
        
        // CRITICAL: Don't force autofill if this came from our popup login
        if ( wp_doing_ajax() ) {
            self::safe_log('🔄 LLRP: Skipping force autofill - AJAX request (likely our popup)');
            return;
        }
        
        // Check if there's a recent login from our popup (within last 10 seconds)
        if ( ! session_id() && ! headers_sent() ) {
            @session_start();
        }
        
        if ( isset( $_SESSION['llrp_popup_login_timestamp'] ) && 
             ( time() - $_SESSION['llrp_popup_login_timestamp'] ) < 10 ) {
            self::safe_log('🔄 LLRP: Skipping force autofill - recent popup login detected');
            return;
        }
        
        $user_id = get_current_user_id();
        $user_data = self::get_user_checkout_data_static( $user_id );
        
        if ( empty( $user_data ) ) {
            return;
        }
        
        // Forcing autofill for logged-in user (user ID removed for security)
        
        // Inline JavaScript to force autofill
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Force autofill detected (log removed for security)
            
            var userData = <?php echo wp_json_encode( $user_data ); ?>;
            
            // Multiple attempts to ensure autofill works
            function attemptAutofill() {
                if (typeof fillCheckoutFormData === 'function') {
                    // Executing autofill (data removed for security)
                    fillCheckoutFormData(userData);
                    
                    // Sync email fields
                    if (userData.email && typeof syncEmailFields === 'function') {
                        syncEmailFields(userData.email);
                    }
                    
                    // Trigger checkout updates
                    $('body').trigger('update_checkout');
                    $(document.body).trigger('updated_checkout');
                    $(document.body).trigger('checkout_updated');
                    
                    // Force autofill completed
                    return true;
                } else {
                    // Function not available yet, will retry
                    return false;
                }
            }
            
            // Try immediately
            if (!attemptAutofill()) {
                // Try after 1 second if function wasn't available
                setTimeout(function() {
                    if (!attemptAutofill()) {
                        // Final attempt after 3 seconds
                        setTimeout(attemptAutofill, 3000);
                    }
                }, 1000);
            }
        });
        </script>
        <?php
    }

    /**
     * Static method to get user checkout data (for use without instance)
     */
    private static function get_user_checkout_data_static( $user_id ) {
        if ( ! $user_id ) {
            return [];
        }
        
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return [];
        }
        
        // CRITICAL: Email must be the same for both account_email and billing_email
        $user_email = $user->user_email;
        $billing_email = get_user_meta( $user_id, 'billing_email', true ) ?: $user_email;
        
        // Always ensure both email fields have the same value
        $final_email = $billing_email ?: $user_email;
        
        // Collect all user data for checkout form
        $user_data = [
            // CRITICAL: Both email fields must have identical values
            'email' => $final_email,
            'account_email' => $final_email,
            'billing_email' => $final_email,
            
            'first_name' => get_user_meta( $user_id, 'first_name', true ),
            'last_name' => get_user_meta( $user_id, 'last_name', true ),
            'billing_first_name' => get_user_meta( $user_id, 'billing_first_name', true ),
            'billing_last_name' => get_user_meta( $user_id, 'billing_last_name', true ),
            'billing_phone' => get_user_meta( $user_id, 'billing_phone', true ),
            'billing_address_1' => get_user_meta( $user_id, 'billing_address_1', true ),
            'billing_address_2' => get_user_meta( $user_id, 'billing_address_2', true ),
            'billing_city' => get_user_meta( $user_id, 'billing_city', true ),
            'billing_state' => get_user_meta( $user_id, 'billing_state', true ),
            'billing_postcode' => get_user_meta( $user_id, 'billing_postcode', true ),
            'billing_country' => get_user_meta( $user_id, 'billing_country', true ) ?: 'BR',
            'billing_cpf' => get_user_meta( $user_id, 'billing_cpf', true ),
            'billing_cnpj' => get_user_meta( $user_id, 'billing_cnpj', true ),
            
            // Brazilian Market plugin compatibility
            'billing_number' => get_user_meta( $user_id, 'billing_number', true ),
            'billing_neighborhood' => get_user_meta( $user_id, 'billing_neighborhood', true ),
            'billing_cellphone' => get_user_meta( $user_id, 'billing_cellphone', true ),
            'billing_birthdate' => get_user_meta( $user_id, 'billing_birthdate', true ),
            'billing_sex' => get_user_meta( $user_id, 'billing_sex', true ),
            'billing_company_cnpj' => get_user_meta( $user_id, 'billing_company_cnpj', true ),
            'billing_ie' => get_user_meta( $user_id, 'billing_ie', true ),
            'billing_rg' => get_user_meta( $user_id, 'billing_rg', true ),
            'shipping_first_name' => get_user_meta( $user_id, 'shipping_first_name', true ),
            'shipping_last_name' => get_user_meta( $user_id, 'shipping_last_name', true ),
            'shipping_address_1' => get_user_meta( $user_id, 'shipping_address_1', true ),
            'shipping_address_2' => get_user_meta( $user_id, 'shipping_address_2', true ),
            'shipping_city' => get_user_meta( $user_id, 'shipping_city', true ),
            'shipping_state' => get_user_meta( $user_id, 'shipping_state', true ),
            'shipping_postcode' => get_user_meta( $user_id, 'shipping_postcode', true ),
            'shipping_country' => get_user_meta( $user_id, 'shipping_country', true ) ?: 'BR'
        ];
        
        // Remove empty values
        $user_data = array_filter( $user_data, function( $value ) {
            return ! empty( $value );
        } );
        
        return $user_data;
    }
    
}

Llrp_Frontend::init();