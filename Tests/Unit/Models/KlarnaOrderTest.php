<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 09.04.2018
 * Time: 17:57
 */

namespace TopConcepts\Klarna\Testes\Unit\Models;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Models\KlarnaOrder;
use TopConcepts\Klarna\Models\KlarnaPayment;

class KlarnaOrderTest extends \PHPUnit_Framework_TestCase
{

    public function isKPDataProvider()
    {
        return [
            [KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_NOW, true],
            [KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID, false]
        ];
    }

    /**
     * @dataProvider isKPDataProvider
     * @param $paymentId
     * @param $expectedResult
     */
    public function testIsKP($paymentId, $expectedResult)
    {
        $oOrder = oxNew(Order::class);
        $oOrder->oxorder__oxpaymenttype = new Field($paymentId, Field::T_RAW);

        $result = $oOrder->isKP();
        $this->assertEquals($expectedResult, $result);

    }

    public function testGetNewOrderLinesAndTotals($orderId, $iLang, $isCapture)
    {
        $oOrder = oxNew(Order::class);
        $oOrder->load($orderId);

        $result = $oOrder->getNewOrderLinesAndTotals($iLang, $isCapture);


        var_dump($result);

    }

    public function testCancelKlarnaOrder()
    {

    }

    public function testIsKlarnaOrder()
    {

    }

    public function testValidateOrder()
    {

    }

    public function testIsKlarna()
    {

    }

    public function testUpdateKlarnaOrder()
    {

    }

    public function testShowKlarnaErrorMessage()
    {

    }

    public function testCancelOrder()
    {

    }

    public function testGetKlarnaClient()
    {

    }

    public function testRetrieveKlarnaOrder()
    {

    }

    public function testGetAllCaptures()
    {

    }

    public function testCreateOrderRefund()
    {

    }

    public function testCaptureKlarnaOrder()
    {

    }

    public function testAddShippingToCapture()
    {

    }

    public function testIsKCO()
    {

    }
}
