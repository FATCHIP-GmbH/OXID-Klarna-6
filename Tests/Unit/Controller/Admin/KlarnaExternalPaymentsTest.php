<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Request;
use TopConcepts\Klarna\Controller\Admin\KlarnaExternalPayments;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaExternalPaymentsTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $controller = new KlarnaExternalPayments();
        $this->setModuleConfVar('sKlarnaActiveMode', 'test');
        $result = $controller->render();

        $viewData = $controller->getViewData();

        $this->assertEquals('tcklarna_external_payments.tpl',$result);
        $this->assertEquals('test', $viewData['mode']);
        $this->assertNotEmpty($viewData['activePayments']);
        $this->assertEquals(KlarnaConsts::getKlarnaExternalPaymentNames(), $viewData['paymentNames']);

    }

    public function testGetMultilangUrls()
    {
        $controller = new KlarnaExternalPayments();
        $result = $controller->getMultilangUrls();
        $this->assertNotEmpty($result);
        $this->assertJson($result);
    }

    public function testSave()
    {
        $payments = ['klarna_pay_later' => ['oxpayments__tcklarna_paymentoption' => 'other']];

        $oRequest = $this->createStub(Request::class, ['getRequestEscapedParameter' => $payments]);
        $extPayments = oxNew(KlarnaExternalPayments::class);
        $this->setProtectedClassProperty($extPayments, '_oRequest',  $oRequest);

        $extPayments->save();

        $payment = oxNew(Payment::class);
        $payment->load('klarna_pay_later');

        $this->assertEquals($payment->oxpayments__tcklarna_paymentoption->value, 'other');
    }
}
