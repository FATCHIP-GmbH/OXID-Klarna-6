<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use TopConcepts\Klarna\Controller\Admin\KlarnaEmdAdmin;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaEmdAdminTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $emd = oxNew(KlarnaEmdAdmin::class);
        $activePayment = $emd->getViewDataElement('activePayments');
        $this->assertNull($activePayment);
        $result = $emd->render();
        $activePayment = $emd->getViewDataElement('activePayments');

        $this->assertEquals('tcklarna_emd_admin.tpl', $result);
        $this->assertNotEmpty($activePayment);
    }
}
