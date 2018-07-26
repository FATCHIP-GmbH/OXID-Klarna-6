<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrders;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\Exception\KlarnaCaptureNotAllowedException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Model\KlarnaOrder;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaOrdersTest extends ModuleUnitTestCase
{
    /**
     * @param int $oxstorno
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function setOrder($oxstorno = 0)
    {
        $order = $this->getMock(
            Order::class,
            ['load', 'save', 'getTotalOrderSum', 'getNewOrderLinesAndTotals', 'updateKlarnaOrder', 'captureKlarnaOrder']
        );
        $order->expects($this->any())->method('load')->willReturn(true);
        $order->expects($this->any())->method('save')->willReturn(true);
        $order->expects($this->any())->method('getTotalOrderSum')->willReturn(100);
        $order->expects($this->any())->method('getNewOrderLinesAndTotals')->willReturn(['order_lines' => true]);
        $order->expects($this->any())->method('updateKlarnaOrder')->willReturn('test');
        $order->expects($this->any())->method('captureKlarnaOrder')->willReturn(true);
        $order->oxorder__oxstorno            = new Field($oxstorno, Field::T_RAW);
        $order->oxorder__oxpaymenttype       = new Field('klarna_checkout', Field::T_RAW);
        $order->oxorder__tcklarna_merchantid = new Field('smid', Field::T_RAW);
        $order->oxorder_oxbillcountryid      = new Field('a7c40f631fc920687.20179984', Field::T_RAW);
        UtilsObject::setClassInstance(Order::class, $order);

        return $order;
    }

    /**
     * @dataProvider exceptionDataProvider
     * @param $exception
     * @param $expected
     */
    public function testRenderExceptions($exception, $expected)
    {
        $this->setOrder();
        $controller = $this->getMock(
            KlarnaOrders::class,
            ['_authorize', 'getEditObjectId', 'retrieveKlarnaOrder', 'isCredentialsValid']
        );
        $controller->expects($this->any())->method('_authorize')->willReturn(true);
        $controller->expects($this->any())->method('isCredentialsValid')->willReturn(true);
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->any())->method('retrieveKlarnaOrder')->will($this->throwException($exception));

        $controller->render();
        $result = $controller->getViewData()['unauthorizedRequest'];


        if ($expected == 'test') {
            $result = unserialize($this->getSessionParam('Errors')['default'][0]);
            $this->assertInstanceOf(ExceptionToDisplay::class, $result);

            $result = $result->getOxMessage();
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function exceptionDataProvider()
    {
        return [
            [
                new KlarnaWrongCredentialsException(),
                'Unerlaubte Anfrage. Prüfen Sie die Einstellungen des Klarna Moduls und die Merchant ID sowie das zugehörige Passwort',
            ],
            [
                new KlarnaOrderNotFoundException(),
                'Diese Bestellung konnte bei Klarna im System nicht gefunden werden. Änderungen an den Bestelldaten werden daher nicht an Klarna übertragen.',
            ],
            [
                new KlarnaCaptureNotAllowedException(),
                'Diese Bestellung konnte bei Klarna im System nicht gefunden werden. Änderungen an den Bestelldaten werden daher nicht an Klarna übertragen.',
            ],
            [new StandardException('test'), 'test'],
        ];
    }

    /**
     *
     */
    public function testRender()
    {
        $this->setOrder();

        $orderMain = $this->createStub(KlarnaOrders::class, ['isKlarnaOrder' => false, 'getEditObjectId' => 'test']);
        $orderMain->render();
        $warningMessage = $orderMain->getViewData()['sMessage'];
        $this->assertEquals($warningMessage, 'KLARNA_ONLY_FOR_KLARNA_PAYMENT');

        $orderMain = $this->createStub(KlarnaOrders::class, ['isKlarnaOrder' => true, 'getEditObjectId' => 'test']);
        $result    = $orderMain->render();

        $this->assertEquals('tcklarna_orders.tpl', $result);

        $warningMessage = $orderMain->getViewData()['wrongCredentials'];
        $this->assertEquals($warningMessage, 'KLARNA_MID_CHANGED_FOR_COUNTRY');

        $order     = $this->setOrder(1);
        $orderData = [
            'order_amount' => 10000,
            'status'       => 'CANCELLED',
            'refunds'      => 'refunds',
            'captures'     => 'test',
        ];
        $orderMain = $this->createStub(
            KlarnaOrders::class,
            [
                'isKlarnaOrder'       => true,
                'getEditObjectId'     => 'test',
                'isCredentialsValid'  => true,
                'retrieveKlarnaOrder' => $orderData,
            ]
        );
        $orderMain->render();

        $viewData = $orderMain->getViewData();
        $this->assertTrue($viewData['cancelled']);
        $this->assertTrue($viewData['inSync']);
        $this->assertEquals(1, $order->oxorder__tcklarna_sync->value);
        $this->assertEquals($viewData['aRefunds'], 'refunds');
        $this->assertEquals($viewData['sKlarnaRef'], ' - ');
        $this->assertEmpty($viewData['aCaptures']);

        $order     = $this->setOrder();
        $orderMain = $this->createStub(
            KlarnaOrders::class,
            [
                'isKlarnaOrder'       => true,
                'getEditObjectId'     => 'test',
                'isCredentialsValid'  => true,
                'retrieveKlarnaOrder' => ['status' => 'CANCELLED'],
            ]
        );
        $orderMain->render();
        $this->assertEquals(0, $order->oxorder__tcklarna_sync->value);
    }

    /**
     *
     */
    public function testIsCredentialsValid()
    {
        $controller = $this->createStub(KlarnaOrders::class, ['getEditObjectId' => 'test']);
        $result     = $controller->isCredentialsValid('DE');
        $this->assertFalse($result);

        $this->setOrder();
        $this->setModuleConfVar('aKlarnaCreds_DE', '');
        $this->setModuleConfVar('sKlarnaMerchantId', 'smid');
        $this->setModuleConfVar('sKlarnaPassword', 'psw');
        $controller = $this->createStub(KlarnaOrders::class, ['getEditObjectId' => 'test']);

        $result = $controller->isCredentialsValid('DE');
        $this->assertTrue($result);
    }

    /**
     *
     */
    public function testGetKlarnaPortalLink()
    {
        $order                               = $this->setOrder();
        $order->oxorder__tcklarna_servermode = new Field('playground', Field::T_RAW);
        $order->oxorder__tcklarna_orderid    = new Field('id', Field::T_RAW);
        $expected                            = sprintf(KlarnaOrders::KLARNA_PORTAL_PLAYGROUND_URL, 'smid', 'id');
        $controller                          = $this->createStub(KlarnaOrders::class, ['getEditObjectId' => 'test']);
        $result                              = $controller->getKlarnaPortalLink();
        $this->assertEquals($expected, $result);

        $order->oxorder__tcklarna_servermode = new Field('test', Field::T_RAW);
        $expected                            = sprintf(KlarnaOrders::KLARNA_PORTAL_LIVE_URL, 'smid', 'id');
        $result                              = $controller->getKlarnaPortalLink();
        $this->assertEquals($expected, $result);
    }

    /**
     *
     */
    public function testFormatPrice()
    {
        $order                      = $this->setOrder();
        $order->oxorder__oxcurrency = new Field('€', Field::T_RAW);
        $controller                 = $this->createStub(KlarnaOrders::class, ['getEditObjectId' => 'test']);
        $result                     = $controller->formatPrice(100);
        $this->assertEquals("1,00 €", $result);

    }

    /**
     *
     */
    public function testRetrieveKlarnaOrder()
    {
        $this->setOrder();
        $controller = $this->createStub(KlarnaOrders::class, ['getEditObjectId' => 'test']);
        $this->setExpectedException(KlarnaWrongCredentialsException::class, 'KLARNA_UNAUTHORIZED_REQUEST');
        $controller->retrieveKlarnaOrder();
    }

    /**
     *
     */
    public function testFormatCaptures()
    {
        $controller = $this->createStub(KlarnaOrders::class, ['getEditObjectId' => 'test']);
        $result     = $controller->formatCaptures([['captured_at' => '2018']]);

        $this->assertArrayHasKey('captured_at', $result[0]);
    }

    /**
     *
     */
    public function testRefundOrderAmount()
    {
        $this->setOrder();

        $client = $this->createStub(KlarnaOrderManagementClient::class, ['createOrderRefund' => 'test']);

        $controller = $this->createStub(KlarnaOrders::class, ['getEditObjectId' => 'test', 'getKlarnaMgmtClient' => $client]);
        $this->setProtectedClassProperty($controller, 'client', $client);
        $result = $controller->refundOrderAmount(100);

        $this->assertEquals('test', $result);
        $client->expects($this->any())->method('createOrderRefund')->will($this->throwException(new \Exception('testException')));
        $this->setProtectedClassProperty($controller, 'client', $client);
        $result = $controller->refundOrderAmount(100);

        $this->assertNull($result);
    }

    /**
     *
     */
    public function testCaptureFullOrder()
    {
        $order                            = $this->setOrder();
        $order->oxorder__tcklarna_orderid = new Field('id', Field::T_RAW);
        $controller                       = $this->createStub(KlarnaOrders::class, ['getEditObjectId' => 'test']);
        $controller->captureFullOrder();
        $this->assertEquals(new Field(1), $order->oxorder__tcklarna_sync);

        $order->expects($this->any())->method('captureKlarnaOrder')->willThrowException(new StandardException('test'));
        $controller = $this->createStub(KlarnaOrders::class, ['getEditObjectId' => 'test']);
        $controller->captureFullOrder();

        $result = unserialize($this->getSessionParam('Errors')['default'][0]);
        $this->assertInstanceOf(DisplayError::class, $result);

        $result = $result->getOxMessage();

        $this->assertEquals('test', $result);
    }

    /**
     * @dataProvider testCancelOrderDataProvider
     */
    public function testCancelOrder($data, $expectedResult)
    {
        $cancelKlarnaOrder = $data['cancelKlarnaOrder'];
        $oOrder            = $this->getMock(KlarnaOrder::class, [
                'getId',
                'isLoaded',
                'isKlarnaOrder',
                'getFieldData',
                'cancelKlarnaOrder',
                'save',
            ]
        );
        $methods           = [
            'getId'         => 'test',
            'isLoaded'      => $data['isLoaded'],
            'isKlarnaOrder' => $data['isKlarnaOrder'],
            'getFieldData'  => $data['getFieldData'],
            'save'          => true,
        ];
        foreach ($methods as $method => $return) {
            $oOrder->expects($this->any())->method($method)->willReturn($return);
        }
        if ($cancelKlarnaOrder === 'test' || $cancelKlarnaOrder === 'Order is canceled.') {
            $oOrder->expects($this->any())->method('cancelKlarnaOrder')->willThrowException(new StandardException($cancelKlarnaOrder));
        } else {
            $oOrder->expects($this->any())->method('cancelKlarnaOrder')->willReturn($cancelKlarnaOrder);
        }

        $controller = $this->createStub(KlarnaOrders::class, [
            'getEditObject' => $oOrder,
            'resetCache'    => true,
        ]);

        $controller->cancelOrder();
        $result = $this->getSession()->getVariable($oOrder->getId().'orderCancel');
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function testCancelOrderDataProvider()
    {
        return [
            [
                ['isLoaded' => true, 'isKlarnaOrder' => true, 'getFieldData' => false, 'cancelKlarnaOrder' => true],
                true,
            ],
            [
                ['isLoaded' => false, 'isKlarnaOrder' => true, 'getFieldData' => false, 'cancelKlarnaOrder' => true],
                false,
            ],
            [
                ['isLoaded' => true, 'isKlarnaOrder' => true, 'getFieldData' => false, 'cancelKlarnaOrder' => 'test'],
                false,
            ],
            [
                ['isLoaded' => true, 'isKlarnaOrder' => true, 'getFieldData' => false, 'cancelKlarnaOrder' => 'Order is canceled.'],
                true,
            ],
        ];
    }

    /**
     * @dataProvider testIsOrderCancellationInSyncDataProvider
     * @param $oxstorno
     * @param $expectedResult
     */
    public function testIsOrderCancellationInSync($oxstorno, $expectedResult)
    {
        $Order      = $this->createStub(KlarnaOrder::class, ['getFieldData' => $oxstorno]);
        $controller = $this->createStub(KlarnaOrders::class, [
                'getViewDataElement' => 'asdf',
                'getEditObject'      => $Order]
        );

        $result = $controller->isOrderCancellationInSync();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function testIsOrderCancellationInSyncDataProvider()
    {
        return [
            [1, false],
            [0, true],
        ];
    }
}
