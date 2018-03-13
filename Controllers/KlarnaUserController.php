<?php
namespace Klarna\Klarna\Controllers;


use Klarna\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

class KlarnaUserController extends KlarnaUserController_parent
{
    /**
     *
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function init()
    {
        parent::init();

        if ($amazonOrderId = Registry::get(Request::class)->getRequestParameter('amazonOrderReferenceId')) {
            Registry::getSession()->setVariable('amazonOrderReferenceId', $amazonOrderId);
        }

        if (KlarnaUtils::isKlarnaCheckoutEnabled()) {
            $sCountryISO = Registry::getSession()->getVariable('sCountryISO');

            if (KlarnaUtils::isCountryActiveInKlarnaCheckout($sCountryISO) &&
                !Registry::getSession()->hasVariable('amazonOrderReferenceId')
            ) {
                Registry::getUtils()->redirect(Registry::getConfig()->getShopHomeURL() .
                                                 'cl=KlarnaExpress', false, 302);
            }
        }

    }

    /**
     * @return mixed
     */
    public function getInvoiceAddress()
    {
        $result   = parent::getInvoiceAddress();
        $viewConf = Registry::get('oxViewConfig');

        if (!$result && $viewConf->isCheckoutNonKlarnaCountry()) {
            $oCountry                      = oxNew(Country::class);
            $result['oxuser__oxcountryid'] = $oCountry->getIdByCode(Registry::getSession()->getVariable('sCountryISO'));
        }

        return $result;
    }

    /**
     *
     */
    public function klarnaResetCountry()
    {
        $invadr = Registry::getConfig()->getRequestParameter('invadr');
        Registry::get('oxcmp_user')->changeuser();
        unset($invadr['oxuser__oxcountryid']);
        unset($invadr['oxuser__oxzip']);
        unset($invadr['oxuser__oxstreet']);
        unset($invadr['oxuser__oxstreetnr']);
        $invadr['oxuser__oxusername'] = Registry::get(Request::class)->getRequestParameter('lgn_usr');
        Registry::getSession()->setVariable('invadr', $invadr);
        KlarnaUtils::fullyResetKlarnaSession();

        $sUrl = Registry::getConfig()->getShopSecureHomeURL() . 'cl=KlarnaExpress&reset_klarna_country=1';
        Registry::getUtils()->showMessageAndExit($sUrl);
    }
}