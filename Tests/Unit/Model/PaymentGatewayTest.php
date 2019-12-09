<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\Model;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\User;
use TopConcepts\Klarna\Core\InstantShopping\PaymentHandler;
use TopConcepts\Klarna\Model\KlarnaPayment;
use TopConcepts\Klarna\Model\PaymentGateway;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class PaymentGatewayTest extends ModuleUnitTestCase
{

    public function createPaymentHandlerDP()
    {
        return [
            [KlarnaPayment::KLARNA_INSTANT_SHOPPING, \TopConcepts\Klarna\Core\InstantShopping\PaymentHandler::class],
            [KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID, \TopConcepts\Klarna\Core\KlarnaPayments\PaymentHandler::class],
            ['other_payment', null]
        ];
    }

    /**
     * @dataProvider createPaymentHandlerDP
     * @param $paymentId
     * @param $expectedHandlerType
     * @throws \ReflectionException
     */
    public function testCreatePaymentHandler($paymentId, $expectedHandlerType)
    {
        $context = new \stdClass();
        $this->setConfigParam(PaymentHandler::ORDER_CONTEXT_KEY, $context);

        $oSession = Registry::getSession();
        $oSession->setUser(oxNew(User::class));
        $oSession->setBasket(oxNew(Basket::class));

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFieldData'])
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getFieldData')
            ->with('OXPAYMENTTYPE')
            ->willReturn($paymentId);

        $oGateway = oxNew(PaymentGateway::class);
        $method = new \ReflectionMethod(PaymentGateway::class, 'createPaymentHandler');
        $method->setAccessible(true);
        $oHandler = $method->invoke($oGateway, $orderMock);
        if ($expectedHandlerType) {
            $this->assertInstanceOf($expectedHandlerType, $oHandler);
        } else {
            $this->assertFalse($oHandler);
        }
    }

    public function executePaymentDP()
    {
        $getHandlerMock = function($result, $error) {
            $oHandlerMock = $this->getMockBuilder(\TopConcepts\Klarna\Core\KlarnaPayments\PaymentHandler::class)
                ->disableOriginalConstructor()
                ->setMethods(['execute', 'getError'])
                ->getMock();
            $oHandlerMock->expects($this->once())
                ->method('execute')
                ->willReturn($result);
            $oHandlerMock->expects($this->once())
                ->method('getError')
                ->willReturn($error);
            return $oHandlerMock;
        };


        return [
            [$getHandlerMock(true, null), true, null],
            [$getHandlerMock(false, 'GENERIC_ERROR'), false, 'GENERIC_ERROR'],
            [false, true, null],
        ];
    }

    /**
     * @dataProvider executePaymentDP
     * @param $oHandlerMock
     * @param $expectedResult
     * @param $expectedError
     */
    public function testExecutePayment($oHandlerMock, $expectedResult, $expectedError)
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oGateway = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\PaymentGateway::class)
            ->setMethods(['createPaymentHandler'])
            ->getMock();
        $oGateway->expects($this->once())
            ->method('createPaymentHandler')
            ->willReturn($oHandlerMock)
        ;
        $result = $oGateway->executePayment(10.00, $orderMock);
        $this->assertEquals($expectedResult, $result);
        $lastError = $this->getProtectedClassProperty($oGateway, '_sLastError');
        $this->assertEquals($expectedError, $lastError);
    }
}
