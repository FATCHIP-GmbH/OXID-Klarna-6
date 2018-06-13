<?php

namespace TopConcepts\Klarna\Testes\Unit\Controllers;


use ReflectionClass;
use TopConcepts\Klarna\Controller\KlarnaValidationController;
use TopConcepts\Klarna\Core\KlarnaLogs;
use TopConcepts\Klarna\Core\KlarnaOrderValidator;
use TopConcepts\Klarna\Model\KlarnaPayment;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaValidationControllerTest
 * @package TopConcepts\Klarna\Testes\Unit\Controllers
 * @covers \TopConcepts\Klarna\Controller\KlarnaValidationController
 */
class KlarnaValidationControllerTest extends ModuleUnitTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setModuleConfVar('blKlarnaLoggingEnabled', true, 'bool');
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->setModuleConfVar('blKlarnaLoggingEnabled', false, 'bool');
    }

    /**
     * @dataProvider initDataProvider
     * @param $requestBody
     * @param $isValid
     * @param $errors
     * @param $eRes
     */
    public function testInit($requestBody, $isValid, $errors, $eRes)
    {
        \oxUtilsHelper::$iCode = null;
        $data                  = json_decode($requestBody, true);
        $validator             = $this->getMock(KlarnaOrderValidator::class, ['validateOrder', 'isValid', 'getResultErrors'], [$data]);
        $validator->expects($this->once())
            ->method('isValid')
            ->willReturn($isValid);
        $validator->expects($this->any())
            ->method('getResultErrors')
            ->willReturn($errors);

        $validationController = $this->getMock(KlarnaValidationController::class, ['getRequestBody', 'logKlarnaData', 'getValidator', 'setValidResponseHeader']);
        $validationController->expects($this->once())
            ->method('getRequestBody')
            ->willReturn($requestBody);
        $validationController->expects($this->once())
            ->method('getValidator')
            ->willReturn($validator);
        $validationController->expects($this->once())
            ->method('logKlarnaData');
        $validationController->expects($this->any())
            ->method('setValidResponseHeader');


        $this->setProtectedClassProperty($validationController, 'order_id', $data['order_id']);

        $validationController->init();

        $this->assertEquals($eRes['code'], \oxUtilsHelper::$iCode);
    }

    public function initDataProvider()
    {
        $validResponse   = ['urlShouldContain' => "", 'code' => null];
        $invalidResponse = ['urlShouldContain' => "klarnaInvalid=1", 'code' => 303];

        return [
            ["{\"order_id\": \"0000\"}", true, [], $validResponse],
            ["{\"order_id\": \"0001\"}", false, ['MY_ERROR' => 33], $invalidResponse],
        ];
    }

    public function testInit_errorsAndLogs()
    {
        $errors    = ['MY_ERROR' => 33, 'CANT_BUY' => 10];
        $validator = $this->createStub(KlarnaOrderValidator::class, [
            'validateOrder'   => null,
            'isValid'         => false,
            'getResultErrors' => $errors,
        ]);

        $randId      = "rand_" . rand(1, 100000);
        $requestBody = "{\"order_id\": \"$randId\", \"fake_order\": \"data\"}";
        $data        = json_decode($requestBody, true);

        $validationController = $this->getMock(KlarnaValidationController::class, ['getRequestBody', 'getValidator']);
        $validationController->expects($this->once())
            ->method('getRequestBody')
            ->willReturn($requestBody);
        $validationController->expects($this->once())
            ->method('getValidator')
            ->willReturn($validator);

        $this->setProtectedClassProperty($validationController, 'order_id', $data['order_id']);

        $validationController->init();

        $result = $this->getDb()->select("SELECT * FROM `tcklarna_logs` WHERE `TCKLARNA_ORDERID` = '$randId'");
        $this->assertNotEmpty($result->count());

        $this->assertEquals(303, \oxUtilsHelper::$iCode);
        $this->assertContains('klarnaInvalid=1&MY_ERROR=33&CANT_BUY=10', \oxUtilsHelper::$sRedirectUrl);
    }


    public function testGetValidator()
    {
        $randId               = "rand_" . rand(1, 100000);
        $requestBody          = "{\"order_id\": \"$randId\", \"fake_order\": \"data\"}";
        $validationController = new KlarnaValidationController();
        $this->setProtectedClassProperty($validationController, 'requestBody', $requestBody);
        $class  = new ReflectionClass(get_class($validationController));
        $method = $class->getMethod('getValidator');
        $method->setAccessible(true);
        $result = $method->invokeArgs($validationController, []);

        $this->assertInstanceOf(KlarnaOrderValidator::class, $result);
        $this->assertEquals($randId, $this->getProtectedClassProperty($validationController, 'order_id'));
    }
}
