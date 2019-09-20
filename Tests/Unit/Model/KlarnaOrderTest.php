<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 09.04.2018
 * Time: 17:57
 */

namespace TopConcepts\Klarna\Testes\Unit\Models;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\UtilsObject;
use ReflectionClass;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Model\KlarnaPayment;
use TopConcepts\Klarna\Models\KlarnaOrder;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderTest extends ModuleUnitTestCase
{

    public function isKPDataProvider()
    {
        return [
            [KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_NOW, true],
            [KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID, false],
        ];
    }


    /**
     * @dataProvider isKPDataProvider
     * @param $paymentId
     * @param $expectedResult
     */
    public function testIsKP($paymentId, $expectedResult)
    {
        $oOrder                         = oxNew(Order::class);
        $oOrder->oxorder__oxpaymenttype = new Field($paymentId, Field::T_RAW);

        $result = $oOrder->isKP();
        $this->assertEquals($expectedResult, $result);

    }

    public function getNewOrderLinesAndTotalsDataProvider()
    {
        $homeUrl    = $this->getConfigParam('sShopURL');
        $orderLines = [
            'order_lines'      => [
                [
                    'type'                => 'physical',
                    'reference'           => '2103',
                    'quantity'            => 1,
                    'unit_price'          => 32900,
                    'tax_rate'            => 1900,
                    'total_amount'        => 32900,
                    'total_tax_amount'    => 5253,
                    'quantity_unit'       => 'pcs',
                    'name'                => 'Wakeboard LIQUID FORCE GROOVE 2010',
                    'product_url'         => $homeUrl . 'index.php',
                    'image_url'           => $homeUrl . 'out/pictures/generated/product/1/540_340_75/lf_groove_2010_1.jpg',
                    'product_identifiers' => [
                        'category_path'            => '',
                        'global_trade_item_number' => '',
                        'manufacturer_part_number' => '',
                        'brand'                    => '',


                    ],
                ],
                [
                    'type'                  => 'shipping_fee',
                    'reference'             => 'oxidstandard',
                    'name'                  => 'Standard',
                    'quantity'              => 1,
                    'total_amount'          => 0,
                    'total_discount_amount' => 0,
                    'total_tax_amount'      => 0,
                    'unit_price'            => 0,
                    'tax_rate'              => 1900,
                ],

            ],
            'order_amount'     => 32900,
            'order_tax_amount' => 5253,
        ];

        return [
            [0, true, $orderLines],
            [0, false, $orderLines],
        ];
    }

    /**
     * @dataProvider getNewOrderLinesAndTotalsDataProvider
     * @param $iLang
     * @param $isCapture
     * @param $expectedResult
     */
    public function testGetNewOrderLinesAndTotals($iLang, $isCapture, $expectedResult)
    {
        $id     = $this->prepareKlarnaOrder();
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
            ['invalid_payment', 5],
        ];
    }

    /**
     * @dataProvider validateOrderDataProvider
     * @param $paymentId
     * @param $expectedResult
     */
    public function testValidateOrder($paymentId, $expectedResult)
    {
        $paymentModel = $this->getMockBuilder(Payment::class)
            ->setMethods(['isValidPayment'])
            ->getMock();

        $paymentModel
            ->method('isValidPayment')
            ->willReturn(true);

        UtilsObject::setClassInstance(Payment::class, $paymentModel);

        $this->setSessionParam('sDelAddrMD5','d41d8cd98f00b204e9800998ecf8427e');

        /** @var \OxidEsales\Eshop\Application\Model\Basket $oBasket */
        $oBasket = $this->prepareBasketWithProduct();
        $this->getSession()->setBasket($oBasket);
        $oUser   = oxNew(User::class);
        $oBasket->setPayment($paymentId);
        $order = $this->getMockBuilder(Order::class)->setMethods(['validateDeliveryAddress'])->getMock();
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
        $order                         = oxNew(Order::class);
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
        $order                         = oxNew(Order::class);
        $order->oxorder__oxpaymenttype = new Field($type, Field::T_RAW);
        $result                        = $order->isKlarna();
        $this->assertEquals($expectedResult, $result);
    }

    public function errorDataProvider()
    {
        return [
            [403], [422], [401], [404],
        ];
    }

    public function isKCODataProvider()
    {
        return [
            ['type1', false],
            ['klarna_checkout', true],
            ['klarna_pay_later', false],
        ];
    }

    /**
     * @dataProvider isKCODataProvider
     * @param $type
     * @param $expectedResult
     */
    public function testIsKCO($type, $expectedResult)
    {
        $order                         = oxNew(Order::class);
        $order->oxorder__oxpaymenttype = new Field($type, Field::T_RAW);

        $result = $order->isKCO();
        $this->assertEquals($expectedResult, $result);
    }

    public function testCancelKlarnaOrder()
    {
        $id       = 'zzz';
        $response = ['response'];

        $client = $this->getMockBuilder(KlarnaOrderManagementClient::class)->setMethods(['cancelOrder'])->getMock();
        $client->expects($this->once())->method('cancelOrder')->willReturn($response);
        $order  = oxNew(Order::class);
        $result = $order->cancelKlarnaOrder($id, null, $client);
        $this->assertEquals($response, $result);
    }


    public function testUpdateKlarnaOrder()
    {
        $id       = 'zzz';
        $data     = ['update' => 'data'];
        $response = ['response'];
        $uniqueId_1 = 'uid_1';
        $uniqueId_2 = 'uid_2';

        $client = $this->getMockBuilder(KlarnaOrderManagementClient::class)->setMethods(['updateOrderLines'])->getMock();
        $client->expects($this->once())->method('updateOrderLines')->willReturn($response);

        $order  = oxNew(Order::class);
        $order->oxorder__tcklarna_orderid = new Field($uniqueId_1);
        $result = $order->updateKlarnaOrder($data, $id, null, $client);
        $this->assertNull($result);

        $this->assertEquals(1, $order->oxorder__tcklarna_sync->value);

        // Test exception
        $client = $this->getMockBuilder(KlarnaOrderManagementClient::class)->setMethods(['updateOrderLines'])->getMock();
        $client->expects($this->once())->method('updateOrderLines')->willThrowException(new KlarnaClientException("Test"));

        $order  = oxNew(Order::class);
        $order->oxorder__tcklarna_orderid = new Field($uniqueId_2);
        $result = $order->updateKlarnaOrder($data, $id, null, $client);

        $this->assertEquals(0, $order->oxorder__tcklarna_sync->value);
        $this->assertEquals("Test", $result);

    }

    public function testCaptureKlarnaOrder()
    {
        $id       = 'zzz';
        $data     = ['update' => 'data'];
        $response = ['response'];
        $client = $this->getMockBuilder(KlarnaOrderManagementClient::class)->setMethods(['captureOrder'])->getMock();
        $client->expects($this->once())->method('captureOrder')->willReturn($response);

        $order  = oxNew(Order::class);
        $result = $order->captureKlarnaOrder($data, $id, null, $client);
        $this->assertEquals($response, $result);

        $client = $this->getMockBuilder(KlarnaOrderManagementClient::class)->setMethods(['captureOrder'])->getMock();
        $client->expects($this->once())
            ->method('captureOrder')
            ->with($this->callback(
                function ($data) {
                    return isset($data['shipping_info']) && $data['shipping_info'] === [['tracking_number' => 'trackcode']];
                })
            )
            ->willReturn($response);

        $order->oxorder__oxtrackcode = new Field('trackcode', Field::T_RAW);
        $result                      = $order->captureKlarnaOrder($data, $id, null, $client);
        $this->assertEquals($response, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function test_setNumber()
    {
        $id     = $this->prepareKlarnaOrder();
        $order  = $this->getMockBuilder(Order::class)->setMethods(['isKlarna', 'isKP', 'isKCO'])->getMock();
        $class  = new ReflectionClass(get_class($order));
        $method = $class->getMethod('_setNumber');
        $method->setAccessible(true);

        $order->expects($this->any())->method('isKlarna')->willReturn(true);
        $order->expects($this->any())->method('isKP')->willReturn(true);

        // successful update
        $order->load($id);
        $order->oxorder__tcklarna_orderid = new Field('', Field::T_RAW);
        $this->setSessionParam('klarna_last_KP_order_id', 'klarnaId');

        $response = ['response'];
        $client = $this->getMockBuilder(KlarnaOrderManagementClient::class)->setMethods(['sendOxidOrderNr'])->getMock();
        $client->expects($this->once())->method('sendOxidOrderNr')->willReturn($response);
        $client->expects($this->once())
            ->method('sendOxidOrderNr')
            ->willReturn($response);

        $result = $method->invokeArgs($order, [$client]);
        $this->assertTrue($result);
        $this->assertNull($this->getSessionParam('klarna_last_KP_order_id'));
        $this->assertEquals('klarnaId', $order->oxorder__tcklarna_orderid->value);
        $this->assertEquals('klarnaId', $order->oxorder__tcklarna_orderid->value);


        // exception
        $order->oxorder__tcklarna_orderid = new Field('', Field::T_RAW);
        $order->expects($this->any())->method('isKCO')->willReturn(true);
        $this->setSessionParam('klarna_checkout_order_id', 'klarnaId');

        $response = ['response'];
        $client = $this->getMockBuilder(KlarnaOrderManagementClient::class)->setMethods(['sendOxidOrderNr'])->getMock();
        $client->expects($this->once())->method('sendOxidOrderNr')->willReturn($response);
        $client->expects($this->once())
            ->method('sendOxidOrderNr')
            ->willThrowException(new KlarnaClientException('Test'));

        $result = $method->invokeArgs($order, [$client]);
        $this->assertTrue($result);
        $this->assertLoggedException(KlarnaClientException::class, 'Test');
        $this->removeKlarnaOrder($id);
    }


    /**
     * @throws \ReflectionException
     */
    public function test_setOrderArticle()
    {

        $order   = oxNew(Order::class);
        $oBasket = $this->prepareBasketWithProduct();

        $class  = new ReflectionClass(get_class($order));
        $method = $class->getMethod('_setOrderArticles');
        $method->setAccessible(true);

        $this->setProtectedClassProperty($order, 'isAnonymous', true);
        $result = $method->invokeArgs($order, [$oBasket->getContents()]);
        $this->assertNull($result);

        $oList    = $order->getOrderArticles();
        $aList    = $this->getProtectedClassProperty($oList, '_aArray');
        $oArticle = reset($aList); // get first element

        $this->assertNotEmpty($oArticle->getFieldData('oxorderarticles__tcklarna_title'));
        $this->assertNotEmpty($oArticle->getFieldData('oxorderarticles__tcklarna_artnum'));

        $this->setProtectedClassProperty($order, 'isAnonymous', null);
        $result = $method->invokeArgs($order, [$oBasket->getContents()]);
        $this->assertNull($result);

        $oList    = $order->getOrderArticles();
        $aList    = $this->getProtectedClassProperty($oList, '_aArray');
        $oArticle = reset($aList); // get first element

        $this->assertNull($oArticle->getFieldData('oxorderarticles__tcklarna_title'));
        $this->assertNull($oArticle->getFieldData('oxorderarticles__tcklarna_artnum'));

    }

    public function setOrderArticleDataProvider()
    {

        return [
            [true],
            [null],
        ];
    }
}
