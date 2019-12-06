<?php
namespace TopConcepts\Klarna\Core\KlarnaPayments;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Core\PaymentHandlerInterface;

class PaymentHandler implements PaymentHandlerInterface
{
    protected $error = null;

    /** @var \TopConcepts\Klarna\Core\KlarnaPaymentsClient  */
    protected $httpClient;

    public function __construct()
    {
        $this->httpClient = KlarnaPaymentsClient::getInstance();
    }

    public function execute(Order $oOrder): bool
    {
        $oSession = Registry::getSession();
        /** @var  KlarnaPayment $oKlarnaPayment */
        $oKlarnaPayment = oxNew(KlarnaPayment::class,
            $oSession->getBasket(),
            $oSession->getUser()
        );
        $oKlarnaPayment->validateOrder();
        $errors = $oKlarnaPayment->getError();
        if (count($errors) > 0) {
            $this->error = reset($errors);
            return false;
        }
        // returns success response or false
        // errors are added automatically to the view by httpClient
        $response = $this->httpClient->initOrder($oKlarnaPayment)->createNewOrder();
        if ($response) {
            $this->updateOrder($oOrder, $response);
            Registry::getConfig()->setConfigParam('kp_order_id', $response['order_id']);
        }

        return (bool)$response;
    }

    public function getError()
    {
        return $this->error;
    }

    protected function updateOrder(Order $oOrder, $response)
    {
        $oOrder->oxorder__tcklarna_orderid = new Field($response['order_id'], Field::T_RAW);
        $oOrder->saveMerchantIdAndServerMode();
        $oOrder->save();
    }
}