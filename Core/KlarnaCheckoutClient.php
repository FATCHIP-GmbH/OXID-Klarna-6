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

namespace TopConcepts\Klarna\Core;


use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use OxidEsales\Eshop\Core\Registry;

class KlarnaCheckoutClient extends KlarnaClientBase

{
    const ORDERS_ENDPOINT = '/checkout/v3/orders/%s';

    /**
     * @var array
     * Let's save klarna checkout data after each request
     */
    protected $aOrder;

    /**
     * @var KlarnaOrder object
     */
    protected $_oKlarnaOrder;


    /**
     * @param $oKlarnaOrder
     * @return $this
     */
    public function initOrder(KlarnaOrder $oKlarnaOrder)
    {
        $this->_oKlarnaOrder = $oKlarnaOrder;

        return $this;
    }

    /**
     * Creates or Updates existing Klarna Order
     * Saves Klarna order_id to the session and keeps Klarna response in aOrder property
     * what allows us access html_snippet later
     * @param string $requestBody in json format
     * @return mixed
     */
    public function createOrUpdateOrder($requestBody = null)
    {
        if (!$requestBody)
            $requestBody = $this->formatOrderData();

        try {
            // update existing order
            return $this->postOrder($requestBody, $this->getOrderId());
        } catch (KlarnaClientException $oEx) {
            /**
             * Try again with a new session ( no order id )
             */
            $oEx->debugOut();
            return $this->postOrder($requestBody);
        }
    }

    /**
     * @param $data
     * @param string $order_id
     * @throws KlarnaClientException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     * @throws \Exception
     * @return array|bool
     */
    protected function postOrder($data, $order_id = '')
    {
        $oResponse = $this->post(sprintf(self::ORDERS_ENDPOINT, $order_id), $data);
        $this->logKlarnaData(
            $order_id === '' ? 'Create Order' : 'Update Order',
            $data,
            self::ORDERS_ENDPOINT,
            $oResponse->body,
            $order_id,
            $oResponse->status_code
        );

        $this->aOrder = $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);

        Registry::getSession()->setVariable('klarna_checkout_order_id', $this->aOrder['order_id']);
        Registry::getSession()->setVariable('klarna_checkout_user_email', $this->aOrder['billing_address']['email']);

        return $this->aOrder;
    }

    /**
     * @param null $order_id
     * @return array
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaClientException
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     * @throws \Exception
     */
    public function getOrder($order_id = null)
    {
        if ($order_id === null) {
            $order_id = $this->getOrderId();
        }

        $oResponse = $this->get(sprintf(self::ORDERS_ENDPOINT, $order_id));

        $this->logKlarnaData(
            'Get Order',
            '',
            self::ORDERS_ENDPOINT,
            $oResponse->body,
            $order_id,
            $oResponse->status_code
        );

        $this->aOrder = $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
        Registry::getSession()->setVariable('klarna_checkout_user_email', $this->aOrder['billing_address']['email']);

        return $this->aOrder;
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        if (isset($this->aOrder)) {
            return $this->aOrder['order_id'];
        }

        return Registry::getSession()->getVariable('klarna_checkout_order_id') ?: '';
    }

    /**
     * @return bool|string
     */
    public function getHtmlSnippet()
    {
        if (isset($this->aOrder)) {
            return $this->aOrder['html_snippet'];
        }

        return false;
    }

//    /**
//     * @return string|bool
//     */
//    public function getLoadedPurchaseCountry()
//    {
//        if (isset($this->aOrder)) {
//
//            return Registry::getSession()->getVariable('sCountryISO');
//        }
//
//        return false;
//    }
}