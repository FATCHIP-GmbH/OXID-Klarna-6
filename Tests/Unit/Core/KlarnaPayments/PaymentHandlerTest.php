<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\KlarnaPayments;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Core\KlarnaPayments\PaymentHandler;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Core\KlarnaPayment as PaymentContext;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class PaymentHandlerTest extends ModuleUnitTestCase
{

    public function test__construct()
    {
        $oSession = Registry::getSession();
        $oSession->setUser(oxNew(User::class));
        $oSession->setBasket(oxNew(Basket::class));
        $oHandler = new PaymentHandler();
        $context = $this->getProtectedClassProperty($oHandler, 'context');
        $this->assertInstanceOf(KlarnaPayment::class, $context);
    }


    public function executeDP()
    {
        $id = '123';
        $responseAccepted = [
            'order_id' => $id,
            'fraud_status' =>  'ACCEPTED'
        ];
        $responsePending = [
            'order_id' => $id,
            'fraud_status' =>  'PENDING'
        ];

        $errors = ['GENERIC_ERROR'];

        return [
            [$responseAccepted, [], true, $id],
            [null, [$errors], false, null],
            [$responsePending, [], false, null]
        ];
    }

    /**
     * @dataProvider executeDP
     * @param $createResponse
     * @param $errors
     * @param $expectedFraudCheckResult
     * @param $expectedKlarnaId
     */
    public function testExecute($createResponse, $errors , $expectedFraudCheckResult, $expectedKlarnaId)
    {
        $oPaymentContext = $this->getMockBuilder(PaymentContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['validateOrder', 'getError'])
            ->getMock();
        $oPaymentContext->expects($this->once())
            ->method('validateOrder');
        $oPaymentContext->expects($this->once())
            ->method('getError')
            ->willReturn($errors);
        $clientMock = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['createNewOrder'])
            ->getMock();
        if ($createResponse) {
            $clientMock->expects($this->once())
                ->method('createNewOrder')
                ->willReturn($createResponse);
        }
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveMerchantIdAndServerMode', 'save'])
            ->getMock();

        if($expectedFraudCheckResult) {
            $orderMock->expects($this->once())
                ->method('saveMerchantIdAndServerMode');
            $orderMock->expects($this->once())
                ->method('save');
        } else {
            $orderMock->expects($this->never())
                ->method('saveMerchantIdAndServerMode');
            $orderMock->expects($this->never())
                ->method('save');
        }

        $oHandlerMock = $this->getMockBuilder(PaymentHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContext'])
            ->getMock();
        $this->setProtectedClassProperty($oHandlerMock, 'httpClient', $clientMock);
        $this->setProtectedClassProperty($oHandlerMock, 'context', $oPaymentContext);

        $result = $oHandlerMock->execute($orderMock);

        $this->assertEquals($expectedKlarnaId, $orderMock->oxorder__tcklarna_orderid->value);
        $this->assertEquals($expectedFraudCheckResult, $result);
    }
}
