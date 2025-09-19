<?php
/**
 * Plugin Name: Lightweight Login & Register Popup
 * Plugin URI: https://github.com/agenciadw/lightweight-login-register-popup
 * Description: Popup personalizável na página do carrinho para permitir login, cadastro e recuperação de senha sem recarregar a página
 * Version: 0.5.0
 * Author: David William da Costa
 * Author URI: https://github.com/agenciadw
 * Requires PHP: 7.4 or higher
 * Requires at least: 6.6
 * Text Domain: lightweight-login-register-popup
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'LLRP_VERSION', '0.5.0' );
define( 'LLRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LLRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include core classes
require_once LLRP_PLUGIN_DIR . 'includes/class-llrp-frontend.php';
require_once LLRP_PLUGIN_DIR . 'includes/class-llrp-ajax.php';
if ( is_admin() ) {
    require_once LLRP_PLUGIN_DIR . 'includes/class-llrp-admin.php';
}

/**
 * Initialize plugin components
 */
function llrp_init() {
    Llrp_Frontend::init();
    Llrp_Ajax::init();
    if ( is_admin() ) {
        Llrp_Admin::init();
    }
}
add_action( 'plugins_loaded', 'llrp_init' );
