<?php

namespace TopConcepts\Klarna\Tests\Unit\Controllers\Admin;

use TopConcepts\Klarna\Controller\Admin\KlarnaExternalPayments;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaExternalPaymentsTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $controller = new KlarnaExternalPayments();
        $this->setModuleConfVar('tcklarna_sKlarnaActiveMode', 'test');
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
}
