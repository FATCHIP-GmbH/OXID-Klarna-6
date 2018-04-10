<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 09.04.2018
 * Time: 17:57
 */

namespace TopConcepts\Klarna\Testes\Unit\Models;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\TestingLibrary\UnitTestCase;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Models\KlarnaOrder;
use TopConcepts\Klarna\Models\KlarnaPayment;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderTest extends ModuleUnitTestCase
{

    protected function prepareKlarnaOrder()
    {
        /** @var \OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database $db */
        $db = UnitTestCase::getDb();
        $db->execute("INSERT INTO `oxorder` VALUES('fce317bd07065a16d6c0b8b530346a8e', '1', 'bbf2387f1e85d75ffaac693c2338d400', '2018-03-13 11:45:41', '3', '', 'dabrowski@topconcepts.de', 'Greg', 'Dabrowski', 'afafafafafa', '1', '', '', 'Hamburg', 'a7c40f631fc920687.20179984', '', '12012', '', '', 'Mr', '', 'Greg', 'Dabrowski', 'afafafafafa', '1', '', 'Hamburg', 'a7c40f631fc920687.20179984', '', '12012', '', '', 'Mr', 'a66b77a68e3d3f84cd8950e7c99f5362', 'klarna_checkout', '276.47', '329', '329', '19', '52.53', '0', '0', '0', '19', '0', '0', '0', '0', '0', '19', '', '', '0', '0', '', '0000-00-00', '', '0000-00-00 00:00:00', '', '0', 'EUR', '1', 'ORDERFOLDER_NEW', '', '', '', '0000-00-00 00:00:00', '0', '', 'OK', '0', '0', 'oxidstandard', '2018-03-13 12:13:35', '0', 'K501664', '334d4946-6e76-7f13-9b78-c4461b5c8b9d', '1', '')");
        $db->execute("INSERT INTO `oxorderarticles` VALUES('3c492e4ed3b7aa51b0fd1d85e26d2dc7', 'fce317bd07065a16d6c0b8b530346a8e', '0', '058de8224773a1d5fd54d523f0c823e0', '1302', 'Kiteboard CABRINHA CALIBER 2011', 'Freestyle und Freeride Board', '', '402.52', '479', '76.48', '19', '', '479', '479', '402.52', '', '', '', '', '', 'cabrinha_caliber_2011.jpg', 'cabrinha_caliber_2011_deck.jpg', 'cabrinha_caliber_2011_bottom.jpg', '', '', '0', '12', '0000-00-00', '2010-12-06', '2018-03-13 12:13:35', '0', '0', '0', '', 'kiteboard, kite, board, caliber, cabrinha', '', '', '1', '', 'oxarticle', '0', '1', '0', '', '')");
        $db->execute("INSERT INTO `oxorderarticles` VALUES('886fab2af7827129caa39ef0be3e522e', 'fce317bd07065a16d6c0b8b530346a8e', '1', 'ed6573c0259d6a6fb641d106dcb2faec', '2103', 'Wakeboard LIQUID FORCE GROOVE 2010', 'Stylisches Wakeboard mit traumhafter Performance', '', '276.47', '329', '52.53', '19', '', '329', '329', '276.47', '', '', '', '', '', 'lf_groove_2010_1.jpg', 'lf_groove_2010_deck_1.jpg', 'lf_groove_2010_bottom_1.jpg', '', '', '0', '9', '0000-00-00', '2010-12-09', '2018-03-13 11:45:41', '0', '0', '0', '', 'wakeboarding, wake, board, liquid force, groove', '', '', '1', '', 'oxarticle', '0', '1', '0', '', '')");
    }

    protected function removeKlarnaOrder()
    {
        /** @var \OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database $db */
        $db = UnitTestCase::getDb();
        $db->execute("DELETE FROM `oxorder` WHERE `oxid` = 'fce317bd07065a16d6c0b8b530346a8e'");
        $db->execute("DELETE FROM `oxorderarticles` WHERE `oxorderid` = 'fce317bd07065a16d6c0b8b530346a8e'");
    }

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
            ['fce317bd07065a16d6c0b8b530346a8e', 0, true, $orderLines],
            ['fce317bd07065a16d6c0b8b530346a8e', 0, false, $orderLines]
        ];
    }

    /**
     * @dataProvider getNewOrderLinesAndTotalsDataProvider
     * @param $orderId
     * @param $iLang
     * @param $isCapture
     * @param $expectedResult
     */
    public function testGetNewOrderLinesAndTotals($orderId, $iLang, $isCapture, $expectedResult)
    {
        $this->prepareKlarnaOrder();
        $oOrder = oxNew(Order::class);
        $oOrder->load($orderId);
        $result = $oOrder->getNewOrderLinesAndTotals($iLang, $isCapture);

        $this->assertEquals($expectedResult, $result);
        $this->removeKlarnaOrder();
    }

    public function testCancelKlarnaOrder()
    {
        $id = 'zzz';
        $client = $this->getMock(KlarnaOrderManagementClient::class, ['cancelOrder']);
        $client->expects($this->once())->method('cancelOrder')->with($id);
        $order = $this->getMock(Order::class, ['getKlarnaClient']);
        $order->expects($this->once())->method('getKlarnaClient')->willReturn($client);
        $order->oxorder__klorderid = new Field('zzz', Field::T_RAW);

        $order->cancelKlarnaOrder();
    }

    public function testIsKlarnaOrder()
    {

    }

    public function testValidateOrder()
    {

    }

    public function testIsKlarna()
    {

    }

    public function testUpdateKlarnaOrder()
    {

    }

    public function testShowKlarnaErrorMessage()
    {

    }

    public function testCancelOrder()
    {

    }

    public function testGetKlarnaClient()
    {

    }

    public function testRetrieveKlarnaOrder()
    {

    }

    public function testGetAllCaptures()
    {

    }

    public function testCreateOrderRefund()
    {

    }

    public function testCaptureKlarnaOrder()
    {

    }

    public function testAddShippingToCapture()
    {

    }

    public function testIsKCO()
    {

    }
}
