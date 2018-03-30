<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 27.03.2018
 * Time: 18:48
 */

namespace TopConcepts\Klarna\Testes\Unit\Controllers;


use TopConcepts\Klarna\Controllers\KlarnaValidationController;
use TopConcepts\Klarna\Core\KlarnaOrderValidator;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaValidationControllerTest extends ModuleUnitTestCase
{

    /**
     * @dataProvider initDataProvider
     * @param $id
     * @param $requestBody
     * @param $isValid
     * @param $errors
     * @param $rUrl
     */
    public function testInit($id, $requestBody, $isValid, $errors, $rUrl)
    {
        $validator = $this->getMock(
            KlarnaOrderValidator::class, ['validateOrder', 'isValid', 'getResultErrors'], [], '', false);
        $validator->expects($this->once())
            ->method('isValid')
            ->willReturn($isValid);

        $validator->expects($this->any())
            ->method('getResultErrors')
            ->willReturn($errors);

        $validationController = $this->getMock(KlarnaValidationController::class, ['getRequestBody', 'getValidator']);
        $validationController->expects($this->once())
            ->method('getRequestBody')
            ->willReturn($requestBody);

        $validationController->expects($this->once())
            ->method('getValidator')
            ->willReturn($validator);



        $this->markTestIncomplete("Find solution for error linked to header() function call.");
        $validationController->init();

    }

    public function initDataProvider()
    {
        return [
            ['0000', '{"order_id": "0000"}', true, [], null],
            ['0001', '{"order_id": "0001"}', false, ['MY_ERROR' => 33], '']
        ];
    }
}
