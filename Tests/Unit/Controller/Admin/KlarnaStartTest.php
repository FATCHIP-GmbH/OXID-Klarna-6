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
        $module = $this->getMock(Module::class, ['getInfo']);
        $module->expects($this->at(0))
        ->method('getInfo')
        ->will($this->returnValue('TEST'));

        $module->expects($this->at(1))
            ->method('getInfo')
            ->will($this->returnValue('1'));

        UtilsObject::setClassInstance(Module::class, $module);
        $start = oxNew(KlarnaStart::class);
        $result = $start->getKlarnaModuleInfo();

        $this->assertEquals('TEST VERSION 1', $result);
    }
}
