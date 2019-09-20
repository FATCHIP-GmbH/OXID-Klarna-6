<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrderOverview;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaOrderOverviewTest extends ModuleUnitTestCase {

    protected function setOrder($oxstorno = 0) {
        $order = $this->getMockBuilder(
            Order::class)->setMethods(
            ['load', 'save', 'getTotalOrderSum', 'getNewOrderLinesAndTotals', 'updateKlarnaOrder', 'captureKlarnaOrder']
        )->getMock();
        $order->expects($this->any())->method('load')->willReturn(true);
        $order->expects($this->any())->method('save')->willReturn(true);
        $order->expects($this->any())->method('getTotalOrderSum')->willReturn(100);
        $order->expects($this->any())->method('getNewOrderLinesAndTotals')->willReturn(['order_lines' => true]);
        $order->expects($this->any())->method('updateKlarnaOrder')->willReturn('test');
        $order->expects($this->any())->method('captureKlarnaOrder')->willReturn(true);
        $order->oxorder__oxstorno = new Field($oxstorno, Field::T_RAW);
        $order->oxorder__oxpaymenttype = new Field('klarna_checkout', Field::T_RAW);
        $order->oxorder__tcklarna_merchantid = new Field('smid', Field::T_RAW);
        $order->oxorder_oxbillcountryid = new Field('a7c40f631fc920687.20179984', Field::T_RAW);
        UtilsObject::setClassInstance(Order::class, $order);

        return $order;
    }

    public function testInit() {

        $order = $this->setOrder();

        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods([
                '_authorize',
                'getEditObjectId',
                'isCredentialsValid'
            ])
            ->getMock();
        $controller->expects($this->once())->method('_authorize')->willReturn(true);
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->once())->method('isCredentialsValid')->willReturn(false);
        $controller->init();
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);

        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods([
                '_authorize',
                'getEditObjectId',
                'isCredentialsValid',
                'retrieveKlarnaOrder'
            ])
            ->getMock();
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
        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods([
                '_authorize',
                'getEditObjectId',
                'isCredentialsValid',
                'retrieveKlarnaOrder'
            ])
            ->getMock();
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
        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods(['_authorize', 'getEditObjectId', 'retrieveKlarnaOrder', 'isCredentialsValid'])
            ->getMock();
        $controller->expects($this->any())->method('_authorize')->willReturn(true);
        $controller->expects($this->any())->method('isCredentialsValid')->willReturn(true);
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->any())->method('retrieveKlarnaOrder')->will($this->throwException($exception));

        $controller->init();
        $result = $controller->getViewData()['sErrorMessage'];
        $this->assertEquals($expected, $result);
    }

    public function exceptionDataProvider() {
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

    public function testRender() {
        $order = $this->setOrder();
        $orderMain = $this->getMockBuilder(KlarnaOrderOverview::class)->setMethods(['isKlarnaOrder'])->getMock();
        $orderMain->expects($this->once())->method('isKlarnaOrder')->willReturn(true);
        $result = $orderMain->render();

        $this->assertEquals('order_overview.tpl', $result);

        $warningMessage = $orderMain->getViewData()['sWarningMessage'];
        $this->assertEquals(
            '<strong>Wrong credentials!</strong> This order has been placed using <strong>smid</strong> merchant id. Currently configured merchant id for <strong></strong> is <strong></strong>.',
            $warningMessage
        );
        $this->setRequestParameter('fnc', false);
        $orderMain = $this->getMockBuilder(KlarnaOrderOverview::class)->setMethods(['isKlarnaOrder', 'isCredentialsValid'])->getMock();
        $orderMain->expects($this->once())->method('isKlarnaOrder')->willReturn(true);
        $orderMain->expects($this->once())->method('isCredentialsValid')->willReturn(true);
        $this->setProtectedClassProperty($orderMain, 'klarnaOrderData', ['status' => 'CANCELLED']);
        $orderMain->render();
        $warningMessage = $orderMain->getViewData()['sWarningMessage'];
        $this->assertEquals("Die Bestellung wurde storniert. Ihre Änderungen an dieser Bestellung werden nicht an Klarna übertragen.", $warningMessage);
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);

        $orderMain = $this->getMockBuilder(KlarnaOrderOverview::class)->setMethods(['isKlarnaOrder', 'isCredentialsValid'])->getMock();
        $orderMain->expects($this->once())->method('isKlarnaOrder')->willReturn(true);
        $orderMain->expects($this->once())->method('isCredentialsValid')->willReturn(true);
        $this->setProtectedClassProperty($orderMain, 'klarnaOrderData', ['order_amount' => 1]);
        $orderMain->render();
        $warningMessage = $orderMain->getViewData()['sWarningMessage'];
        $this->assertEquals(
            '<strong>Achtung!</strong> Die Daten dieser Bestellung weichen von den bei Klarna gespeicherten Daten ab. Ihre Änderungen an dieser Bestellung werden nicht an Klarna übertragen.',
            $warningMessage
        );
        $this->assertEquals(new Field(0), $order->oxorder__tcklarna_sync);
        $orderMain = $this->getMockBuilder(KlarnaOrderOverview::class)->setMethods(['isKlarnaOrder', 'isCredentialsValid', 'isCaptureInSync'])->getMock();
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
        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods(['isKlarnaOrder', 'isCredentialsValid', 'retrieveKlarnaOrder'])
            ->getMock();
        $controller->expects($this->any())->method('isKlarnaOrder')->willReturn(true);
        $controller->expects($this->any())->method('isCredentialsValid')->willReturn(true);
        $controller->expects($this->any())->method('retrieveKlarnaOrder')->will($this->throwException($exception));

        $controller->render();
        $result = $controller->getViewData()['sErrorMessage'];
        $this->assertEquals($expected, $result);
    }

    public function testRetrieveKlarnaOrder() {
        $this->setOrder();
        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods(['getEditObjectId',])
            ->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');

        $this->expectException(KlarnaWrongCredentialsException::class);
        $this->expectExceptionMessage('Unerlaubte Anfrage. Prüfen Sie die Einstellungen des Klarna Moduls und die Merchant ID sowie das zugehörige Passwort');
        $controller->retrieveKlarnaOrder();
    }

    public function testSendorder() {
        $this->setOrder(1);
        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods(['getEditObjectId',])
            ->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');

        $controller->sendOrder();
        $result = $controller->getViewData()['sErrorMessage'];
        $this->assertEquals(' Die Bestellung konnte nicht abgebucht werden, da sie bereits storniert wurde.', $result);

        $order = $this->setOrder();
        $order->oxorder__tcklarna_sync = new Field(1, Field::T_RAW);
        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods(['getEditObjectId', 'retrieveKlarnaOrder'])
            ->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $controller->expects($this->once())->method('retrieveKlarnaOrder')->willReturn('test');
        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['remaining_authorized_amount' => 1]);
        $controller->sendOrder();

        $result = $controller->getViewData()['sMessage'];
        $this->assertEquals('Der Betrag wurde erfolgreich abgebucht.', $result);
    }

    public function testSendorderException() {
        $order = $this->setOrder();
        $order->oxorder__tcklarna_sync = new Field(1, Field::T_RAW);
        $order->expects($this->any())->method('captureKlarnaOrder')->willThrowException(new StandardException('test'));
        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods(['getEditObjectId'])
            ->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');

        $this->setProtectedClassProperty($controller, 'klarnaOrderData', ['remaining_authorized_amount' => 1]);
        $controller->sendOrder();
        $result = $controller->getViewData()['sErrorMessage'];

        $this->assertEquals('test', $result);

    }

    /**
     * @dataProvider captureInSyncDataProvider
     * @param $klarnaOrderData
     * @param $expected
     * @param bool $withOrder
     */
    public function testIsCaptureInSync($klarnaOrderData, $expected, $withOrder = false) {

        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods(['getEditObjectId',])
            ->getMock();
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

    public function testIsCredentialsValid() {
        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
            ->setMethods(['getEditObjectId',])
            ->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $result = $controller->isCredentialsValid();
        $this->assertFalse($result);

        $this->setOrder();
        $this->setModuleConfVar('aKlarnaCreds_DE', '');
        $this->setModuleConfVar('sKlarnaMerchantId', 'smid');
        $this->setModuleConfVar('sKlarnaPassword', 'psw');
        $controller = $this->getMockBuilder(KlarnaOrderOverview::class)
        ->setMethods(['getEditObjectId'])
        ->getMock();
        $controller->expects($this->any())->method('getEditObjectId')->willReturn('test');
        $result = $controller->isCredentialsValid();
        $this->assertTrue($result);
    }
}
