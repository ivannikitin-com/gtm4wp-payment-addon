Добавление данных о способах доставки и платежа в dataLayer плагина gtm4wp
==========================================================================

При [интеграции с WooCommerce](https://github.com/duracelltomi/gtm4wp/blob/master/integration/woocommerce.php)
плагин [GTM4WP](https://wordpress.org/plugins/duracelltomi-google-tag-manager/)
не фиксирует информацию о способе доставки и типе платежного метода.

Этот плагин добавляет эти возможности. На странице WooCommerce Thank you page плагин добавляет в dataLayer события:
1. `add_shipping_info` -- информация о доставке
2. `add_payment_info`  -- информация о платежной системе

Кроме того, плагин добавляет в событие `gtm4wp.orderCompletedEEC` (завершение заказа для старого Enhanced E-commerce Universal Analytics)
параметр `ecommerce.purchase.actionField.option` со значением "Способ доставки | Способ платежа"
