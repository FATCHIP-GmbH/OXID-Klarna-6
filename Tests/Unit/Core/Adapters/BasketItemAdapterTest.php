<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\Adapters;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Price;
use TopConcepts\Klarna\Core\Adapters\BaseBasketItemAdapter;
use TopConcepts\Klarna\Core\Adapters\BasketItemAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class BasketItemAdapterTest extends ModuleUnitTestCase
{
    public function updateDataProvider()
    {
        $oBasketItemMock = $this->getMockBuilder(BasketItem::class)
            ->setMethods(['getProductId', 'getSelList', 'getPersParams', 'isBundle', 'getTitle'])
            ->getMock();

        $oBasketMock = $this->getMockBuilder(Basket::class)
            ->setMethods(['addToBasket'])
            ->getMock();
        $oBasketMock->expects($this->once())->method('addToBasket')
        ->willReturn($oBasketItemMock);

        $oBasketMockWithException = $this->getMockBuilder(Basket::class)
            ->setMethods(['addToBasket'])
            ->getMock();
        $oBasketMockWithException->expects($this->once())->method('addToBasket')
            ->willThrowException(new StandardException('m'));

        $oBasketMockNotUsed = $this->getMockBuilder(Basket::class)
            ->setMethods(['addToBasket'])
            ->getMock();
        $oBasketMockNotUsed->expects($this->never())->method('addToBasket');

        return [
            [
                ['key' => 'quantity'],
                $oBasketMock,
                $oBasketItemMock,
                false,
                '11'
            ],
            [
                ['key' => 'quantity'],
                $oBasketMockWithException,
                $oBasketItemMock,
                true,
                '00'
            ],
            [
                ['key' => 'total_amount'],
                $oBasketMockNotUsed,
                $oBasketItemMock,
                false,
                '01'
            ]
        ];
    }

    /**
     * @dataProvider updateDataProvider
     * @param $diffData
     * @param $oBasketMock
     * @param $oBasketItemMock
     * @param $changeBasketException
     * @param $expectedResult
     * @throws StandardException
     */
    public function testHandleUpdate($diffData, $oBasketMock, $oBasketItemMock, $changeBasketException, $expectedResult)
    {
        $updateData = [];
        $oSUT = new BasketItemAdapter([], $oBasketItemMock,$oBasketMock);
        $this->setProtectedClassProperty($oSUT, 'diffData', $diffData);

        $result = $oSUT->handleUpdate($updateData);
        if ($changeBasketException) {
            $this->assertLoggedException(StandardException::class, 'm');
        }
        $this->assertEquals($expectedResult, $result);

    }

    public function prepareItemDataProvider()
    {
        $oArticleMock = $this->getMockBuilder(Article::class)
            ->setMethods(['getUnitPrice'])
            ->getMock();
        $oArticleMock->expects($this->once())->method('getUnitPrice')->willReturn(oxNew(Price::class, 1.00));

        $expectedItemData = [
            'type' => 'physical',
            'reference' => NULL,
            'name' => ' ',
            'quantity' => 0,
            'unit_price' => 0,
            'total_amount' => 0,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
            'merchant_data' => ['type' => 'basket_item'],
            'quantity_unit' => 'pcs',
            'product_url' => NULL,
            'image_url' => NULL,
            'product_identifiers' => [
                'category_path' => NULL,
                'global_trade_item_number' => NULL,
                'manufacturer_part_number' => NULL,
                'brand' => NULL
            ],
            'tax_rate' => 0
        ];

        $getItemMock = function($isBundle, $oArticleMock, $oPrice, $isOrder) use(&$expectedItemData) {
            $oBasketItemMock = $this->getMockBuilder(BasketItem::class)
                ->setMethods(['isBundle', 'getArticle', 'getUnitPrice', 'getRegularUnitPrice'])
                ->getMock();
            $oBasketItemMock->expects($this->any())->method('isBundle')->willReturn($isBundle);
            $oBasketItemMock->expects($this->any())->method('getUnitPrice')->willReturn($oPrice);
            if ($isBundle) {
                $expectedItemData['merchant_data']['type'] = 'bundle';
                $oBasketItemMock->expects($this->never())->method('getArticle');
            } else {
                $expectedItemData['unit_price'] = 400;
                $expectedItemData['merchant_data']['type'] = 'basket_item';
                $oBasketItemMock->expects($this->once())->method('getRegularUnitPrice')->willReturn($oPrice);
                if ($isOrder) {
                    $oBasketItemMock->expects($this->once())->method('getArticle')->willReturn($oArticleMock);
                }
            }
            return $oBasketItemMock;
        };



        return [
            [
                null,
                [
                    [],
                    $getItemMock(true, null, oxNew(Price::class, 1), null),
                    null,
                    null,
                    null
                ],
                $expectedItemData
            ],
            [
                null,
                [
                    [],
                    $getItemMock(false, $oArticleMock, oxNew(Price::class, 4.00), true),
                    null,
                    null,
                    true
                ],
                $expectedItemData
            ],
            [
                null,
                [
                    [],
                    $getItemMock(false, $oArticleMock, oxNew(Price::class, 4.00), false),
                    null,
                    null,
                    false
                ],
                $expectedItemData
            ]
        ];
    }

    /**
     * @dataProvider prepareItemDataProvider
     * @param $lang
     * @param $constructorArgs
     */
    public function testPrepareItemData($lang, $constructorArgs, $expectedItemData)
    {
        $oSUT = $this->getMockBuilder(BasketItemAdapter::class)
            ->setConstructorArgs($constructorArgs)
            ->setMethods(['getArticle'])
            ->getMock();
        $oSUT->expects($this->once())->method('getArticle')
            ->willReturn(
                $this->getMockBuilder(Article::class)->disableOriginalConstructor()->getMock()
            );
        $result = $oSUT->prepareItemData($lang);
        $this->assertEquals($oSUT, $result);


        $this->assertEquals($expectedItemData, $oSUT->getItemData());
    }

    public function validateItemDP()
    {
        $validOrderLine = $invalidOrderLineQuantity = $invalidOrderLineTotalAmount = [
          "quantity" => 1,
          "unit_price" => 2121,
          "total_amount" => 1000,
          "type" => "physical",
          "reference" => "3788",
          "name" => "Transport container BARREL",
          "quantity_unit" => "pcs",
          "tax_rate" => 1900,
          "total_discount_amount" => 0,
          "total_tax_amount" => 339,
          "merchant_data" => "{\"type\":\"basket_item\"}",
          "product_url" => "http://demohost.topconcepts.net/arek/klarna/ce_620/source/en/Special-Offers/Transport-container-BARREL.html",
          "image_url" => "http://demohost.topconcepts.net/arek/klarna/ce_620/source/out/pictures/generated/product/1/540_340_75/mikejucker_textilcontainer_1.jpg",
          "product_identifiers" => [
                "category_path" => "Special Offers",
            "brand" => "Jucker Hawaii"
          ],
          "product_attributes" => []
        ];
        $invalidOrderLineTotalAmount['total_amount'] = 1001;
        $invalidOrderLineQuantity['quantity'] = 2;
        return [
            [$validOrderLine, []],
            [$invalidOrderLineTotalAmount,
                [
                    'key' => 'total_amount',
                    'requestedValue'=> 1001,
                    'basketValue' => 1000
                ]
            ],
            [$invalidOrderLineQuantity,
                [
                    'key' => 'quantity',
                    'requestedValue'=> 2,
                    'basketValue' => 1
                ]
            ]
        ];
    }

    /**
     * @dataProvider validateItemDP
     * @param $orderLine
     * @param $diffData
     * @throws InvalidItemException
     * @throws StandardException
     */
    public function testValidateItem($orderLine, $diffData)
    {
        $oBasketItemMock = $this->getMockBuilder(BasketItem::class)
            ->setMethods(['getAmount', 'getPrice'])
            ->getMock();
        $oBasketItemMock->expects($this->any())->method('getPrice')->willReturn(oxNew(Price::class, 10.00));
        $oBasketItemMock->expects($this->any())->method('getAmount')->willReturn(1);

        $oSUT = new BasketItemAdapter([], $oBasketItemMock);

        if ($diffData) {
            $this->expectException(InvalidItemException::class);
        }

        $oSUT->validateItem($orderLine);

        $this->assertEquals($diffData, $oSUT->getDiffData());
        if ($diffData) {
            $this->assertLoggedException(InvalidItemException::class);
        }
    }

    public function testGetReference()
    {
        $itemData = [];
        $oArticleMock = $this->getMockBuilder(Article::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFieldData'])
            ->getMock();
        $oArticleMock->expects($this->once())->method('getFieldData')
            ->willReturn('ref');
        $oSUT = $this->getMockBuilder(BasketItemAdapter::class)
            ->setConstructorArgs([$itemData])
            ->setMethods(['getArticle'])
            ->getMock();
        $oSUT->expects($this->once())->method('getArticle')
            ->willReturn($oArticleMock);

        $result = $oSUT->getReference();
        $this->assertEquals('ref', $result);


        $itemData = ['reference' => 'ref1'];
        $oSUT = $this->getMockBuilder(BasketItemAdapter::class)
            ->setConstructorArgs([$itemData])
            ->setMethods(['getArticle'])
            ->getMock();
        $result = $oSUT->getReference();
        $this->assertEquals('ref1', $result);
    }

    public function testEncodeMerchantData()
    {
        $oSUT = $this->getMockBuilder(BaseBasketItemAdapter::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $itemData['merchant_data'] = ["test" => "data"];

        $result = $oSUT->encodeMerchantData($itemData);
        $this->assertSame(json_encode(["test" => "data"]), $result['merchant_data']);
    }

    public function testGetItemKey()
    {
        $oSUT = $this->getMockBuilder(BaseBasketItemAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getType', 'getReference'])
            ->getMockForAbstractClass();

        $oSUT->expects($this->once())->method('getType')
            ->willReturn("test");

        $oSUT->expects($this->once())->method('getReference')
            ->willReturn("data");


        $result = $oSUT->getItemKey();
        $this->assertSame("test_data", $result);
    }
}
