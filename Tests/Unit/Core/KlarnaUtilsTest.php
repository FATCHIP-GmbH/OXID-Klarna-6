<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Core\Price;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

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
        $result = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($item, false);
        $this->assertEquals($expected, $result);

        $price = $this->createStub(Price::class, ['getVat' => 100, 'getBruttoPrice' => 20]);
        $priceUnit = $this->createStub(Price::class, ['getVat' => 100, 'getBruttoPrice' => 10]);

        $article = $this->createStub(Article::class, ['getUnitPrice' => $price]);

        $item = $this->createStub(
            BasketItem::class,
            ['isBundle' => false, 'getUnitPrice' => $price, 'getArticle' => $article, 'getRegularUnitPrice' => $priceUnit]
        );
        $result = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($item, true);
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


        $item = $this->createStub(
            BasketItem::class,
            ['isBundle' => false, 'getUnitPrice' => $price, 'getRegularUnitPrice' => $priceUnit]
        );
        $result = KlarnaUtils::calculateOrderAmountsPricesAndTaxes($item, false);
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
        UtilsObject::setClassInstance(CountryList::class, $list);
        $result = KlarnaUtils::isNonKlarnaCountryActive();
        $this->assertFalse($result);

        $this->setProtectedClassProperty($list, '_aArray', ['test1', 'test2']);
        UtilsObject::setClassInstance(CountryList::class, $list);
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
        UtilsObject::setClassInstance(CountryList::class, $list);
        $result = KlarnaUtils::isCountryActiveInKlarnaCheckout('invalid');
        $this->assertFalse($result);
    }
}
