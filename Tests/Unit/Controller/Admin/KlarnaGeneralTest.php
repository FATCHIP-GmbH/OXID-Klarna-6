<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use TopConcepts\Klarna\Controller\Admin\KlarnaGeneral;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaGeneralTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $general = new KlarnaGeneral();
        $result = $general->render();
        $this->assertEquals("tcklarna_general.tpl", $result);

        $expected = ['test' => 'test'];
        $notSet = ['notSet' => 'test'];
        $this->setProtectedClassProperty($general, '_aKlarnaCountryCreds', $expected);
        $this->setProtectedClassProperty($general, '_aKlarnaCountries', $notSet);

        $general->render();

        $viewData = $general->getViewData();

        $this->assertEquals(json_encode($notSet), $viewData['tcklarna_countryList']);
        $this->assertEquals($expected, $viewData['tcklarna_countryCreds']);
        $this->assertEquals($notSet, $viewData['tcklarna_notSetUpCountries']);

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        putenv("HTTP_X_REQUESTED_WITH=xmlhttprequest");
        $general = $this->getMockBuilder(KlarnaGeneral::class)->setMethods(['getMultiLangData'])->getMock();
        $general->expects($this->once())->method('getMultiLangData')->willReturn('test');
        $result = $general->render();
        $this->assertEquals('"test"', $result);
    }

    public function testConvertNestedParams()
    {
        $notSet = ['DE' => 'test'];
        $expected = [
            'aKlarnaCreds_test' => ['key' => 'test'],
        ];
        $methodReflection = new \ReflectionMethod(KlarnaGeneral::class, 'convertNestedParams');
        $methodReflection->setAccessible(true);

        $general = $this->getMockBuilder(KlarnaGeneral::class)->setMethods(['removeConfigKeys'])->getMock();
        $general->expects($this->any())->method('removeConfigKeys')->willReturn(null);
        $this->setProtectedClassProperty($general, '_aKlarnaCountries', $notSet);
        $result = $methodReflection->invokeArgs($general, ['nestedArray' => $expected]);

        $this->assertEquals(['aKlarnaCreds_test' => 'key => test'], $result);

        $result = $methodReflection->invokeArgs($general, ['nestedArray' => 'invalid']);
        $this->assertEquals('invalid', $result);
    }
}
