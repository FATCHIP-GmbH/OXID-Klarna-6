<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\InstantShopping;

use Requests_Response;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class HttpClientTest extends ModuleUnitTestCase
{

    public function testResolveCredentials()
    {
        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['updateButton'])
            ->getMock();

        $httpClient->resolveCredentials();

        $credentials = $this->getProtectedClassProperty($httpClient, 'aCredentials');

        $this->assertEmpty($credentials["mid"]);

        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['updateButton'])
            ->getMock();

        $this->getConfig()->saveShopConfVar('str', 'sKlarnaDefaultCountry', 'DE', null, 'module:tcklarna');
        $this->getConfig()->saveShopConfVar('str', 'sKlarnaMerchantId', 'merchantId', null, 'module:tcklarna');
        $this->getConfig()->saveShopConfVar('str', 'sKlarnaPassword', 'pass', null, 'module:tcklarna');
        $this->getConfig()->saveShopConfVar('aarr', 'aKlarnaCreds_DE', false, null, 'module:tcklarna');

        $httpClient->resolveCredentials();

        $credentials = $this->getProtectedClassProperty($httpClient, 'aCredentials');

        $this->assertSame('merchantId', $credentials["mid"]);
        $this->assertSame('pass', $credentials["password"]);
    }

    public function testCreateButton()
    {
        $response = new Requests_Response();
        $response->body = 'test';
        $response->status_code = 200;

        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['post', 'handleResponse'])
            ->getMock();

        $httpClient->expects($this->once())->method('post')->willReturn($response);
        $httpClient->expects($this->once())->method('handleResponse');

        $httpClient->createButton(['dummy' => 'data']);

    }

    public function testUpdateButton()
    {
        $response = new Requests_Response();
        $response->body = 'test';
        $response->status_code = 200;

        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['put', 'handleResponse'])
            ->getMock();

        $httpClient->expects($this->once())->method('put')->willReturn($response);
        $httpClient->expects($this->once())->method('handleResponse');

        $httpClient->updateButton('12345',['dummy' => 'data']);
    }

    public function testGetButton()
    {
        $response = new Requests_Response();
        $response->body = 'test';
        $response->status_code = 200;

        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['get', 'handleResponse'])
            ->getMock();

        $httpClient->expects($this->once())->method('get')->willReturn($response);
        $httpClient->expects($this->once())->method('handleResponse');

        $httpClient->getButton('12345');
    }

    public function testGetOrder()
    {
        $getResponse = new Requests_Response();
        $getResponse->body = 'test';
        $getResponse->status_code = 200;

        $order['billing_address']['email'] = 'test@test.com';

        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['get','handleResponse'])
            ->getMock();
        $httpClient->expects($this->once())->method('get')->willReturn($getResponse);
        $httpClient->expects($this->once())->method('handleResponse')->willReturn($order);

        $result = $httpClient->getOrder("12345");
        $this->assertEquals($result, $order);
    }

    public function testApproveOrder()
    {
        $response = new Requests_Response();
        $response->body = 'test';
        $response->status_code = 200;

        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['post', 'handleResponse'])
            ->getMock();

        $httpClient->expects($this->once())->method('post')->willReturn($response);
        $httpClient->expects($this->once())->method('handleResponse');

        $httpClient->approveOrder('12345',['dummy' => 'data']);
    }

    public function testDeclineOrder()
    {
        $response = new Requests_Response();
        $response->body = 'test';
        $response->status_code = 200;

        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['delete', 'handleResponse'])
            ->getMock();

        $httpClient->expects($this->once())->method('delete')->willReturn($response);
        $httpClient->expects($this->once())->method('handleResponse');

        $httpClient->declineOrder('12345',['dummy' => 'data']);
    }

    /**
     * @dataProvider handleResponseDataprovider
     * @param $code
     * @param $expectedException
     */
    public function testHandleResponse($code, $expectedException)
    {
        $httpClient = $this->getMockBuilder(HttpClient::class)->disableOriginalConstructor()->setMethods(
            ['addItemToOrderLines'])->getMock();

        $response = new Requests_Response();

        if($code == 400){
            $response->body = json_encode(['error_messages' => ['test']]);
        }
        $response->status_code = $code;
        !$expectedException ?: $this->expectException($expectedException);

        $method = self::getMethod('handleResponse', HttpClient::class);
        $result = $method->invokeArgs($httpClient, [$response, __CLASS__, __METHOD__]);

        if($code === 200) {
            $this->assertTrue($result);
        }

        if($code === 201) {
            $response->body = json_encode(['test']);
            $method = self::getMethod('handleResponse', HttpClient::class);
            $result = $method->invokeArgs($httpClient, [$response, __CLASS__, __METHOD__]);

            $this->assertSame('test', $result[0]);
        }

    }

    public function handleResponseDataprovider()
    {
        return [
            [400, KlarnaClientException::class],
            [200, null],
            [201, null]
        ];
    }

}