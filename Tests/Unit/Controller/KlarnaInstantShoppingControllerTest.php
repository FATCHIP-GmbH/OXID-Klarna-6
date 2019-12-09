<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;

use Exception;
use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Controller\KlarnaInstantShoppingController;
use TopConcepts\Klarna\Core\Adapters\BasketAdapter;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\InstantShopping\PaymentHandler;
use TopConcepts\Klarna\Core\KlarnaUserManager;
use TopConcepts\Klarna\Model\KlarnaInstantBasket;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaInstantShoppingControllerTest extends ModuleUnitTestCase
{
    public function testInit()
    {
        $controller = $this->getMockBuilder(KlarnaInstantShoppingController ::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'buildOrderLinesFromBasket',

            ])
            ->getMock();

        $this->assertNull($this->getProtectedClassProperty($controller, "httpClient"));
        $this->assertNull($this->getProtectedClassProperty($controller, "db"));
        $this->assertNull($this->getProtectedClassProperty($controller, "userManager"));

        $controller->init();

        $this->assertNotNull($this->getProtectedClassProperty($controller, "httpClient"));
        $this->assertNotNull($this->getProtectedClassProperty($controller, "db"));
        $this->assertNotNull($this->getProtectedClassProperty($controller, "userManager"));

    }

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
        $oSUT = $this->getMockBuilder(KlarnaInstantShoppingController::class)
            ->setMethods(['createBasketAdapter'])
            ->getMock();

        $oSUT->expects($this->any())
            ->method('createBasketAdapter')->willReturn(false);

        $result = $oSUT->placeOrder();

        $this->assertEmpty($result);

        $oSUT = $this->getMockBuilder(KlarnaInstantShoppingController::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'createBasketAdapter',
                    'approveOrder',
                    'updateOrderObject',
                    'prepareOrderExecution',
                    'extractOrderException',
                    'declineOrder',
                    'logError'
                ]
            )
            ->getMock();

        $oSUT->expects($this->any())
            ->method('logError');

        $oSUT->expects($this->any())
            ->method('extractOrderException')->willReturn(new Exception('Exception'));

        $oSUT->expects($this->at(1))
            ->method('declineOrder')->willReturn(true);

        $oSUT->expects($this->at(2))
            ->method('declineOrder')->willThrowException(new KlarnaClientException('klarnaexception'));

        $oSUT->expects($this->any())->method('approveOrder')->willReturn(true);
        $oSUT->expects($this->any())->method('updateOrderObject')->willReturn(true);
        $oSUT->expects($this->any())->method('prepareOrderExecution')->willReturn(1);

        $adapter = $this->getMockBuilder(BasketAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['buildOrderLinesFromBasket', 'validateOrderLines', 'closeBasket'])
            ->getMock();

        $adapter->expects($this->any())->method('buildOrderLinesFromBasket')->willReturn(true);
        $adapter->expects($this->any())->method('validateOrderLines')->willReturn(true);
        $adapter->expects($this->any())->method('closeBasket')->willReturn(true);

        $orderController = $this->getMockBuilder(OrderController::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'validateOrderLines'])
            ->getMock();

        $orderController->expects($this->at(0))->method('execute')->willReturn(KlarnaInstantShoppingController::EXECUTE_SUCCESS);
        $orderController->expects($this->at(1))->method('execute')->willReturn("error");
        $orderController->expects($this->at(2))->method('execute')->willReturn("error");
        $oSUT->expects($this->any())->method('createBasketAdapter')->willReturn($adapter);

        $db = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->setMethods(['startTransaction', 'rollbackTransaction', 'commitTransaction'])
            ->getMock();

        $db->expects($this->any())->method('startTransaction');
        $db->expects($this->any())->method('commitTransaction');
        $db->expects($this->any())->method('rollbackTransaction')->willReturn(true);

        $this->setProtectedClassProperty($oSUT, 'db', $db);
        Registry::set(OrderController::class, $orderController);
        $oSUT->placeOrder();
        $oSUT->placeOrder();
        $oSUT->placeOrder();

    }

    public function testPrepareOrderExecution()
    {
        Registry::getSession()->setVariable("sess_stoken", "token");
        Registry::getSession()->setVariable("deladrid", "id");
        $oSUT = $this->getMockBuilder(KlarnaInstantShoppingController::class)
            ->setMethods(['getUser', 'getDelAddress'])
            ->getMock();
        $user = $this->getMockBuilder(User::class)->setMethods(['getEncodedDeliveryAddress'])->getMock();
        $user->expects($this->any())->method('getEncodedDeliveryAddress')->willReturn("test");

        $oSUT->expects($this->any())->method('getUser')->willReturn($user);
        $oSUT->expects($this->any())->method('getDelAddress')->willReturn("address");

        $this->setProtectedClassProperty($oSUT,'actionData','actiondata');
        $return = $oSUT->prepareOrderExecution();

        $this->assertSame("testaddress", $_GET['sDeliveryAddressMD5']);
        $this->assertNotEmpty($return);
        $actionData = $this->getConfigParam(PaymentHandler::ORDER_CONTEXT_KEY);
        $this->assertSame('actiondata', $actionData);
    }

    public function declineOrder()
    {

    }

    public function creatBasketAdapterDP()
    {
        $oInstantBasketMock = function($loaded, $oBasketMock) {
            $oMock = $this->getMockBuilder(KlarnaInstantBasket::class)
                ->setMethods(['load', 'getBasket'])
                ->getMock();

            $oMock->expects($this->once())
                ->method('load')
                ->willReturn($loaded);
            if ($loaded) {
                $oMock->expects($this->once())
                    ->method('getBasket')
                    ->willReturn($oBasketMock);

            }

            return $oMock;
        };

        $oBasketMock = $this->getMockBuilder(Basket::class)
            ->setMethods(['setShipping', 'setBasketUser'])
            ->getMock();
        $oBasketMock->expects($this->once())
            ->method('setShipping')
            ->with('shpId');
        $oBasketMock->expects($this->once())
            ->method('setBasketUser');

        return  [
            [$oInstantBasketMock(false, null), false],
            [$oInstantBasketMock(true, $oBasketMock), false],
        ];
    }

    /**
     * @dataProvider creatBasketAdapterDP
     * @param $oInstantBasket
     * @param $expectedAdapter
     * @throws \ReflectionException
     */
    public function testCreatBasketAdapter($oInstantBasket, $expectedAdapter)
    {
        $actionData = [];
        $actionData['order']['selected_shipping_option']['id'] = 'shpId';
        $actionData['order']['billing_address']['email'] = 'fake@mail';

        $methodReflection = new \ReflectionMethod(KlarnaInstantShoppingController::class, 'createBasketAdapter');
        $methodReflection->setAccessible(true);
        $oSUT = $this->getMockBuilder(KlarnaInstantShoppingController::class)
            ->setMethods(['getUser'])
            ->getMock();
        $oSUT->expects($this->any())
            ->method('getUser')
            ->willReturn(oxNew(User::class));
        Registry::set(KlarnaInstantBasket::class, $oInstantBasket);
        $this->setProtectedClassProperty($oSUT, 'actionData', $actionData);
        $this->setProtectedClassProperty($oSUT, 'userManager', $this->getMockBuilder(KlarnaUserManager::class)->getMock());

        $basketAdapter = $methodReflection->invoke($oSUT);
        $this->assertSame($expectedAdapter, $basketAdapter);
    }
}
