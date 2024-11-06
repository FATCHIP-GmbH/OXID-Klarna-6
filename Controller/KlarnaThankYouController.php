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


use OxidEsales\EshopCommunity\Application\Controller\FrontendController;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Model\KlarnaInstantBasket;

/**
 * Class KlarnaThankYouController
 * @package TopConcepts\Klarna\Controller
 *
 * @extends \OxidEsales\Eshop\Application\Controller\ThankYouController
 * @property $_oBasket
 */
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

//        if ($payload = Registry::getSession()->getVariable("keborderpayload")) {
//            print_r($payload);
//            die("test");
//        }
        
        if ($sKlarnaId = Registry::getSession()->getVariable('klarna_checkout_order_id')) {
            $oOrder = Registry::get(Order::class);
            $oOrder->loadByKlarnaId($sKlarnaId);
            if ($oOrder->isLoaded()) {
                $this->loadClient($oOrder);
                try {
                    $this->client->getOrder($sKlarnaId);

                } catch (KlarnaClientException $e) {
                    KlarnaUtils::logException($e);
                }
                // add klarna confirmation snippet
                $this->addTplParam("klOrder", $oOrder);
                $this->addTplParam("sKlarnaIframe", $this->client->getHtmlSnippet());
            }
        }

        KlarnaUtils::fullyResetKlarnaSession();

        return $render;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getNewKlarnaInstantBasket()
    {
        return oxNew(KlarnaInstantBasket::class);
    }

    protected function loadClient($oOrder) {
        if(!$this->client){
            $this->client = KlarnaCheckoutClient::getInstance(
                KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'))
            );
        }
    }
}