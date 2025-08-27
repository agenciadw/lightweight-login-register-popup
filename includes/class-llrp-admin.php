<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Llrp_Admin {
    public static function init() {
        add_action( 'admin_menu',            [ __CLASS__, 'add_settings_page'    ] );
        add_action( 'admin_init',            [ __CLASS__, 'register_settings'    ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_color_picker' ] );
    }

    /**
     * Enqueue WP color picker assets on our settings page
     */
    public static function enqueue_color_picker( $hook ) {
        if ( 'woocommerce_page_llrp-settings' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'wp-color-picker' );
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
            'popup_width'               => 'absint',
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
            'whatsapp_enabled'          => 'absint',
            'whatsapp_sender_phone'     => 'sanitize_text_field',
            'whatsapp_interactive_buttons' => 'absint',
            'color_btn_code_bg'         => 'sanitize_hex_color',
            'color_btn_code_bg_hover'   => 'sanitize_hex_color',
            'color_btn_code_border'     => 'sanitize_hex_color',
            'color_btn_code_border_hover' => 'sanitize_hex_color',
            'color_btn_code_text'       => 'sanitize_hex_color',
            'color_btn_code_text_hover' => 'sanitize_hex_color',
            'cpf_login_enabled'         => 'absint',
            'cnpj_login_enabled'        => 'absint',
            'google_login_enabled'      => 'absint',
            'google_client_id'          => 'sanitize_text_field',
            'google_client_secret'      => 'sanitize_text_field',
            'facebook_login_enabled'    => 'absint',
            'facebook_app_id'           => 'sanitize_text_field',
            'facebook_app_secret'       => 'sanitize_text_field',
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
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Lightweight Login & Register Popup', 'llrp' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'llrp_options' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Popup Width (px)', 'llrp' ); ?></th>
                        <td>
                            <input type="number" name="llrp_popup_width" value="<?php echo esc_attr( get_option( 'llrp_popup_width', 590 ) ); ?>" />
                        </td>
                    </tr>
                    <!-- Overlay color: allow hex8/rgba manually -->
                    <tr>
                        <th><?php esc_html_e( 'Cor do Overlay', 'llrp' ); ?></th>
                        <td>
                            <input type="text" name="llrp_color_overlay" value="<?php echo esc_attr( get_option( 'llrp_color_overlay', 'rgba(0,0,0,0.5)' ) ); ?>" placeholder="#00000033" />
                            <p class="description"><?php esc_html_e( 'Digite valor hex8 ou rgba, ex: #00000033 ou rgba(0,0,0,0.2)', 'llrp' ); ?></p>
                        </td>
                    </tr>
                    <?php
                    // Other color fields (exclude overlay to allow transparency)
                    $color_fields = [
                        'color_bg'               => __( 'Fundo do Popup', 'llrp' ),
                        'color_header_bg'        => __( 'Fundo do Cabeçalho', 'llrp' ),
                        'color_text'             => __( 'Cor do Texto', 'llrp' ),
                        'color_link'             => __( 'Cor do Link', 'llrp' ),
                        'color_link_hover'       => __( 'Link Hover', 'llrp' ),
                        'color_btn_bg'           => __( 'Botão (bg)', 'llrp' ),
                        'color_btn_bg_hover'     => __( 'Botão Hover (bg)', 'llrp' ),
                        'color_btn_border'       => __( 'Botão (borda)', 'llrp' ),
                        'color_btn_border_hover' => __( 'Borda Hover', 'llrp' ),
                        'color_btn_text'         => __( 'Texto do Botão', 'llrp' ),
                        'color_btn_text_hover'   => __( 'Texto Hover', 'llrp' ),
                        'color_btn_code_bg'      => __( 'Botão Código (bg)', 'llrp' ),
                        'color_btn_code_bg_hover'=> __( 'Botão Código Hover (bg)', 'llrp' ),
                        'color_btn_code_border'  => __( 'Botão Código (borda)', 'llrp' ),
                        'color_btn_code_border_hover' => __( 'Borda Código Hover', 'llrp' ),
                        'color_btn_code_text'    => __( 'Texto do Botão Código', 'llrp' ),
                        'color_btn_code_text_hover'   => __( 'Texto Código Hover', 'llrp' ),
                    ];
                    ?>
                    <tr>
                        <th><?php esc_html_e( 'Enable WhatsApp', 'llrp' ); ?></th>
                        <td>
                            <input type="checkbox" name="llrp_whatsapp_enabled" value="1" <?php checked( get_option( 'llrp_whatsapp_enabled' ), 1 ); ?> />
                            <p class="description"><?php esc_html_e( 'Requer o plugin Joinotify instalado e ativado.', 'llrp' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Sender Phone Number', 'llrp' ); ?></th>
                        <td>
                            <input type="text" name="llrp_whatsapp_sender_phone" value="<?php echo esc_attr( get_option( 'llrp_whatsapp_sender_phone' ) ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Enable Interactive Buttons', 'llrp' ); ?></th>
                        <td>
                            <input type="checkbox" name="llrp_whatsapp_interactive_buttons" value="1" <?php checked( get_option( 'llrp_whatsapp_interactive_buttons' ), 1 ); ?> />
                            <p class="description"><?php esc_html_e( 'Ativa botões interativos como "Copiar código" na mensagem do WhatsApp. Requer suporte do plugin Joinotify.', 'llrp' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Enable Login with CPF', 'llrp' ); ?></th>
                        <td>
                            <input type="checkbox" name="llrp_cpf_login_enabled" value="1" <?php checked( get_option( 'llrp_cpf_login_enabled' ), 1 ); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Enable Login with CNPJ', 'llrp' ); ?></th>
                        <td>
                            <input type="checkbox" name="llrp_cnpj_login_enabled" value="1" <?php checked( get_option( 'llrp_cnpj_login_enabled' ), 1 ); ?> />
                        </td>
                    </tr>
                    
                    <!-- Login Social Settings -->
                    <tr>
                        <th colspan="2" style="background: #f0f0f1; padding: 10px; font-weight: bold;"><?php esc_html_e( 'Configurações de Login Social', 'llrp' ); ?></th>
                    </tr>
                    
                    <!-- Google Login -->
                    <tr>
                        <th><?php esc_html_e( 'Ativar Login com Google', 'llrp' ); ?></th>
                        <td>
                            <input type="checkbox" name="llrp_google_login_enabled" value="1" <?php checked( get_option( 'llrp_google_login_enabled' ), 1 ); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Google Client ID', 'llrp' ); ?></th>
                        <td>
                            <input type="text" name="llrp_google_client_id" value="<?php echo esc_attr( get_option( 'llrp_google_client_id' ) ); ?>" style="width: 100%; max-width: 500px;" />
                            <p class="description"><?php esc_html_e( 'Obtenha em: https://console.cloud.google.com/apis/credentials', 'llrp' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Google Client Secret', 'llrp' ); ?></th>
                        <td>
                            <input type="password" name="llrp_google_client_secret" value="<?php echo esc_attr( get_option( 'llrp_google_client_secret' ) ); ?>" style="width: 100%; max-width: 500px;" />
                            <p class="description"><?php esc_html_e( 'Mantenha em segurança. Obtido junto com o Client ID.', 'llrp' ); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Facebook Login -->
                    <tr>
                        <th><?php esc_html_e( 'Ativar Login com Facebook', 'llrp' ); ?></th>
                        <td>
                            <input type="checkbox" name="llrp_facebook_login_enabled" value="1" <?php checked( get_option( 'llrp_facebook_login_enabled' ), 1 ); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Facebook App ID', 'llrp' ); ?></th>
                        <td>
                            <input type="text" name="llrp_facebook_app_id" value="<?php echo esc_attr( get_option( 'llrp_facebook_app_id' ) ); ?>" style="width: 100%; max-width: 500px;" />
                            <p class="description"><?php esc_html_e( 'Obtenha em: https://developers.facebook.com/apps/', 'llrp' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Facebook App Secret', 'llrp' ); ?></th>
                        <td>
                            <input type="password" name="llrp_facebook_app_secret" value="<?php echo esc_attr( get_option( 'llrp_facebook_app_secret' ) ); ?>" style="width: 100%; max-width: 500px;" />
                            <p class="description"><?php esc_html_e( 'Mantenha em segurança. Obtido junto com o App ID.', 'llrp' ); ?></p>
                        </td>
                    </tr>
                    <?php
                    foreach ( $color_fields as $field => $label ) {
                        $value = esc_attr( get_option( 'llrp_' . $field, '' ) );
                        ?>
                        <tr>
                            <th><?php echo esc_html( $label ); ?></th>
                            <td>
                                <input type="text" class="llrp-color-field" name="llrp_<?php echo esc_attr( $field ); ?>" value="<?php echo $value; ?>" />
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
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
}

Llrp_Admin::init();