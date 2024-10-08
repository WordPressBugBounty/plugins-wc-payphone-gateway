<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once (dirname(wc_gateway_payphone()->file) . '/includes/exceptions/wc-payphone-exception.php');

/**
 * 
 */
class WC_Gateway_PayPhone extends WC_Payment_Gateway
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
        $this->icon = IMGDIR . 'logo-woocommerce.png';
        $this->method_description = __("Tarjetas de crédito o débito Visa y Mastercard | Payphone", 'payphone');
        $this->has_fields = false;

        //Form and settings
        $this->init_form_fields();
        $this->init_settings();

        $this->language = get_bloginfo('language');
        $this->title = __("Tarjetas de crédito o débito Visa y Mastercard | Payphone", 'payphone');
        $this->description = $this->settings['description'];
        $this->textactive = 0;

        //$this->payphone_language = $this->settings['payphone_language'];
        $this->redirect_page_success_id = get_site_url() . "/payphone-order//";
        $this->redirect_page_decline_id = get_site_url() . "/payphone-order-decline";

        $this->token = $this->settings['token'];
        $this->storeId = $this->settings['storeId'];

        add_action('payphone_response', array($this, 'payphone_response'));

        //callback url action
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_payphone_response'));
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            /* 2.0.0 */
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
        } else {
            /* 1.6.6 */
            add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
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
    public function admin_options()
    {
        echo '<h3>PayPhone</h3>';
        echo '<p>' . __('Pay with PayPhone', 'payphone') . '</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        echo '<h3>' . __('Initial Key Setting', 'payphone') . '</h3>';
        echo __('Response URL:', 'payphone') . ' ' . get_site_url() . '/wc-api/WC_Gateway_PayPhone';
        echo '<br>';

        $this->generate_settings_html();
        echo '</table>';
    }

    /**
     * Do some additonal validation before saving options via the API.
     */
    public function process_admin_options()
    {

        parent::process_admin_options();

        // Validate credentials.
        $this->validate_active_credentials();
    }

    /**
     * Validate the provided credentials.
     */
    protected function validate_active_credentials()
    {
        $settings_array = (array) get_option('woocommerce_ppec_paypal_settings', array());
        update_option('woocommerce_ppec_paypal_settings', $settings_array);
    }

    function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);

        if (
            wc_gateway_payphone()->settings->get_active_api_credentials() == '' ||
            wc_gateway_payphone()->settings->get_active_api_credentials() == null
        ) {
            $errorMsg = __('You must set up a valid token', 'payphone');
            do_action('wc_gateway_stripe_process_payment_error', $errorMsg, $order);
            throw new Exception($errorMsg);
        }
        try {
            include_once (dirname(__FILE__) . '/wc-gateway-payphone-process.php');
            $process = new WC_Gateway_PayPhone_Process($order_id, wc_gateway_payphone()->settings->get_active_api_credentials(), wc_gateway_payphone()->settings->get_payphone_redirect_url(), $this->storeId);

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
            do_action('wc_gateway_stripe_process_payment_error', $errorMsg, $order);
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
            return new WP_Error('payphone_refund_error', __('Refund Error: You need to specify a refund amount.', 'payphone'));
        }

        if ($amount != $order->get_total()) {
            return new WP_Error('payphone_refund_error', sprintf(__('Refund Error: PayPhone only allow refund order total %s', 'payphone'), $order->get_total()));
        }

        try {
            include_once (dirname(__FILE__) . '/wc-gateway-payphone-refund.php');
            $refund = new WC_Gateway_PayPhone_Refund($order_id, wc_gateway_payphone()->settings->get_active_api_credentials(), wc_gateway_payphone()->settings->get_payphone_redirect_url());
            $result = $refund->refund();
            if ($result) {
                $order->add_order_note(__('PayPhone refund completed', 'payphone'));
                return true;
            } else {
                return false;
            }
        } catch (WC_PayPhone_Exception $ex) {
            return new WP_Error('payphone_refund_error', sprintf(__('Refund Error: %s'), $ex->get_error()->message));
        } catch (Exception $ex) {
            return new WP_Error('payphone_refund_error', sprintf(__('Refund Error: %s'), $ex->getMessage()));
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
        if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_payphone_supported_currencies', array('ARS', 'BRL', 'COP', 'MXN', 'PEN', 'USD'))))
            return false;

        return true;
    }

    /**
     * Check for valid payphone server callback
     *
     * @access public
     * @return void
     * */
    function check_payphone_response()
    {
        @ob_clean();
        if (!empty($_REQUEST)) {
            header('HTTP/1.1 200 OK');
            do_action("payphone_response", $_REQUEST);
        } else {
            wp_die(__("PayPhone Request Failure", 'payphone'));
        }
    }

    function payphone_response($request)
    {
        $id = $request['id'];
        $order_id = $request['clientTransactionId'];

        if ($order_id != null) {
            $order = wc_get_order($order_id);

            $redirect_url = $this->redirect_page_decline_id . "?order=" . $order_id;

            try {
                if (isset($request['msg'])) {

                    //For wooCoomerce 2.0
                    $msg = $request['msg'];
                    $order->update_status('cancelled', __($msg, 'payphone'));
                    $this->update_payphone_post_meta($order, "mesaggeError", __('Payment error:', 'payphone') . $msg);
                    wp_redirect($redirect_url);
                }

                include_once (dirname(__FILE__) . '/wc-gateway-payphone-response.php');
                $result = new WC_Gateway_PayPhone_Response($order_id, $id, wc_gateway_payphone()->settings->get_active_api_credentials(), wc_gateway_payphone()->settings->get_payphone_redirect_url());


                $this->update_payphone_post_meta($order, 'payphone_tx_id', $id);

                $response = $result->confirm();

                if ($response->statusCode == 2) {
                    //For wooCoomerce 2.0
                    do_action('payphone_canceled_pay', $response);
                    $this->update_payphone_post_meta($order, "mesaggeError", __('Payment error:', 'payphone') . $response->message);
                    wp_redirect($redirect_url);
                    //throw new Exception(__('Payment error:', 'payphone') . $result->message);  
                }

                if ($response->statusCode == 3) {
                    $this->update_payphone_post_meta($order, 'Authorization number', $response->authorizationCode);
                    $this->update_payphone_post_meta($order, 'Card Brand', $response->cardBrand);
                    //Agregar datos de la respuesta de payphone a la orden de woocommerce
                    $order->update_meta_data('DataPayphone', json_encode($response));
                    $order->save();
                    do_action('payphone_approved_pay', $response);
                    wp_redirect($this->redirect_page_success_id . $order_id);
                }
            } catch (WC_PayPhone_Exception $ex) {
                //For wooCoomerce 2.0
                $order->update_status('cancelled', $ex->getMessage());
                $this->update_payphone_post_meta($order, "mesaggeError", __('Payment error:', 'payphone') . $ex->getMessage());
                wp_redirect($redirect_url);
            } catch (Exception $ex) {
                //For wooCoomerce 2.0
                $this->update_payphone_post_meta($order, "mesaggeError", __('Payment error:', 'payphone') . $ex->getMessage());
                wp_redirect($redirect_url);
            }
        } else {
            //For wooCoomerce 2.0
            $msg = $request['msg'];
            $redirect_url = add_query_arg('error', __('Payment error:', 'payphone') . $msg, $this->redirect_page_decline_id);
            wp_redirect($redirect_url);
        }
    }

    private function update_payphone_post_meta($order, $key, $value)
    {
        update_post_meta($order->get_id(), $key, $value);
    }

}