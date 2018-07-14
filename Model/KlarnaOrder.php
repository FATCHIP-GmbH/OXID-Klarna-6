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

namespace TopConcepts\Klarna\Model;


use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;

class KlarnaOrder extends KlarnaOrder_parent
{

    protected $isAnonymous;

    /**
     * Validates order parameters like stock, delivery and payment
     * parameters
     *
     * @param Basket $oBasket basket object
     * @param User $oUser order user
     *
     * @return bool|null|void
     */
    public function validateOrder($oBasket, $oUser)
    {
        $_POST['sDeliveryAddressMD5'] = Registry::getSession()->getVariable('sDelAddrMD5');

        return parent::validateOrder($oBasket, $oUser);
    }

    /**
     * @param null|KlarnaOrderManagementClient $client for UnitTest purpose
     * @return mixed
     */
    protected function _setNumber($client = null)
    {
        if ($blUpdate = parent::_setNumber()) {

            if ($this->isKlarna() && empty($this->oxorder__tcklarna_orderid->value)) {

                $session = Registry::getSession();

                if ($this->isKP()) {
                    $klarna_id = $session->getVariable('klarna_last_KP_order_id');
                    $session->deleteVariable('klarna_last_KP_order_id');
                }

                if ($this->isKCO()) {
                    $klarna_id = $session->getVariable('klarna_checkout_order_id');
                }

                $this->oxorder__tcklarna_orderid = new Field($klarna_id, Field::T_RAW);

                $this->saveMerchantIdAndServerMode();

                $this->save();

                try {
                    $sCountryISO = KlarnaUtils::getCountryISO($this->getFieldData('oxbillcountryid'));
                    if (!$client) {
                        $client = KlarnaOrderManagementClient::getInstance($sCountryISO); // @codeCoverageIgnore
                    }
                    $client->sendOxidOrderNr($this->oxorder__oxordernr->value, $klarna_id);
                } catch (KlarnaClientException $e) {
                    $e->debugOut();
                }
            }
        }

        return $blUpdate;
    }

    /**
     *
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    protected function saveMerchantIdAndServerMode()
    {
        $sCountryISO = KlarnaUtils::getCountryISO($this->getFieldData('oxbillcountryid'));

        $aKlarnaCredentials = KlarnaUtils::getAPICredentials($sCountryISO);
        $test               = KlarnaUtils::getShopConfVar('blIsKlarnaTestMode');

        preg_match('/(?<mid>^[a-zA-Z0-9]+)/', $aKlarnaCredentials['mid'], $matches);
        $mid        = $matches['mid'];
        $serverMode = $test ? 'playground' : 'live';

        $this->oxorder__tcklarna_merchantid = new Field($mid, Field::T_RAW);
        $this->oxorder__tcklarna_servermode = new Field($serverMode, Field::T_RAW);
    }

    /**
     * @return bool
     */
    public function isKP()
    {
        return in_array($this->oxorder__oxpaymenttype->value, KlarnaPayment::getKlarnaPaymentsIds('KP'));
    }

    /**
     * @return bool
     */
    public function isKCO()
    {
        return $this->oxorder__oxpaymenttype->value === KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID;
    }

    /**
     * @return bool
     */
    public function isKlarna()
    {
        return in_array($this->oxorder__oxpaymenttype->value, KlarnaPayment::getKlarnaPaymentsIds());
    }

    /**
     * Check if order is Klarna order
     *
     * @return boolean
     */
    public function isKlarnaOrder()
    {
        if (strstr($this->getFieldData('oxpaymenttype'), 'klarna_')) {
            return true;
        }

        return false;
    }

    /**
     * @param $orderId
     * @param null $sCountryISO
     * @param KlarnaOrderManagementClient|null $client
     * @return mixed
     */
    public function cancelKlarnaOrder($orderId = null, $sCountryISO = null, KlarnaOrderManagementClient $client = null)
    {
        $orderId = $orderId ?: $this->getFieldData('tcklarna_orderid');

        if (!$client) {
            $client = KlarnaOrderManagementClient::getInstance($sCountryISO); // @codeCoverageIgnore
        }

        return $client->cancelOrder($orderId);
    }

    /**
     * @param $data
     * @param $orderId
     * @param $sCountryISO
     * @return string
     */
    public function updateKlarnaOrder($data, $orderId, $sCountryISO = null, KlarnaOrderManagementClient $client = null)
    {
        if (!$client) {
            $client = KlarnaOrderManagementClient::getInstance($sCountryISO); // @codeCoverageIgnore
        }

        try {
            $client->updateOrderLines($data, $orderId);
            $this->oxorder__tcklarna_sync = new Field(1);
            $this->save();

        } catch (KlarnaClientException $e) {

            $this->oxorder__tcklarna_sync = new Field(0, Field::T_RAW);
            $this->save();

            return $e->getMessage();
        }
    }

    /**
     * @param $data
     * @param $orderId
     * @param null $sCountryISO
     * @param KlarnaOrderManagementClient|null $client
     * @return array
     */
    public function captureKlarnaOrder($data, $orderId, $sCountryISO = null, KlarnaOrderManagementClient $client = null)
    {
        if ($trackcode = $this->getFieldData('oxtrackcode')) {
            $data['shipping_info'] = array(array('tracking_number' => $trackcode));
        }
        if (!$client) {
            $client = KlarnaOrderManagementClient::getInstance($sCountryISO); // @codeCoverageIgnore
        }

        return $client->captureOrder($data, $orderId);
    }

    /**
     * @param $orderLang
     * @param bool $isCapture
     * @return mixed
     */
    public function getNewOrderLinesAndTotals($orderLang, $isCapture = false)
    {
        $cur = $this->getOrderCurrency();
        Registry::getConfig()->setActShopCurrency($cur->id);
        if ($isCapture) {
            $this->reloadDiscount(false);
        }
//        $this->recalculateOrder();
        $oBasket = $this->_getOrderBasket();
        $oBasket->setKlarnaOrderLang($orderLang);
        $this->_addOrderArticlesToBasket($oBasket, $this->getOrderArticles(true));

        $oBasket->calculateBasket(true);
        $orderLines = $oBasket->getKlarnaOrderLines($this->getId());

        return $orderLines;
    }

    /**
     * Set anonymous data if anonymization is enabled.
     *
     * @param $aArticleList
     */
    protected function _setOrderArticles($aArticleList)
    {

        parent::_setOrderArticles($aArticleList);

        if ($this->isKlarnaAnonymous()) {
            $oOrderArticles = $this->getOrderArticles();
            if ($oOrderArticles && count($oOrderArticles) > 0) {
                $this->_setOrderArticleKlarnaInfo($oOrderArticles);
            }
        }
    }

    /**
     * @param $oOrderArticles
     */
    protected function _setOrderArticleKlarnaInfo($oOrderArticles)
    {
        $iIndex = 0;
        foreach ($oOrderArticles as $oOrderArticle) {
            $iIndex++;
            $oOrderArticle->tcklarna_setTitle($iIndex);
            $oOrderArticle->tcklarna_setArtNum($iIndex);
        }
    }

    /**
     * @return mixed
     */
    protected function isKlarnaAnonymous()
    {
        if ($this->isAnonymous !== null)
            return $this->isAnonymous;

        return $this->isAnonymous = KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization');
    }


}