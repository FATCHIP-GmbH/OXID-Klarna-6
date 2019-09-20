<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaUtilsTest extends ModuleUnitTestCase
{

    public function testCalculateOrderAmountsPricesAndTaxes() {
        $expected = [
            0,
            0,
            0,
            0,
            10000,
            0,
            "pcs",
        ];

        $price = $this->getMockBuilder(Price::class)->setMethods(['getVat'])->getMock();
        $price->expects($this->once())->method('getVat')->willReturn(100);

        $item = $this->getMockBuilder(BasketItem::class)->setMethods(['isBundle', 'getUnitPrice'])->getMock();
        $item->expects($this->exactly(2))->method('isBundle')->willReturn(true);
        $item->expects($this->once())->method('getUnitPrice')->willReturn($price);
        $result = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($item, false);
        $this->assertEquals($expected, $result);
    }

    public function testCalculateOrderAmountsPricesAndTaxes_1()
    {
        $price = $this->getMockBuilder(Price::class)->setMethods(['getVat', 'getBruttoPrice'])->getMock();
        $price->expects($this->any())->method('getBruttoPrice')->willReturn(20);
        $price->expects($this->any())->method('getVat')->willReturn(2);
        $priceUnit = $this->getMockBuilder(Price::class)->setMethods(['getBruttoPrice'])->getMock();
        $priceUnit->expects($this->any())->method('getBruttoPrice')->willReturn(10);
        $article = $this->getMockBuilder(Article::class)->setMethods(['getUnitPrice'])->getMock();
        $article->expects($this->once())->method('getUnitPrice')->willReturn($price);
        $item = $this->getMockBuilder(BasketItem::class)
            ->setMethods(['isBundle', 'getUnitPrice', 'getArticle', 'getRegularUnitPrice'])
            ->getMock();
        $item->expects($this->exactly(2))->method('isBundle')->willReturn(false);
        $item->expects($this->once())->method('getUnitPrice')->willReturn($price);
        $item->expects($this->once())->method('getArticle')->willReturn($article);
        $item->expects($this->once())->method('getRegularUnitPrice')->willReturn($priceUnit);
        $result = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($item, true);
        $expected = [
            0,
            1000,
            0,
            0,
            200,
            0,
            "pcs",
        ];
        $this->assertEquals($expected, $result);
    }

    public function testCalculateOrderAmountsPricesAndTaxes_2()
    {
        $item = $this->getMockBuilder(BasketItem::class)
            ->setMethods(['isBundle', 'getUnitPrice', 'getRegularUnitPrice'])
            ->getMock();
        $item->expects($this->exactly(2))->method('isBundle')->willReturn(false);
        $price = $this->getMockBuilder(Price::class)->setMethods(['getVat', 'getBruttoPrice'])->getMock();
        $price->expects($this->once())->method('getBruttoPrice')->willReturn(20);
        $price->expects($this->any())->method('getVat')->willReturn(7);
        $priceUnit = $this->getMockBuilder(Price::class)->setMethods(['getBruttoPrice'])->getMock();
        $priceUnit->expects($this->any())->method('getBruttoPrice')->willReturn(10);
        $item->expects($this->any())->method('getUnitPrice')->willReturn($price);
        $item->expects($this->once())->method('getRegularUnitPrice')->willReturn($priceUnit);

        $result = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($item, false);
        $expected = [
            0,
            1000,
            0,
            0,
            700,
            0,
            "pcs",
        ];
        $this->assertEquals($expected, $result);
    }

    public function testIsNonKlarnaCountryActive()
    {
        $list = $this->getMockBuilder(CountryList::class)->setMethods(['loadActiveNonKlarnaCheckoutCountries'])->getMock();
        $list->expects($this->any())->method('loadActiveNonKlarnaCheckoutCountries')->willReturn([null]);
        Registry::set(CountryList::class, $list);
        $result = KlarnaUtils::isNonKlarnaCountryActive();
        $this->assertFalse($result);

        $this->setProtectedClassProperty($list, '_aArray', ['test1', 'test2']);
        $result = KlarnaUtils::isNonKlarnaCountryActive();
        $this->assertTrue($result);

    }

    public function testGetSubCategoriesArray()
    {
        $categoryParent = $this->getMockBuilder(Category::class)
            ->setMethods(['getTitle', 'getParentCategory'])->getMock();
        $category = clone $categoryParent;
        $categoryParent->expects($this->once())->method('getTitle')->willReturn('parentTitle');
        $category->expects($this->once())->method('getTitle')->willReturn('category');
        $category->expects($this->once())->method('getParentCategory')->willReturn($categoryParent);

        $result = KlarnaUtils::getSubCategoriesArray($category, ['test' => 'test']);
        $expected = [
            'test' => 'test',
            'category',
            'parentTitle',
        ];
        $this->assertEquals($expected, $result);

    }

    public function testIsCountryActiveInKlarnaCheckout()
    {
        $list = $this->getMockBuilder(CountryList::class)->setMethods(['loadActiveKlarnaCheckoutCountries'])->getMock();
        $list->expects($this->once())->method('loadActiveKlarnaCheckoutCountries')->willReturn([null]);
        $this->setProtectedClassProperty($list, '_aArray', []);
        Registry::set(CountryList::class, $list);
        $result = KlarnaUtils::isCountryActiveInKlarnaCheckout('invalid');
        $this->assertFalse($result);
    }
}
