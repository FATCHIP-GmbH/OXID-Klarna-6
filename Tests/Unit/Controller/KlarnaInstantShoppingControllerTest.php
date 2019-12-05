<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;

use OxidEsales\Eshop\Core\Exception\StandardException;
use TopConcepts\Klarna\Controller\KlarnaInstantShoppingController;
use TopConcepts\Klarna\Core\Adapters\BasketAdapter;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaInstantShoppingControllerTest extends ModuleUnitTestCase
{
    public function updateOrderDP()
    {
        $requestData1 = [
            'update_context' => 'identification_updated'
        ];
        $requestData2 = [
            'update_context' => 'specifications_selected'
        ];
        $requestData3 = [
            'update_context' => 'other'
        ];
        $getBasketAdapterMock = function($updateData = [], $withException = false) {
            $basketAdapterMock = $this->getMockBuilder(BasketAdapter::class)
                ->disableOriginalConstructor()
                ->setMethods([
                    'buildOrderLinesFromBasket',
                    'setHandleBasketUpdates',
                    'validateOrderLines',
                    'storeBasket',
                    'getUpdateData',
                    'sendResponse'
                ])
                ->getMock();
            $basketAdapterMock
                ->expects($this->once())
                ->method('buildOrderLinesFromBasket');
            $basketAdapterMock
                ->expects($this->once())
                ->method('setHandleBasketUpdates');
            $basketAdapterMock
                ->expects($this->once())
                ->method('validateOrderLines');
            if ($withException) {
                $basketAdapterMock
                    ->expects($this->once())
                    ->method('storeBasket')
                    ->willThrowException(new StandardException('StoreBasketException'))
                ;
                $basketAdapterMock
                    ->expects($this->never())
                    ->method('getUpdateData');
            } else {
                $basketAdapterMock
                    ->expects($this->once())
                    ->method('storeBasket');
                $basketAdapterMock
                    ->expects($this->once())
                    ->method('getUpdateData')
                    ->willReturn($updateData);
            }

            return $basketAdapterMock;
        };
        $updateData = ['update' => 'data'];
        return [
            // no updates
            [
                $requestData1,
                true,
                $getBasketAdapterMock(),
                null
            ],
            // update
            [
                $requestData2,
                true,
                $getBasketAdapterMock($updateData),
                null
            ],
            // basketAdapter exception
            [
                $requestData2,
                true,
                $getBasketAdapterMock([], true),
                ['type' => StandardException::class, 'msg' => 'StoreBasketException']
            ],
            // can not create BasketAdapter
            [
                $requestData2,
                true,
                false,
                null
            ],
            // invalid context
            [
                $requestData3,
                false,
                null,
                null
            ],
        ];
    }

    /**
     * @dataProvider updateOrderDP
     * @param $requestData
     * @param $shouldProcessRequest
     * @param $basketAdapterMock
     * @param $expectedLoggedException
     * @throws StandardException
     */
    public function testUpdateOrder($requestData, $shouldProcessRequest, $basketAdapterMock, $expectedLoggedException)
    {
        $oSUT = $this->getMockBuilder(KlarnaInstantShoppingController::class)
            ->setMethods(['createBasketAdapter', 'sendResponse'])
            ->getMock();
        if ($shouldProcessRequest) {
            $oSUT->expects($this->once())
                ->method('createBasketAdapter')
                ->willReturn($basketAdapterMock);
            $oSUT->expects($this->any())
                ->method('sendResponse');
        } else {
            $oSUT->expects($this->never())
                ->method('createBasketAdapter');
        }
        $this->setProtectedClassProperty($oSUT, 'requestData', $requestData);
        $this->setProtectedClassProperty($oSUT, 'actionData', $requestData);

        $oSUT->updateOrder();

        if ($expectedLoggedException) {
            $this->assertLoggedException($expectedLoggedException['type'], $expectedLoggedException['msg']);
        }
    }

    public function testPlaceOrder()
    {

    }
}
