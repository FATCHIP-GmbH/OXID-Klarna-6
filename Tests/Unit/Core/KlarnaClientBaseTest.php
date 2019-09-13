<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaClientBaseTest extends ModuleUnitTestCase
{

    public function testLoadHttpHandler()
    {
        $method = new \ReflectionMethod(KlarnaClientBase::class, 'loadHttpHandler');
        $method->setAccessible(true);
        $klarnaClientBase = $this->getMockForAbstractClass(KlarnaClientBase::class);

        $sessionRequest = new \Requests_Session('test');

        $result = $this->getProtectedClassProperty($klarnaClientBase, 'session');

        $this->assertNull($result);
        $method->invokeArgs($klarnaClientBase, [$sessionRequest]);

        $result = $this->getProtectedClassProperty($klarnaClientBase, 'session');
        $this->assertEquals($sessionRequest, $result);
    }

    /**
     * @dataProvider sessionDataProvider
     */
    public function testPostGetPatchAndDelete($method)
    {
        $methodReflection = new \ReflectionMethod(KlarnaClientBase::class, $method);
        $methodReflection->setAccessible(true);

        $response = new \Requests_Response();
        $response->body = json_encode(['test']);
        $response->status_code = 200;

        $klarnaClientBase = $this->getMockForAbstractClass(KlarnaClientBase::class);
        $sessionMock = $this->getMockBuilder(\Requests_Session::class)
            ->setMethods([$method])->getMock();
        $sessionMock->expects($this->once())->method($method)->willReturn($response);

        $this->setProtectedClassProperty($klarnaClientBase,'session',$sessionMock);

        $result = $methodReflection->invokeArgs($klarnaClientBase, ['https://']);
        $this->assertEquals($response, $result);

    }

    public function sessionDataProvider()
    {
        return [
            ['post'],
            ['get'],
            ['patch'],
            ['delete']
        ];

    }

    /**
     * @dataProvider handleResponseDataprovider
     */
    public function testHandleResponse($code, $expectedException)
    {
        $method = new \ReflectionMethod(KlarnaClientBase::class, 'handleResponse');
        $method->setAccessible(true);

        $klarnaClientBase = $this->getMockForAbstractClass(KlarnaClientBase::class);

        $response = new \Requests_Response();

        if($code == 400){
            $response->body = json_encode(['error_messages' => ['test']]);
        }
        $response->status_code = $code;
        !$expectedException ?: $this->expectException($expectedException);
        $result = $method->invokeArgs($klarnaClientBase, [$response, __CLASS__, __METHOD__]);

        if($code === 200) {//assert only for status code 200
            $this->assertTrue($result);
        }

    }

    public function handleResponseDataprovider()
    {
        return [
            [200, null],
            [400, KlarnaClientException::class],
            [401, KlarnaWrongCredentialsException::class],
            [403, KlarnaOrderReadOnlyException::class],
            [404, KlarnaOrderNotFoundException::class],
            [0, KlarnaClientException::class],
            [422, KlarnaClientException::class],
        ];
    }

    public function testResetInstance()
    {
        //Instanciate using a child class
        $result = KlarnaPaymentsClient::getInstance();
        $this->assertNotNull($result);

        $result = KlarnaPaymentsClient::resetInstance();
        $this->assertNull($result);
    }
}
