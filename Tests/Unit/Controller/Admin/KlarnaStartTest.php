<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;

use OxidEsales\Eshop\Core\Module\Module;
use TopConcepts\Klarna\Controller\Admin\KlarnaStart;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaStartTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $start = oxNew(KlarnaStart::class);
        $result = $start->render();
        $this->assertEquals('tcklarna_start.tpl', $result);

    }

    public function testGetKlarnaModuleInfo()
    {
        $module = $this->getMockBuilder(Module::class)->setMethods(['getInfo'])->getMock();
        $module->expects($this->once())
            ->method('getInfo')
            ->willReturn('1');

        UtilsObject::setClassInstance(Module::class, $module);
        $start = oxNew(KlarnaStart::class);
        $result = $start->getKlarnaModuleInfo();

        $this->assertEquals(' VERSION 1', $result);
    }
}
