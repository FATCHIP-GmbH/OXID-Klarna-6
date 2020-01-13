<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\Adapters;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Adapters\BaseBasketItemAdapter;
use TopConcepts\Klarna\Core\Adapters\BasketAdapter;
use TopConcepts\Klarna\Core\Adapters\BasketItemAdapter;
use TopConcepts\Klarna\Core\Adapters\DiscountAdapter;
use TopConcepts\Klarna\Core\Adapters\ShippingAdapter;
use TopConcepts\Klarna\Core\Adapters\VoucherAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Model\KlarnaInstantBasket;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class BasketAdapterTest extends ModuleUnitTestCase
{
    protected function prepareBasketItemMock($productId, $isBundle)
    {
        $oItemMock = $this->getMockBuilder(BasketItem::class)
            ->setMethods(['getProductId',
                          'isBundle',
                          'getArticle',
                          'getUnitPrice',
                          'getRegularUnitPrice',
                          'validateItem'
            ])
            ->getMock();
        $oItemMock->expects($this->any())
            ->method('getProductId')
            ->willReturn($productId);
        $oItemMock->expects($this->any())
            ->method('isBundle')
            ->willReturn($isBundle);
        $oItemMock->expects($this->any())
            ->method('getArticle')
            ->willReturn($this->prepareArticleMock($productId));
        $oItemMock->expects($this->any())
            ->method('getUnitPrice')
            ->willReturn($this->preparePriceMock(10.99));
        $oItemMock->expects($this->any())
            ->method('getRegularUnitPrice')
            ->willReturn($this->preparePriceMock(12.00));

        return $oItemMock;
    }

    protected function prepareArticleMock($id)
    {
        $oArticleMock = $this->getMockBuilder(Article::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $oArticleMock;
    }

    protected function prepareItemAdapterMock($class, $valid, $flags)
    {
        $itemAdapterMock = $this->getMockBuilder($class)
        ->disableOriginalConstructor()
        ->setMethods(['validateItem', 'handleUpdate'])
        ->getMock();

        if ($valid) {
            $itemAdapterMock->expects($this->once())
                ->method('validateItem');
        } else {
            $itemAdapterMock->expects($this->once())
                ->method('validateItem')
                ->willThrowException(new InvalidItemException);
            if ($flags) {
                $itemAdapterMock->expects($this->once())
                    ->method('handleUpdate')
                    ->willReturn($flags);
            }
        }



        return $itemAdapterMock;
    }

    protected function preparePriceMock($price = null)
    {
        $oPriceMock = $this->getMockBuilder(Price::class)
            ->setConstructorArgs([(double)$price])
            ->setMethods([])
            ->getMock();

        return $oPriceMock;
    }

    /**
     * @param array $content
     * @param array $costs
     * @param array $voucher
     * @param array $discounts
     * @return mixed
     */
    protected function prepareBasketMock($content = [], $costs = [], $voucher = [], $discounts = [])
    {
        $oBasketMock = $this->getMockBuilder(Basket::class)
            ->setMethods(['getContents', 'getVouchers', 'getDiscounts', 'calculateBasket'])
            ->getMock();
        $oBasketMock->expects($this->any())
            ->method('getContents')
            ->willReturn($content);
        $this->setProtectedClassProperty($oBasketMock, '_aCosts', $costs);
        $oBasketMock->expects($this->any())
            ->method('getVouchers')
            ->willReturn($voucher);
        $oBasketMock->expects($this->any())
            ->method('getDiscounts')
            ->willReturn($discounts);
        $oBasketMock->expects($this->any())
            ->method('calculateBasket');

        return $oBasketMock;
    }

    protected function prepareUserMock()
    {
        $oUserMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $oUserMock;
    }

    public function testBuildOrderLinesFromBasket()
    {
        $generator =  function() {
            $oItemAdapter = $this->getMockBuilder(BasketItemAdapter::class)
                ->disableOriginalConstructor()
                ->setMethods(['addItemToOrderLines', 'getItemKey'])
                ->getMock();
            $oItemAdapter2  = clone $oItemAdapter;
            $oItemAdapter
                ->expects($this->once())
                ->method('addItemToOrderLines')
                ->willReturn(true);
            $oItemAdapter
                ->expects($this->once())
                ->method('getItemKey')
                ->willReturn('key_0');
            $oItemAdapter2
                ->expects($this->once())
                ->method('addItemToOrderLines')
                ->willReturn(false);
            foreach ([$oItemAdapter, $oItemAdapter2] as $oItemAdapter) {
                yield $oItemAdapter;
            }
        };

        $oSUT = $this->getMockBuilder(BasketAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateBasketItemAdapters'])->getMock();
        $oSUT->expects($this->once())
            ->method('generateBasketItemAdapters')
            ->willReturn($generator());


        $oSUT->buildOrderLinesFromBasket();
        $orderLines = $oSUT->getOrderData();

        $this->assertEquals([
            'order_lines' => [],
            'order_amount' => 0,
            'order_tax_amount' => 0
            ],
            $orderLines
        );
        $adaptersProp = $this->getProtectedClassProperty($oSUT, 'itemAdapters');
        $this->assertArrayHasKey('key_0', $adaptersProp);
        $this->assertCount(1, $adaptersProp);
    }

    public function testGenerateBasketItemAdapters()
    {
        $oSUT = $this->getMockBuilder(BasketAdapter::class)
            ->setConstructorArgs([
                $this->prepareBasketMock(
                    ['item1' => 'itemInstance1', 'item2' => 'itemInstance2'],
                    ['oxdelivery' => 'shippingCost', 'oxpayment' => null],
                    ['voucherId' => 'voucherInstance'],
                    ['discountId' => 'discountInstance']
                ),
                $this->prepareUserMock(),
                [],
                null
            ])
            ->setMethods(['addOrderTotals'])
            ->getMock();

        $assertAdapter = function($expectedAdapterDetails, $adapter) {
            $this->assertInstanceOf($expectedAdapterDetails['class'], $adapter, 'INVALID_TYPE');
            $instance = $this->getProtectedClassProperty($adapter, 'oItem');
            $this->assertEquals($expectedAdapterDetails['instance'], $instance, 'INVALID_INSTANCE');
        };

        $i = 0;
        $expectedAdapterDetails = [
            ['class' => BasketItemAdapter::class, 'instance' => 'itemInstance1'],
            ['class' => BasketItemAdapter::class, 'instance' => 'itemInstance2'],
            ['class' => ShippingAdapter::class, 'instance' => 'shippingCost'],
            ['class' => VoucherAdapter::class, 'instance' => 'voucherInstance'],
            ['class' => DiscountAdapter::class, 'instance' => 'discountInstance'],
        ];
        foreach($oSUT->generateBasketItemAdapters() as $adapter) {
            $assertAdapter($expectedAdapterDetails[$i], $adapter);
            $i++;
        }
    }

    public function testCloseBasket()
    {
        $orderId = 'id';
        $oSUT = $this->getMockBuilder(BasketAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['storeBasket'])
            ->getMock();
        $oFakeBasket = $this->getMockBuilder(Basket::class)
            ->setMethods(['setOrderId'])
            ->getMock();
        $oFakeBasket->expects($this->once())
            ->method('setOrderId')
            ->with($orderId);


        $this->setProtectedClassProperty($oSUT, 'oBasket', $oFakeBasket);
        $oInstantShoppingBasketMock = $this->getMockBuilder(KlarnaInstantBasket::class)
            ->disableOriginalConstructor()
            ->setMethods(['setBasketInfo', 'save', 'setStatus'])
            ->getMock();
        $oInstantShoppingBasketMock->expects($this->once())->method('setBasketInfo');
        $oInstantShoppingBasketMock->expects($this->once())->method('save');
        $oInstantShoppingBasketMock->expects($this->once())->method('setStatus');

        $this->setProtectedClassProperty($oSUT, 'oInstantShoppingBasket', $oInstantShoppingBasketMock);

        $oSUT->closeBasket($orderId);
    }

    public function storeBasketDP()
    {
        $newBasket = $this->getMockBuilder(KlarnaInstantBasket::class)
            ->disableOriginalConstructor()
            ->setMethods(['setBasketInfo', 'save', 'setType', 'setStatus'])
            ->getMock();
        $newBasket->expects($this->once())->method('setType')->with(KlarnaInstantBasket::TYPE_BASKET);
        $newBasket->expects($this->once())->method('setStatus');
        $newBasket->expects($this->once())->method('setBasketInfo');
        $newBasket->expects($this->once())->method('save');

        $existingBasket = $this->getMockBuilder(KlarnaInstantBasket::class)
            ->disableOriginalConstructor()
            ->setMethods(['setBasketInfo', 'save'])
            ->getMock();
        $existingBasket->expects($this->once())
            ->method('setBasketInfo');
        $existingBasket->expects($this->once())
            ->method('save');

        return [
            [KlarnaInstantBasket::TYPE_BASKET, null, $newBasket],
            [KlarnaInstantBasket::TYPE_SINGLE_PRODUCT, $existingBasket, $existingBasket]
        ];
    }

    /**
     * @dataProvider storeBasketDP
     * @param $type
     * @param $oInstantShoppingBasketProperty
     * @param $oInstantShoppingBasketMock
     */
    public function testStoreBasket($type, $oInstantShoppingBasketProperty, $oInstantShoppingBasketMock)
    {
        $oFakeBasket = new \stdClass();
        $oSUT = $this->getMockBuilder(BasketAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrderData'])
            ->getMock();
        $this->setProtectedClassProperty($oSUT, 'oBasket', $oFakeBasket);
        $this->setProtectedClassProperty($oSUT, 'oInstantShoppingBasket', $oInstantShoppingBasketProperty);
        Registry::set(KlarnaInstantBasket::class, $oInstantShoppingBasketMock);

        $oSUT->storeBasket($type);
    }

    public function validationDataProvider()
    {

        $orderLines = $orderLinesWithShipping = [
            [
                "reference" => "2103",
                "merchant_data" => "{\"type\":\"basket_item\"}"
            ]
        ];
        $shippingLine = [
            "type" => "shipping_fee",
            "reference" => "oxidstandard",
        ];
        $orderLinesWithShipping[] = $shippingLine;

        $key = 'basket_item_2103';
        $invalidKey = 'some';
        $shippingKey = 'oxdelivery_oxidstandard';

        $emptyUpdateData= [];
        $updateOrderLines = ['order_lines' => $orderLines];
        $updateShipping = ['order_lines' => $orderLinesWithShipping];

        return [
            [
                $orderLines,
                [$invalidKey => 'FakeAdapter'],
                false,
                StandardException::class,
                $emptyUpdateData,
                false,
                false
            ],
            [
                $orderLines,
                [$key => $this->prepareItemAdapterMock(BasketItemAdapter::class, true, null)],
                false,
                null,
                $emptyUpdateData,
                false,
                false
            ],
            [
                $orderLines,
                [$key => $this->prepareItemAdapterMock(BasketItemAdapter::class, false, null)],
                false,
                InvalidItemException::class,
                $emptyUpdateData,
                false,
                false
            ],
            [
                $orderLines,
                [$key => $this->prepareItemAdapterMock(BasketItemAdapter::class, true, null)],
                true,
                null,
                $emptyUpdateData,
                false,
                false
            ],
            [
                $orderLines,
                [$key => $this->prepareItemAdapterMock(BasketItemAdapter::class, false, '11')],
                true,
                null,
                $updateOrderLines,
                true,
                true
            ],
            [
                $orderLinesWithShipping,
                [
                    $key => $this->prepareItemAdapterMock(BasketItemAdapter::class, true, '00'),
                    $shippingKey => $this->prepareItemAdapterMock(ShippingAdapter::class, true, '00')
                ],
                 true,
                 null,
                $emptyUpdateData,
                false,
                false
            ],
        ];
    }

    /**
     * @dataProvider validationDataProvider
     * @param $orderLines
     * @param $adapters
     * @param $handleUpdates
     * @param $exception
     * @param $updateData
     * @param $basketRecalculated
     * @param $orderLinesSend
     * @throws InvalidItemException
     * @throws StandardException
     */
    public function testValidateOrderLines($orderLines, $adapters,  $handleUpdates, $exception, $updateData, $basketRecalculated, $orderLinesSend)
    {
        $oBasketMock = $this->prepareBasketMock(
            ['item1' => 'BasketItemInstance']
        );

        /** @var BasketAdapter $oSUT */
        $oSUT = $this->getMockBuilder(BasketAdapter::class)
            ->setConstructorArgs([
                $oBasketMock,
                $this->prepareUserMock(),
                ['order_lines' => $orderLines],
                null
            ])
            ->setMethods(['addOrderTotals', 'buildOrderLinesFromBasket'])
            ->getMock();

        $this->setProtectedClassProperty($oSUT, 'itemAdapters', $adapters);
        if ($exception) {
            $this->expectException($exception);
        }
        if ($basketRecalculated) {
            $oSUT->expects($this->once())
                ->method('buildOrderLinesFromBasket');
            $oBasketMock->expects($this->once())->method('calculateBasket');
        }


        $oSUT->setHandleBasketUpdates($handleUpdates);
        $oSUT->validateOrderLines();

        if ($orderLinesSend) {
            $this->assertArrayHasKey('order_lines', $oSUT->getUpdateData());
        }
        $this->assertEquals($updateData, $oSUT->getUpdateData());
    }

    public function testGetMerchantData()
    {
        $oInstantShoppingBasketMock = $this->getMockBuilder(KlarnaInstantBasket::class)
        ->disableOriginalConstructor()
        ->setMethods(['getId'])
        ->getMock();
        $oInstantShoppingBasketMock->expects($this->once())->method('getId')->willReturn("testId");

        $oSUT = $this->getMockBuilder(BasketAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateBasketItemAdapters'])->getMock();

        $this->setProtectedClassProperty($oSUT, "oInstantShoppingBasket", $oInstantShoppingBasketMock);
        $id = $oSUT->getMerchantData();

        $this->assertSame($id, "testId");
    }

    public function testIsValidItemData()
    {
        $oSUT = $this->getMockBuilder(BaseBasketItemAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['addItemToOrderLines'])->getMockForAbstractClass();

        $prepareItemData = self::getMethod('isValidItemData', BaseBasketItemAdapter::class);
        $result = $prepareItemData->invokeArgs($oSUT, []);

        $this->assertFalse($result);

        $itemData = ['name' => 'testName', 'reference' => 'testreference'];
        $this->setProtectedClassProperty($oSUT, "itemData", $itemData);

        $prepareItemData = self::getMethod('isValidItemData', BaseBasketItemAdapter::class);
        $result = $prepareItemData->invokeArgs($oSUT, []);
        $this->assertTrue($result);
    }
}
