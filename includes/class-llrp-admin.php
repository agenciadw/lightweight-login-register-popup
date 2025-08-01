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
            'popup_width',
            'header_email', 'text_email', 'placeholder_email', 'button_email', 'text_login', 'placeholder_password', 'text_remember', 'button_login',
            'header_register', 'text_register', 'placeholder_register', 'button_register',
            // color settings including overlay
            'color_bg', 'color_header_bg', 'color_text', 'color_overlay',
            'color_link', 'color_link_hover',
            'color_btn_bg', 'color_btn_bg_hover', 'color_btn_border', 'color_btn_border_hover',
            'color_btn_text', 'color_btn_text_hover',
        ];
        foreach ( $settings as $field ) {
            register_setting( 'llrp_options', 'llrp_' . $field );
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
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

Llrp_Admin::init();