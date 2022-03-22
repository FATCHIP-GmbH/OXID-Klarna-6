<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;

use Exception;
use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Controller\KlarnaInstantShoppingController;
use TopConcepts\Klarna\Core\Adapters\BasketAdapter;
use TopConcepts\Klarna\Core\Adapters\BasketItemAdapter;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Core\Exception\InvalidOrderExecuteResult;
use TopConcepts\Klarna\Core\Exception\KlarnaBasketTooLargeException;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\InstantShopping\Button;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;
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
                    'logError',
                    'logOrderNotFound'
                ]
            )
            ->getMock();

        $oSUT->expects($this->any())
            ->method('logError');

        $oSUT->expects($this->any())
            ->method('logOrderNotFound');

        $oSUT->expects($this->any())
            ->method('extractOrderException')->willReturn(new Exception('Exception'));

        $oSUT->expects($this->any())
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

    /**
     * @dataProvider declineOrderDP
     */
    public function testDeclineOrder($exception, $testMethod)
    {
        if($exception === InvalidItemException::class) {
            $adapter = $this->getMockBuilder(BasketItemAdapter::class)
                ->disableOriginalConstructor()
                ->setMethods(['getErrorCode'])
                ->getMock();

            $adapter->expects($this->once())->method('getErrorCode')->willReturn(400);

            $exception = $this->getMockBuilder($exception)
                ->setMethods([$testMethod])
                ->getMock();

            $exception->expects($this->once())->method($testMethod)->willReturn($adapter);
        } else if($exception === InvalidOrderExecuteResult::class) {
            $exception = $this->getMockBuilder($exception)
                ->setMethods([$testMethod])
                ->getMock();

            $exception->expects($this->once())->method($testMethod)->willReturn('oxOutOfStockException');
        } else {
            $exception = $this->getMockBuilder($exception)
                ->setMethods([$testMethod])
                ->getMock();

            $exception->expects($this->any())->method($testMethod);
        }

        $oSUT = $this->getMockBuilder(KlarnaInstantShoppingController::class)
            ->setMethods(['getUser'])
            ->getMock();

        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['declineOrder'])
            ->getMock();

        $httpClient->expects($this->any())->method('declineOrder')->willReturn("test_response");

        $this->setProtectedClassProperty($oSUT, 'httpClient', $httpClient);
        $data['order']['locale'] = 'DE';

        $this->setProtectedClassProperty($oSUT, 'actionData', $data);
        $result = $oSUT->declineOrder($exception);

        $this->assertSame("test_response", $result);

    }

    public function declineOrderDP()
    {
        return [
            [KlarnaBasketTooLargeException::class, "getMessage"],
            [InvalidItemException::class, "getItemAdapter"],
            [InvalidOrderExecuteResult::class, "getType"]
        ];

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
        $oBasketMock->expects($this->exactly(2))
            ->method('setBasketUser');

        return  [
            [$oInstantBasketMock(false, null), false],
            [$oInstantBasketMock(true, $oBasketMock), true],
        ];
    }

    /**
     * @dataProvider creatBasketAdapterDP
     * @param $oInstantBasket
     * @param $expectAdapter
     * @throws \ReflectionException
     */
    public function testCreatBasketAdapter($oInstantBasket, $expectAdapter)
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
        if ($expectAdapter) {
            $this->assertInstanceOf(BasketAdapter::class, $basketAdapter);
        } else {
            $this->assertFalse($basketAdapter);
        }
    }
    public function startSessionDP()
    {
        $artNum = '111';
        $orderLines = [
            ['reference' => $artNum]
        ];

        $oProductMock = $this->getMockBuilder(Article::class)
            ->disableOriginalConstructor()
            ->setMethods(['klarna_loadByArtNum'])
            ->getMock();
        $oProductMock->expects($this->once())
            ->method('klarna_loadByArtNum')
            ->with($artNum);

        $getBasketAdapterMock = function() {
            $oBasketAdapterMock = $this->getMockBuilder(BasketAdapter::class)
                ->disableOriginalConstructor()
                ->setMethods(['storeBasket', 'getMerchantData'])
                ->getMock();
            $oBasketAdapterMock->expects($this->once())
                ->method('storeBasket');
            $oBasketAdapterMock->expects($this->once())
                ->method('getMerchantData')
                ->willReturn('id');
            return $oBasketAdapterMock;
        };


        $getButtonMock = function($oBasketAdapterMock) {
            $oButtonMock = $this->getMockBuilder(Button::class)
                ->setMethods(['instantiateBasketAdapter'])
                ->getMock();
            $oButtonMock->expects($this->once())
                ->method('instantiateBasketAdapter')
                ->willReturn($oBasketAdapterMock);

            return $oButtonMock;
        };

        return [
            ['basket', null, null, null, true],
            ['single_product', $orderLines, $getButtonMock($getBasketAdapterMock()), $oProductMock,  false],
            ['basket', $orderLines, $getButtonMock($getBasketAdapterMock()), null, false],
        ];
    }

    /**
     * @dataProvider startSessionDP
     * @param $typeRef string initial ref - basket type
     * @param $orderLines
     * @param $oProductMock
     * @param $basketExists
     */
    public function testStartSessionAjax($typeRef, $orderLines, $oButtonMock, $oProductMock, $basketExists)
    {
        $oSUT = $this->getMockBuilder(KlarnaInstantShoppingController::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendResponse'])
            ->getMock();

        $sendResponseConstraints = $basketExists ? $this->never() : $this->once();
        $oSUT->expects($sendResponseConstraints)
            ->method('sendResponse')
            ->with(['merchant_reference2' => 'id']);

        $data['merchant_reference2'] = $typeRef;
        $data['order_lines'] = $orderLines;

        $this->setProtectedClassProperty($oSUT, 'actionData', $data);
        $oInstantBasketMock = $this->getMockBuilder(KlarnaInstantBasket::class)
            ->setMethods(['load'])
            ->getMock();
        $oInstantBasketMock->expects($this->once())
            ->method('load')
            ->willReturn($basketExists);

        if ($oProductMock) {
            Registry::set(Article::class, $oProductMock);
        }
        Registry::set(KlarnaInstantBasket::class, $oInstantBasketMock);
        Registry::set(Button::class, $oButtonMock);

        $oSUT->startSessionAjax();
    }

    public function testExtractOrderException()
    {
        $oSUT = $this->getMockBuilder(KlarnaInstantShoppingController::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();

        $result = $oSUT->extractOrderException('testresult');

        $this->assertSame('testresult', $result->getType());

        $exceptionStub = $this->getMockBuilder(ExceptionToDisplay::class)
            ->disableOriginalConstructor()
            ->setMethods(['getErrorClassType', 'getValues'])
            ->getMock();

        $exceptionStub->expects($this->any())->method('getErrorClassType')->willReturn('testresult');
        $exceptionStub->expects($this->any())->method('getValues')->willReturn(['testvalues']);

        Registry::getSession()->setVariable('Errors', ['test' => [serialize($exceptionStub)]]);

        $result = $oSUT->extractOrderException('testresult');

        $this->assertSame('testresult', $result->getType());
        $this->assertSame(['testvalues'], $result->getValues());
    }

    public function endSessionAjaxDP()
    {
        return [
            [ 12, true, true, KlarnaInstantBasket::TYPE_BASKET, 1 ],
            [ 12, true, true, KlarnaInstantBasket::TYPE_SINGLE_PRODUCT, 0 ]
        ];
    }

    /**
     * @dataProvider endSessionAjaxDP
     * @param $basketId
     * @param $loaded
     * @param $isFinalized
     * @param $type
     * @param $expectedResult
     */
    public function testEndSessionAjax($basketId, $loaded, $isFinalized, $type, $expectedResult)
    {

        $oSUT = $this->getMockBuilder(KlarnaInstantShoppingController::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendResponse'])
            ->getMock();
        $data['merchant_reference2'] = $basketId;
        $this->setProtectedClassProperty($oSUT, 'actionData', $data);

        $oInstantBasketMock =  $this->getMockBuilder(KlarnaInstantBasket::class)
            ->setMethods(['load', 'isFinalized', 'getType'])
            ->getMock();

        $oInstantBasketMock->expects($this->once())
            ->method('load')->willReturn($loaded);
        $oInstantBasketMock->expects($this->once())
            ->method('isFinalized')->willReturn($isFinalized);
        $oInstantBasketMock->expects($this->once())
            ->method('getType')->willReturn($type);
        Registry::set(KlarnaInstantBasket::class, $oInstantBasketMock);

        $oSUT->expects($this->once())
            ->method('sendResponse')
            ->with(['result' => $expectedResult]);

        $oSUT->endSessionAjax();

        $this->assertEquals(
            $basketId,
            $this->getSessionParam('instant_shopping_basket_id')
        );
    }
}
