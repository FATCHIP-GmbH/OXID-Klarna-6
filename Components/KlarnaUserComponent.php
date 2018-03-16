<?php

namespace TopConcepts\Klarna\Components;


use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\Eshop\Core\Registry as oxRegistry;

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

        oxRegistry::getSession()->setVariable("iShowSteps", 1);
        $oViewConfig = oxNew(ViewConfig::class);
        if ($oViewConfig->isKlarnaCheckoutEnabled()) {
            if ($this->klarnaRedirect()) {
                oxRegistry::getUtils()->redirect(
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
    public function klarnaRedirect()
    {
        $sClass = oxRegistry::get(Request::class)->getRequestParameter('cl');

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
        $result = parent::changeuser_testvalues();
        if (KlarnaUtils::isKlarnaCheckoutEnabled() && $result === 'account_user') {

            KlarnaUtils::fullyResetKlarnaSession();
            if (oxRegistry::get(Request::class)->getRequestParameter('blshowshipaddress')) {
                oxRegistry::getSession()->setVariable('blshowshipaddress', 1);
                oxRegistry::getSession()->setVariable('deladrid', oxRegistry::get(Request::class)->getRequestEscapedParameter('oxaddressid'));
            } else {
                oxRegistry::getSession()->deleteVariable('deladrid');
            }
        }

        return $result;
    }
}
