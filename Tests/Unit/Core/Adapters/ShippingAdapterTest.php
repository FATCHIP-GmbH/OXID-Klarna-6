<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\Adapters;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\PaymentList;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Adapters\ShippingAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Core\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class ShippingAdapterTest extends ModuleUnitTestCase
{
    public function testGetName()
    {
        $oDeliverySetMock = $this->getMockBuilder(DeliverySet::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFieldData'])
            ->getMock();
        $oDeliverySetMock->expects($this->once())
            ->method('getFieldData')
            ->willReturn('"test", \'test\'');

        $oSUT = new ShippingAdapter([]);
        $this->setProtectedClassProperty($oSUT, 'oDeliverySet', $oDeliverySetMock);

        $this->assertEquals('"test", \'test\'', $oSUT->getName());
    }

    public function getShippingOptionsProvider()
    {
        $oSippingSetMock = oxNew(DeliverySet::class);
        $oSippingSetMock2 = clone $oSippingSetMock;
        $oSippingSetMock->oxdeliveryset__oxtitle = new Field('title');
        $oSippingSetMock2->oxdeliveryset__oxtitle = new Field('title2');




        return [
            [
                [],
                $this->getPaymentListMock([]),
                [],
                KlarnaConfigException::class
            ],
            [
                [
                    'oxidstandard' => $oSippingSetMock,
                    'oxidstandard2' => $oSippingSetMock2,
                ],
                $this->getPaymentListMock(['klarna_instant_shopping' => 'PaymentInstance']),
                [
                    [
                        'id' => 'oxidstandard',
                        'name' => 'title',
                        'description' => '',
                        'tax_amount' => 0,
                        'price' => 390,
                        'tax_rate' => 0,
                        'preselected' => true,
                    ],
                    [
                        'id' => 'oxidstandard2',
                        'name' => 'title2',
                        'description' => '',
                        'tax_amount' => 0,
                        'price' => 390,
                        'tax_rate' => 0,
                        'preselected' => false,
                    ]
                ],
                null
            ],
            [
                [
                    'oxidstandard' => $oSippingSetMock,
                    'oxidstandard2' => $oSippingSetMock2,
                ],
                $this->getPaymentListMock([]),
                [],
                KlarnaConfigException::class
            ]
        ];
    }

    /**
     * @dataProvider getShippingOptionsProvider
     * @param $shippingSets
     * @param $oPaymentListMock
     * @param $expectedResult
     * @param $expectedException
     */
    public function testGetShippingOptions($shippingSets, $oPaymentListMock, $expectedResult, $expectedException)
    {

        $selectedShipping = 'oxidstandard';
        $oBasketMock = $this->getMockBuilder(Basket::class)
            ->setMethods(['tcklarna_calculateDeliveryCost', 'getPriceForPayment'])
            ->getMock();
        $oBasketMock->expects($this->any())
            ->method('tcklarna_calculateDeliveryCost')
            ->willReturn(oxNew(Price::class, 3.90));
        $oBasketMock->expects($this->any())
            ->method('getPriceForPayment')
            ->willReturn(50.00);
        $oBasketMock->setShipping($selectedShipping);
        $oUserStub = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();


        $oSUT = $this->getMockBuilder(ShippingAdapter::class)
            ->setConstructorArgs([[], null, $oBasketMock, $oUserStub])
            ->setMethods(['getShippingSets'])
            ->getMock();
        $oSUT->expects($this->once())
            ->method('getShippingSets')
            ->willReturn($shippingSets);
        Registry::set(PaymentList::class, $oPaymentListMock);

        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $result = $oSUT->getShippingOptions('klarna_instant_shopping');
        $this->assertEquals($expectedResult, $result);
        $this->assertEquals($selectedShipping, $oBasketMock->getShippingId());

    }

    public function testHandleUpdate()
    {

        $oSUT = $this->getMockBuilder(ShippingAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingOptions'])
            ->getMock();
        $oSUT->expects($this->once())
            ->method('getShippingOptions')
            ->willReturn(['update']);
        $this->setProtectedClassProperty($oSUT, 'oBasket', $this->getMockBuilder(Basket::class)->getMock());

        $toUpdate = [];
        $oSUT->handleUpdate($toUpdate);

        $this->assertEquals(['shipping_options' => ['update']], $toUpdate);
    }

    public function validateItemDataProvider()
    {
        $orderLine = [
            "quantity" => 1,
            "unit_price" => 390,
            "total_amount" => 390,
            "type" => "shipping_fee",
            "reference" => "oxidstandard",
            "name" => "Standard",
            "tax_rate" => 1900,
            "total_tax_amount" => 62,
            "product_attributes" => []
        ];

        return [
            [$orderLine, ['klarna_instant_shopping' => 'PaymentInstance'], true],
            [$orderLine, ['payId' => 'PaymentInstance'], false],
        ];
    }

    /**
     * @dataProvider validateItemDataProvider
     * @param $orderLine
     * @param $paymentArray
     * @param $expectValidResult
     */
    public function testValidateItem($orderLine, $paymentArray, $expectValidResult)
    {
        $oBasketMock = $this->getMockBuilder(Basket::class)
            ->setMethods(['getPaymentId', 'getPriceForPayment', 'getCosts'])
            ->getMock();
        $oBasketMock->expects($this->once())
            ->method('getPaymentId')
            ->willReturn('klarna_instant_shopping');
        $oBasketMock->expects($this->any())
            ->method('getPriceForPayment')
            ->willReturn(50.00);
        $oBasketMock->expects($this->any())
            ->method('getCosts')
            ->willReturn(oxNew(Price::class, 3.90));

        $oSUT = oxNew(ShippingAdapter::class,
            [],
            null,
            $oBasketMock
        );
        Registry::set(PaymentList::class, $this->getPaymentListMock($paymentArray));

        if (!$expectValidResult) {
            $this->expectException(InvalidItemException::class);
        }
        $oSUT->validateItem($orderLine);
        $this->assertEmpty($oSUT->getDiffData());
    }

    public function prepareItemDataDP()
    {
        $getBasketMock = function($id, $cost) {
            $oBasketMock = $this->getMockBuilder(Basket::class)
                ->setMethods(['getShippingId', 'getCosts'])
                ->getMock();
            $oBasketMock->expects($this->once())
                ->method('getShippingId')
                ->willReturn($id);
            $oBasketMock->expects($this->any())
                ->method('getCosts')
                ->willReturn(oxNew(Price::class, $cost));

            return $oBasketMock;
        };

        $oOrderMock = $this->getMockBuilder(Order::class)
            ->setMethods(['getFieldData'])
            ->getMock();
        $oOrderMock->expects($this->once())
            ->method('getFieldData')
            ->willReturn('oxidstandard');

        $expectedItemData = [
            'type' => NULL,
            'reference' => "oxidstandard",
            'name' => "Standard",
            'quantity' => 1,
            'unit_price' => 390,
            'tax_rate' => 0,
            'total_amount' => 390,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0
        ];

        return [
            [
                null,
                $getBasketMock('oxidstandard', 3.90),
                $expectedItemData,
                null
            ],
            [
                1,
                $getBasketMock('oxidstandard', 3.90),
                $expectedItemData,
                $oOrderMock
            ],
        ];
    }

    /**
     * @dataProvider prepareItemDataDP
     * @param $oBasketMock
     * @param $expectedItemData
     */
    public function testPrepareItemData($langId, $oBasketMock, $expectedItemData, $oOrderMock)
    {
        $oSUT = oxNew(ShippingAdapter::class,
            [],
            null,
            $oBasketMock,
            null,
            $oOrderMock
        );
        $oSUT->prepareItemData($langId);
        $this->assertEquals($expectedItemData, $oSUT->getItemData());
    }

    protected function getPaymentListMock($paymentArray)
    {
        $oPaymentListMock = $this->getMockBuilder(PaymentList::class)
            ->setMethods(['getPaymentList'])
            ->getMock();
        $oPaymentListMock->expects($this->any())->method('getPaymentList')->willReturn($paymentArray);

        return $oPaymentListMock;
    }
}
