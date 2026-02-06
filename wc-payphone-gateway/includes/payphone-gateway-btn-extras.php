<?php
/**
 * Class Payphone_Gateway_Btn_Extras
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Payphone_Gateway_Btn_Extras
{

    public function __construct()
    {

        add_action('wp_ajax_payphone_g_btn_update_status', array($this, 'payphone_g_btn_update_status'));
        add_filter('woocommerce_admin_order_actions', array($this, 'payphone_g_btn_add_order_action_button'), 10, 2);
        add_action('woocommerce_cancel_unpaid_orders', array($this, 'payphone_g_btn_check_unpaid_orders'), 1, 0);
    }

    function payphone_g_btn_update_status()
    {
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash( $_REQUEST['_wpnonce'])), 'update_payphone_nonce')) {
            exit('No naughty business please');
        }
    
        // Sanear el parámetro recibido
        $order_id = isset($_REQUEST['order_id']) ? absint($_REQUEST['order_id']) : 0;
        if (!$order_id) {
            wp_send_json_error('Invalid order ID.');
            exit;
        }
    
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Order not found.');
            exit;
        }
    
        // Sanear metadatos también
        $client_tx_id = sanitize_text_field(get_post_meta($order_id, 'client_tx_id', true));
        $this->get_tx_status($client_tx_id, $order);
    
        // Distinguir si es AJAX
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REQUESTED_WITH']))) === 'xmlhttprequest'
        ) {
            wp_send_json_success();
        } else {
            // Redirigir solo a URLs internas
            $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : home_url();
            wp_safe_redirect($referer);
            exit;
        }
    }

    function payphone_g_btn_add_order_action_button($actions, $order)
    {
        wp_enqueue_style('PayPhoneStyle', PAYPHONE_G_BTN_CSS_URL . 'payphone_style.css', array(), PAYPHONE_G_BTN_VERSION);
        //payphone
        $method = $order->get_payment_method();
        if ($order->has_status(array('pending')) && $method == 'payphone') {
            $actions['update'] = array(
                'url' => wp_nonce_url(admin_url('admin-ajax.php?action=payphone_g_btn_update_status&order_id=' . $order->get_id()), 'update_payphone_nonce'),
                'name' => __('Verify transaction', 'wc-payphone-gateway'),
                'action' => "pp_refresh", // setting "view" for proper button CSS
            );
        }

        return $actions;
    }

    /*
     * Ejecuta esta tarea al correr el cron de woocommerce
     * Se configura por los ajustes de inventario
     * */

    function payphone_g_btn_check_unpaid_orders()
    {
        $held_duration = get_option('woocommerce_hold_stock_minutes');

        try {
            global $wpdb;
            payphone_g_btn_log('-----payphone_g_btn_check_unpaid_orders start------');

            if ($held_duration < 1 || get_option('woocommerce_manage_stock') != 'yes') {
                $held_duration = 1;
            }

            $date = gmdate("Y-m-d H:i:s", strtotime('-' . absint($held_duration) . ' MINUTES', current_time('timestamp', true)));
            $order_types = array_map('sanitize_key', wc_get_order_types());
            $status = sanitize_key('wc-pending');

            if ( ! empty( $order_types ) ) {
                $placeholders = implode( ', ', array_fill( 0, count( $order_types ), '%s' ) );
                $sql = "
                    SELECT posts.ID
                    FROM {$wpdb->posts} AS posts
                    WHERE posts.post_type IN ($placeholders)
                    AND posts.post_status = %s
                    AND posts.post_modified < %s
                ";

                $params = array_merge($order_types, [$status, $date]);
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $query = $wpdb->prepare($sql, ...$params);
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Custom query for pending WooCommerce orders, not suitable for WP_Query or caching.
                $unpaid_orders = $wpdb->get_col($query);
                payphone_g_btn_log($unpaid_orders);
                if ($unpaid_orders) {
                    foreach ($unpaid_orders as $unpaid_order) {
                        $order = wc_get_order($unpaid_order);
                        $client_tx_id = get_post_meta($unpaid_order, 'client_tx_id', TRUE);

                        $this->get_tx_status($client_tx_id, $order);
                    }
                }
                payphone_g_btn_log('-----payphone_g_btn_check_unpaid_orders finish------');
                wp_clear_scheduled_hook('woocommerce_cancel_unpaid_orders');
                wp_schedule_single_event(time() + (absint($held_duration) * 60), 'woocommerce_cancel_unpaid_orders');
            }
        } catch (Exception $exc) {
            payphone_g_btn_log($exc->getTraceAsString(), 'error');
            wp_clear_scheduled_hook('woocommerce_cancel_unpaid_orders');
            wp_schedule_single_event(time() + (absint($held_duration) * 60), 'woocommerce_cancel_unpaid_orders');
        }
    }

    /**
     * Obtiene el estado de una transaccion 
     * @global type $woocommerce
     * @param type $client_tx_id
     * @param type $order
     */
    /*
     * Pending payment – Order received (unpaid)
     * Failed – Payment failed or was declined (unpaid). Note that this status may not show immediately and instead show as Pending until verified (i.e., PayPal)
     * Processing – Payment received and stock has been reduced – the order is awaiting fulfillment. All product orders require processing, except those for Digital/Downloadable products.
     * Completed – Order fulfilled and complete – requires no further action
     * On-Hold – Awaiting payment – stock is reduced, but you need to confirm payment
     * Cancelled – Cancelled by an admin or the customer – no further action required
     * Refunded – Refunded by an admin – no further action required
     */
    private function get_tx_status($client_tx_id, $order)
    {

        try {
            global $woocommerce;

            $url = payphone_gateway_btn()->settings->get_payphone_redirect_url();
            $token = payphone_gateway_btn()->settings->get_active_api_credentials();

            $response = $this->statusCall($client_tx_id, $token, $url);
            $sale = $response[0];

            if ($sale->statusCode == 3) {
                $order->payment_complete();
                update_post_meta($order->get_id(), __('Authorization Code', 'wc-payphone-gateway'), $sale->authorizationCode);
                update_post_meta($order->get_id(), __('Card Brand', 'wc-payphone-gateway'), $sale->cardBrand);
                update_post_meta($order->get_id(), 'payphone_tx_id', $sale->transactionId);
                do_action('payphone_g_btn_pay_approved', $order->get_id(), $order);
                $woocommerce->cart->empty_cart();
            } else if ($sale->statusCode == 2) {
                /* translators: %s: Payphone rejection reason */
                $order->update_status('cancelled', sprintf(esc_html__('Payment denied. Reasons: %s', 'wc-payphone-gateway'), esc_html($sale->message)));
                update_post_meta($order->get_id(), __('Transaction Status', 'wc-payphone-gateway'), $sale->transactionStatus);
                update_post_meta($order->get_id(), __('Error Message', 'wc-payphone-gateway'), $sale->message);
                update_post_meta($order->get_id(), 'payphone_tx_id', $sale->transactionId);
                do_action('payphone_g_btn_pay_cancel', $order->get_id(), $order);
            } else if ($sale->statusCode == 1) {
                /* translators: %s: Payment pending message */
                $order->update_status('pending', sprintf(esc_html__('Pending payment, message: %s', 'wc-payphone-gateway'), esc_html($sale->message)));
                do_action('payphone_g_btn_pay_pending', $order->get_id(), $order);
            }
        } catch (Payphone_Gateway_Btn_Exception $exc) {
            payphone_g_btn_log(json_encode($exc->ErrorList));
            /* translators: %s: Reason for failed payment */
            $order->update_status('cancelled', sprintf(esc_html__('Failed order: %s', 'wc-payphone-gateway'), esc_html($exc->get_error()->message)));
        } catch (Exception $exc) {
            payphone_g_btn_log(json_encode($exc->getMessage()));
        }
    }

    private function statusCall($client_tx_id, $token, $url)
    {
        $headers = array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        );

        $args = array(
            'headers' => $headers
        );
        $response = wp_remote_get($url . "/api/sale/client/" . $client_tx_id, $args);
        $obj_response = json_decode($response['body']);
        $info = wp_remote_retrieve_response_code($response);
        $statusCode = $info;
        $tipo = get_class($response);
        if (strcmp($tipo, 'WP_Error') !== 0) {
            if ($statusCode == 200) {
                return $obj_response;
            } else {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new Payphone_Gateway_Btn_Exception(esc_html($obj_response->message), $statusCode, $obj_response);
            }
        } else {
            throw new Exception(esc_html__('An error has ocurred', 'wc-payphone-gateway'));
        }
    }
}
