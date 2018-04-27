<?php

namespace TopConcepts\Klarna\Tests\Unit\Controllers\Admin;

use TopConcepts\Klarna\Controller\Admin\KlarnaDesign;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaDesignTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $obj = new KlarnaDesign();
        $result = $obj->render();

        $viewData = $obj->getViewData();
        $this->assertEquals('tcklarna_design.tpl',$result);
        $this->assertEquals('de_de', $viewData['locale']);
        $this->assertEquals('KCO', $viewData['mode']);

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        putenv("HTTP_X_REQUESTED_WITH=xmlhttprequest");
        $obj = $this->createStub(KlarnaDesign::class, ['getMultiLangData' => 'test']);
        $result = $obj->render();
        $this->assertEquals('"test"', $result);

    }
}
