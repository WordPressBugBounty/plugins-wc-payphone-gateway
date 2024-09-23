<?php

/*
  Plugin Name: WooCommerce - PayPhone Gateway
  Plugin URI: https://www.payphone.app/business/
  Description: PayPhone Payment Gateway for WooCommerce. Recibe pagos en internet mediante payphone!
  Version: 3.1.2
  Author: Payphone.
  Author URI: https://www.payphone.app/
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define("PAYPHONE_PATH", plugin_dir_path(__FILE__));

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!defined('WC_GATEWAY_PAYPHONE_VERSION')) {
    define('WC_GATEWAY_PAYPHONE_VERSION', '2.1.0');
}

//Agregar el filter para crear la pagina virtual
add_filter('generate_rewrite_rules', function ($wp_rewrite) {
    $wp_rewrite->rules = array_merge(
        ['payphone-order/(\d+)/?$' => 'index.php?order-id=$matches[1]'],
        $wp_rewrite->rules
    );
    $wp_rewrite->rules = array_merge(
        ['payphone-order-decline?$' => 'index.php?order-id=0'],
        $wp_rewrite->rules
    );
});
//Agregar el parametro de la pagina virtual
add_filter('query_vars', 'payphone_order_plugin_add_query_vars');
//Template que se muestra en la pagina virtual
add_action('template_redirect', 'payphone_order_plugin_template_redirect');

function payphone_order_plugin_add_query_vars(array $vars): array
{
    $vars[] = 'order-id';
    return $vars;
}

function payphone_order_plugin_template_redirect(): void
{
    $orderId = get_query_var('order-id');
    if ($orderId == "0") {
        include_once plugin_dir_path(__FILE__) . 'templates/payphone-order-decline-template.php';
        exit;
    } else if ($orderId) {
        include_once plugin_dir_path(__FILE__) . 'templates/payphone-order-template.php';
        exit;
    }
}

register_activation_hook(__DIR__ . '/woocommerce-gateway-payphone.php', 'payphone_order_plugin_activate');

function payphone_order_plugin_activate()
{
    flush_rewrite_rules();
}



/**
 * Return instance of WC_Gateway_PayPhone_Plugin.
 *
 * @return WC_Gateway_PayPhone_Plugin
 */
function wc_gateway_payphone()
{
    static $plugin;

    if (!isset($plugin)) {
        require_once ('includes/wc-gateway-payphone-plugin.php');
        $plugin = new WC_Gateway_PayPhone_Plugin(__FILE__, WC_GATEWAY_PAYPHONE_VERSION);
    }

    return $plugin;
}

wc_gateway_payphone()->maybe_run();

if (!function_exists('write_log')) {

    function write_log($log)
    {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }

}