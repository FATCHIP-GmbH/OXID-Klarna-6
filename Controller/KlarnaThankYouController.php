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


use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;

class KlarnaThankYouController extends KlarnaThankYouController_parent
{
    /** @var KlarnaCheckoutClient */
    protected $client;
    /**
     * @return mixed
     */
    public function render()
    {
        $render = parent::render();
        if ($sKlarnaId = Registry::getSession()->getVariable('klarna_checkout_order_id')) {
            $oOrder = oxNew(Order::class);
            $query = $oOrder->buildSelectString(array('tcklarna_orderid' => $sKlarnaId));
            $oOrder->assignRecord($query);
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));
            $this->addTplParam("klOrder", $oOrder);

            if(!$this->client){
                $this->client = KlarnaCheckoutClient::getInstance($sCountryISO);
            }

            try {
                $this->client->getOrder($sKlarnaId);

            } catch (KlarnaClientException $e) {
                KlarnaUtils::logException($e);
            }
            // add klarna confirmation snippet
            $this->addTplParam("sKlarnaIframe", $this->client->getHtmlSnippet());
        }

        KlarnaUtils::fullyResetKlarnaSession();

        return $render;
    }
}