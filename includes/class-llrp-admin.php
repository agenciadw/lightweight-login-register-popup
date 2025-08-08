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
        // Text fields
        $text_fields = [
            'header_email', 'text_email', 'placeholder_email', 'button_email', 'text_login', 'placeholder_password', 'text_remember', 'button_login',
            'header_register', 'text_register', 'placeholder_register', 'button_register',
        ];
        foreach ( $text_fields as $field ) {
            register_setting( 'llrp_options', 'llrp_' . $field, 'sanitize_text_field' );
        }

        // Color fields
        $color_fields = [
            'color_bg', 'color_header_bg', 'color_text',
            'color_link', 'color_link_hover',
            'color_btn_bg', 'color_btn_bg_hover', 'color_btn_border', 'color_btn_border_hover',
            'color_btn_text', 'color_btn_text_hover',
            'color_btn_code_bg', 'color_btn_code_bg_hover', 'color_btn_code_border', 'color_btn_code_border_hover',
            'color_btn_code_text', 'color_btn_code_text_hover',
        ];
        foreach ( $color_fields as $field ) {
            register_setting( 'llrp_options', 'llrp_' . $field, 'sanitize_hex_color' );
        }

        // Special fields
        register_setting( 'llrp_options', 'llrp_popup_width', 'absint' );
        register_setting( 'llrp_options', 'llrp_color_overlay', [ __CLASS__, 'sanitize_rgba_or_hex' ] );

        // WhatsApp Settings
        register_setting( 'llrp_options', 'llrp_whatsapp_enabled', 'absint' );
        register_setting( 'llrp_options', 'llrp_whatsapp_sender_phone', 'sanitize_text_field' );
    }

    /**
     * Sanitize RGBA or HEX color values.
     *
     * @param string $color The color string.
     * @return string Sanitized color string.
     */
    public static function sanitize_rgba_or_hex( $color ) {
        if ( empty( $color ) ) {
            return '';
        }
        // If 'rgba' is found, check for valid format.
        if ( strpos( trim( $color ), 'rgba' ) === 0 ) {
            if ( preg_match( '/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([0-9.]{1,3})\s*\)$/', $color, $matches ) ) {
                // Check that R, G, B are between 0-255 and A is between 0-1.
                if ( $matches[1] >= 0 && $matches[1] <= 255 && $matches[2] >= 0 && $matches[2] <= 255 && $matches[3] >= 0 && $matches[3] <= 255 && $matches[4] >= 0 && $matches[4] <= 1 ) {
                    return $color;
                }
            }
            return ''; // Return empty for invalid rgba.
        }

        // Check for hex8 format, e.g., #RRGGBBAA.
        if ( preg_match( '/^#([a-fA-F0-9]{8})$/', $color ) ) {
            return $color;
        }

        // Fallback to sanitize_hex_color for standard hex colors (e.g. #FFF, #FFFFFF).
        return sanitize_hex_color( $color );
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
                    <!-- WhatsApp Settings -->
                    <tr>
                        <th colspan="2" style="padding-bottom: 1em;"><h2><?php esc_html_e( 'Integração com WhatsApp (Joinotify)', 'llrp' ); ?></h2></th>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Ativar envio por WhatsApp', 'llrp' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="llrp_whatsapp_enabled" value="1" <?php checked( 1, get_option( 'llrp_whatsapp_enabled' ), true ); ?> />
                                <?php esc_html_e( 'Enviar o código de login por WhatsApp ao invés de e-mail.', 'llrp' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Requer o plugin Joinotify instalado e ativado.', 'llrp' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Telefone de envio (Instância)', 'llrp' ); ?></th>
                        <td>
                            <input type="text" name="llrp_whatsapp_sender_phone" value="<?php echo esc_attr( get_option( 'llrp_whatsapp_sender_phone' ) ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Insira o número de telefone da instância do Joinotify que enviará a mensagem (ex: 5541999998888).', 'llrp' ); ?></p>
                        </td>
                    </tr>

                    <!-- General Color Settings -->
                    <tr>
                        <th colspan="2" style="padding-top: 2em; padding-bottom: 1em;"><h2><?php esc_html_e( 'Cores', 'llrp' ); ?></h2></th>
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
                        'color_btn_bg'           => __( 'Botão Padrão (bg)', 'llrp' ),
                        'color_btn_bg_hover'     => __( 'Botão Padrão Hover (bg)', 'llrp' ),
                        'color_btn_border'       => __( 'Botão Padrão (borda)', 'llrp' ),
                        'color_btn_border_hover' => __( 'Borda Padrão Hover', 'llrp' ),
                        'color_btn_text'         => __( 'Texto do Botão Padrão', 'llrp' ),
                        'color_btn_text_hover'   => __( 'Texto Padrão Hover', 'llrp' ),
                    ];
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
                    <tr>
                        <th colspan="2" style="padding-top: 2em; padding-bottom: 1em;"><h3><?php esc_html_e( 'Botão "Receber código por e-mail"', 'llrp' ); ?></h3></th>
                    </tr>
                    <?php
                    $code_button_fields = [
                        'color_btn_code_bg'           => __( 'Botão (bg)', 'llrp' ),
                        'color_btn_code_bg_hover'     => __( 'Botão Hover (bg)', 'llrp' ),
                        'color_btn_code_border'       => __( 'Botão (borda)', 'llrp' ),
                        'color_btn_code_border_hover' => __( 'Borda Hover', 'llrp' ),
                        'color_btn_code_text'         => __( 'Texto do Botão', 'llrp' ),
                        'color_btn_code_text_hover'   => __( 'Texto Hover', 'llrp' ),
                    ];
                    foreach ( $code_button_fields as $field => $label ) {
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
}

Llrp_Admin::init();