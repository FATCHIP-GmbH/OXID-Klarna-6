<?php

use TopConcepts\Klarna\Components\KlarnaBasketComponent;
use TopConcepts\Klarna\Components\KlarnaUserComponent;
use TopConcepts\Klarna\Components\Widgets\KlarnaServiceMenu;
use TopConcepts\Klarna\Controllers\Admin\KlarnaConfiguration;
use TopConcepts\Klarna\Controllers\Admin\KlarnaDesign;
use TopConcepts\Klarna\Controllers\Admin\KlarnaEmdAdmin;
use TopConcepts\Klarna\Controllers\Admin\KlarnaExternalPayments;
use TopConcepts\Klarna\Controllers\Admin\KlarnaGeneral;
use TopConcepts\Klarna\Controllers\Admin\KlarnaOrderAddress;
use TopConcepts\Klarna\Controllers\Admin\KlarnaOrderArticle as KlarnaAdminOrderArticle;
use TopConcepts\Klarna\Controllers\Admin\KlarnaOrderList;
use TopConcepts\Klarna\Controllers\Admin\KlarnaOrderMain;
use TopConcepts\Klarna\Controllers\Admin\KlarnaOrderOverview;
use TopConcepts\Klarna\Controllers\Admin\KlarnaOrders;
use TopConcepts\Klarna\Controllers\Admin\KlarnaStart;
use TopConcepts\Klarna\Controllers\KlarnaUserController;
use TopConcepts\Klarna\Controllers\KlarnaAcknowledgeController;
use TopConcepts\Klarna\Controllers\KlarnaAjaxController;
use TopConcepts\Klarna\Controllers\KlarnaBasketController;
use TopConcepts\Klarna\Controllers\KlarnaEpmDispatcher;
use TopConcepts\Klarna\Controllers\KlarnaExpressController;
use TopConcepts\Klarna\Controllers\KlarnaOrderController;
use TopConcepts\Klarna\Controllers\KlarnaPaymentController;
use TopConcepts\Klarna\Controllers\KlarnaThankYouController;
use TopConcepts\Klarna\Controllers\KlarnaValidationController;
use TopConcepts\Klarna\Controllers\KlarnaViewConfig;
use TopConcepts\Klarna\Core\KlarnaEmail;
use TopConcepts\Klarna\Models\KlarnaAddress;
use TopConcepts\Klarna\Models\KlarnaArticle;
use TopConcepts\Klarna\Models\KlarnaBasket;
use TopConcepts\Klarna\Models\KlarnaCountryList;
use TopConcepts\Klarna\Models\KlarnaOrder;
use TopConcepts\Klarna\Models\KlarnaOrderArticle;
use TopConcepts\Klarna\Models\KlarnaPayment;
use TopConcepts\Klarna\Models\KlarnaUser;
use TopConcepts\Klarna\Models\KlarnaUserPayment;

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
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\ViewConfig;

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

