<?php


namespace TopConcepts\Klarna\Core\InstantShopping;

use TopConcepts\Klarna\Core\PaymentHandlerInterface;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;

class PaymentHandler implements PaymentHandlerInterface
{
    const ORDER_CONTEXT_KEY = 'klarna_order_data';

    protected $error = null;

    /** @var HttpClient */
    protected $httpClient;

    /** @var array Klarna Order Data */
    protected $context;

    public function __construct()
    {
        $this->httpClient = HttpClient::getInstance();
        $this->context = $this->getContext();
    }

    /**
     * @param Order $oOrder
     * @return bool
     * @throws KlarnaClientException
     * @throws StandardException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException
     */
    public function execute(Order $oOrder): bool
    {
        $this->context['order']['merchant_reference1'] = $oOrder->oxorder__oxordernr->value;
        $this->context['order']['merchant_reference2'] = "";
        $approveResponse = $this->httpClient->approveOrder(
            $this->context['authorization_token'],
            $this->context['order']
        );
        Registry::getLogger()->debug(__METHOD__, $approveResponse);

        return  $this->checkFraudStatus($approveResponse, $oOrder);
    }


    public function getError()
    {
        return $this->error;
    }

    /**
     * @return mixed
     * @throws StandardException
     */
    protected function getContext()
    {
        $context = Registry::getConfig()->getConfigParam(static::ORDER_CONTEXT_KEY);
        if ($context) {
            return $context;
        }
        $msg = 'Missing execution context. Expected Config param ' . static::ORDER_CONTEXT_KEY;
        throw new StandardException($msg);
    }

    protected function checkFraudStatus(array $approveResponse, Order $oOrder)
    {
        $approveResponse['fraud_status'] = 'PENDING';
        if($approveResponse['fraud_status'] !== 'ACCEPTED') {
            $this->error = 'fraud_status=' . $approveResponse['fraud_status'];
            return false;
        }

        $oOrder->oxorder__tcklarna_orderid = new Field($approveResponse['order_id'], Field::T_RAW);
        $oOrder->saveMerchantIdAndServerMode();
        $oOrder->save();

        return true;
    }
}