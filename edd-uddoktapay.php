<?php
/**
 * Plugin Name: EDD UddoktaPay
 * Plugin URI: https://uddoktapay.com
 * Description: Adds UddoktaPay as a payment gateway for Easy Digital Downloads.
 * Version: 1.0.0
 * Author: UddoktaPay
 * Author URI: https://uddoktapay.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: edd-uddoktapay
 */

// Prevent direct access to the file
defined('ABSPATH') || exit;

// Autoload classes
require_once plugin_dir_path(__FILE__) . 'lib/UddoktaPayEdd.php';

function edd_uddoktapay_check_version()
{
    if (!defined('EDD_VERSION') || version_compare(EDD_VERSION, '3.0.0', '<')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>' . __('EDD UddoktaPay Gateway requires EDD version 3.0.0 or higher.', 'edd-uddoktapay') . '</p></div>';
        });
    }
}
add_action('plugins_loaded', 'edd_uddoktapay_check_version');

/**
 * Registers UddoktaPay as a payment gateway in Easy Digital Downloads.
 *
 * @param array $gateways Existing gateways.
 * @return array Modified gateways.
 */
function edd_uddoktapay_register_gateway($gateways)
{
    $display_name = edd_get_option('edd_uddoktapay_display_name', __('UddoktaPay', 'edd-uddoktapay'));

    $gateways['uddoktapay'] = [
        'admin_label'    => 'UddoktaPay',
        'checkout_label' => $display_name,
        'supports'       => ['buy_now'],
    ];

    return $gateways;
}
add_filter('edd_payment_gateways', 'edd_uddoktapay_register_gateway');

/**
 * Adds UddoktaPay settings section in Easy Digital Downloads.
 *
 * @param array $sections Existing sections.
 * @return array Modified sections.
 */
function edd_uddoktapay_add_settings_section($sections)
{
    $sections['uddoktapay'] = __('UddoktaPay', 'edd-uddoktapay');
    return $sections;
}
add_filter('edd_settings_sections_gateways', 'edd_uddoktapay_add_settings_section');

/**
 * Adds UddoktaPay settings fields in Easy Digital Downloads.
 *
 * @param array $settings Existing settings.
 * @return array Modified settings.
 */
function edd_uddoktapay_add_settings($settings)
{
    $uddoktapay_settings = [
        'uddoktapay' => [
            [
                'id'   => 'edd_uddoktapay_settings',
                'name' => '<strong>' . __('UddoktaPay Settings', 'edd-uddoktapay') . '</strong>',
                'desc' => __('Configure the UddoktaPay settings.', 'edd-uddoktapay'),
                'type' => 'header',
            ],
            [
                'id'   => 'edd_uddoktapay_display_name',
                'name' => __('Gateway Display Name', 'edd-uddoktapay'),
                'desc' => __('Enter the name that will be displayed on the checkout page for the UddoktaPay gateway.', 'edd-uddoktapay'),
                'type' => 'text',
                'size' => 'regular',
            ],
            [
                'id'   => 'edd_uddoktapay_api_key',
                'name' => __('API KEY', 'edd-uddoktapay'),
                'desc' => __('Enter your UddoktaPay API KEY.', 'edd-uddoktapay'),
                'type' => 'text',
            ],
            [
                'id'   => 'edd_uddoktapay_api_url',
                'name' => __('API URL', 'edd-uddoktapay'),
                'desc' => __('Enter your UddoktaPay API URL.', 'edd-uddoktapay'),
                'type' => 'text',
            ],
            [
                'id'      => 'edd_uddoktapay_exchange_rate',
                'name'    => __('Exchange Rate', 'edd-uddoktapay'),
                'desc'    => __('1 USD = ?BDT', 'edd-uddoktapay'),
                'type'    => 'number',
                'default' => 1,
            ],
        ],
    ];

    return array_merge($settings, $uddoktapay_settings);
}
add_filter('edd_settings_gateways', 'edd_uddoktapay_add_settings');

/**
 * Processes UddoktaPay payment.
 *
 * @param array $purchase_data Purchase data.
 */
