<?php
/**
 * @package           gtm4wp-payment-addon
 * @author            Ivan Nikitin
 * @copyright         2022 IvanNikitin.com
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       GTM4WP Информация о доставке и методе платежа
 * Plugin URI:        https://github.com/ivannikitin-com/gtm4wp-payment-addon
 * Description:       При интеграции с WooCommerce плагин GTM4WP не фиксирует информацию о способе доставки и типе платежного метода. Этот плагин добавляет эти возможности.
 * Version:           1.0.1
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Иван Никитин
 * Author URI:        https://ivannikitin.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https://github.com/ivannikitin-com/gtm4wp-payment-addon
 * Text Domain:       gtm4wp_payment_addon
 * Domain Path:       /lang
 */
// Напрямую не вызываем!
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Так как нам нужно добавить два новых события, то хук gtm4wp_compile_datalayer не может быть использован 
 * (он обрабатывает только одно событие)
 * Добавим два новых события непосредственной вставкой JS кода после определения dataLayer
 */
add_filter( 'gtm4wp_after_datalayer', 'gtm4wp_after_datalayer_add_shipping_payment', 999 );
function gtm4wp_after_datalayer_add_shipping_payment( $after_code ) {
    /**
     * Проверка страницы thank you page и чтение текущего номера заказа
     * https://stackoverflow.com/questions/52235441/how-to-check-woocommerce-thank-you-page
     */
    if ( is_wc_endpoint_url( 'order-received' ) ) {
        global $wp;
        /**
         * Get Order ID
         */
        $order_id =  intval( str_replace( 'checkout/order-received/', '', $wp->request ) );
        $order = wc_get_order( $order_id );

        /**
         *  Данные заказа
         */ 
        $currency_code = $order->get_currency();
        $order_value = $order->get_total();
        $shipping_method = $order->get_shipping_method();
        $payment_method = $order->get_payment_method_title();

        // Добавим события
        $after_code .= "
        <!-- gtm4wp-payment-addon -->
        <script>
            dataLayer.push(
                {
                    'event' : 'add_shipping_info', 
                    'shipping_tier' : '{$shipping_method}',
                    'currency' : '{$currency_code}',
                    'value' : {$order_value}
                },
                {
                    'event' : 'add_payment_info', 
                    'payment_type' : '{$payment_method}',
                    'currency' : '{$currency_code}',
                    'value' : {$order_value}                 
                }
            );
        </script>";        

    }

    return $after_code;
}

/**
 * Этим хуком пытаемся добавить данные о способе доставки и способе оплаты для старого GA
 */
add_filter( 'gtm4wp_compile_datalayer', 'gtm4wp_compile_datalayer_add_shipping_payment', 999 );
function gtm4wp_compile_datalayer_add_shipping_payment( $dataLayer ) {

    // Ищем событие gtm4wp.orderCompletedEEC для старого GA
    if ( isset( $dataLayer[ 'event' ] ) && 'gtm4wp.orderCompletedEEC' == $dataLayer[ 'event' ] ) {
        // current order
        $order_id = ( isset( $dataLayer['ecommerce'][ 'purchase' ][ 'actionField' ][ 'id' ] ) ) ? $dataLayer['ecommerce'][ 'purchase' ][ 'actionField' ][ 'id' ] : null;
        if ( !$order_id ) {
            // Order ID is not defined! Nothing to do...
            return $dataLayer;
        }


        // Order data
        $order = wc_get_order( $order_id );
        if ( !$order ) {
            // Order is not defined! Nothing to do...
            return $dataLayer;
        }

        // Для старого UA. Не факт что сработает...
        $dataLayer['ecommerce'][ 'purchase' ][ 'actionField' ][ 'option' ] = 
            $order->get_shipping_method() . ' | ' . $order->get_payment_method_title();
    }

    return $dataLayer;
}