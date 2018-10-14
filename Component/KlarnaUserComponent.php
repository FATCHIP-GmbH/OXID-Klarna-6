<?php
/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TopConcepts\Klarna\Component;


use TopConcepts\Klarna\Core\KlarnaPayment;
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
        parent::login_noredirect();

        Registry::getSession()->setVariable("iShowSteps", 1);
        $oViewConfig = oxNew(ViewConfig::class);
        if ($oViewConfig->isKlarnaCheckoutEnabled()) {
            KlarnaUtils::fullyResetKlarnaSession();
            Registry::getSession()->deleteVariable('sFakeUserId');
            if ($this->klarnaRedirect()) {
                Registry::getUtils()->redirect(
                    $this->getConfig()->getShopSecureHomeUrl() . 'cl=KlarnaExpress',
                    false,
                    302
                );
            }
        }
        if ($oViewConfig->isKlarnaPaymentsEnabled()) {
            KlarnaPayment::cleanUpSession();
        }
    }

    /**
     * Redirect to klarna checkout
     * @return bool
     */
    protected function klarnaRedirect()
    {
        $sClass = Registry::get(Request::class)->getRequestEscapedParameter('cl');

        return in_array($sClass, $this->_aClasses);
    }


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
