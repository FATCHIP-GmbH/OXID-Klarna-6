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

namespace TopConcepts\Klarna\Component\Widgets;

use TopConcepts\Klarna\Core\KlarnaOrder;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class oxw_klarna_servicemenu extends service menu widget
 */
class KlarnaArticleDetails extends KlarnaArticleDetails_parent
{
    public function render()
    {
        $oSession       = Registry::getSession();
        $oBasket        = $oSession->getBasket();

        $oBasket->calculateBasket(true);
        $oUser = $this->getUser();

        $oKlarnaOrder   = oxNew(KlarnaOrder::class, $oBasket, $oUser);
        $aOrderData     = $oKlarnaOrder->getOrderData();

        $aOrderData = $this->modifyOrderForKeb($aOrderData, $oBasket, $oUser);

        $orderPayload = json_encode($aOrderData);

        $oSession->setVariable("keborderpayload", $orderPayload);
        $this->addTplParam("keborderpayload", $orderPayload);

        $this->addTplParam("kebtheme", KlarnaUtils::getShopConfVar("sKlarnaKEBTheme"));
        $this->addTplParam("kebshape", KlarnaUtils::getShopConfVar("sKlarnaKEBShape"));

        return parent::render();
    }

    public function isUserLoggedIn()
    {
        return $this->getUser() !== null;
    }

    protected function modifyOrderForKeb(array $aOrderData, $oBasket, $oUser)
    {
        if (Registry::getSession()->getVariable("keborderpayload")) {
            unset($aOrderData["merchant_urls"]);
            unset($aOrderData["billing_address"]);

            $currencyName = $oBasket->getBasketCurrency()->name;
            $sCountryISO = $oUser->resolveCountry();

            $aOrderData["purchase_country"] = $sCountryISO;
            $aOrderData["purchase_currency"] = $currencyName;
        }

        return $aOrderData;
    }
}