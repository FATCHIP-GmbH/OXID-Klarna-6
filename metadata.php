<?php
$aModule = array(
    'id'          => 'klarna',
    'title'       => 'Klarna',
    'description' => 'Klarna Extension',
    'version'     => '4.0.0',
    'author'      => 'topconcepts.com',
    'thumbnail'   => 'logo_black.png',
    'url'         => 'http://integration.klarna.com',
    'email'       => 'integration@klarna.com',

    'files' => array(
        // core classes
        'KlarnaInstaller'                  => 'klarna/klarna/core/KlarnaInstaller.php',
        'KlarnaClientBase'                 => 'klarna/klarna/core/KlarnaClientBase.php',
        'KlarnaCheckoutClient'             => 'klarna/klarna/core/KlarnaCheckoutClient.php',
        'KlarnaPaymentsClient'             => 'klarna/klarna/core/KlarnaPaymentsClient.php',
        'KlarnaOrderManagementClient'      => 'klarna/klarna/core/KlarnaOrderManagementClient.php',
        'KlarnaUtils'                      => 'klarna/klarna/core/KlarnaUtils.php',
        'KlarnaFormatter'                  => 'klarna/klarna/core/KlarnaFormatter.php',
        'KlarnaConsts'                     => 'klarna/klarna/core/KlarnaConsts.php',
        'klarna_logs'                      => 'klarna/klarna/core/klarna_logs.php',
        'KlarnaPayment'                    => 'klarna/klarna/core/KlarnaPayment.php',
        'KlarnaOrder'                      => 'klarna/klarna/core/KlarnaOrder.php',
        'KlarnaOrderValidator'             => 'klarna/klarna/core/KlarnaOrderValidator.php',

        // controllers
        'klarna_express'                   => 'klarna/klarna/controllers/klarna_express.php',
        'klarna_ajax'                      => 'klarna/klarna/controllers/klarna_ajax.php',
        'klarna_epm_dispatcher'            => 'klarna/klarna/controllers/klarna_epm_dispatcher.php',
        'klarna_acknowledge'               => 'klarna/klarna/controllers/klarna_acknowledge.php',
        'klarna_validate'                  => 'klarna/klarna/controllers/klarna_validate.php',
        'klarna_selenium_controller'       => 'klarna/klarna/tests/oxidTools/klarna_selenium_controller.php',
        'KlarnaOxidConfig'                 => 'klarna/klarna/tests/oxidTools/klarna_oxid_config.php',

        //admin
        'klarna_general'                   => 'klarna/klarna/controllers/admin/klarna_general.php',
        'klarna_base_config'               => 'klarna/klarna/controllers/admin/klarna_base_config.php',
        'klarna_design'                    => 'klarna/klarna/controllers/admin/klarna_design.php',
        'klarna_start'                     => 'klarna/klarna/controllers/admin/klarna_start.php',
        'klarna_configuration'             => 'klarna/klarna/controllers/admin/klarna_configuration.php',
        'klarna_external_payments'         => 'klarna/klarna/controllers/admin/klarna_external_payments.php',
        'klarna_emd_admin'                 => 'klarna/klarna/controllers/admin/klarna_emd_admin.php',
        'klarna_orders'                    => 'klarna/klarna/controllers/admin/klarna_orders.php',

        // models
        'klarna_emd'                       => 'klarna/klarna/models/klarna_emd.php',
        'klarna_customer_account_info'     => 'klarna/klarna/models/emd_payload/klarna_customer_account_info.php',
        'klarna_payment_history_full'      => 'klarna/klarna/models/emd_payload/klarna_payment_history_full.php',
        'klarna_pass_through'              => 'klarna/klarna/models/emd_payload/klarna_pass_through.php',
        'klarna_invoicepdfarticlesummary'  => 'klarna/klarna/models/klarna_invoicepdfarticlesummary.php',

        //Exceptions
        'KlarnaClientException'            => 'klarna/klarna/exception/KlarnaClientException.php',
        'KlarnaWrongCredentialsException'  => 'klarna/klarna/exception/KlarnaWrongCredentialsException.php',
        'KlarnaOrderNotFoundException'     => 'klarna/klarna/exception/KlarnaOrderNotFoundException.php',
        'KlarnaOrderReadOnlyException'     => 'klarna/klarna/exception/KlarnaOrderReadOnlyException.php',
        'KlarnaConfigException'            => 'klarna/klarna/exception/KlarnaConfigException.php',
        'KlarnaCaptureNotAllowedException' => 'klarna/klarna/exception/KlarnaCaptureNotAllowedException.php',
        'KlarnaBasketTooLargeException'    => 'klarna/klarna/exception/KlarnaBasketTooLargeException.php',
    ),

    'extend' => array(
        // models
        'oxbasket'       => 'klarna/klarna/models/klarna_oxbasket',
        'oxuser'         => 'klarna/klarna/models/klarna_oxuser',
        'oxarticle'      => 'klarna/klarna/models/klarna_oxarticle',
        'oxorder'        => 'klarna/klarna/models/klarna_oxorder',
        'oxemail'        => 'klarna/klarna/models/klarna_oxemail',
        'oxaddress'      => 'klarna/klarna/models/klarna_oxaddress',
        'oxpayment'      => 'klarna/klarna/models/klarna_oxpayment',
        'oxcountrylist'  => 'klarna/klarna/models/klarna_oxcountrylist',
        'oxorderarticle' => 'klarna/klarna/models/klarna_oxorderarticle',
        'oxuserpayment'  => 'klarna/klarna/models/klarna_oxuserpayment',

        // controllers
        'order'          => 'klarna/klarna/controllers/klarna_order',
        'thankyou'       => 'klarna/klarna/controllers/klarna_thankyou',
        'oxviewconfig'   => 'klarna/klarna/controllers/klarna_oxviewconfig',
        'user'           => 'klarna/klarna/controllers/klarna_user',
        'payment'        => 'klarna/klarna/controllers/klarna_payment',
        'basket'         => 'klarna/klarna/controllers/klarna_basket',

        // admin
        'order_address'  => 'klarna/klarna/controllers/admin/klarna_order_address',
        'order_list'     => 'klarna/klarna/controllers/admin/klarna_order_list',
        'order_article'  => 'klarna/klarna/controllers/admin/klarna_order_article',
        'order_main'     => 'klarna/klarna/controllers/admin/klarna_order_main',
        'order_overview' => 'klarna/klarna/controllers/admin/klarna_order_overview',

        //components
        'oxcmp_basket'   => 'klarna/klarna/components/klarna_oxcmp_basket',
        'oxcmp_user'     => 'klarna/klarna/components/klarna_oxcmp_user',
        'oxwservicemenu' => 'klarna/klarna/components/widgets/oxw_klarna_servicemenu',

    ),

    'templates' => array(
        'kl_klarna_checkout.tpl'                => 'klarna/klarna/views/checkout/kl_klarna_checkout.tpl',
        'kl_amazon_login.tpl'                   => 'klarna/klarna/views/checkout/kl_amazon_login.tpl',
        'changepwd.tpl'                         => 'klarna/klarna/views/emails/html/changepwd.tpl',
        'changepwd_plain.tpl'                   => 'klarna/klarna/views/emails/plain/changepwd.tpl',
        'kl_klarna_servicemenu.tpl'             => 'klarna/klarna/views/widget/header/kl_klarna_servicemenu.tpl',
        'kl_klarna_checkout_voucher_data.tpl'   => 'klarna/klarna/views/checkout/inc/kl_klarna_checkout_voucher_data.tpl',
        'kl_klarna_checkout_voucher_box.tpl'    => 'klarna/klarna/views/checkout/inc/kl_klarna_checkout_voucher_box.tpl',
        'kl_klarna_checkout_voucher_errors.tpl' => 'klarna/klarna/views/checkout/inc/kl_klarna_checkout_voucher_errors.tpl',
        'kl_klarna_json.tpl'                    => 'klarna/klarna/views/checkout/inc/kl_klarna_json.tpl',
        'kl_klarna_country_select_popup.tpl'    => 'klarna/klarna/views/checkout/inc/kl_klarna_country_select_popup.tpl',
        'kl_klarna_checkout_login_box.tpl'      => 'klarna/klarna/views/checkout/inc/kl_klarna_checkout_login_box.tpl',
        'kl_klarna_checkout_address_box.tpl'    => 'klarna/klarna/views/checkout/inc/kl_klarna_checkout_address_box.tpl',
        'kl_klarna_notice.tpl'                  => 'klarna/klarna/views/widget/kl_klarna_notice.tpl',
        //
        //admin
        'kl_klarna_general.tpl'                 => 'klarna/klarna/views/admin/tpl/kl_klarna_general.tpl',
        'kl_klarna_design.tpl'                  => 'klarna/klarna/views/admin/tpl/kl_klarna_design.tpl',
        'kl_klarna_kco_config.tpl'              => 'klarna/klarna/views/admin/tpl/kl_klarna_kco_config.tpl',
        'kl_klarna_kp_config.tpl'               => 'klarna/klarna/views/admin/tpl/kl_klarna_kp_config.tpl',
        'kl_klarna_start.tpl'                   => 'klarna/klarna/views/admin/tpl/kl_klarna_start.tpl',
        'kl_klarna_external_payments.tpl'       => 'klarna/klarna/views/admin/tpl/kl_klarna_external_payments.tpl',
        'kl_klarna_emd_admin.tpl'               => 'klarna/klarna/views/admin/tpl/kl_klarna_emd_admin.tpl',
        'kl_klarna_orders.tpl'                  => 'klarna/klarna/views/admin/tpl/kl_klarna_orders.tpl',
        //admin partial
        'kl_country_creds.tpl'                  => 'klarna/klarna/views/admin/tpl/kl_country_creds.tpl',
        'kl_header.tpl'                         => 'klarna/klarna/views/admin/tpl/kl_header.tpl',
        'kl_lang_spec_conf.tpl'                 => 'klarna/klarna/views/admin/tpl/kl_lang_spec_conf.tpl',
        //
    ),

    'blocks' => array(
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
            'block'    => 'admin_order_article_listitem',
            'file'     => 'kl_admin_order_article_listitem',
        ),
        array(
            'template' => 'order_list.tpl',
            'block'    => 'admin_list_order_filter',
            'file'     => 'kl_admin_list_order_filter',
        ),

    ),

    'settings' => array(),
    'events'   => array(
        'onActivate'   => 'KlarnaInstaller::onActivate',
        'onDeactivate' => 'KlarnaInstaller::onDeactivate',
    ),
);