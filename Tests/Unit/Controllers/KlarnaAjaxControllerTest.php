<?php

namespace TopConcepts\Klarna\Tests\Unit\Controllers;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use TopConcepts\Klarna\Controllers\KlarnaAjaxController;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Exception\KlarnaClientException;
use TopConcepts\Klarna\Models\KlarnaBasket;
use TopConcepts\Klarna\Models\KlarnaUser;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaAjaxControllerTest extends ModuleUnitTestCase
{

    public function testInit()
    {
        $ajaxController = $this->createStub(KlarnaAjaxController::class, ['getKlarnaCheckoutClient' => null]);
        $ajaxController->init();
        $this->assertEquals('Invalid payment ID', \oxUtilsHelper::$response);

        $oBasket = $this->createStub(KlarnaBasket::class, ['getPaymentId' => 'klarna_checkout']);
        $session = Registry::getSession();
        $session->setBasket($oBasket);

        $client = $this->getMock(KlarnaCheckoutClient::class, ['getOrder']);
        $client->expects($this->any())->method('getOrder')->willThrowException(new KlarnaClientException('test', 404));
        $ajaxController = $this->createStub(KlarnaAjaxController::class, ['getKlarnaCheckoutClient' => $client]);
        $result = $ajaxController->init();
        $expected = '{"action":"init","status":"restart needed","data":null}';
        $this->assertEquals($expected, $result);
        $this->assertNull($this->getProtectedClassProperty($ajaxController, '_aOrderData'));
        $oOrder = ['test1', 'test2'];
        $client = $this->createStub(KlarnaCheckoutClient::class, ['getOrder' => $oOrder]);
        $ajaxController = $this->createStub(KlarnaAjaxController::class, ['getKlarnaCheckoutClient' => $client]);
        $ajaxController->init();
        $result = $this->getProtectedClassProperty($ajaxController, '_aOrderData');

        $this->assertEquals($oOrder, $result);


    }

    public function testRender()
    {
        $e = $this->getMock(StandardException::class, ['debugOut']);
        $e->expects($this->once())->method('debugOut');

        $ajaxController = $this->getMock(KlarnaAjaxController::class, ['getKlarnaCheckoutClient', 'updateKlarnaOrder']);
        $ajaxController->expects($this->any())->method('updateKlarnaOrder')->willThrowException($e);
        $ajaxController->render();

        $oOrder = $this->createStub(Order::class, []);
        \oxTestModules::addModuleObject(Order::class, $oOrder);
        $user = $this->createStub(KlarnaUser::class, []);
        $client = $this->createStub(KlarnaCheckoutClient::class, ['createOrUpdateOrder' => []]);
        $ajaxController = $this->createStub(KlarnaAjaxController::class, ['getKlarnaCheckoutClient' => $client]);
        $this->setProtectedClassProperty($ajaxController, '_oUser', $user);
        $basket = $this->createStub(KlarnaBasket::class, ['getPaymentId' => 'klarna_checkout']);
        $session = Registry::getSession();
        $session->setBasket($basket);
        $ajaxController->render();
        $this->assertEquals($basket, $session->getBasket());

        $ajaxController = new KlarnaAjaxController();
        $this->setProtectedClassProperty($ajaxController, '_aErrors', ['test']);
        $result = $ajaxController->render();
        $this->assertNull($result);


    }

    public function testGetKlarnaCheckoutClient()
    {
        $ajaxController = new KlarnaAjaxController();
        $result = $ajaxController->getKlarnaCheckoutClient();
        $this->assertInstanceOf(KlarnaCheckoutClient::class, $result);
    }

    /**
     * @dataProvider vouchersdataProvider
     * @param $method
     * @throws \oxSystemComponentException
     */
    public function testVouchers($method)
    {
        $this->setRequestParameter('voucherNr', '1');
        $this->setRequestParameter('voucherId', '1');
        $ajaxController = new KlarnaAjaxController();
        $ajaxController->$method();
        $result = $ajaxController->getViewData()['aIncludes'];
        $expected = [
            'vouchers' => "kl_klarna_checkout_voucher_data.tpl",
            'error' => "kl_klarna_checkout_voucher_errors.tpl",
        ];

        $this->assertEquals($expected, $result);

    }

    public function vouchersdataProvider()
    {
        return [
            ['addVoucher'],
            ['removeVoucher'],
        ];
    }

    public function testSetKlarnaDeliveryAddress()
    {
        $this->setRequestParameter('klarna_address_id', '1');
        $ajaxController = new KlarnaAjaxController();
        $ajaxController->setKlarnaDeliveryAddress();

        $deladrid = $this->getSessionParam('deladrid');
        $this->assertEquals($deladrid, '1');
        $blshowshipaddress = $this->getSessionParam('blshowshipaddress');
        $this->assertEquals($blshowshipaddress, 1);

        $orderId = $this->getSessionParam('klarna_checkout_order_id');
        $this->assertNull($orderId);
    }

    public function test_initUser()
    {

        $user = $this->createStub(KlarnaUser::class, ['getKlarnaData' => true]);
        $ajaxController = $this->createStub(KlarnaAjaxController::class, ['getUser' => $user]);
        $ajaxController->_initUser();
        $result = $this->getProtectedClassProperty($user, '_type');
        $this->assertEquals(2, $result);

        $viewConfig = $this->createStub(ViewConfig::class, ['isUserLoggedIn' => true]);
        $ajaxController = $this->createStub(
            KlarnaAjaxController::class,
            ['getUser' => $user, 'getViewConfig' => $viewConfig]
        );
        $ajaxController->_initUser();
        $result = $this->getProtectedClassProperty($user, '_type');
        $this->assertEquals(3, $result);

    }

    public function testUpdateUserObject()
    {
        $methodReflection = new \ReflectionMethod(KlarnaAjaxController::class, 'updateUserObject');
        $methodReflection->setAccessible(true);

        $user = $this->createStub(
            KlarnaUser::class,
            ['kl_getType' => 3, 'updateDeliveryAddress' => true, 'save' => true, 'assign' => true]
        );
        $user->oxuser__oxbirthdate = 'test';
        $order = [
            'customer' => ['date_of_birth' => 'test'],
            'billing_address' => ['street_address' => 'street address 1'],
            'shipping_address' => ['street_address' => 'street address 2'],
        ];
        $ajaxController = $this->createStub(KlarnaAjaxController::class, ['updateKlarnaOrder' => true]);
        $this->setProtectedClassProperty($ajaxController, '_aOrderData', $order);
        $this->setProtectedClassProperty($ajaxController, '_oUser', $user);

        $methodReflection->invoke($ajaxController);

        $result = $this->getProtectedClassProperty($ajaxController, '_oUser');
        $expected = new Field('test');
        $this->assertEquals($expected, $result->oxuser__oxbirthdate);
    }
}
