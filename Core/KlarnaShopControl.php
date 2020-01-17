<?php


namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Core\Registry;

class KlarnaShopControl extends KlarnaShopControl_parent
{

    protected function _initializeViewObject($sClass, $sFunction, $aParams = null, $aViewsChain = null)
    {
        // detect paypal button clicks
        $searchTerm = 'paypalExpressCheckoutButton';
        $found = array_filter(array_keys($_REQUEST), function ($paramName) use($searchTerm) {
            return strpos($paramName, $searchTerm) !== false;
        });
        // remove KCO id from session
        if ((bool)$found) {
            Registry::getSession()->deleteVariable('klarna_checkout_order_id');
            KlarnaUtils::log('debug','Paypal button usage detected: ' . json_encode($found, 128));
        }

        return parent::_initializeViewObject($sClass, $sFunction, $aParams, $aViewsChain);
    }
}