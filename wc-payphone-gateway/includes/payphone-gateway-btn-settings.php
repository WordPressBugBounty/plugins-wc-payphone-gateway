<?php
/**
 * Class Payphone_Gateway_Btn_Settings
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings retrieval from the settings API.
 */
class Payphone_Gateway_Btn_Settings {

    /**
     * Setting values from get_option.
     *
     * @var array
     */
    protected $_settings = array();

    /**
     * List of locales supported by PayPal.
     *
     * @var array
     */
    protected $_supported_locales = array(
        'en_GB',
        'en_US',
        'es_ES',
    );

    /**
     * Flag to indicate setting has been loaded from DB.
     *
     * @var bool
     */
    private $_is_setting_loaded = false;

    public function __set($key, $value) {
        if (isset($this->_settings[$key])) {
            $this->_settings[$key] = $value;
        }
    }

    public function __get($key) {
        if (isset($this->_settings[$key])) {
            return $this->_settings[$key];
        }
        return null;
    }

    public function __isset($key) {
        return isset($this->_settings[$key]);
    }

    public function __construct() {
        $this->load();
    }

    /**
     * Load settings from DB.
     *
     * @since 1.2.0
     *
     * @param bool $force_reload Force reload settings
     *
     * @return Payphone_Gateway_Btn_Settings Instance of Payphone_Gateway_Btn_Settings
     */
    public function load($force_reload = false) {
        if ($this->_is_setting_loaded && !$force_reload) {
            return $this;
        }
        $this->_settings = (array) get_option('woocommerce_payphone_settings', array());
        $this->_is_setting_loaded = true;
        return $this;
    }

    /**
     * Load settings from DB.
     *
     * @deprecated
     */
    public function load_settings($force_reload = false) {
        _deprecated_function(__METHOD__, '1.2.0', 'Payphone_Gateway_Btn_Settings::load');
        return $this->load($force_reload);
    }

    /**
     * Save current settings.
     *
     * @since 1.2.0
     */
    public function save() {
        update_option('woocommerce_payphone_settings', $this->_settings);
    }

    /**
     * Get API credentials for the current envionment.
     *
     * @return object
     */
    public function get_active_api_credentials() {
        return $this->token;
    }

    /**
     * Get Payphone redirect URL.
     *
     * @return string Payphone redirect URL
     */
    public function get_payphone_redirect_url() {
        return 'https://pay.payphonetodoesposible.com';
    }

    /**
     * Get locale for PayPhone.
     *
     * @return string
     */
    public function get_payphone_locale() {
        $locale = get_locale();
        if (!in_array($locale, $this->_supported_locales)) {
            $locale = 'en_US';
        }
        return $locale;
    }
}
