<?php

use OxidEsales\Eshop\Application\Controller\Admin\PaymentMain;
use TopConcepts\Klarna\Component\KlarnaBasketComponent;
use TopConcepts\Klarna\Component\KlarnaUserComponent;
use TopConcepts\Klarna\Component\Widgets\KlarnaServiceMenu;
use TopConcepts\Klarna\Controller\Admin\KlarnaConfiguration;
use TopConcepts\Klarna\Controller\Admin\KlarnaDesign;
use TopConcepts\Klarna\Controller\Admin\KlarnaEmdAdmin;
use TopConcepts\Klarna\Controller\Admin\KlarnaExternalPayments;
use TopConcepts\Klarna\Controller\Admin\KlarnaGeneral;
use TopConcepts\Klarna\Controller\Admin\KlarnaMessaging;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrderAddress;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrderArticle as KlarnaAdminOrderArticle;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrderList;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrderMain;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrderOverview;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrders;
use TopConcepts\Klarna\Controller\Admin\KlarnaPaymentMain;
use TopConcepts\Klarna\Controller\Admin\KlarnaShipping;
use TopConcepts\Klarna\Controller\Admin\KlarnaStart;
use TopConcepts\Klarna\Controller\KlarnaUserController;
use TopConcepts\Klarna\Controller\KlarnaAcknowledgeController;
use TopConcepts\Klarna\Controller\KlarnaAjaxController;
use TopConcepts\Klarna\Controller\KlarnaBasketController;
use TopConcepts\Klarna\Controller\KlarnaEpmDispatcher;
use TopConcepts\Klarna\Controller\KlarnaExpressController;
use TopConcepts\Klarna\Controller\KlarnaOrderController;
use TopConcepts\Klarna\Controller\KlarnaPaymentController;
use TopConcepts\Klarna\Controller\KlarnaThankYouController;
use TopConcepts\Klarna\Controller\KlarnaValidationController;
use TopConcepts\Klarna\Controller\KlarnaViewConfig;
use TopConcepts\Klarna\Core\Config;
use TopConcepts\Klarna\Model\KlarnaAddress;
use TopConcepts\Klarna\Model\KlarnaArticle;
use TopConcepts\Klarna\Model\KlarnaBasket;
use TopConcepts\Klarna\Model\KlarnaCountryList;
use TopConcepts\Klarna\Model\KlarnaOrder;
use TopConcepts\Klarna\Model\KlarnaOrderArticle;
use TopConcepts\Klarna\Model\KlarnaPayment;
use TopConcepts\Klarna\Model\KlarnaUser;
use TopConcepts\Klarna\Model\KlarnaUserPayment;
use TopConcepts\Klarna\Core\KlarnaShopControl;

use OxidEsales\Eshop\Application\Component\BasketComponent;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Component\Widget\ServiceMenu;
use OxidEsales\Eshop\Application\Controller\Admin\OrderAddress;
use OxidEsales\Eshop\Application\Controller\Admin\OrderArticle as AdminOrderArticle;
use OxidEsales\Eshop\Application\Controller\Admin\OrderList;
use OxidEsales\Eshop\Application\Controller\Admin\OrderMain;
use OxidEsales\Eshop\Application\Controller\Admin\OrderOverview;
use OxidEsales\Eshop\Application\Controller\BasketController;
use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Controller\ThankYouController;
use OxidEsales\Eshop\Application\Controller\UserController;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\UserPayment;
use OxidEsales\Eshop\Core\ViewConfig;
use TopConcepts\Klarna\Model\PaymentGateway;

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

