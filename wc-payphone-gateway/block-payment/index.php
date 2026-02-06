<?php
/**
 * Class WC_Payphone_Gateway_Blocks
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

final class WC_Payphone_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    
    /**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
    protected $name = 'payphone';

    public function initialize() {
        $gateways       = WC()->payment_gateways->payment_gateways();
		    $this->gateway  = $gateways[ $this->name ];
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
		$script_asset_path = PAYPHONE_G_BTN_PLUGIN_PATH . 'block-payment/build/payphone-gateway-btn.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version'      => $this->get_file_version( $script_asset_path ),
            );


        wp_register_script(
            'payphone-g-btn-blocks-integration',
            plugin_dir_url(__FILE__) . 'build/payphone-gateway-btn.js',
            $script_asset[ 'dependencies' ],
            $script_asset[ 'version' ],
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations(
                'payphone-g-btn-blocks-integration',
                'wc-payphone-gateway',
                plugin_dir_path( __FILE__ ) . 'languages'
            );
        }
        return [ 'payphone-g-btn-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'lang' => get_bloginfo('language'),
		    'icon'	      => PAYPHONE_G_BTN_IMG_URL . 'logo-cards.png',
            'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
        ];
    }

}
