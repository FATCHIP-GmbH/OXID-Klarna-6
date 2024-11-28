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


use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Application\Model\Basket;
use OxidEsales\EshopCommunity\Application\Model\BasketItem;
use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaOrder;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Basket component
 *
 * @package Klarna
 * @extend OxCmp_basket
 */
class KlarnaBasketComponent extends KlarnaBasketComponent_parent
{
    /**
     * Redirect controller name
     *
     * @var string
     */
    protected $_sRedirectController = 'KlarnaExpress';

    /**
     * Executing Klarna Express checkout from details page
     */
    public function tobasketKEB($sProductId = null, $dAmount = null, $aSel = null, $aPersParam = null, $blOverride = false)
    {
        $oSession = Registry::getSession();
        $sProductId = $sProductId ?: Registry::getConfig()->getRequestParameter('aid');

        if (!$this->basketHasProduct($oSession->getBasket(), $sProductId)) {
            $this->tobasket($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);

            $oBasket = $oSession->getBasket();
            $oBasket->calculateBasket(true);
        }

        Registry::getUtils()->showMessageAndExit($this->getKebOrderPayload());
    }

    protected function basketHasProduct(Basket $basket, $productId)
    {
        if ($basket->getItemsCount() !== 0) {
            /** @var BasketItem $article */
            foreach ($basket->getBasketArticles() as $article) {
                if ($article->getProductId() == $productId) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getClientTokenFromSession()
    {
        $oSession = Registry::getSession();

        $aKPSessionData = $oSession->getVariable('klarna_session_data');
        if(!$aKPSessionData['client_token']) {
            $oBasket    = $oSession->getBasket();
            $oUser      = $this->getUser();

            if ($oBasket->getItemsCount() && $oUser) {
                /** @var KlarnaPayment $oKlarnaPayment */
                $oKlarnaPayment = oxNew(KlarnaPayment::class, $oBasket, $oUser);
                $oKlarnaPayment->setStatus('authorize');

                $this->getKlarnaPaymentsClient()
                    ->initOrder($oKlarnaPayment)
                    ->createOrUpdateSession();
            }
        }

        $aKPSessionData = $oSession->getVariable('klarna_session_data');
        return $aKPSessionData['client_token'];
    }

    /**
     * @param null $sProductId
     * @param null $dAmount
     * @param null $aSel
     * @param null $aPersParam
     * @param bool $blOverride
     */
    public function changebasket($sProductId = null, $dAmount = null, $aSel = null, $aPersParam = null, $blOverride = true)
    {
        parent::changebasket($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);

        if (KlarnaUtils::isKlarnaCheckoutEnabled() && Registry::getSession()->hasVariable('klarna_checkout_order_id')) {
            try {
                $this->updateKlarnaOrder();
            } catch (StandardException $e) {
                KlarnaUtils::logException($e);
                KlarnaUtils::fullyResetKlarnaSession();
            }
        }
    }

    /**
     * @param null $sProductId
     * @param null $dAmount
     * @param null $aSel
     * @param null $aPersParam
     * @param bool $blOverride
     */
    public function tobasket($sProductId = null, $dAmount = null, $aSel = null, $aPersParam = null, $blOverride = false)
    {
        $result = parent::tobasket($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);

        if (KlarnaUtils::isKlarnaCheckoutEnabled() && Registry::getSession()->hasVariable('klarna_checkout_order_id')) {
            try {
                $this->updateKlarnaOrder();
            } catch (StandardException $e) {
                KlarnaUtils::logException($e);
                KlarnaUtils::fullyResetKlarnaSession();

            }
        }

        return $result;
    }

    public function getKebOrderPayload()
    {
        $oSession = Registry::getSession();
        $oBasket = $oSession->getBasket();
        $oUser = $this->getUser();

        if (!$oUser) {
            $tempMail = md5(time());

            $oUser = KlarnaUtils::getFakeUser($tempMail);
            $oSession->setVariable('sShipSet', KlarnaUtils::getShopConfVar("sKlarnaKEBMethod"));

            $oUser->resolveCountry();
            $countryid = $oUser->getKlarnaDeliveryCountry()->getId();
            $oUser->oxuser__oxcountryid = new Field($countryid, Field::T_RAW);

            $oUser->save();

            $oSession->setVariable("kexFakeUserId", $oUser->getId());
        }

        //unlike for the other /sessions requests, the merchant urls are not needed for the create order call here.
        $oKlarnaOrder   = oxNew(KlarnaOrder::class, $oBasket, $oUser, true);
        $aOrderData     = $oKlarnaOrder->getOrderData();

        $aOrderData = $this->modifyOrderForKeb($aOrderData, $oBasket, $oUser);

        $orderPayload = json_encode($aOrderData);

        $oSession->setVariable("keborderpayload", $orderPayload);

        return $orderPayload;
    }

    protected function modifyOrderForKeb(array $aOrderData, $oBasket, $oUser)
    {
        unset($aOrderData["merchant_urls"]);
        unset($aOrderData["billing_address"]);

        $currencyName = $oBasket->getBasketCurrency()->name;
        $sCountryISO = $oUser->resolveCountry();

        $aOrderData["purchase_country"] = $sCountryISO;
        $aOrderData["purchase_currency"] = $currencyName;

        return $aOrderData;
    }

    /**
     * Sends update request to checkout API
     * @return array order data
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     * @throws \oxSystemComponentException
     * @internal param oxBasket $oBasket
     * @internal param oxUser $oUser
     */
    protected function updateKlarnaOrder()
    {
        $orderLines = Registry::getSession()->getBasket()->getKlarnaOrderLines();
        $oClient    = $this->getKlarnaCheckoutClient();

        return $oClient->createOrUpdateOrder(json_encode($orderLines));
    }

    /**
     * @codeCoverageIgnore
     * @return KlarnaCheckoutClient|KlarnaClientBase
     */
    protected function getKlarnaCheckoutClient()
    {
        return KlarnaCheckoutClient::getInstance();
    }

    /**
     * @codeCoverageIgnore
     * @return KlarnaOrderManagementClient|KlarnaClientBase
     */
    protected function getKlarnaOrderClient()
    {
        return KlarnaOrderManagementClient::getInstance();
    }

    /**
     *
     * @return KlarnaPaymentsClient|KlarnaClientBase
     */
    protected function getKlarnaPaymentsClient()
    {
        return KlarnaPaymentsClient::getInstance();
    }
}
