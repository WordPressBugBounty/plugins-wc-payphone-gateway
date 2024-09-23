<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'woocommerce_payphone_settings',
    array(
        'enabled' => array(
            'title' => __('Enable/Disable', 'payphone'),
            'type' => 'checkbox',
            'label' => __('Enable PayPhone Payment Module.', 'payphone'),
            'default' => 'no',
            'description' => __('Show payphone in the Payment List as a payment option', 'payphone')
        ),
        'description' => array(
            'title' => __('Description:', 'payphone'),
            'type' => 'textarea',
            'default' => 'Usa tus tarjetas de crédito o débito Visa y Mastercard de cualquier banco del mundo y, si tienes la aplicación Payphone, utiliza tu saldo.',
            'description' => __('This controls the description which the user sees during checkout.', 'payphone'),
            'desc_tip' => true
        ),
        'token' => array(
            'title' => __('Authorization Token:', 'payphone'),
            'type' => 'textarea',
            'description' => __('Given by payphone', 'payphone'),
            'desc_tip' => true
        ),
        'storeId' => array(
            'title' => __('Store Id:', 'payphone'),
            'type' => 'text',
            'description' => __('Given by payphone', 'payphone'),
            'desc_tip' => true
        )
    )
);