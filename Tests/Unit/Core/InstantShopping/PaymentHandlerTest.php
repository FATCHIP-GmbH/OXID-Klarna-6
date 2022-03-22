<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\InstantShopping;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;
use TopConcepts\Klarna\Core\InstantShopping\PaymentHandler;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class PaymentHandlerTest extends ModuleUnitTestCase
{
    public function testGetContext_excpetion()
    {
        $this->expectException(StandardException::class);
        new PaymentHandler();
    }

    public function testGetContext()
    {
        $context = ['klarna_context'];
        $this->setConfigParam(PaymentHandler::ORDER_CONTEXT_KEY, $context);
        $oHandler = new PaymentHandler();
        $this->assertEquals($context, $this->getProtectedClassProperty($oHandler, 'context', $context));
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

        return [
            [$responseAccepted, true, $id],
            [$responsePending, false, null]
        ];
    }

    /**
     * @dataProvider executeDP
     * @param $approveResponse
     * @param $expectedFraudCheckResult
     * @param $expectedKlarnaId
     * @throws StandardException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaClientException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException
     */
    public function testExecute($approveResponse, $expectedFraudCheckResult, $expectedKlarnaId)
    {
        $context = [
            'order' => ['merchant_reference2' => ''],
            'authorization_token' => 'xyz'
        ];
        $this->setConfigParam(PaymentHandler::ORDER_CONTEXT_KEY, $context);
        $clientMock = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['approveOrder'])
            ->getMock();
        $clientMock->expects($this->once())
            ->method('approveOrder')
            ->with($context['authorization_token'], $context['order'])
            ->willReturn($approveResponse);
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

        $oHandler = new PaymentHandler();
        $this->setProtectedClassProperty($oHandler, 'httpClient', $clientMock);

        $result = $oHandler->execute($orderMock);

        $this->assertEquals($expectedKlarnaId, $orderMock->oxorder__tcklarna_orderid->value);
        $this->assertEquals($expectedFraudCheckResult, $result);
    }
}
