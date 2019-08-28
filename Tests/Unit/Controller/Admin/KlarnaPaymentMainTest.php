<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use TopConcepts\Klarna\Controller\Admin\KlarnaPaymentMain;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaPaymentMainTest extends ModuleUnitTestCase
{

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender($id, $expected)
    {
        $stub = $this->getMockBuilder(KlarnaPaymentMain::class)->setMethods(['getEditObjectid'])->getMock();
        $stub->expects($this->any())->method('getEditObjectid')->willReturn($id);
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
