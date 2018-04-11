<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 09.04.2018
 * Time: 17:57
 */

namespace TopConcepts\Klarna\Testes\Unit\Models;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\TestingLibrary\UnitTestCase;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Exception\KlarnaClientException;
use TopConcepts\Klarna\Models\KlarnaOrder;
use TopConcepts\Klarna\Models\KlarnaPayment;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderTest extends ModuleUnitTestCase
{

    public function isKPDataProvider()
    {
        return [
            [KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_NOW, true],
            [KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID, false]
        ];
    }



    /**
     * @dataProvider isKPDataProvider
     * @param $paymentId
     * @param $expectedResult
     */
    public function testIsKP($paymentId, $expectedResult)
    {
        $oOrder = oxNew(Order::class);
        $oOrder->oxorder__oxpaymenttype = new Field($paymentId, Field::T_RAW);

        $result = $oOrder->isKP();
        $this->assertEquals($expectedResult, $result);

    }

    public function getNewOrderLinesAndTotalsDataProvider()
    {
        $orderLines = [
            'order_lines' => [
                [
                    'type' => 'physical',
                    'reference' => '',
                    'quantity' => 1,
                    'unit_price' => 32900,
                    'tax_rate' => 1900,
                    'total_amount' => 32900,
                    'total_tax_amount' => 5253,
                    'quantity_unit' => 'pcs',
                    'name' => '(no title)',
                    'product_url' => 'http://arek.ox6.demohost.topconcepts.net/index.php',
                    'image_url' => 'http://arek.ox6.demohost.topconcepts.net/out/pictures/generated/product/1/540_340_75/nopic.jpg',
                    'product_identifiers' => [
                        'category_path' => '',
                        'global_trade_item_number' => '',
                        'manufacturer_part_number' => '',
                        'brand' => ''


                    ]
                ],
                [
                    'type' => 'shipping_fee',
                    'reference' => 'oxidstandard',
                    'name' => 'Standard',
                    'quantity' => 1,
                    'total_amount' => 0,
                    'total_discount_amount' => 0,
                    'total_tax_amount' => 0,
                    'unit_price' => 0,
                    'tax_rate' => 1900
                ]

            ],
            'order_amount' => 32900,
            'order_tax_amount' => 5253
        ];
        return [
            [0, true, $orderLines],
            [0, false, $orderLines]
        ];
    }

    /**
     * @dataProvider getNewOrderLinesAndTotalsDataProvider
     * @param $orderId
     * @param $iLang
     * @param $isCapture
     * @param $expectedResult
     */
    public function testGetNewOrderLinesAndTotals($iLang, $isCapture, $expectedResult)
    {
        $id = $this->prepareKlarnaOrder();
        $oOrder = oxNew(Order::class);
        $oOrder->load($id);
        $result = $oOrder->getNewOrderLinesAndTotals($iLang, $isCapture);

        $this->assertEquals($expectedResult, $result);
        $this->removeKlarnaOrder($id);
    }


    public function validateOrderDataProvider()
    {
        return [
            ['klarna_checkout', null],
            ['invalid_payment', 5]
        ];
    }

    /**
     * @dataProvider validateOrderDataProvider
     * @param $paymentId
     * @param $expectedResult
     */
    public function testValidateOrder($paymentId, $expectedResult)
    {
        /** @var \OxidEsales\Eshop\Application\Model\Basket $oBasket */
        $oBasket = $this->prepareBasketWithProduct();
        $oUser = oxNew(User::class);
        $oBasket->setPayment($paymentId);

        $order = oxNew(Order::class);
        $result = $order->validateOrder($oBasket, $oUser);

        $this->assertEquals($expectedResult, $result);

    }

    public function isKlarnaOrderDataProvider()
    {
        return [
            ['type1', false, false],
            ['klarna_type', true, false],
            ['klarna_checkout', true, true],
            ['klarna_pay_later', true, true],
            ['klarna_pay_now', true, true],
            ['klarna_slice_it', true, true],
        ];
    }

    /**
     * @dataProvider isKlarnaOrderDataProvider
     * @param $type
     * @param $expectedResult
     */
    public function testIsKlarnaOrder($type, $expectedResult)
    {
        $order = oxNew(Order::class);
        $order->oxorder__oxpaymenttype = new Field($type, Field::T_RAW);

        $result = $order->isKlarnaOrder();
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider isKlarnaOrderDataProvider
     * @param $type
     * @param $expectedResult
     */
    public function testIsKlarna($type, $notUsed, $expectedResult)
    {
        $order = oxNew(Order::class);
        $order->oxorder__oxpaymenttype = new Field($type, Field::T_RAW);
        $result = $order->isKlarna();
        $this->assertEquals($expectedResult, $result);
    }

    public function errorDataProvider()
    {
        return [
            [403], [422], [401], [404]
        ];
    }

    /**
     * @dataProvider errorDataProvider
     * @param $iCode
     */

    public function testShowKlarnaErrorMessage($iCode)
    {
        $this->setLanguage(1);
        $e = new StandardException("Test Message", $iCode);
        $order = oxNew(Order::class);
        $message = $order->showKlarnaErrorMessage($e);
        $this->assertEquals('KL_ORDER_UPDATE_REJECTED_BY_KLARNA', $message);

    }

    public function isKCODataProvider()
    {
        return [
            ['type1', false],
            ['klarna_checkout', true],
            ['klarna_pay_later', false]
        ];
    }

    /**
     * @dataProvider isKCODataProvider
     * @param $type
     * @param $expectedResult
     */
    public function testIsKCO($type, $expectedResult)
    {
        $order = oxNew(Order::class);
        $order->oxorder__oxpaymenttype = new Field($type, Field::T_RAW);

        $result = $order->isKCO();
        $this->assertEquals($expectedResult, $result);
    }

    public function testCancelKlarnaOrder()
    {
        $id = 'zzz';
        $response = ['response'];

        $client = $this->createStub(KlarnaOrderManagementClient::class, ['cancelOrder' => $response]);
        $order = oxNew(Order::class);
        $result = $order->cancelKlarnaOrder($id, null, $client);
        $this->assertEquals($response, $result);
    }


    public function testUpdateKlarnaOrder()
    {
        $id = 'zzz';
        $data = ['update' => 'data'];
        $response = ['response'];

        $client = $this->createStub(KlarnaOrderManagementClient::class, ['updateOrderLines' => $response]);

        $order = oxNew(Order::class);
        $result = $order->updateKlarnaOrder($data, $id, null, $client);
        $this->assertNull($result);

        $this->assertEquals(1, $order->oxorder__klsync->value);

        // Test exception
        $client = $this->getMock(KlarnaOrderManagementClient::class, ['updateOrderLines']);
        $client->expects($this->once())->method('updateOrderLines')->willThrowException(new KlarnaClientException("Test"));

        $order = oxNew(Order::class);
        $result = $order->updateKlarnaOrder($data, $id, null, $client);

        $this->assertEquals(0, $order->oxorder__klsync->value);
        $this->assertEquals("Test", $result);

    }
    public function testCancelOrder()
    {
        $response = ['response'];
        $id = $this->prepareKlarnaOrder();

        $order = $this->getMock(Order::class, ['cancelKlarnaOrder'] );
        $order->expects($this->any())->method('cancelKlarnaOrder')->willReturn($response);
        $order->load($id);
        $order->oxorder__oxpaymenttype = new Field('klarna_xxx', Field::T_RAW);
        $order->oxorder__oxstorno = new Field(0, Field::T_RAW);
        $order->oxorder__klsync = new Field(1, Field::T_RAW);
        $order->oxorder__klorderid = new Field('aaa', Field::T_RAW);
        $order->save();


        $result = $order->cancelOrder();
        $this->assertNull($result);

        $order->load($id);
        $order->oxorder__oxpaymenttype = new Field('klarna_xxx', Field::T_RAW);
        $order->oxorder__oxstorno = new Field(0, Field::T_RAW);
        $order->oxorder__klsync = new Field(1, Field::T_RAW);
        $order->oxorder__klorderid = new Field('aaa', Field::T_RAW);

        $order->expects($this->once())->method('cancelKlarnaOrder')->willThrowException(new KlarnaClientException("Test"));
        $result = $order->cancelOrder();

//        print_r($order);
        $this->assertEquals("Test", $result);


        $this->removeKlarnaOrder($id);
    }

    public function testCaptureKlarnaOrder()
    {
        $id = 'zzz';
        $data = ['update' => 'data'];
        $response = ['response'];

        $client = $this->createStub(KlarnaOrderManagementClient::class, ['captureOrder' => $response]);
        $client->expects($this->once())->method('captureOrder')->willReturn($response);

        $order = oxNew(Order::class);
        $result = $order->captureKlarnaOrder($data, $id, null, $client);
    }


//    public function testGetAllCaptures()
//    {
//
//    }

//    public function testRetrieveKlarnaOrder()
//    {
//
//    }

//    public function testGetKlarnaClient()
//    {
//
//    }

//    public function testCreateOrderRefund()
//    {
//
//    }

//    public function testAddShippingToCapture()
//    {
//
//    }
}
