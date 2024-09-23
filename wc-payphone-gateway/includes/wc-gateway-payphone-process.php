<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once (dirname(wc_gateway_payphone()->file) . '/includes/exceptions/wc-payphone-exception.php');

class WC_Gateway_PayPhone_Process
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
                throw new Exception($obj_response->message);
            }
        } else {
            throw new Exception(__('The request could not be completed', 'payphone'));
        }
    }

    private function get_payphone_args($order_id)
    {
        global $woocommerce;
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
        //cobrar un valor de servicio del 6% del total
        /* $service = (round($order->get_total()*0.06, 2) * 100);
         $service = round($service, 0);
         $request_params->service = (int)$service;
         $request_params->Amount = (int)$totalAmount+(int)$service;*/


        //Client idn_to_utf8(8)
        $fecha = new DateTime();
        $client_tx_id = $order_id; //. $fecha->getTimestamp();
        //$client_tx_id = "payphone_" . $fecha->getTimestamp();
        update_post_meta($order_id, 'client_tx_id', $client_tx_id);

        $request_params->ClientTransactionId = $client_tx_id;
        $request_params->ResponseUrl = get_site_url() . '/wc-api/WC_Gateway_PayPhone';
        $request_params->CancellationUrl = get_site_url() . '/wc-api/WC_Gateway_PayPhone';
        $request_params->Lang = explode('_', get_locale())[0];
        $request_params->Currency = $order->get_currency();
        $request_params->StoreId = $this->storeId;
        $request_params->reference = "Pedido #" . $client_tx_id . " en: " . get_bloginfo('name') . " " . get_site_url();

        //creamos el arreglo con datos de facturacion billTo con el formato requerido
        $billTo = [
            "address1" => $order->get_billing_address_1(),
            "address2" => $order->get_billing_address_2(),
            "country" => $order->get_billing_country(),
            "state" => $order->get_billing_state(),
            "locality" => $order->get_billing_city(),
            "firstName" => $order->get_billing_first_name(),
            "lastName" => $order->get_billing_last_name(),
            "phoneNumber" => $order->get_billing_phone(),
            "email" => $order->get_billing_email(),
            "postalCode" => $order->get_billing_postcode(),
            "customerId" => $order->get_user_id()
        ];
        $lineItems = [];
        //Recupera solo la lista de productos del carrito de compras
        $items = $order->get_items();
        //Recorremos los productos lo guardamos en el arreglo lineItems en el formato requerido
        foreach ($items as $item) {
            //Recupera detalles del producto    
            $product = wc_get_product($item['product_id']);
            //Recupera detalles del producto  en el carrito
            $item_data = $item->get_data();
            $productos = [
                "productName" => substr(trim(strip_tags($item_data['name'])), 0, 50),
                "unitPrice" => round(round($product->get_price(), 2) * 100, 2),
                "quantity" => $item_data['quantity'],
                "totalAmount" => round(round(($item_data['total'] + $item_data['total_tax']), 2) * 100, 2),
                "taxAmount" => round(round($item_data['total_tax'], 2) * 100, 2),
                "productSKU" => substr(trim(strip_tags($product->get_sku())), 0, 50),
                "productDescription" => substr(trim(strip_tags($product->get_short_description())), 0, 50)
            ];
            $lineItems[] = $productos;
        }
        //DATOS DE ENVIO
        if (!empty($order->get_shipping_method())) {
            $envio = [
                "productName" => substr(trim(strip_tags($order->get_shipping_method())), 0, 50),
                "unitPrice" => round(round($order->get_shipping_total(), 2) * 100, 2),
                "quantity" => 1,
                "totalAmount" => round(round($order->get_shipping_total() + $order->get_shipping_tax(), 2) * 100, 2),
                "taxAmount" => round(round($order->get_shipping_tax(), 2) * 100, 2),
                "productSKU" => substr(trim(strip_tags("Envio Order :#" . $client_tx_id)), 0, 50),
                "productDescription" => substr(trim(strip_tags($order->get_shipping_to_display())), 0, 50)
            ];
            $lineItems[] = $envio;
        }


        $orderArray = array_merge(array("billTo" => $billTo), array("lineItems" => $lineItems));
        if (!empty($order->get_billing_country()) && !empty($order->get_billing_city())) {
            $request_params->order = $orderArray;
        }
        //$request_params->optionalParameter="'".json_encode($orderArray)."'";
        $request_params->optionalParameter = "c:country/ " . $order->get_billing_country() . " | c:city/" . $order->get_billing_city();
        return $request_params;
    }

}