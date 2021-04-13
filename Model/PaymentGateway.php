<?php


namespace TopConcepts\Klarna\Model;


use OxidEsales\Eshop\Application\Model\Order;
use TopConcepts\Klarna\Core\KlarnaPayments\PaymentHandler as KPHandler;
use TopConcepts\Klarna\Core\PaymentHandlerInterface;

/**
 * Class PaymentGateway
 * @package TopConcepts\Klarna\Model
 *
 * @property string _sLastError
 */
class PaymentGateway extends PaymentGateway_parent
{
    protected $paymentHandlerMap = [
        KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID  => KPHandler::class,
        KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID => KPHandler::class,
        KlarnaPayment::KLARNA_PAYMENT_PAY_NOW      => KPHandler::class,
        KlarnaPayment::KLARNA_DIRECTDEBIT          => KPHandler::class,
        KlarnaPayment::KLARNA_CARD                 => KPHandler::class,
        KlarnaPayment::KLARNA_SOFORT               => KPHandler::class,
    ];

    public function executePayment($dAmount, & $oOrder)
    {
        $result = parent::executePayment($dAmount, $oOrder);
        $paymentHandler = $this->createPaymentHandler($oOrder);
        if ($paymentHandler) {
            $result = $paymentHandler->execute($oOrder);
            $this->_sLastError = $paymentHandler->getError();
        }
        return $result;
    }

    /**
     * Payment Handler Factory Function
     * @param Order $oOrder
     * @return bool|PaymentHandlerInterface
     */
    protected function createPaymentHandler(Order $oOrder)
    {
        $paymentId = $oOrder->getFieldData('OXPAYMENTTYPE');
        $handlerClass = $this->paymentHandlerMap[$paymentId];
        if ($handlerClass) {
            return oxNew($handlerClass);
        }
        return false;
    }
}