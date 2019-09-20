<?php

namespace TopConcepts\Klarna\Tests\Unit\Model;


use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Discount;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Application\Model\Delivery;
use OxidEsales\Eshop\Application\Model\DeliveryList;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\ShopIdCalculator;
use OxidEsales\Eshop\Core\UtilsObject;
use ReflectionClass;
use TopConcepts\Klarna\Core\Exception\KlarnaBasketTooLargeException;
use TopConcepts\Klarna\Model\KlarnaBasket;
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
    public function testTcklarna_calculateDeliveryCost()
    {
        $oBasket = $this->getMockBuilder(Basket::class)->setMethods(['getAdditionalServicesVatPercent'])->getMock();
        $oBasket->expects($this->once())->method('getAdditionalServicesVatPercent')->willReturn(7.00);
        $this->setConfigParam('blDeliveryVatOnTop', true);
        $oBasket->setDeliveryPrice('price already set');

        $result = $oBasket->tcklarna_calculateDeliveryCost();
        $this->assertEquals('price already set', $result);


        $oBasket->setDeliveryPrice(null);
        $this->setConfigParam('blCalculateDelCostIfNotLoggedIn', false);

        $result = $oBasket->tcklarna_calculateDeliveryCost();
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
        $oDeliveryList = $this->getMockBuilder(DeliveryList::class)->setMethods(['getDeliveryList'])->getMock();
        $oDeliveryList->expects($this->once())->method('getDeliveryList')->willReturn([$oDelivery]);
        UtilsObject::setClassInstance(DeliveryList::class, $oDeliveryList);

        $result = $oBasket->tcklarna_calculateDeliveryCost();
        $this->assertTrue($result instanceof Price);
        $this->assertEquals(7, $result->getVat());
        $this->assertEquals(100, $result->getBruttoPrice());
        $this->assertEquals(6.54, $result->getVatValue());
    }

    /**
     *
     * @dataProvider KlarnaPaymentDeliveryDataProvider
     * @param $bruttoPrice
     * @param $vat
     * @param $name
     * @param $order
     * @param $expectedResult
     */
    public function testGetKlarnaPaymentDelivery($bruttoPrice, $vat, $name, $order, $expectedResult)
    {
        $oPrice = $this->getMockBuilder(Price::class)->setMethods(['getBruttoPrice', 'getVat'])->getMock();
        $oPrice->expects($this->once())->method('getBruttoPrice')->willReturn($bruttoPrice);
        $oPrice->expects($this->once())->method('getVat')->willReturn($vat);

        $oDeliverySet = $this->getMockBuilder(DeliverySet::class)->setMethods(['getFieldData'])->getMock();
        $oDeliverySet->expects($this->once())->method('getFieldData')->willReturn($name);

        $oOrder       = $order;
        $oBasket      = oxNew(Basket::class);

        $result = $oBasket->getKlarnaPaymentDelivery($oPrice, $oOrder, $oDeliverySet);

        $this->assertEquals($expectedResult, $result);
    }

    protected function getOrderLinesData($anonOn = 0, $wrapping = 0)
    {
        $homeUrl = $this->getConfigParam('sShopURL');
        $lines   = [
            'order_lines'      => [
                [
                    'type'                => 'physical',
                    'reference'           => ($anonOn ? '7b1ce3d73b70f1a7246e7b76a35fb552' : '2103'),
                    'quantity'            => 1,
                    'unit_price'          => 32900,
                    'tax_rate'            => 1900,
                    'total_amount'        => 32900,
                    'total_tax_amount'    => 5253,
                    'quantity_unit'       => 'pcs',
                    'name'                => ($anonOn ? 'Produktname 1' : 'Wakeboard LIQUID FORCE GROOVE 2010'),
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
                    'reference'             => 'SRV_DELIVERY',
                    'name'                  => 'Standard',
                    'quantity'              => 1,
                    'total_amount'          => 0,
                    'total_discount_amount' => 0,
                    'total_tax_amount'      => 0,
                    'unit_price'            => 0,
                    'tax_rate'              => 0,
                ],

            ],
            'order_amount'     => 32900,
            'order_tax_amount' => 5253,
        ];

        if ($anonOn) {
            unset($lines['order_lines'][0]['product_url']);
            unset($lines['order_lines'][0]['image_url']);
            unset($lines['order_lines'][0]['product_identifiers']);
        }

        if ($wrapping) {
            //$lines['order_lines'];
        }

        return $lines;
    }

    /**
     * @param $orderLines
     * @param $orderMgmtId
     * @param $iLang
     * @param $anonymizationOn
     * @param $voucherNr
     * @param Basket $oBasket
     * @throws \oxSystemComponentException
     */
    public function testGetKlarnaOrderLines()
    {

        $this->setModuleConfVar('blKlarnaEnableAnonymization', 1, 'bool');
        $this->setModuleConfVar('iKlarnaValidation', 1, 'bool');
        $this->setModuleMode('KP');

        $orderLines = $this->getOrderLinesData(1);
        $ids        = ['ed6573c0259d6a6fb641d106dcb2faec'];
        /** @var Basket|KlarnaBasket $oBasket */
        $oBasket = oxNew(Basket::class);
        $this->setUpBasket($oBasket, $ids);

        $result = $oBasket->getKlarnaOrderLines();

        $this->assertArrayHasKey('order_lines', $result);
        $this->assertNotEmpty($result['order_lines']);
        $this->assertEquals($orderLines, $result);

        $this->setModuleMode('KCO');
        $this->setModuleConfVar('blKlarnaEnableAnonymization', 0, 'bool');
    }


    public function testGetKlarnaOrderLines_VouchersAndDiscounts()
    {
        $oDiscount = oxNew(Discount::class);
        $oDiscount->load('9fc3e801da9cdd0b2.74513077');
        $oDiscount->oxdiscount__oxactive = new Field(1, Field::T_RAW);
        $oDiscount->save();

        $ids         = ['ed6573c0259d6a6fb641d106dcb2faec'];
        /** @var Basket|KlarnaBasket $oBasket */
        $oBasket = oxNew(Basket::class);
        $this->setUpBasket($oBasket, $ids);

        $oOrder                      = $this->getMockBuilder(Order::class)->setMethods(['load'])->getMock();
        $oOrder->oxorder__oxdiscount = new Field(100, Field::T_RAW);
        UtilsObject::setClassInstance(Order::class, $oOrder);


        $orderLines = $this->getOrderLinesData(0);
        // voucher discount
        array_pop($orderLines['order_lines']);         // remove delivery
        $orderLines['order_lines'][] = [
            'type'             => 'discount',
            'reference'        => 'SRV_COUPON',
            'name'             => 'Gutschein Rabatt',
            'quantity'         => 1,
            'total_amount'     => -2000,
            'total_tax_amount' => -319,
            'unit_price'       => -2000,
            'tax_rate'         => 1900

        ];
        $orderLines['order_lines'][] = [
            'type'             => 'discount',
            'reference'        => 'SRV_DISCOUNT',
            'name'             => 'Rabatt',
            'quantity'         => 1,
            'total_amount'     => -3290,
            'total_tax_amount' => -525,
            'unit_price'       => -3290,
            'tax_rate'         => 1900,
        ];

        $orderLines['order_amount']     = 27610;
        $orderLines['order_tax_amount'] = 4409;

        $this->addVouchersData();
        $oBasket->addVoucher('111');
        $result = $oBasket->getKlarnaOrderLines();
        $this->assertEquals($orderLines, $result);

        $this->removeVouchersData();
        $oDiscount->oxdiscount__oxactive = new Field(0, Field::T_RAW);
        $oDiscount->save();
    }

    public function testGetKlarnaOrderLines_ToLargeException()
    {
        $ids = ['very_expensive'];
        /** @var Basket|KlarnaBasket $oBasket */
        $oBasket = oxNew(Basket::class);
        $this->setUpBasket($oBasket, $ids);
        $this->expectException(KlarnaBasketTooLargeException::class);

        $oBasket->getKlarnaOrderLines();
    }

    protected function setUpBasket($oBasket, $productsIds)
    {
        $oBasket->setDiscountCalcMode(true);
        $this->setConfigParam('blAllowUnevenAmounts', true);
        foreach ($productsIds as $id) {
            $oBasket->addToBasket($id, 1);
        }
        $oBasket->calculateBasket(true);

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

    public function KlarnaPaymentDeliveryDataProvider()
    {
        $oOrder = $this->getMockBuilder(Order::class)->setMethods(['isKCO', 'getFieldData'])->getMock();
        $oOrder->expects($this->once())->method('isKCO')->willReturn(true);
        $oOrder->expects($this->once())->method('getFieldData')->willReturn('asdf');

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
            [1.3, true], [12, false], ['zzzz', false],
        ];
    }

    /**
     * @dataProvider is_fractionDataProvider
     * @param $val
     */
    public function testIs_fraction($val, $eResult)
    {
        $oBasket = oxNew(Basket::class);
        $result  = $oBasket->is_fraction($val);
        $this->assertEquals($eResult, $result);
    }


    protected function addVouchersData()
    {
        $this->removeVouchersData();

        $sShopIdValues = ShopIdCalculator::BASE_SHOP_ID;

        $sInsertSeries = "
        REPLACE INTO `oxvoucherseries`
        (`OXID`, `OXSHOPID`, `OXSERIENR`, `OXSERIEDESCRIPTION`, `OXDISCOUNT`, `OXDISCOUNTTYPE`, `OXBEGINDATE`, `OXENDDATE`, `OXALLOWSAMESERIES`, `OXALLOWOTHERSERIES`, `OXALLOWUSEANOTHER`, `OXMINIMUMVALUE`, `OXCALCULATEONCE`)
        VALUES
        ('test_s1',$sShopIdValues,'s1','regular   ','20','absolute','0000-00-00 00:00:00','0000-00-00 00:00:00',0,0,0,'0',0);";

        $sInsertVouchers = "
        REPLACE INTO `oxvouchers`
        (`OXVOUCHERSERIEID`,`OXID`, `OXDATEUSED`, `OXORDERID`, `OXUSERID`, `OXRESERVED`, `OXVOUCHERNR`, `OXDISCOUNT`)
        VALUES
        ('test_s1','test_111','0000-00-00','','',0,'111',NULL);";

        $this->addToDatabase($sInsertSeries, 'oxvoucherseries');
        $this->addToDatabase($sInsertVouchers, 'oxvouchers');

    }

    protected function removeVouchersData()
    {
        $oSerie = oxNew('oxvoucherserie');
        $oSerie->load('test_s1');

        $oSerie->delete();
    }


    protected function addWrappingAndCard(Basket $oBasket)
    {
        /** @var BasketItem $oBasketItem */
        foreach ($oBasket->getContents() as $oBasketItem) {
            $oBasketItem->setWrapping('81b40cf210343d625.49755120');
        }
        $oBasket->setCardId('81b40cf0cd383d3a9.70988998');
    }


    /**
     *
     */
    public function testGetKlarnaOrderLines_WrappingAndCards()
    {
        // prepare expected result
        $order_lines = $this->getOrderLinesData(0);
        array_pop($order_lines['order_lines']);         // remove delivery
        $order_lines['order_lines'][]    = [                      // wrapping
                                                                  'type'                  => 'physical',
                                                                  'reference'             => 'SRV_WRAPPING',
                                                                  'name'                  => 'Geschenkverpackung',
                                                                  'quantity'              => 1,
                                                                  'total_amount'          => 295,
                                                                  'total_discount_amount' => 0,
                                                                  'total_tax_amount'      => 47,
                                                                  'unit_price'            => 295,
                                                                  'tax_rate'              => 1900,
        ];
        $order_lines['order_lines'][]    = [                      // gift card
                                                                  'type'                  => 'physical',
                                                                  'reference'             => 'SRV_GIFTCARD',
                                                                  'name'                  => 'Grußkarte',
                                                                  'quantity'              => 1,
                                                                  'total_amount'          => 300,
                                                                  'total_discount_amount' => 0,
                                                                  'total_tax_amount'      => 48,
                                                                  'unit_price'            => 300,
                                                                  'tax_rate'              => 1900,
        ];
        $order_lines['order_amount']     = 33495;
        $order_lines['order_tax_amount'] = 5348;

        $ids = ['ed6573c0259d6a6fb641d106dcb2faec'];
        /** @var Basket|KlarnaBasket $oBasket */
        $oBasket = oxNew(Basket::class);
        $this->setUpBasket($oBasket, $ids);
        $this->addWrappingAndCard($oBasket);
        $result = $oBasket->getKlarnaOrderLines();
        $this->assertEquals($order_lines, $result);
    }


    /**
     * @dataProvider sortDataProvider
     * @param $aVal
     * @param $bVal
     * @throws \ReflectionException
     */
    public function testSortOrderLines($aVal, $bVal, $eRes)
    {
        $oBasket = oxNew(Basket::class);
        $class   = new ReflectionClass(get_class($oBasket));
        $method  = $class->getMethod('sortOrderLines');
        $method->setAccessible(true);

        $oBasketItem = $this->getMockBuilder(BasketItem::class)->setMethods(['getArticle', 'getId'])->getMock();
        $oArticle    = oxNew(Article::class);
        $aArt        = clone $oArticle;
        $aBrt        = clone $oArticle;
        $this->setProtectedClassProperty($aArt, '_sOXID', $aVal);
        $this->setProtectedClassProperty($aBrt, '_sOXID', $bVal);

        $a = clone $oBasketItem;
        $a->expects($this->any())->method('getArticle')->willReturn($aArt);
        $b = clone $oBasketItem;
        $b->expects($this->any())->method('getArticle')->willReturn($aBrt);

        $result = $method->invokeArgs($oBasket, [$a, $b]);
        $this->assertEquals($eRes, $result);
    }

    public function sortDataProvider()
    {
        return [
            ['200', '100', 1],
            ['100', '200', -1],
            ['200', '200', 0],
        ];
    }

    public function test_addGiftWrappingCostFractionVat()
    {
        $oWrappingCost = $this->getMockBuilder(Price::class)
            ->setMethods(['getPrice', 'getBruttoPrice', 'getVatValue', 'getVat'])
            ->getMock();
        $oWrappingCost->expects($this->once())->method('getPrice')->willReturn(100);
        $oWrappingCost->expects($this->once())->method('getBruttoPrice')->willReturn(100);
        $oWrappingCost->expects($this->once())->method('getVatValue')->willReturn(10);
        $oWrappingCost->expects($this->once())->method('getVat')->willReturn(10);

        $oBasket = $this->getMockBuilder(Basket::class)
            ->setMethods(['getWrappingCost', 'getOrderVatAverage'])
            ->getMock();
        $oBasket->expects($this->once())->method('getWrappingCost')->willReturn($oWrappingCost);
        $oBasket->expects($this->once())->method('getOrderVatAverage')->willReturn(7.97);


        $class  = new \ReflectionClass(KlarnaBasket::class);
        $method = $class->getMethod('_addGiftWrappingCost');
        $method->setAccessible(true);

        $method->invoke($oBasket);
        $expected = [
            [
                'type'                  => 'physical',
                'reference'             => 'SRV_WRAPPING',
                'name'                  => 'Geschenkverpackung',
                'quantity'              => 1,
                'total_amount'          => 10000,
                'total_discount_amount' => 0,
                'total_tax_amount'      => 1000,
                'unit_price'            => 10000,
                'tax_rate'              => 1000,
            ],
        ];

        $this->assertEquals($expected, $this->getProtectedClassProperty($oBasket, 'klarnaOrderLines'));
    }

    public function test_sortOrderLines()
    {
        $oBasket     = oxNew(KlarnaBasket::class);
        $articleA = $this->getMockBuilder(Article::class)->setMethods(['getId'])->getMock();
        $articleA->expects($this->once())->method('getId')->willReturn('nvuiadnrv8974ht2151');
        $articleB = $this->getMockBuilder(Article::class)->setMethods(['getId'])->getMock();
        $articleB->expects($this->once())->method('getId')->willReturn('vnoruinpq57gh1shy26');
        $notArticleA = $this->getMockBuilder(BasketItem::class)->setMethods(['getArticle'])->getMock();
        $notArticleA->expects($this->once())->method('getArticle')->willReturn($articleA);
        $notArticleB = $this->getMockBuilder(BasketItem::class)->setMethods(['getArticle'])->getMock();
        $notArticleB->expects($this->once())->method('getArticle')->willReturn($articleB);
        $basketItemA = $this->getMockBuilder(BasketItem::class)->setMethods(['getArticle'])->getMock();
        $basketItemA->expects($this->once())->method('getArticle')->willReturn($notArticleA);
        $basketItemB = $this->getMockBuilder(BasketItem::class)->setMethods(['getArticle'])->getMock();
        $basketItemB->expects($this->once())->method('getArticle')->willReturn($notArticleB);

        $class  = new \ReflectionClass(KlarnaBasket::class);
        $method = $class->getMethod('sortOrderLines');
        $method->setAccessible(true);

        $result = $method->invokeArgs($oBasket, [$basketItemA, $basketItemB]);
        $this->assertEquals(1, $result);
    }
}
