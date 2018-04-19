<?php

namespace TopConcepts\Klarna\Tests\Unit\Controllers\Admin;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Controllers\Admin\KlarnaOrderAddress;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderAddressTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $order = oxNew(Order::class);
        $order->oxorder__oxpaymenttype = new Field('klarna_checkout', Field::T_RAW);

        $orderAddress = $this->createStub(KlarnaOrderAddress::class, ['getViewDataElement' => $order]);
        $result = $orderAddress->render();
        $this->assertEquals("order_address.tpl", $result);
        $this->assertTrue($orderAddress->getViewData()['readonly']);

    }
}
