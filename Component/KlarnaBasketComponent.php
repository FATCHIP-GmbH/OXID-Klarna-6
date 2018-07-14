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


use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
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
     * Executing action from details page
     */
    public function actionKlarnaExpressCheckoutFromDetailsPage()
    {
        Registry::getSession()->deleteVariable('_newitem');

        $this->tobasket();

        if (Registry::getSession()->getVariable('_newitem') !== null) {
            Registry::getUtils()->redirect(
                Registry::getConfig()->getShopSecureHomeUrl() . 'cl=' . $this->_sRedirectController . '',
                false,
                302
            );
        }
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
                $e->debugOut();
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
                $e->debugOut();
                KlarnaUtils::fullyResetKlarnaSession();

            }
        }

        return $result;
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

        return $oClient->createOrUpdateOrder(json_encode($orderLines), $oClient->getOrderId());
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

}
