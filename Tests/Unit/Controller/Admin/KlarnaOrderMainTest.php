<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrderMain;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaOrderMainTest extends ModuleUnitTestCase
{

    public function testInit()
    {
        $order = $this->setOrder();

        $controller = $this->createStub(
            KlarnaOrderMain::class,
            [
                '_authorize'         => true,
                'getEditObjectId'    => 'test',
                'isCredentialsValid' => false,
            ]
        );
        $controller->init();
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);

        $controller = $this->createStub(
            KlarnaOrderMain::class,
            [
                '_authorize'          => true,
                'getEditObjectId'     => 'test',
                'isCredentialsValid'  => true,
                'retrieveKlarnaOrder' => ['status' => 'CANCEL'],
            ]
        );
        $controller->init();
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);

        $orderData  = [
            'order_amount'                => 10000,
            'remaining_authorized_amount' => 10000,
            'status'                      => 'AUTHORIZED',
        ];
        $controller = $this->createStub(
            KlarnaOrderMain::class,
            [
                '_authorize'          => true,
                'getEditObjectId'     => 'test',
                'isCredentialsValid'  => true,
                'retrieveKlarnaOrder' => $orderData,
            ]
        );

        $controller->init();

        $this->assertEquals(new Field(1), $order->oxorder__tcklarna_sync);

    }

    /**
     * @dataProvider exceptionDataProvider
     * @param $exception
     * @param $expected
     */
    public function testInitExceptions($exception, $expected)
    {
        $this->setOrder();
        $controller = $this->getMock(
            KlarnaOrderMain::class,
            ['_authorize', 'getEditObjectId', 'retrieveKlarnaOrder', 'isCredentialsValid']
        );
        $controller->expects($this->any())->method('_authorize')->willReturn(true);
        $controller->expects($this->any())->method('isCredentialsValid')->willReturn(true);
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->any())->method('retrieveKlarnaOrder')->will($this->throwException($exception));

        $controller->init();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals($expected, $result);
    }

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
            [new StandardException('test'), 'test'],

        ];
    }

    protected function setOrder($oxstorno = 0)
    {
        $order = $this->getMock(
            Order::class,
            ['load', 'save', 'getTotalOrderSum', 'getNewOrderLinesAndTotals', 'updateKlarnaOrder', 'captureKlarnaOrder']
        );
        $order->expects($this->any())->method('load')->willReturn(true);
        $order->expects($this->any())->method('save')->willReturn(true);
        $order->expects($this->any())->method('getTotalOrderSum')->willReturn(100);
        $order->expects($this->any())->method('getNewOrderLinesAndTotals')->willReturn(true);
        $order->expects($this->any())->method('updateKlarnaOrder')->willReturn('test');
        $order->expects($this->any())->method('captureKlarnaOrder')->willReturn(true);
        $order->oxorder__oxstorno       = new Field($oxstorno, Field::T_RAW);
        $order->oxorder__oxpaymenttype  = new Field('klarna_checkout', Field::T_RAW);
        $order->oxorder__tcklarna_merchantid   = new Field('smid', Field::T_RAW);
        $order->oxorder_oxbillcountryid = new Field('a7c40f631fc920687.20179984', Field::T_RAW);
        UtilsObject::setClassInstance(Order::class, $order);

        return $order;
    }

    public function testRender()
    {
        $order     = $this->setOrder();
        $orderMain = $this->createStub(KlarnaOrderMain::class, ['isKlarnaOrder' => true]);
        $result    = $orderMain->render();

        $this->assertEquals('order_main.tpl', $result);

        $warningMessage = $orderMain->getViewDataElement('sWarningMessage');
        $this->assertEquals($warningMessage, 'KLARNA_MID_CHANGED_FOR_COUNTRY');

        $this->setRequestParameter('fnc', false);
        $orderMain = $this->createStub(KlarnaOrderMain::class, ['isKlarnaOrder' => true, 'isCredentialsValid' => true]);
        $this->setProtectedClassProperty($orderMain, 'klarnaOrderData', ['status' => 'CANCELLED']);
        $orderMain->render();

        $warningMessage = $orderMain->getViewDataElement('sWarningMessage');
        $this->assertEquals("Die Bestellung wurde storniert. TCKLARNA_NO_REQUESTS_WILL_BE_SENT", $warningMessage);
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);

        $orderMain = $this->createStub(KlarnaOrderMain::class, ['isKlarnaOrder' => true, 'isCredentialsValid' => true]);
        $this->setProtectedClassProperty($orderMain, 'klarnaOrderData', ['order_amount' => 1]);
        $orderMain->render();

        $warningMessage = $orderMain->getViewDataElement('sWarningMessage');

        $this->assertEquals(
            $warningMessage,
            "<strong>Achtung!</strong> Die Daten dieser Bestellung weichen von den Daten ab, die bei Klarna gespeichert sind. TCKLARNA_NO_REQUESTS_WILL_BE_SENT"
        );
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);

        $orderMain = $this->createStub(
            KlarnaOrderMain::class,
            ['isKlarnaOrder' => true, 'isCredentialsValid' => true, 'isCaptureInSync' => true]
        );
        $this->setProtectedClassProperty($orderMain, 'klarnaOrderData', ['order_amount' => 10000]);
        $orderMain->render();

        $this->assertEquals(new Field(1), $order->oxorder__tcklarna_sync);

    }

    /**
     * @dataProvider exceptionDataProvider
     * @param $exception
     * @param $expected
     */
    public function testRenderExceptions($exception, $expected)
    {
        $this->setOrder();
        $this->setRequestParameter('fnc', 'test');
        $controller = $this->getMock(
            KlarnaOrderMain::class,
            ['isKlarnaOrder', 'isCredentialsValid', 'retrieveKlarnaOrder']
        );
        $controller->expects($this->any())->method('isKlarnaOrder')->willReturn(true);
        $controller->expects($this->any())->method('isCredentialsValid')->willReturn(true);
        $controller->expects($this->any())->method('retrieveKlarnaOrder')->will($this->throwException($exception));

        $controller->render();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals($expected, $result);
    }


    public function testSendorder()
    {
        $this->setOrder(1);
        $controller = $this->createStub(KlarnaOrderMain::class, ['getEditObjectId' => 'test']);
        $controller->sendOrder();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals('TCKLARNA_CAPUTRE_FAIL_ORDER_CANCELLED', $result);


        $order                  = $this->setOrder(0);
        $order->oxorder__tcklarna_sync = new Field(1, Field::T_RAW);
        $controller             = $this->createStub(
            KlarnaOrderMain::class,
            ['getEditObjectId' => 'test', 'retrieveKlarnaOrder' => 'test']
        );
        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['remaining_authorized_amount' => 1]);

        $controller->sendorder();
        $klarnaOrder = $this->getProtectedClassProperty($controller, 'klarnaOrderData');
        $result      = $controller->getViewDataElement('sMessage');
        $error      = $controller->getViewDataElement('sErrorMessage');

        $this->assertEmpty($error);
        $this->assertEquals('test', $klarnaOrder);
        $this->assertEquals('KLARNA_CAPTURE_SUCCESSFULL', $result);


        $order                  = $this->setOrder();
        $order->oxorder__tcklarna_sync = new Field(1, Field::T_RAW);
        $order->expects($this->once())->method('captureKlarnaOrder')->will($this->throwException(new StandardException('test')));
        $controller = $this->createStub(
            KlarnaOrderMain::class,
            ['getEditObjectId' => 'test', 'retrieveKlarnaOrder' => 'test']
        );
        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['remaining_authorized_amount' => 1]);
        $controller->sendOrder();

        $error = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals('test', $error);
    }

    public function testIsCredentialsValid()
    {
        $controller = $this->createStub(KlarnaOrderMain::class, ['getEditObjectId' => 'test']);
        $result     = $controller->isCredentialsValid();
        $this->assertFalse($result);

        $this->setOrder();
        $this->setModuleConfVar('aKlarnaCreds_DE', '');
        $this->setModuleConfVar('sKlarnaMerchantId', 'smid');
        $this->setModuleConfVar('sKlarnaPassword', 'psw');
        $controller = $this->createStub(KlarnaOrderMain::class, ['getEditObjectId' => 'test']);

        $result = $controller->isCredentialsValid();
        $this->assertTrue($result);
    }

    /**
     * @dataProvider captureInSyncDataProvider
     * @param $klarnaOrderData
     * @param $expected
     * @param bool $withOrder
     */
    public function testIsCaptureInSync($klarnaOrderData, $expected, $withOrder = false)
    {

        $controller = $this->createStub(KlarnaOrderMain::class, ['getEditObjectId' => 'test']);
        if ($withOrder) {
            $order                      = $this->setOrder();
            $order->oxorder__oxsenddate = new Field('-', Field::T_RAW);
            UtilsObject::setClassInstance(Order::class, $order);
        }

        $result = $controller->isCaptureInSync($klarnaOrderData);
        $this->assertEquals($expected, $result);
    }

    public function captureInSyncDataProvider()
    {
        $klarnaOrderData1['status'] = 'TEST';
        $klarnaOrderData2['status'] = 'PART_CAPTURED';
        $klarnaOrderData3['status'] = 'AUTHORIZED';

        return [
            [$klarnaOrderData1, true],
            [$klarnaOrderData2, true],
            [$klarnaOrderData2, false, true],
            [$klarnaOrderData3, true],

        ];
    }

    public function testSave()
    {
        $order                      = $this->setOrder();
        $order->oxorder__oxdiscount = new Field(10, Field::T_RAW);
        $order->oxorder__oxordernr  = new Field(1, Field::T_RAW);
        $order->oxorder__tcklarna_sync     = new Field(1, Field::T_RAW);
        $order->oxorder__tcklarna_orderid  = new Field('1', Field::T_RAW);

        $client = $this->createStub(KlarnaOrderManagementClient::class, ['sendOxidOrderNr' => true]);

        $this->setRequestParameter('editval', 11);
        $controller = $this->createStub(KlarnaOrderMain::class, [
            'getEditObjectId'     => 'test',
            'isKlarnaOrder'       => true,
            'getKlarnaMgmtClient' => $client,
        ]);
        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['captured_amount' => 1]);
        $controller->save();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals('TCKLARNA_ORDER_UPDATE_CANT_BE_SENT_TO_KLARNA', $result);


        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['captured_amount' => 0]);
        $this->setProtectedClassProperty($controller, 'client', $client);
        $controller->save();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals('test', $result);

        $controller = $this->createStub(
            KlarnaOrderMain::class,
            ['getEditObjectId' => 'test', 'isKlarnaOrder' => true, 'discountChanged' => false]
        );
        $client     = $this->getMock(KlarnaOrderManagementClient::class, ['sendOxidOrderNr', 'addShippingToCapture']);
        $client->expects($this->any())->method('sendOxidOrderNr')->willReturn(true);
        $client->expects($this->any())->method('addShippingToCapture')->will(
            $this->throwException(new StandardException())
        );
        $this->setProtectedClassProperty($controller, 'client', $client);
        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['captured_amount' => 1]);
        $this->setRequestParameter('editval', ['oxorder__oxtrackcode' => 1]);
        $controller->save();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals('TCKLARNA_ORDER_UPDATE_CANT_BE_SENT_TO_KLARNA', $result);
    }

    public function testRetrieveKlarnaOrder()
    {
        $this->setOrder();
        $controller = $this->createStub(KlarnaOrderMain::class, ['getEditObjectId' => 'test']);
        $this->setExpectedException(KlarnaWrongCredentialsException::class, 'KLARNA_UNAUTHORIZED_REQUEST');
        $controller->retrieveKlarnaOrder();
    }
}