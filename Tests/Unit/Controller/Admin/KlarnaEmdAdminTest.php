<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Request;
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

    public function testSave()
    {
        $payments = ['klarna_checkout' => ['oxpayments__tcklarna_paymentoption' => 'other']];

        $oRequest = $this->createStub(Request::class, ['getRequestEscapedParameter' => $payments]);
        $emd = oxNew(KlarnaEmdAdmin::class);
        $this->setProtectedClassProperty($emd, '_oRequest',  $oRequest);

        $emd->save();

        $payment = oxNew(Payment::class);
        $payment->load('klarna_checkout');

        $this->assertEquals($payment->oxpayments__tcklarna_paymentoption->value, 'other');
    }
}
