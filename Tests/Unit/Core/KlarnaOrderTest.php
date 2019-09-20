<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\DeliveryList;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\NewsSubscribed;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Application\Model\PaymentList;
use TopConcepts\Klarna\Controller\KlarnaPaymentController;
use TopConcepts\Klarna\Core\KlarnaOrder;
use TopConcepts\Klarna\Core\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Model\KlarnaEMD;
use TopConcepts\Klarna\Model\KlarnaUser;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaOrderTest extends ModuleUnitTestCase {
    public function testDisplayErrors() {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaOrder($oBasket, $oUser);
        $this->setProtectedClassProperty($oKlarnaOrder, 'errors', ['Error1', 'Error2']);
        $oUtilsView = $this->getMockBuilder(UtilsView::class)->setMethods(['addErrorToDisplay'])->getMock();
        $oUtilsView->expects($this->at(0))->method('addErrorToDisplay')->with('Error1');
        $oUtilsView->expects($this->at(1))->method('addErrorToDisplay')->with('Error2');
        Registry::set(UtilsView::class, $oUtilsView);
        $oKlarnaOrder->displayErrors();
    }

    public function testGetKlarnaCountryListByPayment()
    {
        $payment = $this->getMockBuilder(Payment::class)->setMethods(['getCountries'])->getMock();
        $payment->expects($this->once())->method('getCountries')->willReturn(['testId']);
        $aActiveCountries = ['testId' => 'test'];

        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->setMethods(['getExternalPaymentMethods'])
            ->disableOriginalConstructor()
            ->getMock();
        $result = $order->getKlarnaCountryListByPayment($payment, $aActiveCountries);

        $this->assertEquals(['test'], $result);

    }

    public function test__construct() {
        $price = $this->getMockBuilder(Price::class)
            ->setMethods(['getBruttoPrice', 'getVat'])
            ->getMock();
        $price->expects($this->any())->method('getBruttoPrice')->willReturn(1000);
        $price->expects($this->once())->method('getVat')->willReturn(0.23);

        $payment = $this->getMockBuilder(Payment::class)
            ->setMethods(['calculate', 'getPrice'])
            ->getMock();
        $payment->expects($this->exactly(2))->method('calculate')->willReturn(null);
        $payment->expects($this->exactly(2))->method('getPrice')->willReturn($price);
        $payment->oxpayments__tcklarna_externalpayment = new Field(1, Field::T_RAW);
        $payment->oxpayments__tcklarna_externalcheckout = new Field(1, Field::T_RAW);
        $payment->oxpayments__oxlongdesc = new Field('<title>test</title>', Field::T_RAW);

        $paymentList = [
            'klarna_checkout' => $payment,
            'oxidpaypal'      => $payment,
        ];
        $oPaymentList = $this->getMockBuilder(PaymentList::class)->setMethods(['getPaymentList'])->getMock();
        $oPaymentList->expects($this->once())->method('getPaymentList')->willReturn($paymentList);
        Registry::set(PaymentList::class, $oPaymentList);

        $user = $this->getMockBuilder(User::class)
            ->setMethods(['getKlarnaData'])
            ->getMock();
        $user->expects($this->any())->method('getKlarnaData')->willReturn(['test' => 'test']);
        $basket = $this->getMockBuilder(Basket::class)
            ->setMethods(['getBasketCurrency', 'getKlarnaOrderLines', 'getShippingId', 'tcklarna_calculateDeliveryCost', 'getPriceForPayment'])
            ->getMock();
        $basket->expects($this->any())->method('getBasketCurrency')->willReturn((object)['name' => 'test']);
        $basket->expects($this->once())->method('getKlarnaOrderLines')->willReturn(['order_lines' => 'test']);
        $basket->expects($this->any())->method('getShippingId')->willReturn('');
        $basket->expects($this->once())->method('tcklarna_calculateDeliveryCost')->willReturn($price);
        $basket->expects($this->exactly(2))->method('getPriceForPayment')->willReturn(100);
        $delivery = oxNew(DeliverySet::class);
        $delivery->oxdeliveryset__oxtitle = new Field('title', Field::T_RAW);
        $payment = $this->getMockBuilder(PaymentController::class)->setMethods(['getCheckoutShippingSets'])->getMock();
        $payment->expects($this->once())->method('getCheckoutShippingSets')->willReturn(['shippingSetMock' => $delivery]);

        //setup mock
        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->disableOriginalConstructor()
            ->setMethods(['_getPayment', 'doesShippingMethodSupportKCO'])
            ->getMock();
        $order->expects($this->once())->method('_getPayment')->willReturn($payment);
        $order->expects($this->once())->method('doesShippingMethodSupportKCO')->willReturn(true);

        $oDeliveryList = $this->getMockBuilder(DeliveryList::class)->setMethods(['hasDeliveries'])->getMock();
        $oDeliveryList->expects($this->any())->method('hasDeliveries')->willReturn(true);
        Registry::set(DeliveryList::class, $oDeliveryList);

        $this->setModuleConfVar('sKlarnaTermsConditionsURI_DE', 'https://testurl');
        $this->setModuleConfVar('sKlarnaCancellationRightsURI_DE', 'https://testurl');
        $this->setModuleConfVar('iKlarnaValidation', 1);
        $this->setModuleConfVar('blKlarnaEnableAutofocus', false);
        $this->setModuleConfVar('blKlarnaAllowSeparateDeliveryAddress', true);
        $this->setConfigParam('sSSLShopURL', 'https://testurl');

        //call constructor
        $order->__construct($basket, $user);
        $sGetChallenge = Registry::getSession()->getSessionChallengeToken();
        $this->setRequestParameter('stoken', $sGetChallenge);

        $expected = [
            'purchase_country'         => "DE",
            'purchase_currency'        => "test",
            'locale'                   => "de-DE",
            'merchant_urls'            => [
                'terms'              => "https://testurl",
                'checkout'           => "https://testurl?cl=KlarnaExpress",
                'confirmation'       => "https://testurl?cl=order&fnc=execute&klarna_order_id={checkout.order.id}&stoken=$sGetChallenge",
                'push'               => "https://testurl?cl=KlarnaAcknowledge&klarna_order_id={checkout.order.id}",
                'cancellation_terms' => "https://testurl",
                'validation'         => 'https://testurl?cl=KlarnaValidate&s=',
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
                        'id'          => 'shippingSetMock',
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
                    'redirect_url' => "https://testurlindex.php?cl=order&fnc=klarnaExternalPayment&payment_id=oxidpaypal&displayCartInPayPal=1&externalCheckout=1",
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
            'billing_countries' => ["AD", "AT", "DE"]
        ];

        $result = $this->getProtectedClassProperty($order, '_aOrderData');
        $this->assertEquals($expected, $result);

        $this->setModuleConfVar('sKlarnaCancellationRightsURI_DE', null);
        $result = $order->__construct($basket, $user);
        $this->assertFalse($result);
    }

    public function testGetOrderData() {
        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->setMethods(['getExternalPaymentMethods'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertNull($order->getOrderData());
    }

    public function test_getPayment() {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, '_getPayment');
        $methodReflection->setAccessible(true);

        $order = $this->getMockBuilder(KlarnaOrder::class)->disableOriginalConstructor()->getMock();
        $result = $methodReflection->invoke($order);
        $this->assertInstanceOf(KlarnaPaymentController::class, $result);

    }

    public function testGetAdditionalCheckbox() {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'getAdditionalCheckbox');
        $methodReflection->setAccessible(true);

        $user = $this->getMockBuilder(User::class)
            ->setMethods(['getType'])
            ->getMock();
        $user->expects($this->once())->method('getType')->willReturn(KlarnaUser::NOT_EXISTING);
        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->setMethods(['getExternalPaymentMethods'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->setProtectedClassProperty($order, '_oUser', $user);
        $this->setModuleConfVar('iKlarnaActiveCheckbox', '22');
        $result = $methodReflection->invoke($order);
        $this->assertEquals(22, $result);


        $user = $this->getMockBuilder(User::class)
            ->setMethods(['getType'])
            ->getMock();
        $user->expects($this->any())->method('getType')->willReturn(KlarnaUser::REGISTERED);
        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->setMethods(['getExternalPaymentMethods'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->setProtectedClassProperty($order, '_oUser', $user);
        $result = $methodReflection->invoke($order);
        $this->assertEquals(2, $result);

        $this->setModuleConfVar('iKlarnaActiveCheckbox', '0');
        $result = $methodReflection->invoke($order);
        $this->assertEquals(0, $result);

        $newsSubscribed = $this->getMockBuilder(NewsSubscribed::class)->setMethods(['getOptInStatus'])->getMock();
        $newsSubscribed->expects($this->once())->method('getOptInStatus')->willReturn(1);
        $user = $this->getMockBuilder(User::class)
            ->setMethods(['getType', 'getNewsSubscription'])
            ->getMock();
        $user->expects($this->any())->method('getType')->willReturn(KlarnaUser::REGISTERED);
        $user->expects($this->once())->method('getNewsSubscription')->willReturn($newsSubscribed);
        $this->setProtectedClassProperty($order, '_oUser', $user);
        $result = $methodReflection->invoke($order);
        $this->assertEquals(0, $result);
    }

    public function testDoesShippingMethodSupportKCO() {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'doesShippingMethodSupportKCO');
        $methodReflection->setAccessible(true);

        $oPaymentList = $this->getMockBuilder(PaymentList::class)->setMethods(['getPaymentList'])->getMock();
        $oPaymentList->expects($this->once())->method('getPaymentList')->willReturn(['klarna_checkout' => 'test']);
        Registry::set(PaymentList::class, $oPaymentList);
        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->setMethods(['getExternalPaymentMethods'])
            ->disableOriginalConstructor()
            ->getMock();

        $result = $methodReflection->invokeArgs($order, [1, 1]);

        $this->assertTrue($result);

    }


    public function testGetSupportedShippingMethods() {
        $basket = $this->getMockBuilder(Basket::class)->getMock();
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'getSupportedShippingMethods');
        $methodReflection->setAccessible(true);
        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->setMethods(['getExternalPaymentMethods'])
            ->disableOriginalConstructor()
            ->getMock();
        $result = $methodReflection->invokeArgs($order, [$basket]);
        $this->assertEmpty($result);


        $basket = $this->getMockBuilder(Basket::class)
            ->setMethods(['getShippingId', 'getPriceForPayment'])
            ->getMock();
        $basket->expects($this->once())->method('getShippingId')->willReturn('1');
        $basket->expects($this->once())->method('getPriceForPayment')->willReturn(100);
        $delivery = oxNew(DeliverySet::class);
        $delivery->oxdeliveryset__oxtitle = new Field('title', Field::T_RAW);
        $payment = $this->getMockBuilder(PaymentController::class)->setMethods(['getCheckoutShippingSets'])->getMock();
        $payment->expects($this->once())->method('getCheckoutShippingSets')->willReturn(['1' => $delivery]);

        //setup mock
        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->setMethods(['_getPayment', 'doesShippingMethodSupportKCO'])
            ->disableOriginalConstructor()
            ->getMock();
        $order->expects($this->once())->method('_getPayment')->willReturn($payment);
        $order->expects($this->once())->method('doesShippingMethodSupportKCO')->willReturn(false);
        $user = $this->getMockBuilder(User::class)
            ->setMethods(['getActiveCountry'])
            ->getMock();
        $user->expects($this->once())->method('getActiveCountry')->willReturn('1111');
        $this->setProtectedClassProperty($order, '_oUser', $user);

        $this->expectException(KlarnaConfigException::class);
        $this->expectExceptionMessage(sprintf(
            Registry::getLang()->translateString('TCKLARNA_ERROR_NO_SHIPPING_METHODS_SET_UP'),
            ''
        ));
        $methodReflection->invokeArgs($order, [$basket]);
    }

    public function testSetAttachmentsData() {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'setAttachmentsData');
        $methodReflection->setAccessible(true);
        $oMockKlarnaEMD = $this->getMockBuilder(KlarnaEMD::class)
            ->setMethods(['getAttachments'])
            ->getMock();
        $oMockKlarnaEMD->expects($this->once())->method('getAttachments')->willReturn(['test']);
        UtilsObject::setClassInstance(KlarnaEMD::class, $oMockKlarnaEMD);
        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->setMethods(['getExternalPaymentMethods'])
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(User::class)
            ->setMethods(['isFake'])
            ->getMock();
        $user->expects($this->once())->method('isFake')->willReturn(false);

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
     * @throws \ReflectionException
     */
    public function testGetAdditionalCheckboxData($case, $expected) {
        $methodReflection = new \ReflectionMethod(KlarnaOrder::class, 'getAdditionalCheckboxData');
        $methodReflection->setAccessible(true);

        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAdditionalCheckbox'])
            ->getMock();
        $order->expects($this->once())
            ->method('getAdditionalCheckbox')
            ->willReturn($case);

        $result = $methodReflection->invoke($order);

        $this->assertEquals($expected, $result);
    }

    public function additionalCheckboxDataProvider() {
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
