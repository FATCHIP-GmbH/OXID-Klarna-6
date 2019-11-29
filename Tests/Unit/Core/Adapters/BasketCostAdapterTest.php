<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\Adapters;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Price;
use PHPUnit_Framework_MockObject_MockBuilder;
use TopConcepts\Klarna\Core\Adapters\BasketCostAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class BasketCostAdapterTest extends ModuleUnitTestCase
{
    public function testValidateItem()
    {
        $adapter = $this->getMockBuilder(BasketCostAdapter::class)->disableOriginalConstructor()->getMockForAbstractClass();

        $this->prepareBasket($adapter);
        $orderLine = ["total_amount" => 1000];

        $adapter->validateItem($orderLine);

        $this->expectException(InvalidItemException::class);
        $orderLine = ["total_amount" => 0];
        $adapter->validateItem($orderLine);
    }

    public function testPrepareItemData()
    {
        $adapterBuilder = $this->getMockBuilder(BasketCostAdapter::class);
        $adapterBuilder->setMethods(['getKlarnaType', 'getReference', 'getName']);
        $adapter = $adapterBuilder->disableOriginalConstructor()->getMockForAbstractClass();
        $adapter->expects($this->any())->method('getKlarnaType')->willReturn('type');
        $adapter->expects($this->any())->method('getReference')->willReturn('reference');
        $adapter->expects($this->any())->method('getName')->willReturn('name');

        $this->prepareBasket($adapter);
        $adapter->prepareItemData("DE");

        $expected = [
            'type' => 'type',
            'reference' => 'reference',
            'name' => 'name',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 23,
            'total_amount' => 1000,
            'total_discount_amount' => 0,
            'total_tax_amount' => 2,
        ];

        $result = $this->getProtectedClassProperty($adapter, "itemData");
        $this->assertSame($expected, $result);
    }

    protected function prepareBasket($adapter) {
        $basketBuilder = $this->getMockBuilder(Basket::class);
        $basketBuilder->setMethods(['getCosts', 'getType']);
        $basket = $basketBuilder->getMock();

        $price = $this->getMockBuilder(Price::class)
            ->setMethods(['getBruttoPrice', 'getVat'])
            ->getMock();

        $price->expects($this->any())->method('getBruttoPrice')->willReturn(10);
        $price->expects($this->any())->method('getVat')->willReturn(0.23);

        $basket->expects($this->any())->method('getCosts')->willReturn($price);

        $this->setProtectedClassProperty($adapter, "oBasket", $basket);
    }

}