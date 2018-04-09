<?php

namespace TopConcepts\Klarna\Controllers;


use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;

class KlarnaThankYouController extends KlarnaThankYouController_parent
{
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

            try {
                $this->getKlarnaClient($sCountryISO)->getOrder($sKlarnaId);
            } catch (StandardException $e) {
                $e->debugOut();
            }

            // add klarna confirmation snippet
            $this->addTplParam("sKlarnaIframe", $this->getKlarnaClient()->getHtmlSnippet());
        }
        $this->addTplParam("sPaymentId", Registry::getSession()->getVariable('paymentid'));

        KlarnaUtils::fullyResetKlarnaSession();

        return $render;
    }

    /**
     * @param null $sCountryISO
     * @return KlarnaCheckoutClient | \TopConcepts\Klarna\Core\KlarnaClientBase
     */
    public function getKlarnaClient($sCountryISO = null)
    {
        return KlarnaCheckoutClient::getInstance($sCountryISO);
    }
}