<?php

namespace TopConcepts\Klarna\Tests\Unit\Models;


use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Models\KlarnaBasket;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaBasketTest extends ModuleUnitTestCase
{
    protected static $oBasket;

//    public function setUp()
//    {
//        parent::setUp();
//        self::$oBasket = $this->prepareBasketWithProduct();
//    }

    public function is_fractionDataProvider()
    {
        return [
            [1.3, true], [12, false], ['zzzz', false]
        ];
    }
    /**
     * @dataProvider is_fractionDataProvider
     * @param $val
     */
    public function testIs_fraction($val, $eResult)
    {
        $oBasket = oxNew(Basket::class);
        $result = $oBasket->is_fraction($val);
        $this->assertEquals($eResult, $result);
    }

    public function testKl_calculateDeliveryCost()
    {
    }

    public function testGetKlarnaPaymentDelivery()
    {

    }

    public function orderLinesDataProvider()
    {
        $ids = ['ed6573c0259d6a6fb641d106dcb2faec'];
        /** @var Basket|KlarnaBasket $oBasket */
        $oBasket = oxNew(Basket::class);
        $this->setUpBasket($oBasket, $ids);


        $orderLines = function($anonOn){
            $homeUrl = $this->getConfigParam('sShopURL');
            $lines = [
                'order_lines' => [
                    [
                        'type' => 'physical',
                        'reference' => ($anonOn ? '7b1ce3d73b70f1a7246e7b76a35fb552': '2103'),
                        'quantity' => 1,
                        'unit_price' => 32900,
                        'tax_rate' => 1900,
                        'total_amount' => 32900,
                        'total_tax_amount' => 5253,
                        'quantity_unit' => 'pcs',
                        'name' => ($anonOn ? 'Product name 1' : 'Wakeboard LIQUID FORCE GROOVE 2010'),
                        'product_url' => $homeUrl . 'index.php',
                        'image_url' => $homeUrl . 'out/pictures/generated/product/1/540_340_75/lf_groove_2010_1.jpg',
                        'product_identifiers' => [
                            'category_path' => '',
                            'global_trade_item_number' => '',
                            'manufacturer_part_number' => '',
                            'brand' => ''
                        ]
                    ],
                    [
                        'type' => 'shipping_fee',
                        'reference' => 'SRV_DELIVERY',
                        'name' => 'Standard',
                        'quantity' => 1,
                        'total_amount' => 0,
                        'total_discount_amount' => 0,
                        'total_tax_amount' => 0,
                        'unit_price' => 0,
                        'tax_rate' => 0
                    ]

                ],
                'order_amount' => 32900,
                'order_tax_amount' => 5253
            ];

            if($anonOn){
                unset($lines['order_lines'][0]['product_url']);
                unset($lines['order_lines'][0]['image_url']);
                unset($lines['order_lines'][0]['product_identifiers']);
            }

            return $lines;
        };

        return [
            ['fake', 1, 1, $oBasket, $orderLines(1)],
            ['fake', 1, 0, $oBasket, $orderLines(0)]
        ];
    }

    /**
     * @dataProvider orderLinesDataProvider
     * @param $orderMgmtId
     * @param $iLang
     * @param $anonymizationOn
     * @param $oBasket
     * @param $orderLines
     */
    public function testGetKlarnaOrderLines($orderMgmtId, $iLang, $anonymizationOn, $oBasket, $orderLines)
    {
        $this->prepareOrder($iLang);
        $this->setModuleConfVar('blKlarnaEnableAnonymization', $anonymizationOn, 'bool');

        $result = $oBasket->getKlarnaOrderLines($orderMgmtId);

        $this->assertArrayHasKey('order_lines', $result);
        $this->assertNotEmpty($result['order_lines']);
        $this->assertEquals($orderLines, $result);

    }

    protected function prepareOrder($iLang)
    {
        $oOrder                  = $this->getMock(Order::class, ['load']);
        $oOrder->oxorder__oxlang = new Field($iLang, Field::T_RAW);
        \oxTestModules::addModuleObject(Order::class, $oOrder);

    }
//     protected function prepareBasketFromOrder()
//    {
//        $id = $this->prepareKlarnaOrder();
//        $oOrder = oxNew(Order::class);
//        $oOrder->load($id);
//
//    }

    protected function setUpBasket($oBasket, $productsIds)
    {
        $this->setConfigParam('blAllowUnevenAmounts', true);
        foreach($productsIds as $id){
            $oBasket->addToBasket($id, 1);
        }
        $oBasket->calculateBasket();

        //basket name in session will be "basket"
        $this->getConfig()->setConfigParam('blMallSharedBasket', 1);

        return $oBasket;
    }


}
