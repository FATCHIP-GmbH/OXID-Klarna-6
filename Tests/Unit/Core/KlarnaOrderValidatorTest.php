<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 13.04.2018
 * Time: 13:27
 */

namespace TopConcepts\Klarna\Tests\Unit\Core;


use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Core\KlarnaOrderValidator;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaOrderValidatorTest extends ModuleUnitTestCase
{

    public function orderDataProvider()
    {
        $homeUrl = $this->getConfigParam('sShopURL');
        $anonOn = false;

        $orderdata = [
            'order_lines' => [
                [
                    'type' => 'physical',
                    'reference' => ($anonOn ? '7b1ce3d73b70f1a7246e7b76a35fb552': '2103'),
                    'quantity' => 1,
                    'unit_price' => 32900,
                    'tax_rate' => 1900,
                    'total_amount' => 32900,
                    'total_tax_amount' => 5253,
                    'quantity_unit' => 'pcs',
                    'name' => ($anonOn ? 'Product name 1' : 'Wakeboard LIQUID FORCE GROOVE 2010'),
                    'product_url' => $homeUrl . 'index.php',
                    'image_url' => $homeUrl . 'out/pictures/generated/product/1/540_340_75/lf_groove_2010_1.jpg',
                    'product_identifiers' => [
                        'category_path' => '',
                        'global_trade_item_number' => '',
                        'manufacturer_part_number' => '',
                        'brand' => '',
                    ],
                ],
                [
                    'type' => 'shipping_fee',
                    'reference' => 'SRV_DELIVERY',
                    'name' => 'Standard',
                    'quantity' => 1,
                    'total_amount' => 0,
                    'total_discount_amount' => 0,
                    'total_tax_amount' => 0,
                    'unit_price' => 0,
                    'tax_rate' => 0,
                ],

            ],
            'order_amount' => 32900,
            'order_tax_amount' => 5253
        ];

        $empty = ['order_lines' => []];
        $validArticle = [
            'checkForStock' => true,
            'isLoaded' => true,
            'isBuyable' => true,
        ];
        $outOfStockArticle = [
            'checkForStock' => false,
            'isLoaded' => true,
            'isBuyable' => true,
        ];
        $deletedArticle = [
            'checkForStock' => true,
            'isLoaded' => false,
            'isBuyable' => true,
        ];
        $notBuyableArticle = [
            'checkForStock' => true,
            'isLoaded' => true,
            'isBuyable' => false,
        ];


        return [
            [ $orderdata, $validArticle, [], true ],
            [ $orderdata, $outOfStockArticle, ['TCKLARNA_ERROR_NOT_ENOUGH_IN_STOCK' => 'artNum'], false ],
            [ $orderdata, $deletedArticle, ['ERROR_MESSAGE_ARTICLE_ARTICLE_DOES_NOT_EXIST' => 'artNum'], false ],
            [ $orderdata, $notBuyableArticle, ['ERROR_MESSAGE_ARTICLE_ARTICLE_NOT_BUYABLE' => 'artNum'], false ],
            [ $empty, $validArticle,  [], false ]
        ];
    }

    public function test__construct()
    {
        $orderData = ['orderData'];
        $oOrderValidator = new KlarnaOrderValidator($orderData);
        $this->assertEquals($orderData, $this->getProtectedClassProperty($oOrderValidator, 'aOrderData'));
    }

    /**
     * @dataProvider orderDataProvider
     * @param $orderData
     * @param $articleErrors
     * @param $resultErrors
     * @param $eRes
     */
    public function testValidateOrder($orderData, $articleErrors, $resultErrors, $eRes)
    {
        // setup article errors
        $oArticle = $this->getMockBuilder(Article::class)
            ->setMethods(['klarna_loadByArtNum', 'checkForStock', 'isLoaded', 'isBuyable'])
            ->getMock();
        $oArticle->oxarticles__oxartnum = new Field('artNum', Field::T_RAW);
        $oArticle->expects($this->any())->method('checkForStock')->willReturn($articleErrors['checkForStock']);
        $oArticle->expects($this->any())->method('isLoaded')->willReturn($articleErrors['isLoaded']);
        $oArticle->expects($this->any())->method('isBuyable')->willReturn($articleErrors['isBuyable']);
        UtilsObject::setClassInstance(Article::class, $oArticle);

        $oOrderValidator = new KlarnaOrderValidator($orderData);
        $result = $oOrderValidator->validateOrder();

        $this->assertEquals($eRes, $result);
        $this->assertEquals($eRes, $oOrderValidator->isValid());
        $this->assertEquals($resultErrors, $oOrderValidator->getResultErrors());

        array_pop($orderData['order_lines']);  // remove delivery service
        $validatedItems = $this->getProtectedClassProperty($oOrderValidator, 'aOrderData')['order_lines'];
        $this->assertEquals($orderData['order_lines'], $validatedItems);
    }
}
