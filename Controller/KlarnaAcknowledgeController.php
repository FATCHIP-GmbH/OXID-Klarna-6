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


use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

/**
 * Controller for Klarna Checkout Acknowledge push request
 */
class KlarnaAcknowledgeController extends FrontendController
{
    protected $aOrder;

    /**
     * @codeCoverageIgnore
     * @param string $sCountryISO
     * @return KlarnaOrderManagementClient|KlarnaClientBase $klarnaClient
     */
    protected function getKlarnaClient($sCountryISO)
    {
        return KlarnaOrderManagementClient::getInstance($sCountryISO);
    }

    public function init()
    {
        parent::init();

        $orderId = Registry::get(Request::class)->getRequestEscapedParameter('klarna_order_id');

        if (empty($orderId)) {
            return;
        }

        $this->registerKlarnaAckRequest($orderId);
        try {
            $oOrder     = $this->loadOrderByKlarnaId($orderId);
            $countryISO = KlarnaUtils::getCountryISO($oOrder->oxorder__oxbillcountryid->value);
            if ($oOrder->isLoaded()) {
                $this->getKlarnaClient($countryISO)->acknowledgeOrder($orderId);
            } elseif ($this->getKlarnaAckCount($orderId) > 1) {
                $this->getKlarnaClient($countryISO)->cancelOrder($orderId);
            }
        } catch (StandardException $e) {
            $e->debugOut();

            return;
        }
    }

    /**
     * @param $orderId
     * @return Order
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    protected function loadOrderByKlarnaId($orderId)
    {
        return KlarnaUtils::loadOrderByKlarnaId($orderId);
    }


    /**
     * Register Klarna request in DB
     * @param $orderId
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function registerKlarnaAckRequest($orderId)
    {
        KlarnaUtils::registerKlarnaAckRequest($orderId);
    }

    /**
     * Get count of Klarna ACK requests for location ID
     *
     * @param $orderId
     * @return string
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    protected function getKlarnaAckCount($orderId)
    {
        return KlarnaUtils::getKlarnaAckCount($orderId);
    }
}