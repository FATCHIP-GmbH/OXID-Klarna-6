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

namespace TopConcepts\Klarna\Controller;


use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\ViewConfig;

class KlarnaUserController extends KlarnaUserController_parent
{
    /**
     *
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
                Registry::getUtils()->redirect(Registry::getConfig()->getShopSecureHomeUrl() .
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
        $viewConf = Registry::get(ViewConfig::class);

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
        $invadr = Registry::get(Request::class)->getRequestEscapedParameter('invadr');
        Registry::get(UserComponent::class)->changeuser();
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