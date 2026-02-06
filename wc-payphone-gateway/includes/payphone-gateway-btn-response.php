<?php
/**
 * Class Payphone_Gateway_Btn_Response
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once (dirname(payphone_gateway_btn()->file) . '/includes/exceptions/payphone-gateway-btn-exception.php');

class Payphone_Gateway_Btn_Response
{

    public $order_id;
    public $transaction_id;
    public $token;
    public $url;
    public $contador;

    public function __construct($order_id, $transaction_id, $token, $url)
    {
        $this->order_id = $order_id;
        $this->transaction_id = $transaction_id;
        $this->token = $token;
        $this->url = $url;
        $this->contador = 0;
    }

    public function confirm()
    {
        global $woocommerce;
        $order = new WC_Order($this->order_id);

        $result = $this->confirm_call($this->contador);
        $message = $result->message ?? '';

        if ($result == null) {
            $order->update_status('cancelled', __('No valid response was obtained', 'wc-payphone-gateway'));
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(__("Url not found, payment with PayPhone will be automatically reversed, contact the administrator", 'wc-payphone-gateway'));

        } else {
            if ($order->has_status(array('processing', 'completed'))) {
                return $result;
            }

            if ($result->statusCode == 2) {
                $order->update_status('cancelled', esc_html($message));
                throw new Exception(esc_html($message));
            }

            if ($result->statusCode == 3) {
                $order->payment_complete();
                $woocommerce->cart->empty_cart();
            }
        }
        return $result;
    }

    private function confirm_call($cont)
    {
        $payphone_args = $this->get_confirm_args();
        $json = json_encode($payphone_args);
        $headers = array(
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($json)
        );

        $args = array(
            'body' => $json,
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $headers
        );
        $response = wp_remote_post($this->url . "/api/button/V2/Confirm", $args);
        $info = wp_remote_retrieve_response_code($response);
        if (is_array($response)) {
            reset($response);
            $tipo = get_class(current($response));
        } else {
            $tipo = get_class($response);
        }
        if (strcmp($tipo, 'WP_Error') !== 0) {
            $obj_response = json_decode($response['body']);
            if ($info == 200 && $obj_response != null) {
                return json_decode($response['body']);
            }

            $cont = $cont + 1;
            if ($cont <= 1) {
                return $this->confirm_call($cont);
            }

            if ($obj_response == null) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new Payphone_Gateway_Btn_Exception(__("Url not found to confirm the transaction", 'wc-payphone-gateway'), $info['http_code'], $obj_response);

            }

            if ($obj_response->message) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new Payphone_Gateway_Btn_Exception(esc_html($obj_response->message), $info['http_code'], $obj_response);
            }
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(__('The request could not be completed', 'wc-payphone-gateway'));
        }
    }

    private function get_confirm_args()
    {
        $args = new stdClass();

        $args->id = $this->transaction_id;
        $args->clientTxId = $this->order_id;
        return $args;
    }
}