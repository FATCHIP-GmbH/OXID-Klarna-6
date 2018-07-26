<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;


use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Controller\KlarnaAcknowledgeController;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaAcknowledgeControllerTest extends ModuleUnitTestCase
{

    public function testInit()
    {
        $controller = new KlarnaAcknowledgeController();
        $result = $controller->init();
        $this->assertNull($result);

        $this->setRequestParameter('klarna_order_id', '16302e97f6249f2babcdef65004954b1');
        $controller->init();

        $order = $this->createStub(Order::class, ['isLoaded' => true]);
        $order->oxorder__oxbillcountryid = new Field('111', Field::T_RAW);

        $client = $this->createStub(KlarnaOrderManagementClient::class, ['acknowledgeOrder' => true, 'cancelOrder' => true]);
        $controller = $this->createStub(KlarnaAcknowledgeController::class, ['loadOrderByKlarnaId' => $order, 'getKlarnaClient' => $client]);
        $controller->init();

        $order = $this->getMock(Order::class, ['isLoaded']);
        $order->expects($this->any())->method('isLoaded')->will($this->throwException(new StandardException()));
        $controller = $this->createStub(KlarnaAcknowledgeController::class, ['loadOrderByKlarnaId' => $order, 'getKlarnaClient' => $client]);
        $result = $controller->init();

        $this->assertLoggedException(StandardException::class, '');
        $this->assertNull($result);
    }
}
