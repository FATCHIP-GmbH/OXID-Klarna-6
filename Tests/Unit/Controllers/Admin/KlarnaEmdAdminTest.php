<?php

namespace TopConcepts\Klarna\Tests\Unit\Controllers\Admin;

use TopConcepts\Klarna\Controllers\Admin\KlarnaEmdAdmin;
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

        $this->assertEquals('kl_klarna_emd_admin.tpl', $result);
        $this->assertNotEmpty($activePayment);
    }
}
