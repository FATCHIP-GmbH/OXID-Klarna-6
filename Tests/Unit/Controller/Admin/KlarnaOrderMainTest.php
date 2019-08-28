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

class KlarnaOrderMainTest extends ModuleUnitTestCase {

    public function testInit() {
        $order = $this->setOrder();

        $controller = $this->getMockBuilder(KlarnaOrderMain::class)
            ->setMethods(['_authorize', 'getEditObjectId', 'isCredentialsValid'])->getMock();
        $controller->expects($this->once())->method('_authorize')->willReturn(true);
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->once())->method('isCredentialsValid')->willReturn(false);

        $controller->init();
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);

        $controller = $this->getMockBuilder(KlarnaOrderMain::class)
            ->setMethods(['_authorize', 'getEditObjectId', 'isCredentialsValid', 'retrieveKlarnaOrder'])->getMock();
        $controller->expects($this->once())->method('_authorize')->willReturn(true);
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->once())->method('isCredentialsValid')->willReturn(true);
        $controller->expects($this->once())->method('retrieveKlarnaOrder')->willReturn(['status' => 'CANCEL']);
        $controller->init();
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);

        $orderData = [
            'order_amount'                => 10000,
            'remaining_authorized_amount' => 10000,
            'status'                      => 'AUTHORIZED',
        ];
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)
            ->setMethods(['_authorize', 'getEditObjectId', 'isCredentialsValid', 'retrieveKlarnaOrder'])->getMock();
        $controller->expects($this->once())->method('_authorize')->willReturn(true);
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->once())->method('isCredentialsValid')->willReturn(true);
        $controller->expects($this->once())->method('retrieveKlarnaOrder')->willReturn($orderData);

        $controller->init();

        $this->assertEquals(new Field(1), $order->oxorder__tcklarna_sync);

    }

    /**
     * @dataProvider exceptionDataProvider
     * @param $exception
     * @param $expected
     */
    public function testInitExceptions($exception, $expected) {
        $this->setOrder();
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)
            ->setMethods(['_authorize', 'getEditObjectId', 'retrieveKlarnaOrder', 'isCredentialsValid'])->getMock();
        $controller->expects($this->any())->method('_authorize')->willReturn(true);
        $controller->expects($this->any())->method('isCredentialsValid')->willReturn(true);
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->any())->method('retrieveKlarnaOrder')->will($this->throwException($exception));

        $controller->init();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals($expected, $result);
    }

    public function exceptionDataProvider() {
        return [
            [
                new KlarnaWrongCredentialsException(),
                'KLARNA_UNAUTHORIZED_REQUEST',
            ],
            [
                new KlarnaOrderNotFoundException(),
                'KLARNA_ORDER_NOT_FOUND',
            ],
            [new StandardException('test'), 'test'],

        ];
    }

    protected function setOrder($oxstorno = 0) {
        $order = $this->getMockBuilder(Order::class)
            ->setMethods(['load', 'save', 'getTotalOrderSum', 'getNewOrderLinesAndTotals', 'updateKlarnaOrder', 'captureKlarnaOrder'])
            ->getMock();
        $order->expects($this->any())->method('load')->willReturn(true);
        $order->expects($this->any())->method('save')->willReturn(true);
        $order->expects($this->any())->method('getTotalOrderSum')->willReturn(100);
        $order->expects($this->any())->method('getNewOrderLinesAndTotals')->willReturn(true);
        $order->expects($this->any())->method('updateKlarnaOrder')->willReturn('test');
        $order->expects($this->any())->method('captureKlarnaOrder')->willReturn(true);
        $order->oxorder__oxstorno = new Field($oxstorno, Field::T_RAW);
        $order->oxorder__oxpaymenttype = new Field('klarna_checkout', Field::T_RAW);
        $order->oxorder__tcklarna_merchantid = new Field('smid', Field::T_RAW);
        $order->oxorder_oxbillcountryid = new Field('a7c40f631fc920687.20179984', Field::T_RAW);
        UtilsObject::setClassInstance(Order::class, $order);

        return $order;
    }

    public function testRender() {
        $order = $this->setOrder();
        $orderMain = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['isKlarnaOrder'])->getMock();
        $orderMain->expects($this->once())->method('isKlarnaOrder')->willReturn(true);
        $result = $orderMain->render();
        $this->assertEquals('order_main.tpl', $result);
        $warningMessage = $orderMain->getViewDataElement('sWarningMessage');
        $this->assertEquals($warningMessage, '<strong>Wrong credentials!</strong> This order has been placed using <strong>smid</strong> merchant id. Currently configured merchant id for <strong></strong> is <strong></strong>.');

        $this->setRequestParameter('fnc', false);
        $orderMain = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['isKlarnaOrder', 'isCredentialsValid'])->getMock();
        $orderMain->expects($this->once())->method('isKlarnaOrder')->willReturn(true);
        $orderMain->expects($this->once())->method('isCredentialsValid')->willReturn(true);
        $this->setProtectedClassProperty($orderMain, 'klarnaOrderData', ['status' => 'CANCELLED']);
        $orderMain->render();
        $warningMessage = $orderMain->getViewDataElement('sWarningMessage');
        $this->assertEquals("Die Bestellung wurde storniert. Ihre Änderungen an dieser Bestellung werden nicht an Klarna übertragen.", $warningMessage);
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);

        $orderMain = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['isKlarnaOrder', 'isCredentialsValid'])->getMock();
        $orderMain->expects($this->once())->method('isKlarnaOrder')->willReturn(true);
        $orderMain->expects($this->once())->method('isCredentialsValid')->willReturn(true);
        $this->setProtectedClassProperty($orderMain, 'klarnaOrderData', ['order_amount' => 1]);
        $orderMain->render();
        $warningMessage = $orderMain->getViewDataElement('sWarningMessage');
        $this->assertEquals(
            '<strong>Achtung!</strong> Die Daten dieser Bestellung weichen von den bei Klarna gespeicherten Daten ab. Ihre Änderungen an dieser Bestellung werden nicht an Klarna übertragen.',
            $warningMessage
        );
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);
        $orderMain = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['isKlarnaOrder', 'isCredentialsValid', 'isCaptureInSync'])->getMock();
        $orderMain->expects($this->once())->method('isKlarnaOrder')->willReturn(true);
        $orderMain->expects($this->once())->method('isCredentialsValid')->willReturn(true);
        $orderMain->expects($this->once())->method('isCaptureInSync')->willReturn(true);
        $this->setProtectedClassProperty($orderMain, 'klarnaOrderData', ['order_amount' => 10000]);
        $orderMain->render();
        $this->assertEquals(new Field(1), $order->oxorder__tcklarna_sync);

    }

    /**
     * @dataProvider exceptionDataProvider
     * @param $exception
     * @param $expected
     */
    public function testRenderExceptions($exception, $expected) {
        $this->setOrder();
        $this->setRequestParameter('fnc', 'test');
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)
            ->setMethods(['isKlarnaOrder', 'isCredentialsValid', 'retrieveKlarnaOrder'])->getMock();
        $controller->expects($this->any())->method('isKlarnaOrder')->willReturn(true);
        $controller->expects($this->any())->method('isCredentialsValid')->willReturn(true);
        $controller->expects($this->any())->method('retrieveKlarnaOrder')->will($this->throwException($exception));
        $controller->render();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals($expected, $result);
    }


    public function testSendorder() {
        $this->setOrder(1);
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['getEditObjectId'])->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->sendOrder();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals(' Die Bestellung konnte nicht abgebucht werden, da sie bereits storniert wurde.', $result);


        $order = $this->setOrder(0);
        $order->oxorder__tcklarna_sync = new Field(1, Field::T_RAW);
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['getEditObjectId', 'retrieveKlarnaOrder'])->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->any())->method('retrieveKlarnaOrder')->willReturn('test');
        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['remaining_authorized_amount' => 1]);
        $controller->sendorder();
        $klarnaOrder = $this->getProtectedClassProperty($controller, 'klarnaOrderData');
        $result = $controller->getViewDataElement('sMessage');
        $error = $controller->getViewDataElement('sErrorMessage');
        $this->assertEmpty($error);
        $this->assertEquals('test', $klarnaOrder);
        $this->assertEquals('Der Betrag wurde erfolgreich abgebucht.', $result);


        $order = $this->setOrder();
        $order->oxorder__tcklarna_sync = new Field(1, Field::T_RAW);
        $order->expects($this->once())->method('captureKlarnaOrder')->will($this->throwException(new StandardException('test')));
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['getEditObjectId', 'retrieveKlarnaOrder'])->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->any())->method('retrieveKlarnaOrder')->willReturn('test');
        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['remaining_authorized_amount' => 1]);
        $controller->sendOrder();

        $error = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals('test', $error);
    }

    public function testIsCredentialsValid() {
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['getEditObjectId'])->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $result = $controller->isCredentialsValid();
        $this->assertFalse($result);

        $this->setOrder();
        $this->setModuleConfVar('aKlarnaCreds_DE', '');
        $this->setModuleConfVar('sKlarnaMerchantId', 'smid');
        $this->setModuleConfVar('sKlarnaPassword', 'psw');
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['getEditObjectId'])->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');

        $result = $controller->isCredentialsValid();
        $this->assertTrue($result);
    }

    /**
     * @dataProvider captureInSyncDataProvider
     * @param $klarnaOrderData
     * @param $expected
     * @param bool $withOrder
     */
    public function testIsCaptureInSync($klarnaOrderData, $expected, $withOrder = false) {

        $controller = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['getEditObjectId'])->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        if ($withOrder) {
            $order = $this->setOrder();
            $order->oxorder__oxsenddate = new Field('-', Field::T_RAW);
            UtilsObject::setClassInstance(Order::class, $order);
        }

        $result = $controller->isCaptureInSync($klarnaOrderData);
        $this->assertEquals($expected, $result);
    }

    public function captureInSyncDataProvider() {
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

    public function testSave() {
        $order = $this->setOrder();
        $order->oxorder__oxdiscount = new Field(10, Field::T_RAW);
        $order->oxorder__oxordernr = new Field(1, Field::T_RAW);
        $order->oxorder__tcklarna_sync = new Field(1, Field::T_RAW);
        $order->oxorder__tcklarna_orderid = new Field('1', Field::T_RAW);

        $client = $this->getMockBuilder(KlarnaOrderManagementClient::class)->setMethods(['sendOxidOrderNr'])->getMock();
        $client->expects($this->any())->method('sendOxidOrderNr')->willReturn(true);
        $this->setRequestParameter('editval', 11);
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['getEditObjectId', 'isKlarnaOrder', 'getKlarnaMgmtClient'])->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->any())->method('isKlarnaOrder')->willReturn(true);
        $controller->expects($this->any())->method('getKlarnaMgmtClient')->willReturn($client);
        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['captured_amount' => 1]);
        $controller->save();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals('Achtung! Aufgrund des aktuellen Bestellstatus kann diese Anpassung nicht an Klarna übermittelt werden. Führen Sie die entsprechende Anpassung ggf. direkt im Klarna Merchant Portal von Hand durch.', $result);


        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['captured_amount' => 0]);
        $this->setProtectedClassProperty($controller, 'client', $client);
        $controller->save();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals('test', $result);


        $controller = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['getEditObjectId', 'isKlarnaOrder', 'discountChanged'])->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->any())->method('isKlarnaOrder')->willReturn(true);
        $controller->expects($this->any())->method('discountChanged')->willReturn(false);
        $client = $this->getMockBuilder(KlarnaOrderManagementClient::class)->setMethods(['sendOxidOrderNr', 'addShippingToCapture'])->getMock();
        $client->expects($this->any())->method('sendOxidOrderNr')->willReturn(true);
        $client->expects($this->any())->method('sendOxidOrderNr')->willReturn(true);
        $client->expects($this->any())->method('addShippingToCapture')->will(
            $this->throwException(new StandardException())
        );
        $this->setProtectedClassProperty($controller, 'client', $client);
        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['captured_amount' => 1]);
        $this->setRequestParameter('editval', ['oxorder__oxtrackcode' => 1]);
        $controller->save();
        $result = $controller->getViewDataElement('sErrorMessage');
        $this->assertEquals('Achtung! Aufgrund des aktuellen Bestellstatus kann diese Anpassung nicht an Klarna übermittelt werden. Führen Sie die entsprechende Anpassung ggf. direkt im Klarna Merchant Portal von Hand durch.', $result);
    }

    public function testRetrieveKlarnaOrder() {
        $this->setOrder();
        $controller = $this->getMockBuilder(KlarnaOrderMain::class)->setMethods(['getEditObjectId'])->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $this->expectException(KlarnaWrongCredentialsException::class);
        $this->expectExceptionMessage('Unerlaubte Anfrage. Prüfen Sie die Einstellungen des Klarna Moduls und die Merchant ID sowie das zugehörige Passwort');
        $controller->retrieveKlarnaOrder();
    }
}