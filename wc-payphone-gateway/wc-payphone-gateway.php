<?php

/*
  Plugin Name: Payphone - Payment Gateway Button
  Plugin URI: https://www.payphone.app/business/
  Description: Accept payments in your online store with Visa, Mastercard, Diners, Discover cards or Payphone balance using our plugin.
  Version: 3.2.6
  Author: Payphone.
  Author URI: https://www.payphone.app/
  Text Domain: wc-payphone-gateway
  Domain Path: /languages/
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if (!defined('PAYPHONE_G_BTN_VERSION')) {
    define('PAYPHONE_G_BTN_VERSION', '3.2.6');
}

if (!defined('PAYPHONE_G_BTN_PLUGIN_PATH')) {
    define('PAYPHONE_G_BTN_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('PAYPHONE_G_BTN_PLUGIN_URL')) {
    define( 'PAYPHONE_G_BTN_PLUGIN_URL', plugin_dir_url(__FILE__) );
}

/**
 * Agrega reglas personalizadas para las páginas virtuales de PayPhone.
 */
function payphone_g_btn_add_rewrite_rules( $wp_rewrite ) {
    $new_rules = array(
        'payphone-order/([0-9]+)/?$' => 'index.php?order-id=$matches[1]',
        'payphone-order-decline/?$'  => 'index.php?order-id=0',
    );

    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
}
add_filter( 'generate_rewrite_rules', 'payphone_g_btn_add_rewrite_rules' );

/**
 * Agregar el parametro de la pagina virtual
 */
function payphone_g_btn_add_query_vars(array $vars): array
{
    $vars[] = 'order-id';
    return $vars;
}
add_filter('query_vars', 'payphone_g_btn_add_query_vars');

/**
 * Template que se muestra en la pagina virtual
 */
function payphone_g_btn_template_redirect(): void
{
    $orderId = get_query_var('order-id');

    // Cargar estilo en la pagina virtual
    wp_enqueue_style(
        'payphone-g-btn-order-page',
        PAYPHONE_G_BTN_PLUGIN_URL . '/assets/css/payphone-order.css',
        array(),
        PAYPHONE_G_BTN_VERSION
    );

    // Asegurar que los estilos se impriman
    wp_print_styles('payphone-g-btn-order-page');

    if ($orderId == "0") {
        include_once plugin_dir_path(__FILE__) . 'templates/payphone-order-decline-template.php';
        exit;
    } else if ($orderId) {
        include_once plugin_dir_path(__FILE__) . 'templates/payphone-order-template.php';
        exit;
    }
}
add_action('template_redirect', 'payphone_g_btn_template_redirect');

register_activation_hook( __FILE__, 'payphone_g_btn_activate' );

function payphone_g_btn_activate()
{
    flush_rewrite_rules();
}


/**
 * Return instance of Payphone_Gateway_Btn_Plugin.
 *
 * @return Payphone_Gateway_Btn_Plugin
 */
function payphone_gateway_btn()
{
    static $plugin;

    if (!isset($plugin)) {
        require_once ('includes/payphone-gateway-btn-plugin.php');
        $plugin = new Payphone_Gateway_Btn_Plugin(__FILE__, PAYPHONE_G_BTN_VERSION);
    }

    return $plugin;
}

payphone_gateway_btn()->maybe_run();

if ( ! function_exists( 'payphone_g_btn_log' ) ) {
    /**
     * Log helper for PayPhone Gateway.
     *
     * @param mixed $message Data to log.
     * @param string $level Optional. Log level: 'info', 'warning', 'error'. Default 'info'.
     */
    function payphone_g_btn_log( $message, $level = 'info' ) {
        if ( ! class_exists( 'WC_Logger' ) ) {
            return;
        }

        $logger = wc_get_logger();

        if ( is_array( $message ) || is_object( $message ) ) {
            $message = wp_json_encode( $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        }

        $logger->log( $level, $message, [ 'source' => 'wc-payphone-gateway' ] );
    }
}