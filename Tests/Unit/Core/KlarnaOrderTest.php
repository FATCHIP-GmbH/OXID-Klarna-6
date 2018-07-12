<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\NewsSubscribed;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Model\PaymentList;
use TopConcepts\Klarna\Controller\KlarnaPaymentController;
use TopConcepts\Klarna\Core\KlarnaOrder;
use TopConcepts\Klarna\Core\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Model\KlarnaEMD;
use TopConcepts\Klarna\Model\KlarnaUser;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaOrderTest extends ModuleUnitTestCase
{
    public function testGetKlarnaCountryListByPayment()
    {
        $payment          = $this->createStub(Payment::class, ['getCountries' => ['testId']]);
        $aActiveCountries = ['testId' => 'test'];

        $order  = $this->createStub(KlarnaOrder::class, ['__construct' => null]);
        $result = $order->getKlarnaCountryListByPayment($payment, $aActiveCountries);

        $this->assertEquals($result, ['test']);

    }

    public function test__construct()
    {
        $price = $this->createStub(Price::class, ['getBruttoPrice' => 1000, 'getVat' => 0.23]);

        $payment                                 = $this->createStub(Payment::class, ['calculate' => null, 'getPrice' => $price]);
        $payment->oxpayments__tcklarna_externalpayment  = new Field(1, Field::T_RAW);
        $payment->oxpayments__tcklarna_externalcheckout = new Field(1, Field::T_RAW);
        $payment->oxpayments__oxlongdesc         = new Field('<title>test</title>', Field::T_RAW);

        $paymentList  = [
            'klarna_checkout' => $payment,
            'oxidpaypal'      => $payment,
        ];
        $oPaymentList = $this->createStub(PaymentList::class, ['getPaymentList' => $paymentList]);
        UtilsObject::setClassInstance(PaymentList::class, $oPaymentList);

        $user = $this->createStub(
            User::class,
            [
                'getKlarnaData' => ['test' => 'test'],
            ]
        );

        $basket = $this->createStub(
            Basket::class,
            [
                'getBasketCurrency'        => (object)['name' => 'test'],
                'getKlarnaOrderLines'      => ['order_lines' => 'test'],
                'getShippingId'            => '',
                'tcklarna_calculateDeliveryCost' => $price,
                'getPriceForPayment'       => 100,
            ]
        );

        $delivery                         = oxNew(DeliverySet::class);
        $delivery->oxdeliveryset__oxtitle = new Field('title', Field::T_RAW);
        $payment                          = $this->createStub(PaymentController::class, ['getCheckoutShippingSets' => ['1' => $delivery]]);

        //setup mock
        $order = $this->createStub(
            KlarnaOrder::class,
            [
                '_getPayment'                  => $payment,
                'getConfig'                    => Registry::getConfig(),
                'doesShippingMethodSupportKCO' => true,
            ]
        );

        $this->setModuleConfVar('sKlarnaTermsConditionsURI_DE', 'https://testurl');
        $this->setModuleConfVar('sKlarnaCancellationRightsURI_DE', 'https://testurl');
        $this->setModuleConfVar('iKlarnaValidation', 1);
        $this->setModuleConfVar('blKlarnaEnableAutofocus', false);
        $this->setConfigParam('sSSLShopURL', 'https://testurl');

        //call constructor
        $order->__construct($basket, $user);

        $expected = [
            'purchase_country'         => "DE",
            'purchase_currency'        => "test",
            'locale'                   => "de-DE",
            'merchant_urls'            => [
                'terms'              => "https://testurl",
                'checkout'           => "https://testurl?cl=KlarnaExpress",
                'confirmation'       => "https://testurl?cl=order&fnc=execute&klarna_order_id={checkout.order.id}",
                'push'               => "https://testurl?cl=KlarnaAcknowledge&klarna_order_id={checkout.order.id}",
                'cancellation_terms' => "https://testurl",
                'validation'         => 'https://testurl?cl=KlarnaValidate&s=&klarna_order_id={checkout.order.id}',
            ],
            'test'                     => "test",
            'order_lines'              =>
                "test",
            'shipping_countries'       =>
                [
                    "AD",
                    "AT",
                    "DE",
                ],
            'shipping_options'         =>
                [
                    [
                        'id'          => 1,
                        'name'        => "title",
                        'description' => null,
                        'promo'       => null,
                        'tax_amount'  => 229,
                        'price'       => 100000,
                        'tax_rate'    => 23,
                        'preselected' => false,
                    ],
                ],
            'external_payment_methods' => [
                [
                    'name'         => null,
                    'redirect_url' => "https://testurlindex.php?cl=order&fnc=klarnaExternalPayment&payment_id=klarna_checkout",
                    'image_url'    => null,
                    'fee'          => 100000,
                    'description'  => "test",
                    'countries'    => ["AD", "AT", "DE"],
                ],
                [
                    'name'         => null,
                    'redirect_url' => "https://testurlindex.php?cl=order&fnc=klarnaExternalPayment&payment_id=oxidpaypal&displayCartInPayPal=1",
                    'image_url'    => null,
                    'fee'          => 100000,
                    'description'  => "test",
                    'countries'    => ["AD", "AT", "DE"],
                ],
            ],
            'external_checkouts'       => [
                [
                    'name'         => null,
                    'redirect_url' => "https://testurlindex.php?cl=order&fnc=klarnaExternalPayment&payment_id=klarna_checkout&externalCheckout=1",
                    'image_url'    => null,
                    'fee'          => 100000,
                    'description'  => "test",
                    'countries'    => ["AD", "AT", "DE"],
                ],
                [
                    'name'         => null,
                    'redirect_url' => "https://testurlindex.php?cl=order&fnc=klarnaExternalPayment&payment_id=oxidpaypal&externalCheckout=1",
                    'image_url'    => null,
                    'fee'          => 100000,
                    'description'  => "test",
                    'countries'    => ["AD", "AT", "DE"],
                ],
            ],
            'options'                  =>
                [
                    'additional_checkbox'               => null,
                    'allow_separate_shipping_address'   => true,
                    'phone_mandatory'                   => true,
                    'date_of_birth_mandatory'           => true,
                    'require_validate_callback_success' => false,
                    'shipping_details'                  => "Wir kümmern uns schnellstens um den Versand!",
                ],
            'gui'                      => ['options' => ['disable_autofocus']],
            'merchant_data'            => 'To be implemented by the merchant.',
        ];

        $result = $this->getProtectedClassProperty($order, '_aOrderData');

        $this->assertEquals($expected, $result);

        $this->setModuleConfVar('sKlarnaCancellationRightsURI_DE', null);
        $result = $order->__construct($basket, $user);
        $this->assertFalse($result);
    }

    public function testGetOrderData()
    {
        $order = $this->createStub(KlarnaOrder::class, ['__construct' => null]);
        $this->assertNull($order->getOrderData());
    }

    public function test_getPayment()
    {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, '_getPayment');
        $methodReflection->setAccessible(true);

        $order  = $this->createStub(KlarnaOrder::class, []);
        $result = $methodReflection->invoke($order);
        $this->assertInstanceOf(KlarnaPaymentController::class, $result);

    }

    public function testGetAdditionalCheckbox()
    {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'getAdditionalCheckbox');
        $methodReflection->setAccessible(true);

        $user  = $this->createStub(User::class, ['getType' => KlarnaUser::NOT_EXISTING]);
        $order = $this->createStub(KlarnaOrder::class, ['__construct' => null]);
        $this->setProtectedClassProperty($order, '_oUser', $user);
        $this->setModuleConfVar('iKlarnaActiveCheckbox', '22');
        $result = $methodReflection->invoke($order);

        $this->assertEquals($result, 22);


        $user = $this->createStub(User::class, ['getType' => KlarnaUser::REGISTERED]);
        $this->setProtectedClassProperty($order, '_oUser', $user);

        $result = $methodReflection->invoke($order);

        $this->assertEquals($result, 2);

        $this->setModuleConfVar('iKlarnaActiveCheckbox', '0');
        $result = $methodReflection->invoke($order);

        $this->assertEquals($result, 0);

        $newsSubscribed = $this->createStub(NewsSubscribed::class, ['getOptInStatus' => 1]);
        $user           = $this->createStub(User::class, ['getType' => KlarnaUser::REGISTERED, 'getNewsSubscription' => $newsSubscribed]);
        $this->setProtectedClassProperty($order, '_oUser', $user);
        $result = $methodReflection->invoke($order);

        $this->assertEquals($result, 0);
    }

    public function testDoesShippingMethodSupportKCO()
    {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'doesShippingMethodSupportKCO');
        $methodReflection->setAccessible(true);

        $oPaymentList = $this->createStub(PaymentList::class, ['getPaymentList' => ['klarna_checkout' => 'test']]);
        \oxTestModules::addModuleObject(PaymentList::class, $oPaymentList);
        $order = $this->createStub(KlarnaOrder::class, ['__construct' => null]);

        $result = $methodReflection->invokeArgs($order, [1, 1]);

        $this->assertTrue($result);

    }


    public function testGetSupportedShippingMethods()
    {
        $basket           = $this->createStub(Basket::class, []);
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'getSupportedShippingMethods');
        $methodReflection->setAccessible(true);
        $order  = $this->createStub(KlarnaOrder::class, ['__construct' => null]);
        $result = $methodReflection->invokeArgs($order, [$basket]);

        $this->assertEmpty($result);

        $basket = $this->createStub(
            Basket::class,
            [
                'getShippingId'      => '1',
                'getPriceForPayment' => 100,
            ]
        );

        $delivery                         = oxNew(DeliverySet::class);
        $delivery->oxdeliveryset__oxtitle = new Field('title', Field::T_RAW);
        $payment                          = $this->createStub(PaymentController::class, ['getCheckoutShippingSets' => ['1' => $delivery]]);

        //setup mock
        $order = $this->createStub(
            KlarnaOrder::class,
            [
                '_getPayment'                  => $payment,
                'getConfig'                    => Registry::getConfig(),
                'doesShippingMethodSupportKCO' => false,
            ]
        );

        $user = $this->createStub(User::class, ['getActiveCountry' => '1111']);
        $this->setProtectedClassProperty($order, '_oUser', $user);

        $this->setExpectedException(
            KlarnaConfigException::class,
            sprintf(
                Registry::getLang()->translateString('TCKLARNA_ERROR_NO_SHIPPING_METHODS_SET_UP'),
                ''
            )
        );
        $methodReflection->invokeArgs($order, [$basket]);
    }

    public function testSetAttachmentsData()
    {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'setAttachmentsData');
        $methodReflection->setAccessible(true);

        $oMockKlarnaEMD = $this->createStub(KlarnaEMD::class, ['getAttachments' => ['test']]);
        UtilsObject::setClassInstance(KlarnaEMD::class, $oMockKlarnaEMD);
        $order = $this->createStub(KlarnaOrder::class, ['__construct' => null]);

        $user = $this->createStub(User::class, ['isFake' => false]);
        $this->setProtectedClassProperty($order, '_oUser', $user);

        $methodReflection->invoke($order);

        $expected = [
            'attachment' => [
                'content_type' => "application/vnd.klarna.internal.emd-v2+json",
                'body'         => json_encode(['test']),
            ],
        ];

        $this->assertEquals($expected, $this->getProtectedClassProperty($order, '_aOrderData'));
    }

    /**
     * @dataProvider additionalCheckboxDataProvider
     * @param $case
     * @param $expected
     */
    public function testGetAdditionalCheckboxData($case, $expected)
    {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'getAdditionalCheckboxData');
        $methodReflection->setAccessible(true);

        $order = $this->createStub(KlarnaOrder::class, ['__construct' => null, 'getAdditionalCheckbox' => $case]);

        $result = $methodReflection->invoke($order);

        $this->assertEquals($expected, $result);
    }

    public function additionalCheckboxDataProvider()
    {
        return [
            [0, null],
            [
                1,
                [
                    'text'     => Registry::getLang()->translateString('TCKLARNA_CREATE_USER_ACCOUNT', null, false),
                    'checked'  => false,
                    'required' => false,
                ],
            ],
            [
                2,
                [
                    'text'     => Registry::getLang()->translateString('TCKLARNA_SUBSCRIBE_TO_NEWSLETTER', null, false),
                    'checked'  => false,
                    'required' => false,
                ],
            ],
            [
                3,
                [
                    'text'     => Registry::getLang()->translateString('TCKLARNA_CREATE_USER_ACCOUNT_AND_SUBSCRIBE', null, false),
                    'checked'  => false,
                    'required' => false,
                ],
            ],
            [4, null],
        ];
    }
}
