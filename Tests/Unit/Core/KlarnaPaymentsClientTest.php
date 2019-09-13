<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Model\KlarnaOrder;
use TopConcepts\Klarna\Model\KlarnaUser;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaPaymentsClientTest extends ModuleUnitTestCase
{

    public function testGetSessionData()
    {
        $body = ['test' => 'test'];
        $getResponse = $this->getPostResponse($body);
        $checkoutClient = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['get', 'getSessionId'])
            ->getMock();
        $checkoutClient->expects($this->once())->method('get')->willReturn($getResponse);
        $checkoutClient->expects($this->once())->method('getSessionId')->willReturn(1);
        $result = $checkoutClient->getSessionData();
        $this->assertEquals($body, $result);

        $exception = new KlarnaClientException('test', 404);
        $mock = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['get', 'handleResponse'])
            ->getMock();
        $mock->expects($this->once())->method('get')->willReturn($getResponse);
        $mock->expects($this->once())->method('handleResponse')->will($this->throwException($exception));
        $result = $mock->getSessionData();
        $this->assertNull($result);

    }

    public function testCreateNewOrder()
    {
        $body = ['order_id' => 1];

        $checkoutClient = $this->prepareNewOrderTestStub($body, 200);
        $result = $checkoutClient->createNewOrder();

        $this->assertEquals($result, $body);
        $this->assertEquals($this->getSessionParam('kp_order_id'), 1);

    }

    /**
     * @dataProvider exceptionDataprovider
     * @param $errorCode
     */
    public function testCreateNewOrderWithException($errorCode)
    {
        $checkoutClient = $this->prepareNewOrderTestStub(['order_id' => 1], $errorCode);
        $result = $checkoutClient->createNewOrder();
        $this->assertFalse($result);
    }

    public function exceptionDataprovider()
    {
        return [
            [399],
            [400],
            [403],
            [404]
        ];
    }

    protected function prepareNewOrderTestStub($body, $statusCode)
    {
        $user = $this->getMockBuilder(KlarnaUser::class)
            ->setMethods(['getKlarnaPaymentData'])
            ->getMock();
        $user->expects($this->once())->method('getKlarnaPaymentData')->willReturn([]);
        $order = $this->getMockBuilder(KlarnaPayment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getChangedData', 'getOrderData'])
            ->getMock();
        $order->expects($this->any())->method('getChangedData')->willReturn(['billing_address', 'test']);
        $order->expects($this->once())->method('getOrderData')->willReturn(['test', 'test']);

        $checkoutClient = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['post', 'getUser', 'getSessionId', 'formatAndShowErrorMessage'])
            ->getMock();
        $checkoutClient->expects($this->once())->method('post')->willReturn($this->getPostResponse($body, $statusCode));
        $checkoutClient->expects($this->once())->method('getUser')->willReturn($user);
        $checkoutClient->expects($this->any())->method('getSessionId')->willReturn(1);
        $checkoutClient->expects($this->any())->method('formatAndShowErrorMessage')->willReturn('');

        $this->setSessionParam('sAuthToken', 'test');
        $checkoutClient->initOrder($order);
        $sessionData['session_id'] = 1;
        $this->setProtectedClassProperty($checkoutClient,'aSessionData', $sessionData);

        return $checkoutClient;
    }

    public function testUpdateSession()
    {
        $body = ['test' => 'test'];

        $checkoutClient = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['post', 'getSessionId'])
            ->getMock();
        $checkoutClient->expects($this->once())->method('post')->willReturn($this->getPostResponse($body));
        $checkoutClient->expects($this->once())->method('getSessionId')->willReturn(1);

        $result = $checkoutClient->updateSession($body);

        $this->assertEquals($body, $result);
    }

    public function testInitOrder()
    {
        $klarnaPayment = $this->getMockBuilder(KlarnaPayment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $klarnaPaymentClient = oxNew(KlarnaPaymentsClient::class);
        $result = $klarnaPaymentClient->initOrder($klarnaPayment);

        $this->assertTrue($result instanceof KlarnaPaymentsClient);

    }

    public function testCreateOrUpdateSession()
    {
        $clientMock = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['formatOrderData'])->getMock();
        $clientMock->expects($this->at(0))->method('formatOrderData')->willReturn([null,null]);
        $sessionData = ['test'];
        $this->setProtectedClassProperty($clientMock,'aSessionData', $sessionData);
        $result = $clientMock->createOrUpdateSession();
        $this->assertEquals($result, $sessionData);

    }

    public function testCreateOrUpdateSession_1()
    {
        $body = ['test' => 'test'];
        $klarnaPaymentClient = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['post'])->getMock();
        $klarnaPaymentClient->expects($this->once())->method('post')->willReturn($this->getPostResponse($body));
        $klarnaPaymentClient->initOrder($this->createKPOrderMock());
        $result = $klarnaPaymentClient->createOrUpdateSession();
        $this->assertEquals($result, $body);
        $this->assertEquals($this->getSession()->getVariable('klarna_session_data'), $body);
        $this->assertTrue($this->getSession()->hasVariable('sSessionTimeStamp'));
    }

    public function testCreateOrUpdateSession_2() {
        $body = ['test' => 'test'];
        $klarnaPaymentClient = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['post'])->getMock();
        $klarnaPaymentClient->expects($this->once())->method('post')->willReturn($this->getPostResponse($body));
        $klarnaPaymentClient->initOrder($this->createKPOrderMock());
        $sessionData = ['test'];
        $sessionData['payment_method_categories'] = ['test1', 'test2'];
        $this->setProtectedClassProperty($klarnaPaymentClient, 'sSessionId', 1);
        $this->setProtectedClassProperty($klarnaPaymentClient, 'aSessionData', $sessionData);
        $result = $klarnaPaymentClient->createOrUpdateSession();
        $this->assertEquals($result, $body);

    }
    public function testCreateOrUpdateSession_3()
    {
        $body = ['test' => 'test'];
        $sessionData = ['test'];
        $sessionData['payment_method_categories'] = ['test1', 'test2'];
        $klarnaPaymentClient = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['post'])->getMock();
        $klarnaPaymentClient->initOrder($this->createKPOrderMock());
        $exceptionMock = $this->getMockBuilder(KlarnaPaymentsClient::class)
            ->setMethods(['updateSession', 'post'])->getMock();
        $this->setProtectedClassProperty($exceptionMock,'sSessionId', 1);
        $this->setProtectedClassProperty($exceptionMock,'aSessionData', $sessionData);
        $exceptionMock->initOrder($this->createKPOrderMock());

        $exceptionTestBody = ['test2' => 'test2'];
        $exceptionMock->expects($this->any())->method('post')->willReturn($this->getPostResponse($exceptionTestBody));
        $exceptionMock->expects($this->any())->method('updateSession')->willThrowException(new KlarnaOrderNotFoundException());
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        putenv("HTTP_X_REQUESTED_WITH=xmlhttprequest");
        $result = $exceptionMock->createOrUpdateSession();
        $this->assertEquals($this->getSession()->getVariable('klarna_session_data'), $exceptionTestBody);
        $this->assertEquals($result, $exceptionTestBody);
    }
    public function createKPOrderMock() {
        $order = $this->getMockBuilder(KlarnaPayment::class)
            ->setMethods(['getOrderData', 'saveCheckSums', 'setStatus', 'getStatus', 'isAuthorized', 'getChangedData'])
            ->disableOriginalConstructor()
            ->getMock();
        $order->expects($this->any())->method('getOrderData')->willReturn(['test', 'test']);
        $order->expects($this->any())->method('saveCheckSums')->willReturn('');
        $order->expects($this->any())->method('setStatus')->willReturn('');
        $order->expects($this->any())->method('getStatus')->willReturn('authorize');
        $order->expects($this->any())->method('isAuthorized')->willReturn(true);
        $order->expects($this->any())->method('getChangedData')->willReturn(['billing_address' => 'billingaddresstest']);

        return $order;
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

    protected function getPostResponse($body, $statusCode = 200)
    {
        $postResponse = new \Requests_Response();
        $postResponse->body = json_encode($body);
        $postResponse->status_code = $statusCode;

        return $postResponse;
    }
}
