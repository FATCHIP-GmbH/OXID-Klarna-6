<?php

namespace TopConcepts\Klarna\Tests\Unit\Controllers\Admin;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Controllers\Admin\KlarnaOrderMain;
use TopConcepts\Klarna\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderMainTest extends ModuleUnitTestCase
{

    public function testInit()
    {
        $order = $this->setOrder();

        $controller = $this->createStub(
            KlarnaOrderMain::class,
            [
                '_authorize' => true,
                'getEditObjectId' => 'test',
                'isCredentialsValid' => false,
            ]
        );
        $controller->init();
        $this->assertEquals(new Field(0), $order->oxorder__klsync);

        $controller = $this->createStub(
            KlarnaOrderMain::class,
            [
                '_authorize' => true,
                'getEditObjectId' => 'test',
                'isCredentialsValid' => true,
                'retrieveKlarnaOrder' => ['status' => 'CANCEL'],
            ]
        );
        $controller->init();
        $this->assertEquals(new Field(0), $order->oxorder__klsync);

        $orderData = [
            'order_amount' => 10000,
            'remaining_authorized_amount' => 10000,
            'status' => 'AUTHORIZED',
        ];
        $controller = $this->createStub(
            KlarnaOrderMain::class,
            [
                '_authorize' => true,
                'getEditObjectId' => 'test',
                'isCredentialsValid' => true,
                'retrieveKlarnaOrder' => $orderData,
            ]
        );

        $controller->init();

        $this->assertEquals(new Field(1), $order->oxorder__klsync);

    }

    /**
     * @dataProvider initDataProvider
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
        $result = $controller->getViewData()['sErrorMessage'];
        $this->assertEquals($expected, $result);
    }

    public function initDataProvider()
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

    protected function setOrder()
    {
        $order = $this->getMock(Order::class, ['load', 'save', 'getTotalOrderSum']);
        $order->expects($this->any())->method('load')->willReturn(true);
        $order->expects($this->any())->method('save')->willReturn(true);
        $order->expects($this->any())->method('getTotalOrderSum')->willReturn(100);
        $order->oxorder__oxstorno = new Field(0, Field::T_RAW);
        $order->oxorder__oxpaymenttype = new Field('klarna_checkout', Field::T_RAW);
        $order->oxorder__klmerchantid = new Field('smid', Field::T_RAW);
        $order->oxorder_oxbillcountryid = new Field('a7c40f631fc920687.20179984', Field::T_RAW);
        \oxTestModules::addModuleObject(Order::class, $order);

        return $order;
    }
//
//    public function testRender()
//    {
//
//    }


//    public function testSendorder()
//    {
//
//    }
//
    public function testIsCredentialsValid()
    {
        $controller = $this->createStub(KlarnaOrderMain::class, ['getEditObjectId' => 'test']);
        $result = $controller->isCredentialsValid();
        $this->assertFalse($result);

        $this->setOrder();
        $this->setModuleConfVar('aKlarnaCreds_DE', '');
        $this->setModuleConfVar('sKlarnaMerchantId', 'smid');
        $this->setModuleConfVar('sKlarnaPassword', 'psw');
        $controller = $this->createStub(KlarnaOrderMain::class, ['getEditObjectId' => 'test']);

        $result = $controller->isCredentialsValid();
        $this->assertTrue($result);


    }
//
//    public function testGetEditObject()
//    {
//
//    }
//
//
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
            $order = $this->setOrder();
            $order->oxorder__oxsenddate = new Field('-', Field::T_RAW);
            \oxTestModules::addModuleObject(Order::class, $order);
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
//
//    public function testSave()
//    {
//
//    }
//
//    public function testIsKlarnaOrder()
//    {
//
//    }
//
    public function testRetrieveKlarnaOrder()
    {
        $this->setOrder();
        $controller = $this->createStub(KlarnaOrderMain::class, ['getEditObjectId' => 'test']);
        $this->setExpectedException(KlarnaWrongCredentialsException::class, 'KLARNA_UNAUTHORIZED_REQUEST');
        $controller->retrieveKlarnaOrder();
    }
}
