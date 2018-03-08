<?php

class klarna_thankyou extends klarna_thankyou_parent
{
    /**
     * @return mixed
     * @throws oxSystemComponentException
     */
    public function render()
    {
        $render = parent::render();

        if (oxRegistry::getSession()->getVariable('paymentid') === 'klarna_checkout') {


            $sKlarnaId = oxRegistry::getSession()->getVariable('klarna_checkout_order_id');
            $oOrder = oxNew('oxorder');
            $query = $oOrder->buildSelectString(array('klorderid' => $sKlarnaId));
            $oOrder->assignRecord($query);
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));
            $this->addTplParam("klOrder", $oOrder);

            try {
                $this->getKlarnaClient($sCountryISO)->getOrder($sKlarnaId);
            } catch (oxException $e) {
                $e->debugOut();
            }

            // add klarna confirmation snippet
            $this->addTplParam("sKlarnaIframe", $this->getKlarnaClient()->getHtmlSnippet());
        }
        $this->addTplParam("sPaymentId", oxRegistry::getSession()->getVariable('paymentid'));

        KlarnaUtils::fullyResetKlarnaSession();

        return $render;
    }

    /**
     * @param null $sCountryISO
     * @return KlarnaCheckoutClient|KlarnaClientBase
     */
    public function getKlarnaClient($sCountryISO = null)
    {
        return KlarnaCheckoutClient::getInstance($sCountryISO);
    }
}