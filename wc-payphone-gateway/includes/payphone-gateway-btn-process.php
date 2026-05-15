<?php
/**
 * Class Payphone_Gateway_Btn_Process
 */

 if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once (dirname(payphone_gateway_btn()->file) . '/includes/exceptions/payphone-gateway-btn-exception.php');

class Payphone_Gateway_Btn_Process
{

    public $order_id;
    public $token;
    public $url;
    public $storeId;

    public function __construct($order_id, $token, $url, $storeId)
    {
        $this->order_id = $order_id;
        $this->token = $token;
        $this->url = $url;
        $this->storeId = $storeId;
    }

    public function process()
    {
        $payphone_args = $this->get_payphone_args($this->order_id);

        $json = json_encode($payphone_args);

        if (!is_string($json)) {
            payphone_g_btn_log(
                'json_encode failed for order ' . $this->order_id . ': ' . json_last_error_msg(),
                'error'
            );
            throw new Exception(esc_html__('There was a problem with the order data while processing the payment with PayPhone. Please contact the store.', 'wc-payphone-gateway'));
        }

        $headers = array(
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
            'X-Client-Ecommerce' => '7',
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
        $response = wp_remote_post($this->url . "/api/button/Prepare", $args);

        if ( is_wp_error( $response ) ) {
            payphone_g_btn_log( 'WP_Error code: ' . $response->get_error_code() . ' — ' . $response->get_error_message(), 'error' );
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception( __( 'The request could not be completed', 'wc-payphone-gateway' ) );
        }

        $info = wp_remote_retrieve_response_code($response);
        if (is_array($response)) {
            reset($response);
            $tipo = get_class(current($response));
        } else {
            $tipo = get_class($response);
        }
        if (strcmp($tipo, 'WP_Error') !== 0) {
            $obj_response = json_decode($response['body']);
            if ($info == 200) {
                return $obj_response;
            } else {
                throw new Exception(esc_html($obj_response->message));
            }
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(__('The request could not be completed', 'wc-payphone-gateway'));
        }
    }

    private function get_payphone_args($order_id)
    {
        global $wp_rewrite;
        global $woocommerce;
        
        $front = $wp_rewrite->front;
        $order = new WC_Order($order_id);
        $ordettotal = str_replace(",", "", $order->get_total());
        $ordettotal = str_replace(".", "", $ordettotal);

        $request_params = new stdClass();
        $totalAmount = (round($order->get_total(), 2) * 100);
        $totalAmount = round($totalAmount, 0);
        $request_params->Amount = (int) $totalAmount;
        $request_params->AmountWithOutTax = 0;
        $request_params->AmountWithTax = 0;

        $iva = (round($order->total_tax, 2) * 100);
        $iva = round($iva, 0);
        $request_params->Tax = (int) ($iva);
        $items = $order->get_items();
        foreach ($items as $item) {

            if ($order->get_line_tax($item) > 0) {
                $request_params->AmountWithTax += $order->get_line_total($item, false, true);
            } else {
                $request_params->AmountWithOutTax += $order->get_line_total($item, false, true);
            }
        }

        //Revisar para cuando los servicios no cobran iva
        $fees = $order->get_fees();

        foreach ($fees as &$valor) {

            if ($valor['line_tax'] > 0) {
                $request_params->AmountWithTax += (round($valor['line_total'], 2));
            } else {
                $request_params->AmountWithOutTax += (round($valor['line_total'], 2));
            }
        }

        if ($order->get_shipping_tax() > 0) {
            $request_params->AmountWithTax += $order->get_total_shipping();
        } else {
            $request_params->AmountWithOutTax += $order->get_total_shipping();
        }

        $subtotal = (round($request_params->AmountWithTax, 2) * 100);
        $subtotal = round($subtotal, 0);
        $request_params->AmountWithTax = (int) $subtotal;
        $subtotalNoTax = (round($request_params->AmountWithOutTax, 2) * 100);
        $subtotalNoTax = round($subtotalNoTax, 0);
        $request_params->AmountWithOutTax = (int) $subtotalNoTax;
        
        $client_tx_id = $order_id;
        update_post_meta($order_id, 'client_tx_id', $client_tx_id);

        $request_params->ClientTransactionId = $client_tx_id;
        $request_params->ResponseUrl = home_url($front . 'wc-api/payphone_gateway_btn_payment/');
        $request_params->CancellationUrl = home_url($front . 'wc-api/payphone_gateway_btn_payment/');
        $request_params->Lang = explode('_', get_locale())[0];
        $request_params->Currency = $order->get_currency();
        $request_params->StoreId = $this->storeId;
        // safe_utf8 protects against legacy latin1 DB collations returning
        // non-UTF-8 bytes from get_bloginfo('name').
        $default_reference = "Pedido #" . $client_tx_id . " en: " . get_site_url() . " - " . $this->safe_utf8(get_bloginfo('name'));

        $use_sku_reference = (payphone_gateway_btn()->settings->use_sku_reference === 'yes');

        if ($use_sku_reference) {
            $sku_reference = $this->build_sku_reference($order);
            $request_params->reference = ($sku_reference !== '') ? $sku_reference : $this->safe_truncate($default_reference, 99);
        } else {
            $request_params->reference = $this->safe_truncate($default_reference, 99);
        }

        if (!empty($order->get_billing_country()) && !empty($order->get_billing_city())) {
            $request_params->order = $this->getDataBillTo($order, $client_tx_id);
        }

        $request_params->optionalParameter = "c:country/ " . $this->safe_utf8($order->get_billing_country()) . " | c:city/" . $this->safe_utf8($order->get_billing_city());
        return $request_params;
    }

    private function getDataBillTo($order, $client_tx_id)
    {
        $user_id = (int) $order->get_user_id();
        $customer_id = ($user_id > 0) ? (string) $user_id : '';

        $ip_address = '';
        if (class_exists('WC_Geolocation')) {
            $ip_address = WC_Geolocation::get_ip_address();
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip_address = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        // Every billing string goes through safe_utf8 so that data stored in
        // legacy latin1 collations does not break json_encode with invalid UTF-8.
        $billTo = array(
            "billToId" => (int) $client_tx_id,
            "address1" => $this->safe_utf8($order->get_billing_address_1()),
            "address2" => $this->safe_utf8($order->get_billing_address_2()),
            "country" => $this->safe_utf8($order->get_billing_country()),
            "state" => $this->safe_utf8($order->get_billing_state()),
            "locality" => $this->safe_utf8($order->get_billing_city()),
            "firstName" => $this->safe_utf8($order->get_billing_first_name()),
            "lastName" => $this->safe_utf8($order->get_billing_last_name()),
            "phoneNumber" => $this->safe_utf8($order->get_billing_phone()),
            "email" => $this->safe_utf8($order->get_billing_email()),
            "postalCode" => $this->safe_utf8($order->get_billing_postcode()),
            "customerId" => $customer_id,
            "ipAddress" => $ip_address,
        );

        $lineItems = array();
        foreach ($order->get_items() as $item) {
            $item_data = $item->get_data();
            $product = is_callable(array($item, 'get_product')) ? $item->get_product() : wc_get_product($item['product_id']);

            $qty = max(1, (int) $item_data['quantity']);
            $line_total = (float) $item_data['total'];
            $line_tax = (float) $item_data['total_tax'];
            $unit_price = round(round($line_total / $qty, 2) * 100, 2);
            $total_amt = round(round($line_total + $line_tax, 2) * 100, 2);
            $tax_amt = round(round($line_tax, 2) * 100, 2);

            $product_name = $this->safe_truncate(trim(wp_strip_all_tags($this->safe_utf8($item_data['name']))), 50);
            $sku = $product ? $this->safe_truncate(trim(wp_strip_all_tags($this->safe_utf8($product->get_sku()))), 50) : '';
            $desc = $product ? $this->safe_truncate(trim(wp_strip_all_tags($this->safe_utf8($product->get_short_description()))), 50) : '';

            $lineItems[] = array(
                "productName" => $product_name,
                "unitPrice" => $unit_price,
                "quantity" => (int) $item_data['quantity'],
                "totalAmount" => $total_amt,
                "taxAmount" => $tax_amt,
                "productSKU" => $sku,
                "productDescription" => $desc,
            );
        }

        if (!empty($order->get_shipping_method())) {
            $shipping_total = (float) $order->get_shipping_total();
            $shipping_tax = (float) $order->get_shipping_tax();
            $lineItems[] = array(
                "productName" => $this->safe_truncate(trim(wp_strip_all_tags($this->safe_utf8($order->get_shipping_method()))), 50),
                "unitPrice" => round(round($shipping_total, 2) * 100, 2),
                "quantity" => 1,
                "totalAmount" => round(round($shipping_total + $shipping_tax, 2) * 100, 2),
                "taxAmount" => round(round($shipping_tax, 2) * 100, 2),
                "productSKU" => $this->safe_truncate(trim(wp_strip_all_tags("Envio Order :#" . $client_tx_id)), 50),
                "productDescription" => $this->safe_truncate(trim(wp_strip_all_tags($this->safe_utf8($order->get_shipping_to_display()))), 50),
            );
        }

        return array(
            "billTo" => $billTo,
            "lineItems" => $lineItems,
        );
    }

    /**
     * UTF-8 safe truncation. Avoids splitting multi-byte characters mid-byte,
     * which would produce invalid UTF-8 and make json_encode return false.
     */
    private function safe_truncate($value, $length)
    {
        $value = (string) $value;
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length, 'UTF-8');
        }
        return substr($value, 0, $length);
    }

