<?php

namespace TopConcepts\Klarna\Tests\Unit\Controllers\Admin;

use TopConcepts\Klarna\Controllers\Admin\KlarnaPaymentMain;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaPaymentMainTest extends ModuleUnitTestCase
{

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender($id, $expected)
    {
        $stub = $this->createStub(KlarnaPaymentMain::class, ['getEditObjectid' => $id]);
        $stub->render();
        $result = $stub->getViewData()['isKlarnaPayment'];
        $this->assertEquals($expected, $result);


    }

    public function renderDataProvider()
    {
        return [
            ['klarna_checkout', true],
            ['test', false],
        ];

    }
}