$aModule = array(
    'id'          => 'tcklarna',
    'title'       => 'Klarna Checkout and Klarna Payments',
    'description' => array(
        'de' => 'Egal was Sie verkaufen, unsere Produkte sind dafür gemacht, Ihren Kunden das beste Erlebnis zu bereiten. Das gefällt nicht nur Ihnen, sondern auch uns! Die Klarna Plugins werden stets auf Herz und Nieren geprüft und können ganz einfach durch Sie oder Ihre technischen Ansprechpartner aktiviert werden. Das nennen wir smoooth. Hier können Sie sowohl Klarna Payments aktivieren und anschließend genau die Zahlarten auswählen, die Sie wünschen oder mit der Komplettlösung, dem Klarna Checkout, Ihre Customer Journey optimieren. Erfahren Sie hier mehr zu Klarna für OXID: <a href="https://www.klarna.com/de/verkaeufer/oxid/">https://www.klarna.com/de/verkaeufer/oxid/</a> Und so einfach ist die Integration: <a href="https://hello.klarna.com/rs/778-XGY-327/images/How_to_OXID.mp4" target="_blank">Zum Video</a>',
        'en' => 'No matter what you sell, our products are made to give your customers the best purchase experience. This is not only smoooth for you - it is smoooth for us, too! Klarna plugins are always tested and can be activated by you or your technical contact with just a few clicks. That is smoooth. Here you can activate Klarna Payments and then select exactly the payment methods you want or optimize your customer journey with the complete Klarna Checkout solution. Find out more about Klarna for OXID: <a href="https://www.klarna.com/de/verkaeufer/oxid/" target="_blank">https://www.klarna.com/de/verkaeufer/oxid/</a> Integrating Klarna at OXID is easy as pie: <a href="https://hello.klarna.com/rs/778-XGY-327/images/How_to_OXID.mp4" target="_blank">to the video (click)</a>'
    ),
    'version'     => '5.5.0',
    'author'      => '<a href="https://www.cgrd.de/oxid-eshop" target="_blank">https://www.cgrd.de/oxid-eshop</a>',
    'thumbnail'   => '/out/admin/src/img/klarna_lockup_black.jpg',
    'url'         => 'https://www.klarna.com/de/verkaeufer/plattformen-und-partner/oxid/',
    'email'       => 'oxid@klarna.com',

    'controllers' => array(
        // klarna admin
        'KlarnaStart'            => KlarnaStart::class,
        'KlarnaGeneral'          => KlarnaGeneral::class,
        'KlarnaConfiguration'    => KlarnaConfiguration::class,
        'KlarnaDesign'           => KlarnaDesign::class,
        'KlarnaExternalPayments' => KlarnaExternalPayments::class,
        'KlarnaEmdAdmin'         => KlarnaEmdAdmin::class,
        'KlarnaOrders'           => KlarnaOrders::class,
        'KlarnaMessaging'        => KlarnaMessaging::class,
        'KlarnaShipping'         => KlarnaShipping::class,
        // controllers
        'KlarnaExpress'          => KlarnaExpressController::class,
        'KlarnaAjax'             => KlarnaAjaxController::class,
        'KlarnaEpmDispatcher'    => KlarnaEpmDispatcher::class,
        'KlarnaAcknowledge'      => KlarnaAcknowledgeController::class,
        'KlarnaValidate'         => KlarnaValidationController::class,
    ),
    'extend'      => array(
        // models
        Basket::class             => KlarnaBasket::class,
        User::class               => KlarnaUser::class,
        Article::class            => KlarnaArticle::class,
        Order::class              => KlarnaOrder::class,
        Address::class            => KlarnaAddress::class,
        Payment::class            => KlarnaPayment::class,
        CountryList::class        => KlarnaCountryList::class,
        OrderArticle::class       => KlarnaOrderArticle::class,
        UserPayment::class        => KlarnaUserPayment::class,
        // controllers
        ThankYouController::class => KlarnaThankYouController::class,
        ViewConfig::class         => KlarnaViewConfig::class,
        OrderController::class    => KlarnaOrderController::class,
        UserController::class     => KlarnaUserController::class,
        PaymentController::class  => KlarnaPaymentController::class,
        BasketController::class   => KlarnaBasketController::class,
        // admin
        OrderAddress::class       => KlarnaOrderAddress::class,
        OrderList::class          => KlarnaOrderList::class,
        AdminOrderArticle::class  => KlarnaAdminOrderArticle::class,
        OrderMain::class          => KlarnaOrderMain::class,
        OrderOverview::class      => KlarnaOrderOverview::class,
        PaymentMain::class        => KlarnaPaymentMain::class,
        //components
        BasketComponent::class    => KlarnaBasketComponent::class,
        UserComponent::class      => KlarnaUserComponent::class,
        ServiceMenu::class        => KlarnaServiceMenu::class,

        OxidEsales\Eshop\Core\Config::class                      => Config::class,
        OxidEsales\Eshop\Application\Model\PaymentGateway::class => PaymentGateway::class,
        OxidEsales\Eshop\Core\ShopControl::class => KlarnaShopControl::class
    ),
    'templates'   => array(

        'tcklarna_checkout.tpl'                => 'tc/tcklarna/views/tpl/checkout/tcklarna_checkout.tpl',
        'tcklarna_amazon_login.tpl'            => 'tc/tcklarna/views/tpl/checkout/tcklarna_amazon_login.tpl',
        'tcklarna_checkout_voucher_data.tpl'   => 'tc/tcklarna/views/tpl/checkout/inc/tcklarna_checkout_voucher_data.tpl',
        'tcklarna_checkout_voucher_box.tpl'    => 'tc/tcklarna/views/tpl/checkout/inc/tcklarna_checkout_voucher_box.tpl',
        'tcklarna_checkout_voucher_errors.tpl' => 'tc/tcklarna/views/tpl/checkout/inc/tcklarna_checkout_voucher_errors.tpl',
        'tcklarna_json.tpl'                    => 'tc/tcklarna/views/tpl/checkout/inc/tcklarna_json.tpl',
        'tcklarna_country_select_popup.tpl'    => 'tc/tcklarna/views/tpl/checkout/inc/tcklarna_country_select_popup.tpl',
        'tcklarna_checkout_login_box.tpl'      => 'tc/tcklarna/views/tpl/checkout/inc/tcklarna_checkout_login_box.tpl',
        'tcklarna_checkout_address_box.tpl'    => 'tc/tcklarna/views/tpl/checkout/inc/tcklarna_checkout_address_box.tpl',
        //admin
        'tcklarna_general.tpl'                 => 'tc/tcklarna/views/admin/tpl/tcklarna_general.tpl',
        'tcklarna_design.tpl'                  => 'tc/tcklarna/views/admin/tpl/tcklarna_design.tpl',
        'tcklarna_kco_config.tpl'              => 'tc/tcklarna/views/admin/tpl/tcklarna_kco_config.tpl',
        'tcklarna_kp_config.tpl'               => 'tc/tcklarna/views/admin/tpl/tcklarna_kp_config.tpl',
        'tcklarna_start.tpl'                   => 'tc/tcklarna/views/admin/tpl/tcklarna_start.tpl',
        'tcklarna_external_payments.tpl'       => 'tc/tcklarna/views/admin/tpl/tcklarna_external_payments.tpl',
        'tcklarna_emd_admin.tpl'               => 'tc/tcklarna/views/admin/tpl/tcklarna_emd_admin.tpl',
        'tcklarna_orders.tpl'                  => 'tc/tcklarna/views/admin/tpl/tcklarna_orders.tpl',
        'tcklarna_messaging.tpl'               => 'tc/tcklarna/views/admin/tpl/tcklarna_messaging.tpl',
        'tcklarna_shipping.tpl'                => 'tc/tcklarna/views/admin/tpl/tcklarna_shipping.tpl',
        //admin partial
        'tcklarna_country_creds.tpl'           => 'tc/tcklarna/views/admin/tpl/tcklarna_country_creds.tpl',
        'tcklarna_header.tpl'                  => 'tc/tcklarna/views/admin/tpl/tcklarna_header.tpl',
        'tcklarna_lang_spec_conf.tpl'          => 'tc/tcklarna/views/admin/tpl/tcklarna_lang_spec_conf.tpl',
    ),
    'blocks'      => array(
        array(
            'template' => 'widget/minibasket/minibasket.tpl',
            'block'    => 'widget_minibasket',
            'file'     => 'views/blocks/minibasket_widget_minibasket.tpl',
        ),
        array(
            'template' => 'page/shop/start.tpl',
            'block'    => 'start_manufacturer_slider',
            'file'     => 'views/blocks/start_widget_manufacturer.tpl',
        ),
        array(
            'template' => 'layout/header.tpl',
            'block'    => 'header_main',
            'file'     => 'views/blocks/header_main.tpl',
        ),
        array(
            'template' => 'page/details/inc/productmain.tpl',
            'block'    => 'details_productmain_variantselections',
            'file'     => 'views/blocks/productmain_variantselection.tpl',
        ),
        array(
            'template' => 'layout/footer.tpl',
            'block'    => 'footer_main',
            'file'     => 'views/blocks/footer_footer_main.tpl',
        ),
        array(
            'template' => 'page/checkout/payment.tpl',
            'block'    => 'select_payment',
            'file'     => 'views/blocks/payment_select_payment.tpl',
        ),
        array(
            'template' => 'page/checkout/basket.tpl',
            'block'    => 'checkout_basket_next_step_top',
            'file'     => 'views/blocks/basket_checkout_basket_next_step_top.tpl',
        ),
        array(
            'template' => 'page/checkout/inc/basketcontents.tpl',
            'block'    => 'checkout_basketcontents_summary',
            'file'     => 'views/blocks/checkout_basketcontents_summary.tpl',
        ),
        array(
            'template' => 'page/checkout/payment.tpl',
            'block'    => 'change_payment',
            'file'     => 'views/blocks/payment_change_payment.tpl',
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'order_basket',
            'file'     => 'views/blocks/order_order_basket.tpl',
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'shippingAndPayment',
            'file'     => 'views/blocks/order_shippingAndPayment.tpl',
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'checkout_order_next_step_bottom',
            'file'     => 'views/blocks/order_checkout_order_next_step_bottom.tpl',
        ),
        array(
            'template' => 'page/details/inc/productmain.tpl',
            'block'    => 'details_productmain_tobasket',
            'file'     => 'views/blocks/productmain_details_productmain_tobasket.tpl',
        ),
        array(
            'template' => 'page/checkout/thankyou.tpl',
            'block'    => 'checkout_thankyou_info',
            'file'     => 'views/blocks/thankyou_checkout_thankyou_info.tpl',
        ),
        array(
            'template' => 'page/checkout/inc/steps.tpl',
            'block'    => 'checkout_steps_main',
            'file'     => 'views/blocks/steps_checkout_steps_main.tpl',
        ),
        array(
            'template' => 'form/fieldset/user_billing.tpl',
            'block'    => 'form_user_billing_country',
            'file'     => 'views/blocks/user_billing_form_user_billing_country.tpl',
        ),
        array(
            'template' => 'layout/footer.tpl',
            'block'    => 'footer_main',
            'file'     => 'views/blocks/tcklarna_law_notice.tpl',
        ),
        array(
            'template' => 'order_main.tpl',
            'block'    => 'admin_order_main_form',
            'file'     => 'views/blocks/admin/order_main_admin_order_main_form.tpl',
        ),
        array(
            'template' => 'email/html/order_cust.tpl',
            'block'    => 'email_html_order_cust_paymentinfo_top',
            'file'     => 'views/blocks/order_cust_email_html_order_cust_paymentinfo_top.tpl',
        ),
        array(
            'template' => 'form/fieldset/user_shipping.tpl',
            'block'    => 'form_user_shipping_country',
            'file'     => 'views/blocks/user_shipping_form_user_shipping_country.tpl',
        ),
        array(
            'template' => 'order_overview.tpl',
            'block'    => 'admin_order_overview_billingaddress',
            'file'     => 'views/blocks/admin/order_overview_admin_order_overview_billingaddress.tpl',
        ),
        array(
            'template' => 'order_article.tpl',
            'block'    => 'admin_order_article_header',
            'file'     => 'views/blocks/admin/order_article_admin_order_article_header.tpl',
        ),
        array(
            'template' => 'order_list.tpl',
            'block'    => 'admin_order_list_filter',
            'file'     => 'views/blocks/admin/order_list_admin_order_list_filter.tpl',
        ),
        array(
            'template' => 'payment_main.tpl',
            'block'    => 'admin_payment_main_form',
            'file'     => 'views/blocks/admin/payment_main_admin_payment_main_form.tpl',
        ),
    ),
    'settings'    => array(),
    'events'      => array(
        'onActivate'   => '\TopConcepts\Klarna\Core\KlarnaInstaller::onActivate',
        'onDeactivate'   => '\TopConcepts\Klarna\Core\KlarnaInstaller::onDeactivate',
    ),
);
