<?php
/**
 * Class Payphone_Gateway_Btn_Plugin
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Payphone_Gateway_Btn_Plugin
{

    const ALREADY_BOOTSTRAPED = 1;
    const DEPENDENCIES_UNSATISFIED = 2;

    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;

    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public $includes_path;

    /**
     * Flag to indicate the plugin has been boostrapped.
     *
     * @var bool
     */
    private $_bootstrapped = false;

    /**
     * Instance of Payphone_Gateway_Btn_Settings.
     *
     * @var Payphone_Gateway_Btn_Settings
     */
    public $settings;

    /* declarar variables para quitar warning Deprecated: Creation of dynamic property*/
    public $gateway_loader;
    public $extras;


    public function __construct($file, $version)
    {
        $this->file = $file;
        $this->version = $version;

        // Path.
        $this->includes_path = PAYPHONE_G_BTN_PLUGIN_PATH . trailingslashit('includes');

        // Updates
        if (version_compare($version, get_option('payphone_g_btn_version'), '>')) {
            $this->run_updater($version);
        }

        if (!defined('PAYPHONE_G_BTN_IMG_URL')) {
            define( 'PAYPHONE_G_BTN_IMG_URL', PAYPHONE_G_BTN_PLUGIN_URL . 'assets/img/' );
        }

        if (!defined('PAYPHONE_G_BTN_CSS_URL')) {
            define( 'PAYPHONE_G_BTN_CSS_URL', PAYPHONE_G_BTN_PLUGIN_URL . 'assets/css/' );
        }

    }

    /**
     * Handle updates.
     * @param  [type] $new_version [description]
     * @return [type]              [description]
     */
    private function run_updater($new_version)
    {
        
        // Map old settings to settings API
        if (get_option('pp_woo_enabled')) {
            $settings_array = (array) get_option('woocommerce_payphone_settings', array());
            $settings_array['enabled'] = get_option('pp_woo_enabled') ? 'yes' : 'no';
            $settings_array['logo_image_url'] = get_option('pp_woo_logoImageUrl');
            $settings_array['paymentAction'] = strtolower(get_option('pp_woo_paymentAction', 'sale'));
            $settings_array['subtotal_mismatch_behavior'] = 'addLineItem' === get_option('pp_woo_subtotalMismatchBehavior') ? 'add' : 'drop';
            $settings_array['environment'] = get_option('pp_woo_environment');
            $settings_array['button_size'] = get_option('pp_woo_button_size');
            $settings_array['instant_payments'] = get_option('pp_woo_blockEChecks');
            $settings_array['require_billing'] = get_option('pp_woo_requireBillingAddress');
            $settings_array['debug'] = get_option('pp_woo_logging_enabled') ? 'yes' : 'no';

            // Make sure button size is correct.
            if (!in_array($settings_array['button_size'], array('small', 'medium', 'large'))) {
                $settings_array['button_size'] = 'medium';
            }

            // Load client classes before `is_a` check on credentials instance.
            $this->_load_client();

            update_option('woocommerce_payphone_settings', $settings_array);
            delete_option('pp_woo_enabled');
        }

        update_option('payphone_g_btn_version', $new_version);
    }



    /**
     * Maybe run the plugin.
     */
    public function maybe_run()
    {
        add_action('plugins_loaded', array($this, 'payphone_g_btn_bootstrap'));

        add_filter('plugin_action_links_' . plugin_basename($this->file), array($this, 'payphone_g_btn_plugin_action_links'));
        add_action('wp_ajax_payphone_g_btn_dismiss_notice', array($this, 'payphone_g_btn_ajax_dismiss_notice'));

        add_filter('load_textdomain_mofile', array($this, 'payphone_g_btn_load_custom_textdomain'), 10, 2);
    }

    /**
     * Carga el archivo de traducción personalizado para el plugin PayPhone, para el idioma Español
     * Cambiar el archivo locale es_* a es_EC
     *
     * @param string $mofile  Ruta del archivo .mo.
     * @param string $domain  Dominio de texto.
     * @return string Ruta del archivo .mo final.
    */
    function payphone_g_btn_load_custom_textdomain($mofile, $domain)
    {
        if ($domain === "wc-payphone-gateway" && strpos($mofile, 'es_') !== false) {
            $mofile = PAYPHONE_G_BTN_PLUGIN_PATH . 'languages/' . $domain . '-es_EC.mo';
        }
        return $mofile;
    }

    public function payphone_g_btn_bootstrap()
    {
        try {
            if ($this->_bootstrapped) {
                throw new Exception(__('Payphone - Payment Gateway Button plugin can only be called once', 'wc-payphone-gateway'), self::ALREADY_BOOTSTRAPED);
            }

            $this->check_dependencies();
            $this->load_handlers();

            $this->_bootstrapped = true;
            delete_option('payphone_g_btn_bootstrap_warning_message');
        } catch (Exception $e) {
            if (in_array($e->getCode(), array(self::ALREADY_BOOTSTRAPED, self::DEPENDENCIES_UNSATISFIED))) {
                update_option('payphone_g_btn_bootstrap_warning_message', $e->getMessage());
            }
            //Desactiva el plugin
            require_once (ABSPATH . 'wp-admin/includes/plugin.php');
            deactivate_plugins($this->file);
            //Muestra la noticia con el error provocado
            add_action('admin_notices', array($this, 'payphone_g_btn_show_bootstrap_warning'));
            //Elimina el mensaje de activado
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking and unsetting $_GET['activate'] safely; no data is used or processed.
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }

    public function payphone_g_btn_show_bootstrap_warning()
    {
        $dependencies_message = get_option('payphone_g_btn_bootstrap_warning_message', '');
        if (!empty($dependencies_message) && 'yes' !== get_option('payphone_g_btn_bootstrap_warning_message_dismissed', 'no')) {
            ?>
            <div class="notice notice-warning is-dismissible pp-dismiss-bootstrap-warning-message">
                <p>
                    <strong>
                        <?php echo esc_html($dependencies_message); ?>
                    </strong>
                </p>
            </div>
            <script>
                (function ($) {
                    $('.pp-dismiss-bootstrap-warning-message').on('click', '.notice-dismiss', function () {
                        jQuery.post("<?php echo esc_url(admin_url('admin-ajax.php')); ?>", {
                            action: "payphone_g_btn_dismiss_notice",
                            dismiss_action: "pp_dismiss_bootstrap_warning_message",
                            nonce: "<?php echo esc_js(wp_create_nonce('pp_dismiss_notice')); ?>"
                        });
                    });
                })(jQuery);
            </script>
            <?php
        }
    }

    /**
     * AJAX handler for dismiss notice action.
     *
     * @since 1.4.7
     * @version 1.4.7
     */
    public function payphone_g_btn_ajax_dismiss_notice()
    {
        if (empty($_POST['dismiss_action'])) {
            return;
        }

        check_ajax_referer('pp_dismiss_notice', 'nonce');
        switch ($_POST['dismiss_action']) {
            case 'pp_dismiss_bootstrap_warning_message':
                update_option('payphone_g_btn_bootstrap_warning_message_dismissed', 'yes');
                break;
        }
        wp_die();
    }

    /**
     * Check dependencies.
     *
     * @throws Exception
     */
    protected function check_dependencies()
    {
        if (!function_exists('WC')) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(__('Payphone - Payment Gateway Button requires WooCommerce to be activated', 'wc-payphone-gateway'), self::DEPENDENCIES_UNSATISFIED);
        }

        if (!class_exists('WC_Payment_Gateway')) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(__('Payphone - Payment Gateway Button requires WC_Payment_Gateway class', 'wc-payphone-gateway'), self::DEPENDENCIES_UNSATISFIED);
        }

        if (version_compare(WC()->version, '2.5', '<')) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(__('Payphone - Payment Gateway Button requires WooCommerce version 2.5 or greater', 'wc-payphone-gateway'), self::DEPENDENCIES_UNSATISFIED);
        }

        if (!function_exists('curl_init')) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(__('Payphone - Payment Gateway Button requires cURL to be installed on your server', 'wc-payphone-gateway'), self::DEPENDENCIES_UNSATISFIED);
        }

        $openssl_warning = __('Payphone - Payment Gateway Button requires OpenSSL >= 1.0.1 to be installed on your server', 'wc-payphone-gateway');
        if (!defined('OPENSSL_VERSION_TEXT')) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception($openssl_warning, self::DEPENDENCIES_UNSATISFIED);
        }

        preg_match('/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches);
        if (empty($matches[1])) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception($openssl_warning, self::DEPENDENCIES_UNSATISFIED);
        }

        if (!version_compare($matches[1], '1.0.1', '>=')) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception($openssl_warning, self::DEPENDENCIES_UNSATISFIED);
        }
    }

    /**
     * Load handlers.
     */
    protected function load_handlers()
    {
        // Load handlers.
        require_once ($this->includes_path . 'payphone-gateway-btn-settings.php');
        require_once ($this->includes_path . 'payphone-gateway-btn-loader.php');
        require_once ($this->includes_path . 'payphone-gateway-btn-extras.php');

        $this->settings = new Payphone_Gateway_Btn_Settings();
        $this->gateway_loader = new Payphone_Gateway_Btn_Loader();
        $this->extras = new Payphone_Gateway_Btn_Extras();
    }

    /**
     * Add relevant links to plugins page.
     *
     * @since 1.2.0
     *
     * @param array $links Plugin action links
     *
     * @return array Plugin action links
     */
    public function payphone_g_btn_plugin_action_links($links)
    {
        $plugin_links = array();

        if (function_exists('WC')) {
            $setting_url = $this->get_admin_setting_link();
            $plugin_links[] = '<a href="' . esc_url($setting_url) . '">' . esc_html__('Settings', 'wc-payphone-gateway') . '</a>';
        }

        $plugin_links[] = '<a href="https://docs.payphone.app/">' . esc_html__('Docs', 'wc-payphone-gateway') . '</a>';

        return array_merge($plugin_links, $links);
    }

    /**
     * Link to settings screen.
     */
    private function get_admin_setting_link()
    {
        if (version_compare(WC()->version, '2.6', '>=')) {
            $section_slug = 'payphone';
        } else {
            $section_slug = 'wc-payphone-gateway';
        }
        return admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $section_slug);
    }

}
