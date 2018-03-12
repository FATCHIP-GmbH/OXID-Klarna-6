<?php

class klarna_user extends klarna_user_parent
{
    /**
     *
     * @throws oxSystemComponentException
     */
    public function init()
    {
        parent::init();

        if ($amazonOrderId = oxRegistry::getConfig()->getRequestParameter('amazonOrderReferenceId')) {
            oxRegistry::getSession()->setVariable('amazonOrderReferenceId', $amazonOrderId);
        }

        if (KlarnaUtils::isKlarnaCheckoutEnabled()) {
            $sCountryISO = oxRegistry::getSession()->getVariable('sCountryISO');

            if (KlarnaUtils::isCountryActiveInKlarnaCheckout($sCountryISO) &&
                !oxRegistry::getSession()->hasVariable('amazonOrderReferenceId')
                /*&& !oxRegistry::getConfig()->getRequestParameter('non_kco_global_country')*/
            ) {
                oxRegistry::getUtils()->redirect(oxRegistry::getConfig()->getShopHomeURL() .
                                                 'cl=KlarnaExpress', false, 302);
            }
//
//            if ($this->getUser() && !$this->getUser()->isFake() &&
//                !KlarnaUtils::isKlarnaExternalPaymentMethod() &&
//                !KlarnaUtils::isCountryActiveInKlarnaCheckout($sCountryISO) &&
//                oxRegistry::getConfig()->getRequestParameter('non_kco_global_country')
//            ) {
//                $oCountry   = oxNew('oxCountry');
//                $sCountryId = $oCountry->getIdByCode($sCountryISO);
//
//                $this->getUser()->oxuser__oxstreet    = new oxField();
//                $this->getUser()->oxuser__oxstreetnr  = new oxField();
//                $this->getUser()->oxuser__oxcity      = new oxField();
//                $this->getUser()->oxuser__oxzip       = new oxField();
//                $this->getUser()->oxuser__oxcountryid = new oxField($sCountryId);
//                $this->getUser()->oxuser__oxcountry   = new oxField($oCountry->oxcountry__oxtitle->value);
//                $this->getUser()->save();
//
//            }
        }

    }

    /**
     * @return mixed
     * @throws oxSystemComponentException
     */
    public function getInvoiceAddress()
    {
        $result   = parent::getInvoiceAddress();
        $viewConf = oxRegistry::get('oxViewConfig');

        if (!$result && $viewConf->isCheckoutNonKlarnaCountry()) {
            $oCountry                      = oxNew('oxcountry');
            $result['oxuser__oxcountryid'] = $oCountry->getIdByCode(oxRegistry::getSession()->getVariable('sCountryISO'));
        }

        return $result;
    }

    /**
     *
     */
    public function klarnaResetCountry()
    {
        $invadr = oxRegistry::getConfig()->getRequestParameter('invadr');
        oxRegistry::get('oxcmp_user')->changeuser();
        unset($invadr['oxuser__oxcountryid']);
        unset($invadr['oxuser__oxzip']);
        unset($invadr['oxuser__oxstreet']);
        unset($invadr['oxuser__oxstreetnr']);
        $invadr['oxuser__oxusername'] = oxRegistry::getConfig()->getRequestParameter('lgn_usr');
        oxRegistry::getSession()->setVariable('invadr', $invadr);
        KlarnaUtils::fullyResetKlarnaSession();
//        oxRegistry::getSession()->deleteVariable('sCountryISO');
//        oxRegistry::getSession()->deleteVariable('klarna_checkout_order_id');


        $sUrl = oxRegistry::getConfig()->getShopSecureHomeURL() . 'cl=KlarnaExpress&reset_klarna_country=1';
        oxRegistry::getUtils()->showMessageAndExit($sUrl);
    }
}