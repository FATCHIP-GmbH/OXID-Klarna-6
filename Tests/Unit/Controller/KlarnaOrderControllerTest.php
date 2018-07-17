<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;


use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\PayPalModule\Controller\ExpressCheckoutDispatcher;
use TopConcepts\Klarna\Controller\KlarnaOrderController;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Model\KlarnaBasket;
use TopConcepts\Klarna\Model\KlarnaUser;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaOrderControllerTest extends ModuleUnitTestCase
{
    const COUNTRIES = [
        'AT' => 'a7c40f6320aeb2ec2.72885259',
        'DE' => 'a7c40f631fc920687.20179984',
        'AF' => '8f241f11095306451.36998225',
    ];

    public function testKlarnaExternalPaymentError()
    {
        $mock = $this->createStub(OrderController::class, ['init' => true]);
        $mock->klarnaExternalPayment();
        $result = unserialize($this->getSessionParam('Errors')['default'][0]);

        $this->assertInstanceOf(DisplayError::class, $result);
        $result = $result->getOxMessage();
        $this->assertEquals('Ein Fehler ist aufgetreten. Bitte noch einmal versuchen.', $result);
    }

    /**
     * @dataProvider externalPaymentDataProvider
     * @param $paymentId
     * @param $moduleId
     */
    public function testKlarnaExternalPayment($paymentId, $moduleId)
    {
        if($paymentId == 'test' || Registry::get(ViewConfig::class)->isModuleActive($moduleId)) {
            $payment = $this->createStub(Payment::class, ['load' => true]);
            $payment->oxpayments__oxactive = new Field(true);
            UtilsObject::setClassInstance(Payment::class, $payment);

            $this->setSessionParam('klarna_checkout_order_id', '1');
            $this->setRequestParameter('payment_id', $paymentId);

            $oBasket = $this->createStub(KlarnaBasket::class, ['onUpdate' => true]);

            $user = $this->createStub(KlarnaUser::class, ['isCreatable' => true, 'save' => true, 'onOrderExecute' => true]);
            $this->getSession()->setBasket($oBasket);

            $mock = $this->createStub(
                KlarnaOrderController::class,
                [
                    'klarnaExternalCheckout' => true,
                    '_createUser' => true,
                ]
            );

            $this->setProtectedClassProperty($mock, 'isExternalCheckout', true);
            $this->setProtectedClassProperty($mock, '_oUser', $user);
            $this->setProtectedClassProperty(
                $mock,
                '_aOrderData',
                ['selected_shipping_option' => ['id' => 'shippingOption']]
            );

            $result = $mock->klarnaExternalPayment();

            if ($paymentId == 'bestitamazon') {
                $this->assertEquals(
                    Registry::getConfig()->getShopSecureHomeUrl()."cl=KlarnaEpmDispatcher&fnc=amazonLogin",
                    \oxUtilsHelper::$sRedirectUrl
                );
            } elseif ($paymentId == 'oxidpaypal') {
                $this->assertEquals('basket', $result);
            } else {
                $this->assertEquals(null, $result);
            }

            $this->assertEquals($paymentId, $this->getProtectedClassProperty($oBasket, '_sPaymentId'));
        }

    }

    public function externalPaymentDataProvider()
    {

        return [
            ['test', null],
            ['bestitamazon', 'bestitamazonpay4oxid'],
            ['oxidpaypal', 'oepaypal'],
        ];
    }

    public function testExecute()
    {
        $order = $this->createStub(Order::class, ['finalizeOrder' => 1]);
        UtilsObject::setClassInstance(Order::class, $order);
        $user = $this->createStub(KlarnaUser::class, ['getType' => 0, 'save' => true, 'onOrderExecute' => true]);
        $oBasket = $this->createStub(
            KlarnaBasket::class,
            ['getPaymentId' => 'klarna_checkout', 'calculateBasket' => true]
        );
        $this->getSession()->setBasket($oBasket);
        $mock = $this->createStub(
            KlarnaOrderController::class,
            ['kcoBeforeExecute' => true, 'getDeliveryAddressMD5' => 'address']
        );
        $this->setProtectedClassProperty($mock, '_oUser', $user);
        $this->setProtectedClassProperty(
            $mock,
            '_aOrderData',
            ['merchant_requested' => ['additional_checkbox' => true]]
        );
        $this->setModuleConfVar('iKlarnaActiveCheckbox', 1);
        $mock->execute();
        $addressResult = $this->getSessionParam('sDelAddrMD5');
        $this->assertEquals('address', $addressResult);
        $paymentId = $this->getSessionParam('paymentid');
        $this->assertEquals('klarna_checkout', $paymentId);

    }

    /**
     * @throws \ReflectionException
     */
    public function testKcoExecute()
    {
        $this->setSessionParam('klarna_checkout_order_id', 1);
        $oBasket = $this->createStub(KlarnaBasket::class, ['calculateBasket' => true]);
        $order = $this->getMock(Order::class, ['finalizeOrder']);
        $order->expects($this->any())->method('finalizeOrder')->willThrowException(new StandardException('test'));
        UtilsObject::setClassInstance(Order::class, $order);

        $mock = $this->getMock(KlarnaOrderController::class, []);
        $class = new \ReflectionClass(KlarnaOrderController::class);
        $method = $class->getMethod('kcoExecute');
        $method->setAccessible(true);

        $this->assertEquals(1, $this->getSessionParam('klarna_checkout_order_id'));
        $method->invokeArgs($mock, [$oBasket]);
        $this->assertNull($this->getSessionParam('klarna_checkout_order_id'));
        $result = unserialize($this->getSessionParam('Errors')['default'][0]);
        $this->assertInstanceOf(ExceptionToDisplay::class, $result);

        $result = $result->getOxMessage();
        $this->assertEquals('test', $result);
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @throws \ReflectionException
     */
    public function testGetKlarnaAllowedExternalPayments()
    {
        $database = DatabaseProvider::getDB();
        $database->execute("UPDATE oxpayments SET oxactive=1, tcklarna_externalpayment=1 WHERE oxid='oxidpayadvance'");

        $class = new \ReflectionClass(KlarnaOrderController::class);
        $method = $class->getMethod('getKlarnaAllowedExternalPayments');
        $method->setAccessible(true);
        $mock = $this->createStub(KlarnaOrderController::class, ['init' => true]);
        $result = $method->invoke($mock);

        $this->assertEquals('oxidpayadvance', $result[0]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetKlarnaOrderClient()
    {
        $class = new \ReflectionClass(KlarnaOrderController::class);
        $method = $class->getMethod('getKlarnaOrderClient');
        $method->setAccessible(true);
        $mock = $this->createStub(KlarnaOrderController::class, ['init' => true]);
        $result = $method->invokeArgs($mock, ['DE']);

        $this->assertInstanceOf(KlarnaOrderManagementClient::class, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testKcoBeforeExecute()
    {
        $class = new \ReflectionClass(KlarnaOrderController::class);
        $method = $class->getMethod('kcoBeforeExecute');
        $method->setAccessible(true);

        $user = $this->createStub(KlarnaUser::class, ['setNewsSubscription' => true]);
        $mock = $this->createStub(KlarnaOrderController::class, ['_validateUser' => true, 'getUser' => $user]);
        $this->setProtectedClassProperty(
            $mock,
            '_aOrderData',
            ['merchant_requested' => ['additional_checkbox' => true]]
        );
        $this->setModuleConfVar('iKlarnaActiveCheckbox', 2);
        $result = $method->invoke($mock);
        $this->assertNull($result);

        $mock = $this->createStub(KlarnaOrderController::class, ['_validateUser' => true]);
        $this->setProtectedClassProperty(
            $mock,
            '_aOrderData',
            ['merchant_requested' => ['additional_checkbox' => true]]
        );
        $this->setModuleConfVar('iKlarnaActiveCheckbox', 2);

        $this->setExpectedException(StandardException::class, 'no user object');
        $method->invoke($mock);
        $result = $this->getProtectedClassProperty($mock, '_aResultErrors');
        $this->assertEquals('test', $result[0]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testKcoBeforeExecuteException()
    {
        $class = new \ReflectionClass(KlarnaOrderController::class);
        $method = $class->getMethod('kcoBeforeExecute');
        $method->setAccessible(true);

        $mock = $this->getMock(KlarnaOrderController::class, ['_validateUser']);
        $mock->expects($this->any())->method('_validateUser')->willThrowException(new StandardException('test'));
        $method->invoke($mock);
        $result = $this->getProtectedClassProperty($mock, '_aResultErrors');
        $this->assertEquals('test', $result[0]);
    }

    /**
     * @dataProvider validateUserDataProvider
     * @param $type
     * @param $expected
     * @throws \ReflectionException
     */
    public function test_validateUser($type, $expected)
    {
        $class = new \ReflectionClass(KlarnaOrderController::class);
        $method = $class->getMethod('_validateUser');
        $method->setAccessible(true);

        $user = $this->createStub(KlarnaUser::class, ['getType' => $type]);
        $mock = $this->createStub(KlarnaOrderController::class, ['_createUser' => true]);
        $this->setProtectedClassProperty($mock, '_oUser', $user);
        $result = $method->invoke($mock);

        $this->assertEquals($expected, $result);
    }

    public function validateUserDataProvider()
    {
        return [
            [0, true],
            [2, true],
            [3, null],

        ];
    }

    public function testRender()
    {
        $this->setModuleConfVar('sKlarnaActiveMode', KlarnaConsts::MODULE_MODE_KP);
        $oBasket = $this->createStub(
            KlarnaBasket::class,
            ['getPaymentId' => 'klarna_pay_now']
        );
        $this->getSession()->setBasket($oBasket);
        $this->setSessionParam('klarna_session_data', ['client_token' => 'test']);

        $mock = $this->createStub(KlarnaOrderController::class, ['isCountryHasKlarnaPaymentsAvailable' => true]);
        $result = $mock->render();

        $locale = $mock->getViewData()['sLocale'];
        $clientToken = $mock->getViewData()['client_token'];

        $this->assertEquals('page/checkout/order.tpl', $result);
        $this->assertEquals('de-de', $locale);
        $this->assertEquals('test', $clientToken);
        $this->assertEquals(true, $this->getProtectedClassProperty($mock, 'loadKlarnaPaymentWidget'));


        $this->setSessionParam('paymentid', 'klarna_checkout');
        $mock->render();
        $this->assertEquals(Registry::getConfig()->getShopSecureHomeUrl()."cl=basket", \oxUtilsHelper::$sRedirectUrl);
    }

    public function testIsCountryHasKlarnaPaymentsAvailable()
    {
        $oUser = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field(self::COUNTRIES['AT'], Field::T_RAW);

        $oOrderController = $this->getMock(OrderController::class, ['getUser']);
        $oOrderController->expects($this->once())->method('getUser')->willReturn($oUser);

        $result = $oOrderController->isCountryHasKlarnaPaymentsAvailable();
        $this->assertTrue($result);

        $oUser->oxuser__oxcountryid = new Field(self::COUNTRIES['DE'], Field::T_RAW);
        $result = $oOrderController->isCountryHasKlarnaPaymentsAvailable($oUser);
        $this->assertTrue($result);

        $oUser->oxuser__oxcountryid = new Field(self::COUNTRIES['AF'], Field::T_RAW);
        $result = $oOrderController->isCountryHasKlarnaPaymentsAvailable($oUser);
        $this->assertFalse($result);
    }

    public function testKpBeforeExecute()
    {
        $mock = $this->createStub(KlarnaOrderController::class, ['_validateTermsAndConditions' => false]);
        $mock->kpBeforeExecute();

        $errors = unserialize($this->getSessionParam('Errors')['default'][0]);

        $this->assertInstanceOf(DisplayError::class, $errors);
        $errorMessage = $errors->getOxMessage();
        $this->assertEquals('Bitte stimmen Sie den AGB und den Widerrufsbedingungen für digitale Inhalte zu.', $errorMessage);
        $this->assertEquals(Registry::getConfig()->getShopSecureHomeUrl() . 'cl=order', \oxUtilsHelper::$sRedirectUrl);

        $oKlarnaPayment = $this->createStub(KlarnaPayment::class, ['validateOrder' => true, 'isError' => false]);
        UtilsObject::setClassInstance(KlarnaPayment::class, $oKlarnaPayment);

        $client = $this->createStub(KlarnaPaymentsClient::class, ['createNewOrder' => ['order_id' => 'orderId', 'redirect_url' => 'testUrl']]);
        $paymentClient = $this->createStub(KlarnaPaymentsClient::class, ['initOrder' => $client]);

        $this->setRequestParameter('sAuthToken', 'testToken');
        $mock = $this->createStub(KlarnaOrderController::class, ['_validateTermsAndConditions' => true, 'getKlarnaPaymentsClient' => $paymentClient]);
        $mock->kpBeforeExecute();

        $this->assertEquals('testToken', $this->getSessionParam('sAuthToken'));
        $this->assertEquals('orderId', $this->getSessionParam('klarna_last_KP_order_id'));
        $this->assertEquals('testUrl', \oxUtilsHelper::$sRedirectUrl);

        $oKlarnaPayment = $this->createStub(KlarnaPayment::class, ['validateOrder' => true, 'isError' => true, 'displayErrors' => true]);
        UtilsObject::setClassInstance(KlarnaPayment::class, $oKlarnaPayment);

        $mock->kpBeforeExecute();

        $this->assertEquals(Registry::getConfig()->getShopSecureHomeUrl() . 'cl=order', \oxUtilsHelper::$sRedirectUrl);

    }

    public function initDP()
    {
        $userClassName = User::class;
        $kcoExternalPayments = ['oxidpaypal'];

        return [
            ['KP', 'payId', null, null, false, $kcoExternalPayments],
            ['KCO', 'klarna_checkout', 'DE', null, $userClassName, $kcoExternalPayments],
            ['KCO', 'klarna_checkout', 'AT', null, $userClassName, $kcoExternalPayments],
            ['KCO', 'klarna_checkout', 'DE', 1, false, $kcoExternalPayments],
            ['KCO', 'klarna_checkout', 'AF', null, $userClassName, $kcoExternalPayments],
            ['KCO', 'bestitamazon', 'DE', null, false, $kcoExternalPayments],
            ['KCO', 'oxidpaypal', 'DE', null, false, $kcoExternalPayments],
            ['KCO', 'oxidpaypal', 'AF', null, false, $kcoExternalPayments],
        ];
    }

    /**
     * @dataProvider initDP
     * @param $mode
     * @param $payId
     * @param $countryISO
     * @param $externalCheckout
     * @param $userClassName
     * @param $kcoExternalPayments
     */
    public function testInit($mode, $payId, $countryISO, $externalCheckout, $userClassName, $kcoExternalPayments)
    {
        $this->setModuleMode($mode);
        $this->setRequestParameter('externalCheckout', $externalCheckout);
        $this->setSessionParam('sCountryISO', $countryISO);

        $oBasket = oxNew(Basket::class);
        $oBasket->setPayment($payId);
        Registry::getSession()->setBasket($oBasket);
        Registry::getSession()->freeze();

        $client = $this->getMock(KlarnaCheckoutClient::class, ['getOrder']);
        $userClassName && $client->expects($this->once())->method('getOrder');

        $oOrderController = $this->getMock(
            OrderController::class,
            ['getKlarnaCheckoutClient', 'getKlarnaAllowedExternalPayments']
        );
        $userClassName && $oOrderController->expects($this->once())->method('getKlarnaCheckoutClient')->willReturn(
            $client
        );
        $oOrderController->expects($this->any())->method('getKlarnaAllowedExternalPayments')->willReturn(
            $kcoExternalPayments
        );
        $oOrderController->init();

        $oUser = $oOrderController->getUser();
        $userClassName ? $this->assertInstanceOf($userClassName, $oUser) : $this->assertFalse($oUser);
        $this->assertEquals($externalCheckout, $this->getSessionParam('externalCheckout'));
        $this->assertEquals(
            $externalCheckout,
            $this->getProtectedClassProperty($oOrderController, 'isExternalCheckout')
        );
        // back to default
        $this->setModuleMode('KCO');
    }

    public function testInit_exception()
    {
        $this->setSessionParam('sCountryISO', 'DE');

        $oBasket = oxNew(Basket::class);
        $oBasket->setPayment('klarna_checkout');
        Registry::getSession()->setBasket($oBasket);
        Registry::getSession()->freeze();

        $e = $this->getMock(KlarnaClientException::class, ['debugOut']);
        $e->expects($this->once())->method('debugOut');
        $client = $this->getMock(KlarnaCheckoutClient::class, ['getOrder']);
        $client->expects($this->once())->method('getOrder')->willThrowException($e);

        $oOrderController = $this->getMock(OrderController::class, ['getKlarnaCheckoutClient']);
        $oOrderController->expects($this->once())->method('getKlarnaCheckoutClient')->willReturn($client);
        $oOrderController->init();
    }

    public function klarnaExternalCheckoutDP()
    {
        return [
            ['bestitamazon', 0, $this->getConfig()->getSslShopUrl().'index.php?cl=KlarnaEpmDispatcher&fnc=amazonLogin'],
            ['oxidpaypal', 1, null],
            ['other', 0, $this->getConfig()->getSslShopUrl().'index.php?cl=KlarnaExpress'],
        ];
    }

    /**
     * @dataProvider klarnaExternalCheckoutDP
     * @param $paymentId
     * @param $dispatcherCallsCount
     * @param $rUrl
     */
    public function testKlarnaExternalCheckout($paymentId, $dispatcherCallsCount, $rUrl)
    {
        $dispatcher = $this->getMock(BaseController::class, ['setExpressCheckout']);
        $dispatcher->expects($this->exactly($dispatcherCallsCount))->method('setExpressCheckout');
        \oxTestModules::addModuleObject(ExpressCheckoutDispatcher::class, $dispatcher);
        $oOrderController = oxNew(OrderController::class);
        $this->setProtectedClassProperty($oOrderController, 'selfUrl', $rUrl);
        $oOrderController->klarnaExternalCheckout($paymentId);
        $this->assertEquals($rUrl, \oxUtilsHelper::$sRedirectUrl);
    }

    public function testIncludeKPWidget()
    {
        $oSession = Registry::getSession();
        $oBasket = oxNew(Basket::class);
        $oBasket->setPayment('other');
        $oSession->setBasket($oBasket);
        $oSession->freeze();
        $oOrderController = oxNew(OrderController::class);
        $this->assertFalse($oOrderController->includeKPWidget());

        $oSession = Registry::getSession();
        $oBasket = oxNew(Basket::class);
        $oBasket->setPayment('klarna_pay_now');
        $oSession->setBasket($oBasket);
        $oSession->freeze();
        $oOrderController = oxNew(OrderController::class);
        $this->assertTrue($oOrderController->includeKPWidget());
    }

    /**
     * @dataProvider undefinedActionDataProvider
     */
    public function testUpdateKlarnaAjaxUndefinedAction($request)
    {
        $mock = $this->createStub(KlarnaOrderController::class, ['getJsonRequest' => $request]);
        $mock->updateKlarnaAjax();
        $expected = [
            "action" => "undefined action",
            "status" => "error",
            "data" => null,
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));
    }

    public function undefinedActionDataProvider()
    {
        return [
            [
                ['test' => 'test'],
            ],
            [
                ['action' => 'test'],
            ],
        ];
    }

    public function testUpdateKlarnaAjaxAddressChange()
    {
        $orderData = [
            'billing_address' => ['street_address' => 'testBilling'],
            'shipping_address' => ['street_address' => 'testShipping'],
        ];

        $user = $this->createStub(
            KlarnaUser::class,
            ['tcklarna_getType' => 2, 'save' => true, 'clearDeliveryAddress' => true, 'updateDeliveryAddress' => true]
        );
        // test client exception
        $mock = $this->getMock(KlarnaOrderController::class, ['getJsonRequest', 'updateKlarnaOrder']);
        $mock->expects($this->any())->method('getJsonRequest')->willReturn(['action' => 'shipping_address_change']);
        $mock->expects($this->any())->method('updateKlarnaOrder')->willThrowException(new KlarnaWrongCredentialsException());

        $this->setProtectedClassProperty($mock, '_aOrderData', $orderData);
        $this->setProtectedClassProperty($mock, '_oUser', $user);
        $this->setProtectedClassProperty($mock, 'forceReloadOnCountryChange', true);

        $mock->updateKlarnaAjax();

        $expected = [
            "action" => "shipping_address_change",
            "status" => null,
            "data" => null,
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));
        $this->assertLoggedException(KlarnaWrongCredentialsException::class, 'KLARNA_UNAUTHORIZED_REQUEST');

        // test success
        $mock = $this->getMock(KlarnaOrderController::class, ['getJsonRequest', 'updateKlarnaOrder']);
        $mock->expects($this->any())->method('getJsonRequest')->willReturn(['action' => 'shipping_address_change']);
        $mock->expects($this->any())->method('updateKlarnaOrder')->willReturn(null);
        $this->setProtectedClassProperty($mock, '_aOrderData', $orderData);
        $this->setProtectedClassProperty($mock, '_oUser', $user);
        $this->setProtectedClassProperty($mock, 'forceReloadOnCountryChange', true);

        $mock->updateKlarnaAjax();

        $expected = [
            "action" => "shipping_address_change",
            "status" => 'changed',
            "data" => null,
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));


        $mock = $this->getMock(KlarnaOrderController::class, ['getJsonRequest', 'updateKlarnaOrder']);
        $mock->expects($this->any())->method('getJsonRequest')->willReturn(['action' => 'shipping_address_change']);
        $mock->expects($this->any())->method('updateKlarnaOrder')->willReturn(null);
        $this->setProtectedClassProperty($mock, '_aOrderData', $orderData);
        $this->setProtectedClassProperty($mock, '_oUser', $user);
        $this->setProtectedClassProperty($mock, 'forceReloadOnCountryChange', true);

        $oBasketMock = $this->getMock(Basket::class, ['getVouchers', 'klarnaValidateVouchers']);
        $oBasketMock->expects( $this->any())->method( 'getVouchers' )->will($this->returnValue(['voucher1']));
        $this->getSession()->setBasket($oBasketMock);

        // test voucher widget update
        $mock->updateKlarnaAjax();

        $expected = [
            "action" => "shipping_address_change",
            "status" => 'changed',
            "data" => null,
        ];
        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));

        $oBasketMock = $this->getMock(Basket::class, ['getVouchers', 'klarnaValidateVouchers']);
        $oBasketMock->expects( $this->at(0) )->method( 'getVouchers' )->will($this->returnValue(['voucher1']));
        $oBasketMock->expects( $this->at(2) )->method( 'getVouchers' )->will($this->returnValue([]));
        $this->getSession()->setBasket($oBasketMock);
        $mock->updateKlarnaAjax();

        $expected = [
            "action" => "shipping_address_change",
            "status" => 'update_voucher_widget',
            "data" => null,
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));

    }

    public function testUpdateKlarnaAjaxShippingOptionChange()
    {
        $mock = $this->createStub(
            KlarnaOrderController::class,
            ['getJsonRequest' => ['action' => 'shipping_option_change']]
        );
        $mock->updateKlarnaAjax();
        $expected = [
            "action" => "shipping_option_change",
            "status" => "error",
            "data" => null,
        ];
        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));

        $mock = $this->getMock(
            KlarnaOrderController::class,
            ['updateKlarnaOrder', 'getJsonRequest']
        );

        $e = $this->getMock(StandardException::class, ['debugOut']);
        $e->expects($this->once())->method('debugOut');
        $mock->expects($this->any())->method('updateKlarnaOrder')->willThrowException($e);
        $mock->expects($this->any())->method('getJsonRequest')->willReturn(
            ['action' => 'shipping_option_change', 'id' => '1']
        );

        $oBasket = $this->createStub(
            KlarnaBasket::class,
            ['getPaymentId' => 'klarna_checkout']
        );
        $this->getSession()->setBasket($oBasket);

        $mock->updateKlarnaAjax();

        $expected = [
            "action" => "shipping_option_change",
            "status" => "changed",
            "data" => [],
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));
    }

    public function testUpdateKlarnaAjaxUpdateSession()
    {
        $oCountry = $this->createStub(Country::class, ['buildSelectString' => true, 'assignRecord' => true]);
        $oCountry->oxcountry__oxisoalpha2 = new Field('test');
        UtilsObject::setClassInstance(Country::class, $oCountry);

        $mock = $this->getMock(
            KlarnaOrderController::class,
            ['updateKlarnaOrder', 'getJsonRequest']
        );

        $e = $this->getMock(StandardException::class, ['debugOut']);
        $e->expects($this->once())->method('debugOut');
        $mock->expects($this->any())->method('updateKlarnaOrder')->willThrowException($e);
        $mock->expects($this->any())->method('getJsonRequest')->willReturn(
            ['action' => 'change', 'country' => 'country']
        );

        $this->setProtectedClassProperty(
            $mock,
            '_aOrderData',
            ['merchant_urls' => ['checkout' => 'url']]
        );

        $mock->updateKlarnaAjax();
        $expected = [
            "action" => "updateSession",
            "status" => "redirect",
            "data" => ['url' => 'url'],
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));
        $this->assertTrue($this->getProtectedClassProperty($mock, 'forceReloadOnCountryChange'));
        $this->assertEquals('test', $this->getSessionParam('sCountryISO'));
    }

    public function testUpdateKlarnaAjaxCheckOrderStatus()
    {
        $user = $this->createStub(KlarnaUser::class, ['setNewsSubscription' => true, 'resolveCountry' => 'DE']);

        //INVALID SUBMIT TEST
        $mock = $this->createStub(KlarnaOrderController::class, ['getJsonRequest' => ['action' => 'checkOrderStatus']]);

        $mock->updateKlarnaAjax();
        $expected = [
            "action" => "checkOrderStatus",
            "status" => "submit",
            "data" => null,
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));


        //VALID SUBMIT TEST
        $oPayment = $this->createStub(
            KlarnaPayment::class,
            ['isSessionValid' => false, 'validateClientToken' => true, 'isAuthorized' => true]
        );
        $oPayment->paymentChanged = true;
        UtilsObject::setClassInstance(KlarnaPayment::class, $oPayment);

        $mock = $this->createStub(
            KlarnaOrderController::class,
            ['getUser' => $user, 'getJsonRequest' => ['action' => 'checkOrderStatus']]
        );

        $this->setModuleConfVar('sKlarnaActiveMode', KlarnaConsts::MODULE_MODE_KP);
        $this->setSessionParam('klarna_session_data', true);
        $this->setSessionParam('sCountryISO', 'EN');
        $this->setSessionParam('reauthorizeRequired', true);

        $oBasket = $this->createStub(
            KlarnaBasket::class,
            ['getPaymentId' => 'klarna_checkout']
        );
        $this->getSession()->setBasket($oBasket);

        $mock->updateKlarnaAjax();

        $this->assertNull($this->getSessionParam('reauthorizeRequired'));
        $this->assertEquals('authorize', $oPayment->getStatus());

        $expected = [
            'action' => "TopConcepts\Klarna\Controller\KlarnaOrderController::checkOrderStatus",
            'status' => "authorize",
            'data' =>
                [
                    'update' =>
                        [
                            'action' => "checkOrderStatus",
                        ],
                    'paymentMethod' => false,
                    'refreshUrl' => null,
                ],
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));


        $this->setModuleConfVar('sKlarnaActiveMode', KlarnaConsts::MODULE_MODE_KP);
        $this->setSessionParam('klarna_session_data', true);

        //INVALID TOKEN TEST
        $oPayment = $this->createStub(
            KlarnaPayment::class,
            ['isSessionValid' => false, 'validateClientToken' => false, 'isAuthorized' => true]
        );
        UtilsObject::setClassInstance(KlarnaPayment::class, $oPayment);

        $mock = $this->createStub(
            KlarnaOrderController::class,
            [
                'resetKlarnaPaymentSession' => true,
                'getUser' => $user,
                'getJsonRequest' => ['action' => 'checkOrderStatus'],
            ]
        );

        $mock->updateKlarnaAjax();

        $expected = [
            'action' => "TopConcepts\Klarna\Controller\KlarnaOrderController::checkOrderStatus",
            'status' => "refresh",
            'data' => ['refreshUrl' => null],
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));

        //NOT AUTHORIZED PAYMENT TEST
        $oPayment = $this->createStub(
            KlarnaPayment::class,
            ['isSessionValid' => false, 'validateClientToken' => true, 'isAuthorized' => false]
        );
        UtilsObject::setClassInstance(KlarnaPayment::class, $oPayment);

        $mock = $this->createStub(
            KlarnaOrderController::class,
            [
                'resetKlarnaPaymentSession' => true,
                'getUser' => $user,
                'getJsonRequest' => ['action' => 'checkOrderStatus'],
            ]
        );

        $mock->updateKlarnaAjax();

        $expected = [
            'action' => "TopConcepts\Klarna\Controller\KlarnaOrderController::checkOrderStatus",
            'status' => "authorize",
            'data' =>
                [
                    'update' =>
                        [
                            'action' => "checkOrderStatus",
                        ],
                    'paymentMethod' => false,
                    'refreshUrl' => null,
                ],
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));

        //REQUIRE FINALIZATION PAYMENT TEST
        $this->setSessionParam('reauthorizeRequired', false);

        $oPayment = $this->createStub(
            KlarnaPayment::class,
            [
                'isSessionValid' => false,
                'validateClientToken' => true,
                'isAuthorized' => true,
                'isOrderStateChanged' => false,
                'isTokenValid' => true,
                'requiresFinalization' => true,
            ]
        );
        UtilsObject::setClassInstance(KlarnaPayment::class, $oPayment);

        $mock = $this->createStub(
            KlarnaOrderController::class,
            [
                'resetKlarnaPaymentSession' => true,
                'getUser' => $user,
                'getJsonRequest' => ['action' => 'checkOrderStatus'],
            ]
        );

        $mock->updateKlarnaAjax();

        $expected = [
            'action' => "TopConcepts\Klarna\Controller\KlarnaOrderController::checkOrderStatus",
            'status' => "finalize",
            'data' =>
                [
                    'update' =>
                        [
                            'action' => "checkOrderStatus",
                        ],
                    'paymentMethod' => false,
                    'refreshUrl' => null,
                ],
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));
    }

    public function testUpdateKlarnaAjaxAddUserData()
    {
        $this->setModuleConfVar('sKlarnaActiveMode', 'test');
        $oBasket = $this->createStub(
            KlarnaBasket::class,
            ['getPaymentId' => 'klarna_checkout']
        );
        $this->getSession()->setBasket($oBasket);
        $this->setSessionParam('sCountryISO', 'EN');
        $user = $this->createStub(KlarnaUser::class, ['resolveCountry' => 'DE']);

        $oPayment = $this->createStub(
            KlarnaPayment::class,
            ['isSessionValid' => false, 'validateClientToken' => false]
        );
        UtilsObject::setClassInstance(KlarnaPayment::class, $oPayment);

        $mock = $this->createStub(
            KlarnaOrderController::class,
            [
                'resetKlarnaPaymentSession' => true,
                'getUser' => $user,
                'getJsonRequest' => ['action' => 'addUserData'],
            ]
        );

        $mock->updateKlarnaAjax();

        $expected = [
            'action' => "TopConcepts\Klarna\Controller\KlarnaOrderController::addUserData",
            'status' => "refresh",
            'data' => ['refreshUrl' => null],
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));

        //VALID CLIENT TOKEN
        $oPayment = $this->createStub(
            KlarnaPayment::class,
            ['isSessionValid' => false, 'validateClientToken' => true]
        );
        UtilsObject::setClassInstance(KlarnaPayment::class, $oPayment);

        $mock = $this->createStub(
            KlarnaOrderController::class,
            [
                'resetKlarnaPaymentSession' => true,
                'getUser' => $user,
                'getJsonRequest' => ['action' => 'addUserData'],
            ]
        );

        $mock->updateKlarnaAjax();

        $expected = [
            'action' => "TopConcepts\Klarna\Controller\KlarnaOrderController::addUserData",
            'status' => "updateUser",
            'data' => ['update' => []],
        ];

        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));

    }

    public function testUpdateKlarnaAjaxPaymentEnabled()
    {
        $mock = $this->createStub(KlarnaOrderController::class, ['getJsonRequest' => ['test' => 'test']]);
        $this->setModuleConfVar('sKlarnaActiveMode', KlarnaConsts::MODULE_MODE_KP);
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        putenv("HTTP_X_REQUESTED_WITH=xmlhttprequest");
        $mock->updateKlarnaAjax();

        $expected = [
            'action' => "resetKlarnaPaymentSession",
            'status' => "redirect",
            'data' =>
                [
                    'url' => Registry::getConfig()->getShopSecureHomeUrl()."cl=basket",
                ],
        ];
        $this->assertEquals($expected, json_decode(\oxUtilsHelper::$response, true));
    }

    /**
     * @throws \ReflectionException
     */
    public function test_createUser()
    {
        $orderData = [
            'billing_address' => [
                'street_address' => 'testBilling',
                'email' => 'test@email.io',
            ],
            'shipping_address' => ['street_address' => 'testShipping'],
            'customer' => ['date_of_birth' => 'test'],
        ];

        $user = $this->createStub(
            KlarnaUser::class,
            [
                'createUser' => true,
                'load' => true,
                'changeUserData' => true,
                'getId' => 'id',
                'updateDeliveryAddress' => true,
            ]
        );

        $class = new \ReflectionClass(KlarnaOrderController::class);
        $method = $class->getMethod('_createUser');
        $method->setAccessible(true);
        $mock = $this->createStub(
            KlarnaOrderController::class,
            ['isRegisterNewUserNeeded' => true, 'sendChangePasswordEmail' => true]
        );
        $this->setProtectedClassProperty($mock, '_oUser', $user);
        $this->setProtectedClassProperty($mock, '_aOrderData', $orderData);

        $result = $method->invoke($mock);

        $this->assertEquals(new Field('test@email.io', Field::T_RAW), $user->oxuser__oxusername);
        $this->assertEquals(new Field(1, Field::T_RAW), $user->oxuser__oxactive);
        $this->assertEquals(new Field('test'), $user->oxuser__oxbirthdate);
        $this->assertEquals('id', $this->getSessionParam('usr'));
        $this->assertTrue($this->getSessionParam('blNeedLogout'));

        $this->assertTrue($result);
    }

    /**
     * @dataProvider initUserDataProvider
     * @param $expected
     * @param $isUserLoggedIn
     * @throws \ReflectionException
     */
    public function test_initUser($expected, $isUserLoggedIn)
    {
        $class = new \ReflectionClass(KlarnaOrderController::class);
        $method = $class->getMethod('_initUser');
        $method->setAccessible(true);

        $viewConfig = $this->createStub(ViewConfig::class, ['isUserLoggedIn' => $isUserLoggedIn]);
        $user = $this->getMock(User::class, ['getKlarnaData']);
        $user->expects($this->any())->method('getKlarnaData')->willReturn(['test']);
        $mock = $this->createStub(KlarnaOrderController::class, ['getUser' => $user, 'getViewConfig' => $viewConfig]);
        $method->invoke($mock);

        $this->assertEquals($expected, $mock->getUser()->getType());
    }

    public function initUserDataProvider()
    {
        return [
            [KlarnaUser::LOGGED_IN, true],
            [KlarnaUser::NOT_REGISTERED, false],
        ];

    }
}
