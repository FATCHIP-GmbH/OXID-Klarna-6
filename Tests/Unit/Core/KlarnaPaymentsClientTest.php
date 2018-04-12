<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Exception\KlarnaClientException;
use TopConcepts\Klarna\Models\KlarnaOrder;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaPaymentsClientTest extends ModuleUnitTestCase
{

    public function testGetSessionData()
    {
        $getResponse = new \Requests_Response();
        $body = ['test' => 'test'];
        $getResponse->body = json_encode($body);
        $getResponse->status_code = 200;

        $checkoutClient = $this->createStub(KlarnaPaymentsClient::class, ['get' => $getResponse, 'getSessionId' => 1]);
        $result = $checkoutClient->getSessionData();

        $this->assertEquals($body, $result);

        $exception = new KlarnaClientException('test', 404);
        $mock = $this->getMock(KlarnaPaymentsClient::class, ['get','handleResponse']);
        $mock->expects($this->any())->method('get')->willReturn($getResponse);
        $mock->expects($this->any())->method('handleResponse')->will($this->throwException($exception));
        $result = $mock->getSessionData();
        $this->assertNull($result);

    }

    public function testCreateNewOrder()
    {

    }

    public function testUpdateSession()
    {
        $body = ['test' => 'test'];
        $postResponse = new \Requests_Response();
        $postResponse->body = json_encode($body);
        $postResponse->status_code = 200;

        $checkoutClient = $this->createStub(KlarnaPaymentsClient::class, ['post' => $postResponse, 'getSessionId' => 1]);

        $result = $checkoutClient->updateSession($body);

        $this->assertEquals($body, $result);
    }

    public function testInitOrder()
    {
        $klarnaPayment = $this->getMock(KlarnaPayment::class, [], [] , "", false);
        $klarnaPaymentClient = oxNew(KlarnaPaymentsClient::class);
        $result = $klarnaPaymentClient->initOrder($klarnaPayment);

        $this->assertTrue($result instanceof KlarnaPaymentsClient);

    }

    public function testCreateOrUpdateSession()
    {
        $clientMock = $this->getMock(KlarnaPaymentsClient::class, ['formatOrderData']);
        $clientMock->expects($this->at(0))->method('formatOrderData')->willReturn([null,null]);
        $sessionData = ['test'];
        $this->setProtectedClassProperty($clientMock,'aSessionData', $sessionData);
        $result = $clientMock->createOrUpdateSession();

        $this->assertEquals($result, $sessionData);

        $body = ['test' => 'test'];
        $klarnaPaymentClient = $this->createStub(KlarnaPaymentsClient::class, ['post' => $this->getPostResponse($body)]);

        $order = $this->createStub(KlarnaOrder::class, ['getOrderData' => ['test', 'test'], 'saveCheckSums' => '', 'setStatus' => '']);
        $this->setProtectedClassProperty($klarnaPaymentClient, '_oKlarnaOrder', $order);
        $result = $klarnaPaymentClient->createOrUpdateSession();

        $this->assertEquals($result, $body);
        $this->assertEquals($this->getSession()->getVariable('klarna_session_data'), $body);
        $this->assertTrue($this->getSession()->hasVariable('sSessionTimeStamp'));

        $sessionData['payment_method_categories'] = ['test1','test2'];
        $this->setProtectedClassProperty($klarnaPaymentClient,'sSessionId', 1);
        $this->setProtectedClassProperty($klarnaPaymentClient,'aSessionData', $sessionData);
        $result = $klarnaPaymentClient->createOrUpdateSession();
        $this->assertEquals($result, $body);

        $exceptionMock = $this->getMock(KlarnaPaymentsClient::class, ['updateSession', 'formatOrderData', 'post']);
        $this->setProtectedClassProperty($exceptionMock,'sSessionId', 1);
        $this->setProtectedClassProperty($exceptionMock,'aSessionData', $sessionData);
        $this->setProtectedClassProperty($exceptionMock, '_oKlarnaOrder', $order);
        $exceptionMock->expects($this->at(0))->method('formatOrderData')->willReturn(['something','']);

        $exceptionTestBody = ['test2' => 'test2'];
        $exceptionMock->expects($this->any())->method('post')->willReturn($this->getPostResponse($exceptionTestBody));
        $exceptionMock->expects($this->any())->method('updateSession')->willThrowException(new KlarnaClientException('Test'));
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        putenv("HTTP_X_REQUESTED_WITH=xmlhttprequest");
        $result = $exceptionMock->createOrUpdateSession();

        $this->assertEquals($this->getSession()->getVariable('klarna_session_data'), $exceptionTestBody);
        $this->assertEquals($result, $exceptionTestBody);
    }

    protected function getPostResponse($body)
    {
        $postResponse = new \Requests_Response();
        $postResponse->body = json_encode($body);
        $postResponse->status_code = 200;

        return $postResponse;
    }

    public function testGetSessionId()
    {
        $paymentClient = oxNew(KlarnaPaymentsClient::class);
        $this->assertEmpty($paymentClient->getSessionId());

        $sessionData['session_id'] = 1;
        $this->setSessionParam('klarna_session_data', $sessionData);
        $result = $paymentClient->getSessionId();
        $this->assertEquals($result, 1);

        $this->setProtectedClassProperty($paymentClient, 'sSessionId', 2);
        $result = $paymentClient->getSessionId();

        $this->assertEquals($result, 2);

    }
}
