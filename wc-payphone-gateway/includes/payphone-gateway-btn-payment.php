<?php

/**
 * Class Payphone_Gateway_Btn_Payment
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once dirname(payphone_gateway_btn()->file) . '/includes/exceptions/payphone-gateway-btn-exception.php';

class Payphone_Gateway_Btn_Payment extends WC_Payment_Gateway
{
    /* declarar variables para quitar warning Deprecated: Creation of dynamic property*/
    public $language;
    public $textactive;
    public $redirect_page_success_id;
    public $redirect_page_decline_id;
    public $token;
    public $storeId;

    public function __construct()
    {
        global $woocommerce;

        $this->supports[] = 'refunds';

        $this->id = 'payphone';
        $this->icon = PAYPHONE_G_BTN_IMG_URL . 'logo-cards.png';
        $this->method_description = __("Credit or debit cards | Payphone", 'wc-payphone-gateway');
        $this->has_fields = false;

        //Form and settings
        $this->init_form_fields();
        $this->init_settings();

        $this->language = get_bloginfo('language');
        $this->title = __("Credit or debit cards | Payphone", 'wc-payphone-gateway');
        $this->description = 'Usa tus tarjetas de crédito o débito Visa, Mastercard, Diners o Discover de cualquier banco del mundo y, si tienes la aplicación Payphone, utiliza tu saldo.';
        $this->textactive = 0;

        $this->redirect_page_success_id = get_site_url() . "/payphone-order//";
        $this->redirect_page_decline_id = get_site_url() . "/payphone-order-decline";

        $this->token = isset($this->settings['token']) ? sanitize_text_field($this->settings['token']) : '';
        $this->storeId = isset($this->settings['storeId']) ? sanitize_text_field($this->settings['storeId']) : null;

        add_action('payphone_g_btn_response', array($this, 'payphone_g_btn_response'));

        //callback url action
        add_action('woocommerce_api_payphone_gateway_btn_payment', array($this, 'payphone_g_btn_check_response'));
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            /* 2.0.0 */
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        } else {
            /* 1.6.6 */
            add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        }
    }

    /**
     * Check if Gateway can be display 
     *
     * @access public
     * @return void
     */
    function is_available()
    {
        global $woocommerce;

        if ($this->enabled == "yes"):
            if (!$this->is_valid_currency()) {
                return false;
            }
            if ($woocommerce->version < '1.5.8') {
                return false;
            }

            return true;
        endif;

        return false;
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = include (dirname(__FILE__) . '/settings/settings-pp.php');
    }

    /**
     * Output the gateway settings screen.
     */
    public function admin_options() {
        global $wp_rewrite;
        $front = $wp_rewrite->front;
        echo '<h3>Payphone</h3>';
        echo '<p>' . esc_html__('Pay with Payphone', 'wc-payphone-gateway') . '</p>';
        echo '<table class="form-table">';
        
        echo '<h3>' . esc_html__('Initial Key Setting', 'wc-payphone-gateway') . '</h3>';
        echo esc_html__('Response URL:', 'wc-payphone-gateway') . ' ' . esc_url(home_url($front . 'wc-api/payphone_gateway_btn_payment/'));
        echo '<br>';
    
        $this->generate_settings_html();
        echo '</table>';
    }

    function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);

        if (
            payphone_gateway_btn()->settings->get_active_api_credentials() == '' ||
            payphone_gateway_btn()->settings->get_active_api_credentials() == null
        ) {
            $errorMsg = __('You must set up a valid token', 'wc-payphone-gateway');
            do_action('payphone_g_btn_process_payment_error', $errorMsg, $order);
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception($errorMsg);
        }
        try {
            include_once (dirname(__FILE__) . '/payphone-gateway-btn-process.php');
            $process = new Payphone_Gateway_Btn_Process($order_id, payphone_gateway_btn()->settings->get_active_api_credentials(), payphone_gateway_btn()->settings->get_payphone_redirect_url(), $this->storeId);

            $response = $process->process();

            if (!isset($response->payWithCard)) {
                return array(
                    'result' => 'success',
                    'redirect' => $response->payWithPayPhone
                );
            }

            return array(
                'result' => 'success',
                'redirect' => $response->payWithCard
            );
        } catch (Exception $ex) {
            $errorMsg = "PayPhone_Exception: " . $ex->getMessage();
            do_action('payphone_g_btn_process_payment_error', $errorMsg, $order);
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception($errorMsg);
        }
    }

    /**
     * Process refund.
     *
     * @param int    $order_id Order ID
     * @param float  $amount   Order amount
     * @param string $reason   Refund reason
     *
     * @return boolean True or false based on success, or a WP_Error object.
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (0 == $amount || null == $amount) {
            return new WP_Error('payphone_refund_error', __('Refund Error: You need to specify a refund amount.', 'wc-payphone-gateway'));
        }

        if ($amount != $order->get_total()) {
            /* translators: %s: Total order value */
            return new WP_Error('payphone_refund_error', sprintf(esc_html__('Refund Error: PayPhone only allow refund order total %s', 'wc-payphone-gateway'), esc_html($order->get_total())));
        }

        try {
            include_once (dirname(__FILE__) . '/payphone-gateway-btn-refund.php');
            $refund = new Payphone_Gateway_Btn_Refund($order_id, payphone_gateway_btn()->settings->get_active_api_credentials(), payphone_gateway_btn()->settings->get_payphone_redirect_url());
            $result = $refund->refund();
            if ($result) {
                $order->add_order_note(__('PayPhone refund completed', 'wc-payphone-gateway'));
                return true;
            } else {
                return false;
            }
        } catch (Payphone_Gateway_Btn_Exception $ex) {
            /* translators: %s: Reason for refund error */
            return new WP_Error('payphone_refund_error', sprintf(esc_html__('Refund Error: %s', 'wc-payphone-gateway'), esc_html($ex->get_error()->message)));
        } catch (Exception $ex) {
            /* translators: %s: Reason for refund error */
            return new WP_Error('payphone_refund_error', sprintf(esc_html__('Refund Error: %s', 'wc-payphone-gateway'), esc_html($ex->getMessage())));
        }
    }

    /**
     * Check if current currency is valid for payphone
     *
     * @access public
     * @return bool
     */
    function is_valid_currency()
    {
        $supported = apply_filters(
            'payphone_g_btn_supported_currencies',
            array( 'ARS', 'BRL', 'COP', 'MXN', 'PEN', 'USD' )
        );
    
        return in_array( get_woocommerce_currency(), $supported, true );
    }

    /**
     * Check for valid payphone server callback
     *
     * @access public
     * @return void
     * */
    function payphone_g_btn_check_response()
    {
        ob_clean();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External request from PayPhone API; nonce verification not applicable.
        if (!empty($_REQUEST)) {
            // Sanear todos los parámetros de entrada
            $sanitized_request = [];

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External request from PayPhone API; nonce verification not applicable.
            foreach ($_REQUEST as $key => $value) {
                $sanitized_request[$key] = sanitize_text_field(wp_unslash($value));
            }

            header('HTTP/1.1 200 OK');
            do_action("payphone_g_btn_response", $sanitized_request);
        } else {
            wp_die(esc_html__("PayPhone Request Failure", 'wc-payphone-gateway'));
            exit;
        }
    }

    function payphone_g_btn_response($request)
    {
        $id = $request['id'];
        $order_id = $request['clientTransactionId'];

        if ($order_id != null) {
            $order = wc_get_order($order_id);

            $redirect_url = $this->redirect_page_decline_id . "?order=" . $order_id;

            try {
                if (isset($request['msg'])) {
                    $msg = $request['msg'];
                    $order->update_status('cancelled', esc_html($msg));
                    $this->update_payphone_post_meta($order, "mesaggeError", __('Payment error:', 'wc-payphone-gateway') . esc_html($msg));
                    wp_safe_redirect($redirect_url);
                    exit;
                }

                include_once (dirname(__FILE__) . '/payphone-gateway-btn-response.php');
                $result = new Payphone_Gateway_Btn_Response($order_id, $id, payphone_gateway_btn()->settings->get_active_api_credentials(), payphone_gateway_btn()->settings->get_payphone_redirect_url());


                $this->update_payphone_post_meta($order, 'payphone_tx_id', $id);

                $response = $result->confirm();

                if ($response->statusCode == 2) {
                    //For wooCoomerce 2.0
                    do_action('payphone_canceled_pay', $response);
                    $this->update_payphone_post_meta($order, "mesaggeError", __('Payment error:', 'wc-payphone-gateway') . $response->message);
                    wp_safe_redirect($redirect_url);
                }

                if ($response->statusCode == 3) {
                    $this->update_payphone_post_meta($order, 'Authorization number', $response->authorizationCode);
                    $this->update_payphone_post_meta($order, 'Card Brand', $response->cardBrand);
                    //Agregar datos de la respuesta de payphone a la orden de woocommerce
                    $order->update_meta_data('DataPayphone', json_encode($response));
                    $order->save();
                    do_action('payphone_approved_pay', $response);
                    wp_safe_redirect($this->redirect_page_success_id . $order_id);
                }
            } catch (Payphone_Gateway_Btn_Exception $ex) {
                //For wooCoomerce 2.0
                $order->update_status('cancelled', $ex->getMessage());
                $this->update_payphone_post_meta($order, "mesaggeError", __('Payment error:', 'wc-payphone-gateway') . $ex->getMessage());
                wp_safe_redirect($redirect_url);
            } catch (Exception $ex) {
                //For wooCoomerce 2.0
                $this->update_payphone_post_meta($order, "mesaggeError", __('Payment error:', 'wc-payphone-gateway') . $ex->getMessage());
                wp_safe_redirect($redirect_url);
            }
        } else {
            //For wooCoomerce 2.0
            $msg = $request['msg'];
            $redirect_url = add_query_arg('error', __('Payment error:', 'wc-payphone-gateway') . $msg, $this->redirect_page_decline_id);
            wp_safe_redirect($redirect_url);
        }
    }

    private function update_payphone_post_meta($order, $key, $value)
    {
        update_post_meta($order->get_id(), $key, $value);
    }

}
