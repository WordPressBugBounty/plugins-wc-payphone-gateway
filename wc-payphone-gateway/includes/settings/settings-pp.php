<?php

if (!defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'payphone_g_btn_woocommerce_settings',
    array(
        'enabled' => array(
            'title' => __('Enable/Disable', 'wc-payphone-gateway'),
            'type' => 'checkbox',
            'label' => __('Enable PayPhone Payment Module.', 'wc-payphone-gateway'),
            'default' => 'no',
            'description' => __('Show payphone in the Payment List as a payment option', 'wc-payphone-gateway')
        ),
        'description' => array(
            'title' => __('Description:', 'wc-payphone-gateway'),
            'type' => 'textarea',
            'default' => 'Usa tus tarjetas de crédito o débito Visa, Mastercard, Diners o Discover de cualquier banco del mundo y, si tienes la aplicación Payphone, utiliza tu saldo.',
            'description' => __('This controls the description which the user sees during checkout.', 'wc-payphone-gateway'),
            'desc_tip' => true
        ),
        'token' => array(
            'title' => __('Authorization Token:', 'wc-payphone-gateway'),
            'type' => 'textarea',
            'description' => __('Given by payphone', 'wc-payphone-gateway'),
            'desc_tip' => true
        ),
        'storeId' => array(
            'title' => __('Store Id:', 'wc-payphone-gateway'),
            'type' => 'text',
            'description' => __('Given by payphone', 'wc-payphone-gateway'),
            'desc_tip' => true
        ),
        'use_sku_reference' => array(
            'title' => __('Use SKUs in reference', 'wc-payphone-gateway'),
            'type' => 'checkbox',
            'label' => __('Use product SKUs as the payment reference.', 'wc-payphone-gateway'),
            'default' => 'no',
            'description' => __('When enabled, the reference uses the format SKU>xQuantity|SKU>xQuantity. Products without SKU are skipped. If no product has a SKU, the default reference is used.', 'wc-payphone-gateway'),
            'desc_tip' => true
        )
    )
);
