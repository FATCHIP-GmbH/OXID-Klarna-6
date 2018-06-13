<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Controller\Admin\KlarnaOrderAddress;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderAddressTest extends ModuleUnitTestCase
{

    /**
     * @dataProvider testRenderDataProvider
     * @param $paymentId
     * @param $expectedResult
     */
    public function testRender($paymentId, $expectedResult)
    {
        $order                         = oxNew(Order::class);
        $order->oxorder__oxpaymenttype = new Field($paymentId, Field::T_RAW);

        $orderAddress = oxNew(KlarnaOrderAddress::class);
        $orderAddress->addTplParam('edit', $order);
        $result = $orderAddress->render();
        $this->assertEquals("order_address.tpl", $result);
        $this->assertEquals($order, $orderAddress->getViewDataElement('edit'));
        $this->assertEquals($expectedResult, $orderAddress->getViewDataElement('readonly'));
    }

    public function testRenderDataProvider()
    {
        return [
            ['klarna_checkout', true],
            ['klarna_pay_later', true],
            ['klarna_pay_now', true],
            ['klarna_slice_it', true],
            ['oxidcashondel', false],
            ['oxidpayadvance', false],
        ];
    }
}
