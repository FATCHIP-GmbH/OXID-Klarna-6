<?php

namespace TopConcepts\Klarna\Tests\Unit\Models;


use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Application\Model\Delivery;
use OxidEsales\Eshop\Application\Model\DeliveryList;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Price;
use TopConcepts\Klarna\Models\KlarnaBasket;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;


class KlarnaBasketTest extends ModuleUnitTestCase
{
    /**
     * @param $iLang
     * @dataProvider testSetKlarnaOrderLangDataProvider
     */
    public function testSetKlarnaOrderLang($iLang)
    {
        $oBasket = oxNew(Basket::class);
        $oBasket->setKlarnaOrderLang($iLang);

        $result = $this->getProtectedClassProperty($oBasket, 'klarnaOrderLang');
        $this->assertEquals($iLang, $result);
    }

    /**
     *
     */
    public function testKl_calculateDeliveryCost()
    {
        /** @var Basket $oBasket */
        $oBasket = $this->createStub(Basket::class, ['getAdditionalServicesVatPercent' => 7.00]);
        $this->setConfigParam('blDeliveryVatOnTop', true);
        $oBasket->setDeliveryPrice('price already set');

        $result = $oBasket->kl_calculateDeliveryCost();
        $this->assertEquals('price already set', $result);


        $oBasket->setDeliveryPrice(null);
        $this->setConfigParam('blCalculateDelCostIfNotLoggedIn', false);

        $result = $oBasket->kl_calculateDeliveryCost();
        $this->assertTrue($result instanceof Price);
        $this->assertTrue($result->isNettoMode());
        $this->assertEquals(0, $result->getVat());
        $this->assertEquals(0, $result->getBruttoPrice());
        $this->assertEquals(0, $result->getNettoPrice());
        $this->assertEquals(0, $result->getVatValue());


        $this->setConfigParam('blDeliveryVatOnTop', false);
        $oUser = oxNew(User::class);
        $oUser->load('oxdefaultadmin');
        $oBasket->setBasketUser($oUser);
        $oDelivery = oxNew(Delivery::class);
        $oPrice    = oxNew(Price::class);
        $oPrice->setPrice(100.00);
        $oDelivery->setDeliveryPrice($oPrice);
        $oDeliveryList = $this->createStub(DeliveryList::class, ['getDeliveryList' => [$oDelivery]]);
        \oxTestModules::addModuleObject(DeliveryList::class, $oDeliveryList);

        $result = $oBasket->kl_calculateDeliveryCost();
        $this->assertTrue($result instanceof Price);
        $this->assertEquals(7, $result->getVat());
        $this->assertEquals(100, $result->getBruttoPrice());
        $this->assertEquals(6.54, $result->getVatValue());
    }

    /**
     *
     * @dataProvider testGetKlarnaPaymentDeliveryDataProvider
     * @param $bruttoPrice
     * @param $vat
     * @param $name
     * @param $order
     * @param $expectedResult
     */
    public function testGetKlarnaPaymentDelivery($bruttoPrice, $vat, $name, $order, $expectedResult)
    {
        $oPrice       = $this->createStub(Price::class,
            ['getBruttoPrice' => $bruttoPrice, 'getVat' => $vat]
        );
        $oDeliverySet = $this->createStub(DeliverySet::class, [
                'getFieldData'      => $name,
                'getShippingId'     => 'oxidstandard',
                '_findDelivCountry' => 'a7c40f631fc920687.20179984',
            ]
        );
        $oOrder       = $order;
        $oBasket      = oxNew(Basket::class);

        $result = $oBasket->getKlarnaPaymentDelivery($oPrice, $oOrder, $oDeliverySet);

        $this->assertEquals($expectedResult, $result);
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

    public function testSetKlarnaOrderLangDataProvider()
    {
        return [
            [0],
            [1],
        ];
    }

    public function testGetKlarnaPaymentDeliveryDataProvider()
    {
        $oOrder = $this->createStub(Order::class,
            [
                'isKCO'        => true,
                'getFieldData' => 'asdf',
            ]
        );

        return [
            [149.99, 7.00, 'testTitle', null,
             [
                 'type'                  => 'shipping_fee',
                 'reference'             => 'SRV_DELIVERY',
                 'name'                  => 'testTitle',
                 'quantity'              => 1,
                 'total_amount'          => 14999,
                 'total_discount_amount' => 0,
                 'total_tax_amount'      => 981,
                 'unit_price'            => 14999,
                 'tax_rate'              => 700,
             ],
            ],
            [114.90, 19.00, 'testTitle', null,
             [
                 'type'                  => 'shipping_fee',
                 'reference'             => 'SRV_DELIVERY',
                 'name'                  => 'testTitle',
                 'quantity'              => 1,
                 'total_amount'          => 11490,
                 'total_discount_amount' => 0,
                 'total_tax_amount'      => 1835,
                 'unit_price'            => 11490,
                 'tax_rate'              => 1900,
             ],
            ],
            [149.99, 7.00, 'testTitle', $oOrder,
             [
                 'type'                  => 'shipping_fee',
                 'reference'             => 'asdf',
                 'name'                  => 'testTitle',
                 'quantity'              => 1,
                 'total_amount'          => 14999,
                 'total_discount_amount' => 0,
                 'total_tax_amount'      => 981,
                 'unit_price'            => 14999,
                 'tax_rate'              => 700,
             ],
            ],
        ];
    }


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
}
