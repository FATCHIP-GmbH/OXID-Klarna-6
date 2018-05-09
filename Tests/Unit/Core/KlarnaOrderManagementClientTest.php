<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\Exception\KlarnaCaptureNotAllowedException;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderManagementClientTest extends ModuleUnitTestCase
{

    /**
     * @dataProvider patchDataProvider
     */
    public function testPatchOrder($method, $params)
    {
        $body = ['test' => 'test'];
        $getResponse = new \Requests_Response();
        $getResponse->body = json_encode($body);
        $getResponse->status_code = 200;

        $checkoutClient = $this->createStub(
            KlarnaOrderManagementClient::class,
            [
                'patch' => $getResponse,
                'get' => $getResponse,
                'post' => $getResponse,
            ]
        );

        $result = call_user_func_array([$checkoutClient, $method], $params);

        $this->assertEquals($body, $result);
    }

    public function patchDataProvider()
    {
        return [
            ['getOrder', [1]],
            ['acknowledgeOrder', [1]],
            ['cancelOrder', [1]],
            ['getAllCaptures', [1]],
            ['sendOxidOrderNr', [1, 1]],
            ['updateOrderLines', [1, 1]],
            ['captureOrder', [1, 1]],
            ['createOrderRefund', [1, 1]],
            ['addShippingToCapture', [1, 1, 1]],
        ];
    }

    /**
     * @dataProvider handleResponseDataprovider
     */
    public function testHandleResponse($code, $expectedException, $expectedMessage = '')
    {
        $method = new \ReflectionMethod(KlarnaOrderManagementClient::class, 'handleResponse');
        $method->setAccessible(true);

        $klarnaOrderManagementClient = $this->createStub(KlarnaOrderManagementClient::class, ['getOrder' => null]);

        $response = new \Requests_Response();

        if ($code !== 200) {
            $response->body = json_encode(['test' => 'test']);
        }

        if ($code == 400 || $code == 401) {
            $response->body = json_encode(['error_messages' => ['test']]);
        }

        if ($code == 404) {
            $response->body = '<title>404</title>';
        }

        if ($expectedException == KlarnaCaptureNotAllowedException::class) {
            $response->body = json_encode([
                'error_messages' => ['some error'],
                'error_code' => 'CAPTURE_NOT_ALLOWED']);
        }

        $response->status_code = $code;

        $this->setExpectedException($expectedException, $expectedMessage);
        $result = $method->invokeArgs($klarnaOrderManagementClient, [$response, __CLASS__, __METHOD__]);

        if ($code === 200) {//assert only for status code 200
            $this->assertTrue($result);
        }

    }

    public function handleResponseDataprovider()
    {
        return [
            [200, null],
            [400, KlarnaClientException::class],
            [401, KlarnaWrongCredentialsException::class, 'KLARNA_UNAUTHORIZED_REQUEST'],
            [403, KlarnaOrderReadOnlyException::class],
            [403, KlarnaCaptureNotAllowedException::class],
            [404, KlarnaOrderNotFoundException::class, 'KLARNA_ORDER_NOT_FOUND'],
            [0, KlarnaClientException::class],
        ];
    }
}
