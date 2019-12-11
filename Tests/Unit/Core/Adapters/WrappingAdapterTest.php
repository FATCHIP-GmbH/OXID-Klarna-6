<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\Adapters;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Wrapping;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Adapters\WrappingAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class WrappingAdapterTest extends ModuleUnitTestCase
{

    public function testGetName()
    {
        $adapter = $this->getMockBuilder(WrappingAdapter::class)->disableOriginalConstructor()->setMethods(
            ['getKlarnaType']
        )->getMock();

        $prepareItemData = self::getMethod('getName', WrappingAdapter::class);
        $result = $prepareItemData->invokeArgs($adapter, []);

        $this->assertSame(
            Registry::getLang()->translateString(WrappingAdapter::NAME), $result);
    }

    public function testGetReference()
    {
        $adapter = $this->getMockBuilder(WrappingAdapter::class)->disableOriginalConstructor()->setMethods(
            ['getKlarnaType']
        )->getMock();

        $prepareItemData = self::getMethod('getReference', WrappingAdapter::class);
        $result = $prepareItemData->invokeArgs($adapter, []);

        $this->assertSame(WrappingAdapter::REFERENCE, $result);
    }

    public function testValidateItem()
    {
        $adapter = $this->getMockBuilder(WrappingAdapter::class)->disableOriginalConstructor()->setMethods(
            ['addItemToOrderLines'])->getMock();

        $basketItem = $this->getMockBuilder(BasketItem::class)->disableOriginalConstructor()->
        setMethods(['getPrice'])->getMock();

        $price = $this->getMockBuilder(Price::class)
            ->setMethods(['getBruttoPrice'])
            ->getMock();

        $price->expects($this->any())->method('getBruttoPrice')->willReturn(10);
        $basketItem->expects($this->any())->method('getPrice')->willReturn($price);

        $this->setProtectedClassProperty($adapter, "oItem", $basketItem);
        $orderLine = ["total_amount" => 1000];

        $adapter->validateItem($orderLine);

        $this->expectException(InvalidItemException::class);
        $orderLine = ["total_amount" => 0];
        $adapter->validateItem($orderLine);
    }

    public function testPrepareItemData()
    {
        $adapterBuilder = $this->getMockBuilder(WrappingAdapter::class);
        $adapterBuilder->setMethods(['getKlarnaType', 'getReference', 'getName']);
        $adapter = $adapterBuilder->disableOriginalConstructor()->getMockForAbstractClass();
        $adapter->expects($this->any())->method('getKlarnaType')->willReturn('type');
        $adapter->expects($this->any())->method('getReference')->willReturn('reference');
        $adapter->expects($this->any())->method('getName')->willReturn('name');

        $this->prepareWrapping($adapter);
        $prepareItemData = self::getMethod('prepareItemData', WrappingAdapter::class);
        $prepareItemData->invokeArgs($adapter, ['DE']);

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
            'image_url' => 'url',
        ];

        $result = $this->getProtectedClassProperty($adapter, "itemData");
        $this->assertSame($expected, $result);
    }

    protected function prepareWrapping($adapter) {

        $wrapping = $this->getMockBuilder(Wrapping::class)->disableOriginalConstructor()->setMethods(
            ['getPictureUrl', 'getWrappingPrice']
        )->getMock();

        $price = $this->getMockBuilder(Price::class)
            ->setMethods(['getBruttoPrice', 'getVat'])
            ->getMock();

        $price->expects($this->any())->method('getBruttoPrice')->willReturn(10);
        $price->expects($this->any())->method('getVat')->willReturn(0.23);

        $wrapping->expects($this->any())->method('getPictureUrl')->willReturn('url');
        $wrapping->expects($this->any())->method('getWrappingPrice')->willReturn($price);


        $basketItem = $this->getMockBuilder(BasketItem::class)->disableOriginalConstructor()->setMethods(
            ['getWrapping','getAmount']
        )->getMock();

        $basketItem->expects($this->once())->method('getWrapping')->willReturn($wrapping);
        $basketItem->expects($this->once())->method('getAmount')->willReturn(1);

        $this->setProtectedClassProperty($adapter, "oItem", $basketItem);


    }

}