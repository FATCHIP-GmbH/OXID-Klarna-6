<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaOrder;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaCheckoutClientTest extends ModuleUnitTestCase
{

    public function testCreateOrUpdateOrder()
    {
        $getResponse = new \Requests_Response();
        $getResponse->body = 'test';
        $getResponse->status_code = 200;

        $order['billing_address']['email'] = 'test@test.com';
        $order['order_id'] = 1;

        $checkoutClient = $this->getMockBuilder(KlarnaCheckoutClient::class)
            ->setMethods(['post', 'handleResponse', 'formatOrderData'])
            ->getMock();
        $checkoutClient->expects($this->once())->method('post')->willReturn($getResponse);
        $checkoutClient->expects($this->once())->method('handleResponse')->willReturn($order);
        $checkoutClient->expects($this->once())->method('formatOrderData')->willReturn(json_encode(['dummy' => 'data']));
        $result = $checkoutClient->createOrUpdateOrder();

        $orderId = $this->getSessionParam('klarna_checkout_order_id');
        $email = $this->getSessionParam('klarna_checkout_user_email');

        $this->assertEquals($orderId, 1);
        $this->assertEquals($email, 'test@test.com');

        $this->assertEquals($order, $result);

        $exceptionMock = $this->getMockBuilder(KlarnaCheckoutClient::class)
            ->setMethods(['postOrder'])
            ->getMock();
        $exceptionMock->expects($this->at(0))->method('postOrder')->will($this->throwException(new KlarnaOrderNotFoundException()));

        $result = $exceptionMock->createOrUpdateOrder(['dummy' => 'data']);
        $this->assertLoggedException(KlarnaOrderNotFoundException::class, 'KLARNA_ORDER_NOT_FOUND');
        $this->assertEmpty($result);

    }

    public function testInitOrder()
    {
        $checkoutClient = oxNew(KlarnaCheckoutClient::class);
        $order = $this->getMockBuilder(KlarnaOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutClient->initOrder($order);
        $property = $this->getProtectedClassProperty($checkoutClient,'_oKlarnaOrder');
        $this->assertEquals($property, $order);

    }

    public function testGetOrderId()
    {
        $checkoutClient = oxNew(KlarnaCheckoutClient::class);

        $result = $checkoutClient->getOrderId();
        $this->assertEquals($result, "");

        $this->getSession()->setVariable('klarna_checkout_order_id', 1);
        $result = $checkoutClient->getOrderId();
        $this->assertEquals($result, 1);

        $order['order_id'] = 1;
        $this->setProtectedClassProperty($checkoutClient,'aOrder',$order);
        $result = $checkoutClient->getOrderId();
        $this->assertEquals($order['order_id'], $result);
    }

    public function testGetHtmlSnippet()
    {
        $checkoutClient = oxNew(KlarnaCheckoutClient::class);
        $result = $checkoutClient->getHtmlSnippet();
        $this->assertFalse($result);

        $order['html_snippet'] = 'test';
        $this->setProtectedClassProperty($checkoutClient,'aOrder',$order);
        $result = $checkoutClient->getHtmlSnippet();
        $this->assertEquals($order['html_snippet'], $result);

    }

    public function testGetOrder()
    {
        $getResponse = new \Requests_Response();
        $getResponse->body = 'test';
        $getResponse->status_code = 200;

        $order['billing_address']['email'] = 'test@test.com';

        $checkoutClient = $this->getMockBuilder(KlarnaCheckoutClient::class)
            ->setMethods(['get','handleResponse', 'getOrderId'])
            ->getMock();
        $checkoutClient->expects($this->once())->method('get')->willReturn($getResponse);
        $checkoutClient->expects($this->once())->method('handleResponse')->willReturn($order);
        $checkoutClient->expects($this->once())->method('getOrderId')->willReturn(1);

        $result = $checkoutClient->getOrder();
        $param = $this->getSessionParam('klarna_checkout_user_email');

        $this->assertEquals($param, 'test@test.com');
        $this->assertEquals($result, $order);

    }
}
