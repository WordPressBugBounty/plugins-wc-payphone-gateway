<?php
/**
 * Class Payphone_Gateway_Btn_Refund
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Payphone_Gateway_Btn_Refund {

    private $order_id;
    private $token;
    private $uri;

    public function __construct($order_id, $token, $uri) {
        $this->order_id = $order_id;
        $this->token = $token;
        $this->uri = $uri;
    }

    public function refund() {
        $result = $this->refundCall();
        return $result;
    }

    private function refundCall() {
        $refund_args = $this->get_refund_args();
        $json = json_encode($refund_args);

        $headers = array(
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($json)
        );
        
        $args = array(
                  'body' => $json,
                  'timeout' => '60000',
                  'redirection' => '5',
                  'httpversion' => '1.0',
                  'blocking' => true,
                  'headers' => $headers
        );
        
        $urlreverso = $this->uri;
        
        $response = wp_remote_post( $urlreverso . "/api/reverse/set", $args );
        $info = wp_remote_retrieve_response_code( $response );
        if (is_array($response)){
            reset($response);
            $tipo = get_class(current($response));
        }else{
            $tipo = get_class($response);
        }
        if (strcmp($tipo, 'WP_Error') !== 0)
        {
            $obj_response = json_decode($response['body']);
            if ($info == 200) {
                return $obj_response;
            } else {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new Payphone_Gateway_Btn_Exception(esc_html($obj_response->message), $info, $obj_response);
            }
        }
        else
        {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(__('An error has ocurred', 'wc-payphone-gateway'));
        }
    }

    private function get_refund_args() {

        $order = new WC_Order($this->order_id);

        $request_params = new stdClass();
        $request_params->id = get_post_meta($order->get_id(), 'payphone_tx_id', TRUE);
        return $request_params;
    }

}
