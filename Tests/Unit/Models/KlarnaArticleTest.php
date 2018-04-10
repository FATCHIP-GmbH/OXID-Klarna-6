<?php

namespace TopConcepts\Klarna\Tests\Unit\Models;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Manufacturer;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Models\KlarnaArticle;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaArticleTest extends ModuleUnitTestCase
{

    public function testKl_getArticleUrl()
    {
        $articleClass = oxNew(Article::class);

        $result = $articleClass->kl_getArticleUrl();
        $this->assertNotNull($result);

        $article = $this->createStub(KlarnaArticle::class, ['getLink' => null]);

        $result = $article->kl_getArticleUrl();

        $this->assertNull($result);

        $this->setModuleConfVar('blKlarnaSendProductUrls', false, 'bool');

        $result = $article->kl_getArticleUrl();

        $this->assertNull($result);

        //revert to default state
        $this->setModuleConfVar('blKlarnaSendProductUrls', true, 'bool');

    }

    public function testKl_getArticleCategoryPath()
    {
        $articleClass = oxNew(Article::class);
        $articleClass->setId('adc5ee42bd3c37a27a488769d22ad9ed');

        $this->setModuleConfVar('blKlarnaEnableAnonymization', true, 'bool');
        $result = $articleClass->kl_getArticleCategoryPath();
        $this->assertNull($result);

        $this->setModuleConfVar('blKlarnaEnableAnonymization', false, 'bool');
        $result = $articleClass->kl_getArticleCategoryPath();
        $this->assertEquals($result, 'Angebote');

        $article = $this->createStub(KlarnaArticle::class, ['getCategory' => false]);
        $result = $article->kl_getArticleCategoryPath();

        $this->assertNull($result);
    }

    public function testKl_getArticleManufacturer()
    {
        $articleClass = oxNew(Article::class);
        $result = $articleClass->kl_getArticleManufacturer();
        $this->setModuleConfVar('blKlarnaEnableAnonymization', true, 'bool');

        $this->assertNull($result);

        $this->setModuleConfVar('blKlarnaEnableAnonymization', false, 'bool');
        $article = $this->createStub(KlarnaArticle::class, ['getManufacturer' => false]);
        $result = $article->kl_getArticleManufacturer();
        $this->assertNull($result);

        $manufacturer = $this->createStub(Manufacturer::class, ['getTitle' => 'test']);
        $article = $this->createStub(KlarnaArticle::class, ['getManufacturer' => $manufacturer]);
        $result = $article->kl_getArticleManufacturer();
        $this->assertEquals($result, 'test');

    }

    public function testKl_getArticleImageUrl()
    {
        $articleClass = oxNew(Article::class);
        $result = $articleClass->kl_getArticleImageUrl();
        $this->assertNotNull($result);

        $this->setModuleConfVar('blKlarnaSendImageUrls', false, 'bool');
        $result = $articleClass->kl_getArticleImageUrl();
        $this->assertNull($result);

        //revert to default state
        $this->setModuleConfVar('blKlarnaSendImageUrls', true, 'bool');

    }

    public function orderArticleNameDataProvider()
    {
        return [
            [true, null, 'Produktname ', null],
            [true, 'test', 'Produktname test', null],
            [true, 'test', 'Product name test', 1],
            [false, null, '(no title)', null],
            [false, null, '(no title)', 1],

        ];
    }

    /**
     * @dataProvider orderArticleNameDataProvider
     * @param $configValue
     * @param $aticleName
     * @param $expectedResult
     * @param $iOrderLang
     */
    public function testKl_getOrderArticleName($configValue, $aticleName, $expectedResult, $iOrderLang)
    {

        $this->setModuleConfVar('blKlarnaEnableAnonymization', $configValue, 'bool');
        $articleClass = oxNew(Article::class);
        $result = $articleClass->kl_getOrderArticleName($aticleName, $iOrderLang);

        $this->assertEquals($result, $expectedResult);

        if ($configValue == false) {
            $parent = oxNew(Article::class);
            $parent->load('adc5ee42bd3c37a27a488769d22ad9ed');

            $article = $this->createStub(
                KlarnaArticle::class,
                ['getParentArticle' => $parent, 'getViewName' => $parent->getViewName()]
            );
            $result = $article->kl_getOrderArticleName(null, $iOrderLang);

            $this->assertEquals($result, $expectedResult);
        }

    }

    public function testKl_getArticleEAN()
    {
        $articleClass = oxNew(Article::class);
        $articleClass->oxarticles__oxean = new Field('test', Field::T_RAW);

        $result = $articleClass->kl_getArticleEAN();
        $this->assertEquals('test', $result);

        $this->setModuleConfVar('blKlarnaEnableAnonymization', true, 'bool');
        $result = $articleClass->kl_getArticleEAN();
        $this->assertNull($result);

        //revert to default state
        $this->setModuleConfVar('blKlarnaEnableAnonymization', false, 'bool');
    }

    public function testKlarna_loadByArtNum()
    {
        $articleClass = oxNew(Article::class);
        $this->assertNull($articleClass->getId());
        $articleClass->klarna_loadByArtNum(3102);
        $this->assertNotNull($articleClass->getId());

        $result = $articleClass->klarna_loadByArtNum(50);
        $this->assertFalse($result);

    }

    public function goodStockDataProvider()
    {
        return [
            [0,1, true],
            [0,0, false],
            [1,4, false],
            [1,0, false],
        ];
    }

    /**
     * @dataProvider goodStockDataProvider
     * @param $oxstock
     * @param $oxstockflag
     * @param $expected
     */
    public function testIsGoodStockForExpireCheck($oxstock,$oxstockflag, $expected)
    {
        $article = oxNew(Article::class);

        $article->oxarticles__oxstock = new Field($oxstock, Field::T_RAW);
        $article->oxarticles__oxstockflag = new Field($oxstockflag, Field::T_RAW);
        $result = $article->isGoodStockForExpireCheck();

        $this->assertEquals($result, $expected);

    }

    public function testKl_getArticleMPN()
    {
        $articleClass = oxNew(Article::class);
        $articleClass->oxarticles__oxmpn = new Field('test', Field::T_RAW);

        $result = $articleClass->kl_getArticleMPN();
        $this->assertEquals('test', $result);

        $this->setModuleConfVar('blKlarnaEnableAnonymization', true, 'bool');
        $result = $articleClass->kl_getArticleMPN();
        $this->assertNull($result);

        //revert to default state
        $this->setModuleConfVar('blKlarnaEnableAnonymization', false, 'bool');
    }
}