    /**
     * Coerce any string to valid UTF-8. Protects against legacy databases with
     * latin1 collation returning non-UTF-8 byte sequences in billing fields.
     */
    private function safe_utf8($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }
        if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($value, 'UTF-8', 'auto');
        }
        if (function_exists('iconv')) {
            $converted = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $value);
            return ($converted !== false) ? $converted : $value;
        }
        return $value;
    }

    /**
     * Build a reconciliation reference from cart SKUs as "SKU>xQty|SKU>xQty".
     * Products without SKU are skipped. Returns empty string when no item has a SKU.
     */
    private function build_sku_reference($order)
    {
        $tokens = array();
        foreach ($order->get_items() as $item) {
            $product = is_callable(array($item, 'get_product')) ? $item->get_product() : wc_get_product($item['product_id']);
            if (!$product) {
                continue;
            }
            $sku = trim(wp_strip_all_tags($this->safe_utf8($product->get_sku())));
            if ($sku === '') {
                continue;
            }
            $qty = (int) $item->get_quantity();
            $tokens[] = $sku . '>x' . $qty;
        }

        if (empty($tokens)) {
            return '';
        }

        $reference = implode('|', $tokens);

        if (mb_strlen($reference, 'UTF-8') <= 99) {
            return $reference;
        }

        // Cut at the last "|" within 99 chars so a SKU>xQty pair is never split.
        // If a single token already exceeds 99 chars (no "|" to respect), return ""
        // so the caller falls back to the default reference instead of emitting a
        // truncated pair that would violate the contract.
        $truncated = $this->safe_truncate($reference, 99);
        $last_pipe = mb_strrpos($truncated, '|', 0, 'UTF-8');
        return ($last_pipe !== false) ? mb_substr($truncated, 0, $last_pipe, 'UTF-8') : '';
    }
}