$aModule = array(
    'id'          => 'klarna',
    'title'       => 'Klarna',
    'description' => 'Klarna Extension',
    'version'     => '4.0.0',
    'author'      => 'https://www.topconcepts.de/oxid-eshop',
    'thumbnail'   => 'logo_black.png',
    'url'         => 'http://integration.klarna.com',
    'email'       => 'integration@klarna.com',

    'controllers' => array(
        // klarna admin
        'KlarnaStart'            => KlarnaStart::class,
        'KlarnaGeneral'          => KlarnaGeneral::class,
        'KlarnaConfiguration'    => KlarnaConfiguration::class,
        'KlarnaDesign'           => KlarnaDesign::class,
        'KlarnaExternalPayments' => KlarnaExternalPayments::class,
        'KlarnaEmdAdmin'         => KlarnaEmdAdmin::class,
        'KlarnaOrders'           => KlarnaOrders::class,
        // controllers
        'KlarnaExpress'          => KlarnaExpressController::class,
        'KlarnaAjax'             => KlarnaAjaxController::class,
        'KlarnaEpmDispatcher'    => KlarnaEpmDispatcher::class,
        'KlarnaAcknowledge'      => KlarnaAcknowledgeController::class,
        'KlarnaValidate'         => KlarnaValidationController::class,
    ),
    'extend'      => array(
        Email::class              => KlarnaEmail::class,
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
        //components
        BasketComponent::class    => KlarnaBasketComponent::class,
        UserComponent::class      => KlarnaUserComponent::class,
        ServiceMenu::class        => KlarnaServiceMenu::class,
    ),
    'templates'   => array(
        'kl_klarna_checkout.tpl'                => 'tc/klarna/views/checkout/kl_klarna_checkout.tpl',
        'kl_amazon_login.tpl'                   => 'tc/klarna/views/checkout/kl_amazon_login.tpl',
        'changepwd.tpl'                         => 'tc/klarna/views/emails/html/changepwd.tpl',
        'changepwd_plain.tpl'                   => 'tc/klarna/views/emails/plain/changepwd.tpl',
        'kl_klarna_servicemenu.tpl'             => 'tc/klarna/views/widget/header/kl_klarna_servicemenu.tpl',
        'kl_klarna_checkout_voucher_data.tpl'   => 'tc/klarna/views/checkout/inc/kl_klarna_checkout_voucher_data.tpl',
        'kl_klarna_checkout_voucher_box.tpl'    => 'tc/klarna/views/checkout/inc/kl_klarna_checkout_voucher_box.tpl',
        'kl_klarna_checkout_voucher_errors.tpl' => 'tc/klarna/views/checkout/inc/kl_klarna_checkout_voucher_errors.tpl',
        'kl_klarna_json.tpl'                    => 'tc/klarna/views/checkout/inc/kl_klarna_json.tpl',
        'kl_klarna_country_select_popup.tpl'    => 'tc/klarna/views/checkout/inc/kl_klarna_country_select_popup.tpl',
        'kl_klarna_checkout_login_box.tpl'      => 'tc/klarna/views/checkout/inc/kl_klarna_checkout_login_box.tpl',
        'kl_klarna_checkout_address_box.tpl'    => 'tc/klarna/views/checkout/inc/kl_klarna_checkout_address_box.tpl',
        'kl_klarna_notice.tpl'                  => 'tc/klarna/views/widget/kl_klarna_notice.tpl',
        //admin
        'kl_klarna_general.tpl'                 => 'tc/klarna/views/admin/tpl/kl_klarna_general.tpl',
        'kl_klarna_design.tpl'                  => 'tc/klarna/views/admin/tpl/kl_klarna_design.tpl',
        'kl_klarna_kco_config.tpl'              => 'tc/klarna/views/admin/tpl/kl_klarna_kco_config.tpl',
        'kl_klarna_kp_config.tpl'               => 'tc/klarna/views/admin/tpl/kl_klarna_kp_config.tpl',
        'kl_klarna_start.tpl'                   => 'tc/klarna/views/admin/tpl/kl_klarna_start.tpl',
        'kl_klarna_external_payments.tpl'       => 'tc/klarna/views/admin/tpl/kl_klarna_external_payments.tpl',
        'kl_klarna_emd_admin.tpl'               => 'tc/klarna/views/admin/tpl/kl_klarna_emd_admin.tpl',
        'kl_klarna_orders.tpl'                  => 'tc/klarna/views/admin/tpl/kl_klarna_orders.tpl',
        //admin partial
        'kl_country_creds.tpl'                  => 'tc/klarna/views/admin/tpl/kl_country_creds.tpl',
        'kl_header.tpl'                         => 'tc/klarna/views/admin/tpl/kl_header.tpl',
        'kl_lang_spec_conf.tpl'                 => 'tc/klarna/views/admin/tpl/kl_lang_spec_conf.tpl',
    ),
    'blocks'      => array(
        array(
            'template' => 'widget/minibasket/minibasket.tpl',
            'block'    => 'widget_minibasket',
            'file'     => '/views/blocks/widget/minibasket/kl_klarna_minibasket.tpl',
        ),
        array(
            'template' => 'layout/footer.tpl',
            'block'    => 'footer_main',
            'file'     => 'kl_klarna_footer_main',
        ),
        array(
            'template' => 'page/checkout/payment.tpl',
            'block'    => 'select_payment',
            'file'     => 'kl_payment_select_override',
        ),
        array(
            'template' => 'page/checkout/basket.tpl',
            'block'    => 'checkout_basket_next_step_top',
            'file'     => 'kl_basket_override',
        ),
        array(
            'template' => 'page/checkout/payment.tpl',
            'block'    => 'change_payment',
            'file'     => 'kl_kp_widget',
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'order_basket',
            'file'     => 'kl_kp_widget',
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'shippingAndPayment',
            'file'     => 'kl_order_logo',
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'checkout_order_next_step_bottom',
            'file'     => 'kl_remove_amazon',
        ),
        array(
            'template' => 'page/details/inc/productmain.tpl',
            'block'    => 'details_productmain_tobasket',
            'file'     => '/views/blocks/page/details/inc/kl_klarna_checkout_button.tpl',
        ),
        array(
            'template' => 'page/checkout/thankyou.tpl',
            'block'    => 'checkout_thankyou_info',
            'file'     => '/views/blocks/page/checkout/inc/kl_klarna_checkout_thankyou_info.tpl',
        ),
        array(
            'template' => 'page/checkout/inc/steps.tpl',
            'block'    => 'checkout_steps_main',
            'file'     => '/views/blocks/page/checkout/inc/kl_klarna_checkout_steps_main.tpl',
        ),
        array(
            'template' => 'form/fieldset/user_billing.tpl',
            'block'    => 'form_user_billing_country',
            'file'     => '/views/blocks/form/fieldset/kl_klarna_user_billing.tpl',
        ),
        array(
            'template' => 'layout/footer.tpl',
            'block'    => 'footer_main',
            'file'     => '/views/blocks/widget/header/kl_klarna_law_notice.tpl',
        ),
        array(
            'template' => 'order_main.tpl',
            'block'    => 'admin_order_main_form',
            'file'     => 'kl_admin_order_main_form',
        ),
        array(
            'template' => 'email/html/order_cust.tpl',
            'block'    => 'email_html_order_cust_paymentinfo_top',
            'file'     => '/views/emails/html/kl_klarna_email_payment_badge.tpl',
        ),
        array(
            'template' => 'form/fieldset/user_shipping.tpl',
            'block'    => 'form_user_shipping_country',
            'file'     => '/views/blocks/form/fieldset/kl_klarna_user_shipping.tpl',
        ),
        array(
            'template' => 'order_overview.tpl',
            'block'    => 'admin_order_overview_billingaddress',
            'file'     => 'kl_admin_order_overview_billingaddress',
        ),
        array(
            'template' => 'order_article.tpl',
            'block'    => 'admin_order_article_header',
            'file'     => 'kl_admin_order_article_header',
        ),
        array(
            'template' => 'order_list.tpl',
            'block'    => 'admin_order_list_filter',
            'file'     => 'kl_admin_list_order_filter',
        ),
    ),
    'settings'    => array(),
    'events'      => array(
        'onActivate'   => '\TopConcepts\Klarna\Core\KlarnaInstaller::onActivate',
        'onDeactivate' => '\TopConcepts\Klarna\Core\KlarnaInstaller::onDeactivate',
    ),
);

