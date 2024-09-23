<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Payphone_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    
    /**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
    protected $name = 'payphone';

    public function initialize() {
        //woocommerce_$name_settings
        $this->settings = get_option( 'woocommerce_payphone_settings', [] );
        $gateways       = WC()->payment_gateways->payment_gateways();
		    $this->gateway  = $gateways[ $this->name ];
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
		$script_asset_path = PAYPHONE_PATH . 'block-payment/build/payphone-gateway.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version'      => $this->get_file_version( $script_asset_path ),
            );


        wp_register_script(
            'payphone_gateway-blocks-integration',
            plugin_dir_url(__FILE__) . '/build/payphone-gateway.js',
            $script_asset[ 'dependencies' ],
            $script_asset[ 'version' ],
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'payphone_gateway-blocks-integration');
        }
        return [ 'payphone_gateway-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title'       => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
		    'icon'	      => IMGDIR . 'logo-woocommerce.png',
            'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
        ];
    }

}