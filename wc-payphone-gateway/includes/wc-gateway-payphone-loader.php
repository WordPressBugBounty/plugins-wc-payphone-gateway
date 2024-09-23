<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_PayPhone_Loader {

    public function __construct() {
        $includes_path = wc_gateway_payphone()->includes_path;

        require_once( $includes_path . 'wc-gateway-payphone.php' );

        if (isset($_GET['msg']) && !empty($_GET['msg'])) {
            add_action('the_content', array($this, 'showpayphoneMessage'));
        }

        add_filter('woocommerce_payment_gateways', array($this, 'payment_gateways'));

        //Add block woocommerce
        add_action( 'woocommerce_blocks_loaded', array($this, 'woocommerce_payphone_gateway_block' ) );
    }

    /**
     * Register the PayPhone payment methods.
     *
     * @param array $methods Payment methods.
     *
     * @return array Payment methods
     */
    public function payment_gateways($methods) {
        $settings = wc_gateway_payphone()->settings;

        $methods[] = 'WC_Gateway_PayPhone';

        return $methods;
    }
    
    public function showpayphoneMessage($content) {
        return '<div class="' . htmlentities($_GET['type']) . '">' . htmlentities(urldecode($_GET['msg'])) . '</div>' . $content;
    }

    /**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public static function woocommerce_payphone_gateway_block() {
        // Check if the required class exists
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            // Include the custom Blocks Checkout class
			require_once( dirname(wc_gateway_payphone()->file) . '/block-payment/index.php' );
            // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    // Register an instance of My_Custom_Gateway_Blocks
					$payment_method_registry->register( new WC_Payphone_Gateway_Blocks() );
				}
			);
		}
	}

}