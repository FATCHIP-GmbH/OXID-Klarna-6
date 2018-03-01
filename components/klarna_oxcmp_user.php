<?php

/**
 * Class KlarnaOxCmp_User user component
 *
 * @package Klarna
 * @extend OxCmp_User
 */
class Klarna_OxCmp_User extends Klarna_OxCmp_User_parent
{
    /**
     * Redirect to klarna express page from this classes
     *
     * @var array
     */
    protected $_aClasses = array(
        'user',
        'klarna_express',
    );

    /**
     * Login user without redirection
     * @throws oxSystemComponentException
     */
    public function login_noredirect()
    {
        $this->login_noredirectParent();

        oxRegistry::getSession()->setVariable("iShowSteps", 1);
        $oViewConfig = oxNew('oxviewconfig');
        if ($oViewConfig->isKlarnaCheckoutEnabled()) {
            if ($this->klarnaRedirect()) {
                oxRegistry::getUtils()->redirect(
                    $this->getConfig()->getShopSecureHomeUrl() . 'cl=klarna_express',
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
        $sClass = oxRegistry::getConfig()->getRequestParameter('cl');

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
     * @throws oxSystemComponentException
     */
    protected function _getLogoutLink()
    {

        $oViewConfig = oxNew('oxviewconfig');
        if ($oViewConfig->isKlarnaCheckoutEnabled() && $this->klarnaRedirect()) {

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
            if (oxRegistry::getConfig()->getRequestParameter('blshowshipaddress')) {
                oxRegistry::getSession()->setVariable('blshowshipaddress', 1);
                oxRegistry::getSession()->setVariable('deladrid', oxRegistry::getConfig()->getRequestParameter('oxaddressid'));
            } else {
                oxRegistry::getSession()->deleteVariable('deladrid');
            }
        }

        return $result;
    }
}
