<?php
/**
 * Plugin Name: Lightweight Login & Register Popup
 * Plugin URI: https://github.com/agenciadw/lightweight-login-register-popup
 * Description: Popup inteligente para WooCommerce com suporte a checkout de convidado. Inclui login social (Google/Facebook), login com CPF/CNPJ, persistência de carrinho, auto-preenchimento inteligente e compatibilidade total com Fluid Checkout e Brazilian Market. Detecta automaticamente configurações do WooCommerce e se adapta ao comportamento de checkout.
 * Version: 1.2.0
 * Author: David William da Costa
 * Author URI: https://github.com/agenciadw
 * Requires PHP: 7.4 or higher
 * Requires at least: 6.6
 * Tested up to: 6.6
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * Text Domain: lightweight-login-register-popup
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'LLRP_VERSION', '1.2.0' );
define( 'LLRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LLRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
function llrp_is_woocommerce_active() {
    return class_exists( 'WooCommerce' );
}

/**
 * Check if guest checkout is enabled in WooCommerce
 */
function llrp_is_guest_checkout_enabled() {
    if ( ! llrp_is_woocommerce_active() ) {
        return false;
    }
    
    return get_option( 'woocommerce_enable_guest_checkout' ) === 'yes';
}

/**
 * Admin notice for WooCommerce dependency
 */
function llrp_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'Lightweight Login & Register Popup', 'llrp' ); ?></strong>: 
            <?php esc_html_e( 'WooCommerce plugin is required for this plugin to work.', 'llrp' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Declare HPOS compatibility
 */
function llrp_declare_woocommerce_features_compatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
}

/**
 * Initialize plugin components
 */
function llrp_init() {
    // Check WooCommerce dependency
    if ( ! llrp_is_woocommerce_active() ) {
        add_action( 'admin_notices', 'llrp_woocommerce_missing_notice' );
        return;
    }
    
    // Include core classes
    require_once LLRP_PLUGIN_DIR . 'includes/class-llrp-frontend.php';
    require_once LLRP_PLUGIN_DIR . 'includes/class-llrp-ajax.php';
    
    if ( is_admin() ) {
        require_once LLRP_PLUGIN_DIR . 'includes/class-llrp-admin.php';
        Llrp_Admin::init();
    }
    
    // Initialize components
    Llrp_Frontend::init();
    Llrp_Ajax::init();
}

// Declare compatibility before WooCommerce init
add_action( 'before_woocommerce_init', 'llrp_declare_woocommerce_features_compatibility' );

// Initialize plugin after all plugins are loaded
add_action( 'plugins_loaded', 'llrp_init' );
