<?php

namespace TopConcepts\Klarna\Tests\Unit\Model;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Manufacturer;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Model\KlarnaArticle;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaArticleTest extends ModuleUnitTestCase
{

    public function testTcklarna_getArticleUrl()
    {
        $articleClass = oxNew(Article::class);

        $result = $articleClass->tcklarna_getArticleUrl();
        $this->assertNotNull($result);

        $article = $this->createStub(KlarnaArticle::class, ['getLink' => null]);

        $result = $article->tcklarna_getArticleUrl();

        $this->assertNull($result);

        $this->setModuleConfVar('blKlarnaSendProductUrls', false, 'bool');

        $result = $article->tcklarna_getArticleUrl();

        $this->assertNull($result);

        //revert to default state
        $this->setModuleConfVar('blKlarnaSendProductUrls', true, 'bool');

    }

    public function testTcklarna_getArticleCategoryPath()
    {
        $articleClass = oxNew(Article::class);
        $articleClass->setId('adc5ee42bd3c37a27a488769d22ad9ed');

        $this->setModuleConfVar('blKlarnaEnableAnonymization', true, 'bool');
        $result = $articleClass->tcklarna_getArticleCategoryPath();
        $this->assertNull($result);

        $this->setModuleConfVar('blKlarnaEnableAnonymization', false, 'bool');
        $result = $articleClass->tcklarna_getArticleCategoryPath();
        $this->assertEquals($result, 'Angebote');

        $article = $this->createStub(KlarnaArticle::class, ['getCategory' => false]);
        $result = $article->tcklarna_getArticleCategoryPath();

        $this->assertNull($result);
    }

    public function testTcklarna_getArticleManufacturer()
    {
        $articleClass = oxNew(Article::class);
        $result = $articleClass->tcklarna_getArticleManufacturer();
        $this->setModuleConfVar('blKlarnaEnableAnonymization', true, 'bool');

        $this->assertNull($result);

        $this->setModuleConfVar('blKlarnaEnableAnonymization', false, 'bool');
        $article = $this->createStub(KlarnaArticle::class, ['getManufacturer' => false]);
        $result = $article->tcklarna_getArticleManufacturer();
        $this->assertNull($result);

        $manufacturer = $this->createStub(Manufacturer::class, ['getTitle' => 'test']);
        $article = $this->createStub(KlarnaArticle::class, ['getManufacturer' => $manufacturer]);
        $result = $article->tcklarna_getArticleManufacturer();
        $this->assertEquals($result, 'test');

    }

    public function testTcklarna_getArticleImageUrl()
    {
        $articleClass = oxNew(Article::class);
        $result = $articleClass->tcklarna_getArticleImageUrl();
        $this->assertNotNull($result);

        $this->setModuleConfVar('blKlarnaSendImageUrls', false, 'bool');
        $result = $articleClass->tcklarna_getArticleImageUrl();
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
    public function testTcklarna_getOrderArticleName($configValue, $aticleName, $expectedResult, $iOrderLang)
    {

        $this->setModuleConfVar('blKlarnaEnableAnonymization', $configValue, 'bool');
        $articleClass = oxNew(Article::class);
        $result = $articleClass->tcklarna_getOrderArticleName($aticleName, $iOrderLang);

        $this->assertEquals($result, $expectedResult);

        if ($configValue == false) {
            $parent = oxNew(Article::class);
            $parent->load('adc5ee42bd3c37a27a488769d22ad9ed');

            $article = $this->createStub(
                KlarnaArticle::class,
                ['getParentArticle' => $parent, 'getViewName' => $parent->getViewName()]
            );
            $result = $article->tcklarna_getOrderArticleName(null, $iOrderLang);

            $this->assertEquals($result, $expectedResult);
        }

    }

    public function testTcklarna_getArticleEAN()
    {
        $articleClass = oxNew(Article::class);
        $articleClass->oxarticles__oxean = new Field('test', Field::T_RAW);

        $result = $articleClass->tcklarna_getArticleEAN();
        $this->assertEquals('test', $result);

        $this->setModuleConfVar('blKlarnaEnableAnonymization', true, 'bool');
        $result = $articleClass->tcklarna_getArticleEAN();
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

        $result = $articleClass->klarna_loadByArtNum('adc5ee42bd3c37a27a488769d22ad9edadc5ee42bd3c37a27a488769d22ad9ed');//64chars
        $this->assertFalse($result);

        $this->setModuleConfVar('blKlarnaEnableAnonymization', true, 'bool');
        $result = $articleClass->klarna_loadByArtNum(50);//non existant
        $this->assertFalse($result);

        //revert to default state
        $this->setModuleConfVar('blKlarnaEnableAnonymization', false, 'bool');
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

    public function testTcklarna_getArticleMPN()
    {
        $articleClass = oxNew(Article::class);
        $articleClass->oxarticles__oxmpn = new Field('test', Field::T_RAW);

        $result = $articleClass->tcklarna_getArticleMPN();
        $this->assertEquals('test', $result);

        $this->setModuleConfVar('blKlarnaEnableAnonymization', true, 'bool');
        $result = $articleClass->tcklarna_getArticleMPN();
        $this->assertNull($result);

        //revert to default state
        $this->setModuleConfVar('blKlarnaEnableAnonymization', false, 'bool');
    }
}
