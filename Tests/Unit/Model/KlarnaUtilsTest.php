<?php

namespace TopConcepts\Klarna\Tests\Unit\Model;

use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Core\Price;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaUtilsTest extends ModuleUnitTestCase
{

    public function testCalculateOrderAmountsPricesAndTaxes()
    {
        $expected = [
            0,
            0,
            0,
            0,
            10000,
            0,
            "pcs",
        ];

        $price = $this->createStub(Price::class, ['getVat' => 100]);
        $item = $this->createStub(BasketItem::class, ['isBundle' => true, 'getUnitPrice' => $price]);
        $result = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($item);
        $this->assertEquals($expected, $result);

        $price = $this->createStub(Price::class, ['getVat' => 100, 'getBruttoPrice' => 20]);
        $priceUnit = $this->createStub(Price::class, ['getVat' => 100, 'getBruttoPrice' => 10]);
        $item = $this->createStub(
            BasketItem::class,
            ['isBundle' => false, 'getUnitPrice' => $price, 'getRegularUnitPrice' => $priceUnit]
        );
        $result = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($item);
        $expected = [
            0,
            1000,
            0,
            0,
            10000,
            0,
            "pcs",
        ];
        $this->assertEquals($expected, $result);


    }

    public function testIsNonKlarnaCountryActive()
    {
        $list = $this->createStub(CountryList::class, ['loadActiveNonKlarnaCheckoutCountries' => [null]]);
        \oxTestModules::addModuleObject(CountryList::class, $list);
        $result = KlarnaUtils::isNonKlarnaCountryActive();
        $this->assertFalse($result);

        $this->setProtectedClassProperty($list, '_aArray', ['test1', 'test2']);
        \oxTestModules::addModuleObject(CountryList::class, $list);
        $result = KlarnaUtils::isNonKlarnaCountryActive();
        $this->assertTrue($result);

    }

    public function testGetSubCategoriesArray()
    {
        $categoryParent = $this->createStub(Category::class, ['getTitle' => 'parentTitle']);
        $category = $this->createStub(
            Category::class,
            ['getTitle' => 'category', 'getParentCategory' => $categoryParent]
        );
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
        $list = $this->createStub(CountryList::class, ['loadActiveKlarnaCheckoutCountries' => [null]]);
        $this->setProtectedClassProperty($list, '_aArray', []);
        \oxTestModules::addModuleObject(CountryList::class, $list);
        $result = KlarnaUtils::isCountryActiveInKlarnaCheckout('invalid');
        $this->assertFalse($result);
    }
}
