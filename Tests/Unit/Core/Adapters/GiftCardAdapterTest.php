<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\Adapters;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Wrapping;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Adapters\GiftCardAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class GiftCardAdapterTest extends ModuleUnitTestCase
{

    public function testPrepareItemData()
    {
        $adapter = $this->getMockBuilder(GiftCardAdapter::class)->disableOriginalConstructor()->setMethods(
            ['getKlarnaType']
        )->getMock();

        $this->prepareBasket($adapter, false);

        $adapter->prepareItemData('DE');
        $result = $this->getProtectedClassProperty($adapter, "itemData");
        $this->assertNull($result);

        $this->prepareBasket($adapter);

        $adapter->prepareItemData('DE');

        $result = $this->getProtectedClassProperty($adapter, "itemData");

        $this->assertNotNull($result);
    }

    protected function prepareBasket($adapter, $withCard = true)
    {
        $basketBuilder = $this->getMockBuilder(Basket::class);
        $basketBuilder->setMethods(['getCosts', 'getType', 'getCard']);
        $basket = $basketBuilder->getMock();

        $price = $this->getMockBuilder(Price::class)
            ->setMethods(['getBruttoPrice', 'getVat'])
            ->getMock();

        $price->expects($this->any())->method('getBruttoPrice')->willReturn(10);
        $price->expects($this->any())->method('getVat')->willReturn(0.23);

        $wrapping = $this->getMockBuilder(Wrapping::class)->disableOriginalConstructor()->getMock();

        if ($withCard === true) {
            $basket->expects($this->any())->method('getCard')->willReturn($wrapping);
        }

        $basket->expects($this->any())->method('getCosts')->willReturn($price);

        $this->setProtectedClassProperty($adapter, "oBasket", $basket);
    }

    public function testGetName()
    {
        $adapter = $this->getMockBuilder(GiftCardAdapter::class)->disableOriginalConstructor()->setMethods(
            ['getKlarnaType']
        )->getMock();

        $wrapping = $this->getMockBuilder(Wrapping::class)->disableOriginalConstructor()->setMethods(['getFieldData'])->getMock();
        $wrapping->expects($this->any())->method('getFieldData')->willReturn('testname');

        $this->setProtectedClassProperty($adapter, "oCard", $wrapping);

        $prepareItemData = self::getMethod('getName', GiftCardAdapter::class);
        $result = $prepareItemData->invokeArgs($adapter, []);

        $this->assertSame(Registry::getLang()->translateString("GREETING_CARD").' "testname"', $result);
    }

    public function testGetReference()
    {
        $adapter = $this->getMockBuilder(GiftCardAdapter::class)->disableOriginalConstructor()->setMethods(
            ['getKlarnaType']
        )->getMock();

        $wrapping = $this->getMockBuilder(Wrapping::class)->disableOriginalConstructor()->setMethods(['getId'])->getMock();
        $wrapping->expects($this->any())->method('getId')->willReturn('testreference');

        $this->setProtectedClassProperty($adapter, "oCard", $wrapping);

        $prepareItemData = self::getMethod('getReference', GiftCardAdapter::class);
        $result = $prepareItemData->invokeArgs($adapter, []);

        $this->assertSame('testreference', $result);
    }

    public function testValidateItem()
    {
        $adapter = $this->getMockBuilder(GiftCardAdapter::class)->disableOriginalConstructor()
            ->setMethods(['getReference'])->getMock();

        $this->prepareBasket($adapter);
        $orderLine = ["total_amount" => 1000];

        $adapter->validateItem($orderLine);

        $this->expectException(InvalidItemException::class);
        $orderLine = ["total_amount" => 0];
        $adapter->validateItem($orderLine);
    }
}