function edd_uddoktapay_process_payment($purchase_data)
{
    if (edd_get_errors()) {
        edd_send_back_to_checkout('?payment-mode=' . sanitize_text_field($purchase_data['post_data']['edd-gateway']));
    }

    $payment_data = [
        'price'        => $purchase_data['price'],
        'date'         => $purchase_data['date'],
        'user_email'   => $purchase_data['user_email'],
        'purchase_key' => $purchase_data['purchase_key'],
        'currency'     => edd_get_currency(),
        'downloads'    => $purchase_data['downloads'],
        'cart_details' => $purchase_data['cart_details'],
        'user_info'    => $purchase_data['user_info'],
        'status'       => 'pending',
    ];

    $payment_id = edd_insert_payment($payment_data);

    if (!$payment_id) {
        edd_set_error('uddoktapay_error', __('You must enter your UddoktaPay credentials in settings.', 'edd-uddoktapay'));
        edd_send_back_to_checkout('?payment-mode=' . sanitize_text_field($purchase_data['post_data']['edd-gateway']));
    }

    $amount = edd_get_currency() === 'BDT' ? $purchase_data['price'] : $purchase_data['price'] * edd_get_option('edd_uddoktapay_exchange_rate');

    $requestData = [
        'full_name'    => sanitize_text_field($purchase_data['user_info']['first_name']),
        'email'        => sanitize_email($purchase_data['user_info']['email']),
        'amount'       => $amount,
        'metadata'     => ['payment_id' => $payment_id],
        'redirect_url' => add_query_arg('payment-confirmation', 'uddoktapay_success', edd_get_success_page_uri()),
        'return_type'  => 'GET',
        'cancel_url'   => add_query_arg('payment-confirmation', 'uddoktapay_cancel', edd_get_success_page_uri()),
        'webhook_url'  => add_query_arg('payment-confirmation', 'uddoktapay_ipn', edd_get_success_page_uri()),
    ];

    try {
        $uddoktaPay = new UddoktaPayEdd(edd_get_option('edd_uddoktapay_api_key'), edd_get_option('edd_uddoktapay_api_url'));
        $paymentUrl = $uddoktaPay->initPayment($requestData);
        wp_redirect($paymentUrl);
        exit;
    } catch (Exception $e) {
        edd_set_error('uddoktapay_error', __('Initialization Error: ', 'edd-uddoktapay') . esc_html($e->getMessage()));
        edd_send_back_to_checkout('?payment-mode=' . sanitize_text_field($purchase_data['post_data']['edd-gateway']));
    }
}
add_action('edd_gateway_uddoktapay', 'edd_uddoktapay_process_payment');

/**
 * Handles payment confirmation for UddoktaPay.
 */
function edd_uddoktapay_payment_confirmation()
{
    if (isset($_GET['payment-confirmation'])) {
        $confirmation_type = sanitize_text_field($_GET['payment-confirmation']);

        if ($confirmation_type === 'uddoktapay_cancel') {
            edd_send_back_to_checkout();
        }

        try {
            $uddoktaPay = new UddoktaPayEdd(edd_get_option('edd_uddoktapay_api_key'), edd_get_option('edd_uddoktapay_api_url'));

            if ($confirmation_type === 'uddoktapay_success') {
                $response = $uddoktaPay->verifyPayment(sanitize_text_field($_GET['invoice_id']));
                if (isset($response['status'], $response['metadata']['payment_id']) && $response['status'] === 'COMPLETED') {
                    $payment_id = intval($response['metadata']['payment_id']);
                    $payment = edd_get_payment($payment_id);
                    if ($payment && $payment->status === 'pending') {
                        edd_update_payment_status($payment_id, 'complete');
                        edd_insert_payment_note($payment_id, __('Payment completed successfully through UddoktaPay.', 'edd-uddoktapay'));
                        edd_send_to_success_page();
                    }
                }
            }

            if ($confirmation_type === 'uddoktapay_ipn') {
                $response = $uddoktaPay->executePayment();
                if (isset($response['status'], $response['metadata']['payment_id']) && $response['status'] === 'COMPLETED') {
                    $payment_id = intval($response['metadata']['payment_id']);
                    $payment = edd_get_payment($payment_id);
                    if ($payment && $payment->status === 'pending') {
                        edd_update_payment_status($payment_id, 'complete');
                        edd_insert_payment_note($payment_id, __('Payment completed successfully through UddoktaPay.', 'edd-uddoktapay'));
                        edd_send_to_success_page();
                    }
                }
            }
        } catch (Exception $e) {
            edd_set_error('uddoktapay_error', __('Initialization Error: ', 'edd-uddoktapay') . esc_html($e->getMessage()));
            edd_send_back_to_checkout();
        }
    }
}
add_action('template_redirect', 'edd_uddoktapay_payment_confirmation');

/**
 * Disables the credit card form for the UddoktaPay gateway.
 *
 * @return bool False if UddoktaPay is the selected gateway, otherwise the original value.
 */
function edd_uddoktapay_cc_form()
{
    if (edd_get_chosen_gateway() === 'uddoktapay') {
        return false;
    }
}
add_action('edd_uddoktapay_cc_form', 'edd_uddoktapay_cc_form');
