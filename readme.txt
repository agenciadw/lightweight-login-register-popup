=== Lightweight Login & Register Popup ===
Contributors: agenciadw
Tags: woocommerce, login, register, popup
Requires at least: 6.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Popup personalizável na página do carrinho para permitir login, cadastro e recuperação de senha sem recarregar a página

== Description ==

Este plugin adiciona um popup de login e registro na página do carrinho do WooCommerce, permitindo que os clientes façam login, se registrem ou recuperem sua senha sem sair da página.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/lightweight-login-register-popup` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the WooCommerce > Login Popup screen to configure the plugin

== Changelog ==

= 0.2.0 - 2025-08-17 =
* Feature: Added "Login with Code" functionality via email.
* Feature: Added WhatsApp integration for sending login codes using the Joinotify plugin.
* Feature: Added login with CPF and CNPJ.
* Feature: Added admin options to enable/disable CPF and CNPJ login.
* Feature: Added backend validation for CPF and CNPJ numbers.
* Enhancement: Improved security by sanitizing all inputs and adding explicit sanitization callbacks for settings.
* Enhancement: Added a password strength checker to the registration form.
* Enhancement: The "Forgot Password" flow now pre-fills the user's email address.
* Fix: Corrected a bug that caused the "Send Code" button to fail with a 400 error.
* Fix: Corrected a bug that caused the close button to be invisible.
* Fix: Corrected a bug that prevented the "Enter" key from submitting forms.
* Fix: Corrected a bug that caused the "Send Code" button to not use the correct colors from the admin panel.
* Fix: Corrected a bug that caused the user's email to be duplicated in the "Forgot Password" step.

= 0.1.0 =
* Initial release.
