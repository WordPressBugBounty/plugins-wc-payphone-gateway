<?php
/**
 * Class Payphone_Gateway_Btn_Loader
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Payphone_Gateway_Btn_Loader {

    public function __construct() {
        $includes_path = payphone_gateway_btn()->includes_path;

        require_once( $includes_path . 'payphone-gateway-btn-payment.php' );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['msg']) && !empty($_GET['msg'])) {
            add_action('the_content', array($this, 'payphone_g_btn_show_message'));
        }

        add_filter('woocommerce_payment_gateways', array($this, 'payphone_g_btn_register_gateways'));

        //Add block woocommerce
        add_action( 'woocommerce_blocks_loaded', array($this, 'payphone_g_btn_register_block' ) );
    }

    /**
     * Register the PayPhone payment methods.
     *
     * @param array $methods Payment methods.
     *
     * @return array Payment methods
     */
    public function payphone_g_btn_register_gateways($methods) {

        $methods[] = 'Payphone_Gateway_Btn_Payment';

        return $methods;
    }
    
    public function payphone_g_btn_show_message($content) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External redirect from PayPhone, nonce not applicable.
        $type = isset($_GET['type']) ? sanitize_html_class(wp_unslash($_GET['type'])) : 'notice';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- External redirect from PayPhone, value sanitized with wp_unslash() and escaped via esc_html().
        $msg  = isset($_GET['msg']) ? esc_html(urldecode(wp_unslash($_GET['msg']))) : '';

        return '<div class="' . esc_attr($type) . '">' . $msg . '</div>' . $content;
    }

    /**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public static function payphone_g_btn_register_block() {
        // Check if the required class exists
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            // Include the custom Blocks Checkout class
			require_once( dirname(payphone_gateway_btn()->file) . '/block-payment/index.php' );
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