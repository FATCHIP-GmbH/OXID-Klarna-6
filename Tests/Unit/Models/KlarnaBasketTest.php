<?php

namespace TopConcepts\Klarna\Tests\Unit\Models;


use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
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
//        $oBasket = oxNew(Basket::class);
//
//        $result = $oBasket->kl_calculateDeliveryCost();
//        var_dump($result);
//        die;
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
        $oDeliverySet = $this->createStub(DeliverySet::class,
            ['getFieldData' => $name]
        );
        $oOrder       = $order;
        $oBasket      = oxNew(Basket::class);

        $result = $oBasket->getKlarnaPaymentDelivery($oPrice, $oOrder, $oDeliverySet);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetKlarnaOrderLines()
    {

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
}
