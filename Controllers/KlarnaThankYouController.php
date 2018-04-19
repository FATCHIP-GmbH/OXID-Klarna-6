<?php

namespace TopConcepts\Klarna\Controllers;


use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Exception\KlarnaClientException;

class KlarnaThankYouController extends KlarnaThankYouController_parent
{
    /** @var KlarnaCheckoutClient */
    protected $client;
    /**
     * @return mixed
     */
    public function render()
    {
        $render = parent::render();

        if (Registry::getSession()->getVariable('paymentid') === 'klarna_checkout') {

            $sKlarnaId = Registry::getSession()->getVariable('klarna_checkout_order_id');
            $oOrder = oxNew(Order::class);
            $query = $oOrder->buildSelectString(array('klorderid' => $sKlarnaId));
            $oOrder->assignRecord($query);
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));
            $this->addTplParam("klOrder", $oOrder);

            if(!$this->client){
                $this->client = KlarnaCheckoutClient::getInstance($sCountryISO);
            }

            try {
                $this->client->getOrder($sKlarnaId);

            } catch (KlarnaClientException $e) {
                $e->debugOut();
            }

            // add klarna confirmation snippet
            $this->addTplParam("sKlarnaIframe", $this->client->getHtmlSnippet());
        }
        $this->addTplParam("sPaymentId", Registry::getSession()->getVariable('paymentid'));

        KlarnaUtils::fullyResetKlarnaSession();

        return $render;
    }
}