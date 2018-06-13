<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 09.04.2018
 * Time: 17:16
 */

namespace TopConcepts\Klarna\Testes\Unit\Models;

use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderArticleTest extends ModuleUnitTestCase
{

    public function testTcklarna_setArtNum()
    {
        $testVal = 'string-value';
        $expectedResult = md5($testVal);
        $oOrderArticle = oxNew(OrderArticle::class);
        $oOrderArticle->oxorderarticles__oxartnum = new Field($testVal, Field::T_RAW);
        $oOrderArticle->tcklarna_setArtNum();
        $this->assertEquals($expectedResult, $oOrderArticle->oxorderarticles__tcklarna_artnum->value);
    }

    /**
     * @dataProvider setTitleDataProvider
     * @param $iLang
     * @param $expectedResult
     */
    public function testTcklarna_setTitle($iLang, $expectedResult)
    {
        $oOrderArticle = oxNew(OrderArticle::class);
        $oOrderArticle->tcklarna_setTitle(0, $iLang);
        $this->assertEquals($expectedResult, $oOrderArticle->oxorderarticles__tcklarna_title->value);

    }

    public function testGetAmount()
    {
        $oOrderArticle = oxNew(OrderArticle::class);
        $oOrderArticle->oxorderarticles__oxamount = new Field(3, Field::T_RAW);

        $result = $oOrderArticle->getAmount();

        $this->assertEquals(3, $result);
    }

    public function setTitleDataProvider()
    {
        return [
            [0,'Produktname 0'],
            [1,'Product name 0']

        ];
    }

//    public function testGetUnitPrice()
//    {
//
//    }
//
//    public function testGetRegularUnitPrice()
//    {
//
//    }
}
