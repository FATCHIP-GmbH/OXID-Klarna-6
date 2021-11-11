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

    /** @var KlarnaPayment  */
    protected $context;

    public function __construct()
    {
        $this->httpClient = KlarnaPaymentsClient::getInstance();
        $this->context = $this->getContext();
    }

    public function execute(Order $oOrder): bool
    {
        $this->context->validateOrder();
        $errors = $this->context->getError();
        if (count($errors) > 0) {
            $this->error = reset($errors);
            return false;
        }
        // returns success response or false
        // errors are added automatically to the view by httpClient
        $response = $this->httpClient->initOrder($this->context)->createNewOrder();
        $result = is_array($response) ? $this->checkFraudStatus($response) : false;
        if ($result) {
            $this->updateOrder($oOrder, $response);
            //TODO: don't use config
            Registry::getConfig()->setConfigParam('kp_order_id', $response['order_id']);
        }

        return $result;
    }

    /** @codeCoverageIgnore */
    public function getError()
    {
        return $this->error;
    }

    protected function checkFraudStatus(array $createResponse)
    {
        if (!array_key_exists('fraud_status', $createResponse) ) {
            $createResponse['fraud_status'] = 'FRAUD_STATUS_INFO_MISSING';
        }

        if ($createResponse['fraud_status'] !== 'ACCEPTED') {
            $this->error = 'fraud_status=' . $createResponse['fraud_status'];
            return false;
        }

        return true;
    }

    protected function updateOrder(Order $oOrder, $response)
    {
        $oOrder->oxorder__tcklarna_orderid = new Field($response['order_id'], Field::T_RAW);
        $oOrder->saveMerchantIdAndServerMode();
        $oOrder->save();
    }

    /**
     * KlarnaPayment factory function
     */
    protected function getContext()
    {
        $oSession = Registry::getSession();
        /** @var  KlarnaPayment $oKlarnaPayment */
        $oKlarnaPayment = oxNew(KlarnaPayment::class,
            $oSession->getBasket(),
            $oSession->getUser()
        );

        return $oKlarnaPayment;
    }
}