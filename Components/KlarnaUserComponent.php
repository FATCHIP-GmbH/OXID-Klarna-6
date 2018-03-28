<?php

namespace TopConcepts\Klarna\Components;


use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class KlarnaOxCmp_User user component
 *
 * @package Klarna
 * @extend OxCmp_User
 */
class KlarnaUserComponent extends KlarnaUserComponent_parent
{
    /**
     * Redirect to klarna express page from this classes
     *
     * @var array
     */
    protected $_aClasses = array(
        'user',
        'KlarnaExpress',
    );

    /**
     * Login user without redirection
     */
    public function login_noredirect()
    {
        $this->login_noredirectParent();

        Registry::getSession()->setVariable("iShowSteps", 1);
        $oViewConfig = oxNew(ViewConfig::class);
        if ($oViewConfig->isKlarnaCheckoutEnabled()) {
            if ($this->klarnaRedirect()) {
                Registry::getUtils()->redirect(
                    $this->getConfig()->getShopSecureHomeUrl() . 'cl=KlarnaExpress',
                    false,
                    302
                );
            }
        }
    }

    /**
     * Redirect to klarna checkout
     *
     * @return bool
     */
    protected function klarnaRedirect()
    {
        $sClass = Registry::get(Request::class)->getRequestParameter('cl');

        return in_array($sClass, $this->_aClasses);
    }

    /**
     * Calls createUser parent
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    protected function getCreateUserParent()
    {
        return parent::createUser();
    }

    /**
     * Logout action
     */
    public function logout()
    {
        parent::logout();
    }

    /**
     * Calls login_noredirect parent
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    protected function login_noredirectParent()
    {
        parent::login_noredirect();
    }

    /**
     */
    protected function _getLogoutLink()
    {

        $oViewConfig = oxNew(ViewConfig::class);
        if ($oViewConfig->isKlarnaCheckoutEnabled() && $this->klarnaRedirect()) {
            /** @var Config $oConfig */
            $oConfig     = $this->getConfig();
            $sLogoutLink = $oConfig->isSsl() ? $oConfig->getShopSecureHomeUrl() : $oConfig->getShopHomeUrl();
            $sLogoutLink .= 'cl=' . 'basket' . $this->getParent()->getDynUrlParams();

            return $sLogoutLink . '&amp;fnc=logout';
        } else {
            return parent::_getLogoutLink();
        }
    }

    /**
     * @return string
     */
    public function changeuser_testvalues()
    {
        $result = $this->getParent();

        if (KlarnaUtils::isKlarnaCheckoutEnabled() && $result === 'account_user') {

//            KlarnaUtils::fullyResetKlarnaSession();
            Registry::getSession()->setVariable('resetKlarnaSession', 1);

            if (Registry::get(Request::class)->getRequestParameter('blshowshipaddress')) {
                Registry::getSession()->setVariable('blshowshipaddress', 1);
                Registry::getSession()->setVariable('deladrid', Registry::get(Request::class)->getRequestEscapedParameter('oxaddressid'));
            } else {
                Registry::getSession()->deleteVariable('deladrid');
            }
        }

        return $result;
    }
}
