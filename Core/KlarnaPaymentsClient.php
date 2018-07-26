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
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use OxidEsales\Eshop\Core\Registry;

class KlarnaPaymentsClient extends KlarnaClientBase
{
    const PAYMENTS_ENDPOINT               = '/payments/v1/sessions/%s';
    const PAYMENTS_AUTHORIZATION_ENDPOINT = '/payments/v1/authorizations/%s';

    /** @var KlarnaPayment */
    protected $_oKlarnaOrder;

    protected $sSessionId;
    protected $aSessionData;

    /**
     * @param KlarnaPayment $oKlarnaOrder
     * @return $this
     */
    public function initOrder(KlarnaPayment $oKlarnaOrder)
    {
        $this->_oKlarnaOrder = $oKlarnaOrder;
        $this->getSessionId();

        return $this;
    }

    /**
     * @return array
     * @throws KlarnaClientException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaWrongCredentialsException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     * @throws \ReflectionException
     */
    public function createOrUpdateSession()
    {
        $oSession = Registry::getSession();
        list($requestBody, $splittedUpdateData) = $this->formatOrderData();

        if (is_null($requestBody)) {
            return $this->aSessionData;        // nothing to update
        }

        if ($this->sSessionId && count($this->aSessionData['payment_method_categories']) > 0) {
            try {
                // update existing order
                $this->aSessionData = $this->updateSession($requestBody);
                $this->_oKlarnaOrder->saveCheckSums($splittedUpdateData);
            } catch (KlarnaOrderNotFoundException $e) {
                // klarna order expired - create new order
                KlarnaPayment::cleanUpSession();
                list($requestBody, $splittedUpdateData) = $this->formatOrderData();
                $this->aSessionData = $this->postSession($requestBody);
                $oSession->setVariable('sSessionTimeStamp', $this->getTimeStamp());
                $this->_oKlarnaOrder->saveCheckSums($splittedUpdateData);
                $oSession->setVariable('klarna_session_data', $this->aSessionData);
                if (KlarnaUtils::is_ajax()) {
                    $this->_oKlarnaOrder->setStatus('refresh');
                }
            }

        } else {
            // create a new order
            $this->aSessionData = $this->postSession($requestBody);
            $oSession->setVariable('sSessionTimeStamp', $this->getTimeStamp());
            $oSession->setVariable('klarna_session_data', $this->aSessionData);
        }

        return $this->aSessionData;
    }

    /**
     * @param $data array
     * @param string $session_id
     * @return array
     * @throws KlarnaClientException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     * @throws \Exception
     */
    protected function postSession($data, $session_id = '')
    {
        $oResponse = $this->post(sprintf(self::PAYMENTS_ENDPOINT, $session_id), $data);
        $this->logKlarnaData(
            $session_id === '' ? 'Create Payment' : 'Update Payment',
            $data,
            self::PAYMENTS_ENDPOINT,
            $oResponse->body,
            $session_id,
            $oResponse->status_code
        );

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * Update KP session
     * @param $updateData array
     * @return array
     * @throws KlarnaClientException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     */
    public function updateSession($updateData)
    {
        return $this->postSession($updateData, $this->getSessionId());
    }

    /**
     * Returns KP session Id
     * @return string
     */
    public function getSessionId()
    {
        if (isset($this->sSessionId)) {
            return $this->sSessionId;
        } else {
            $this->aSessionData = Registry::getSession()->getVariable('klarna_session_data');
            if (isset($this->aSessionData['session_id']))
                return $this->sSessionId = $this->aSessionData['session_id'];
            else
                return '';
        }
    }

    /**
     * @param $session_id
     * @return array|null
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     * @throws \Exception
     */
    public function getSessionData($session_id = null)
    {
        if (!$session_id) {
            $session_id = $this->getSessionId();
        }

        $oResponse = $this->get(sprintf(self::PAYMENTS_ENDPOINT, $session_id));
        $this->logKlarnaData(
            'Get Payment Session Data',
            '',
            self::PAYMENTS_ENDPOINT,
            $oResponse->body,
            $session_id,
            $oResponse->status_code
        );
        try {
            $this->aSessionData = $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
        } catch (KlarnaClientException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
        }

        return $this->aSessionData;
    }

    /**
     * @return mixed
     * @throws \oxSystemComponentException
     * @throws \Exception
     */
    public function createNewOrder()
    {
        $sAuthToken         = Registry::getSession()->getVariable('sAuthToken');
        $url                = sprintf(self::PAYMENTS_AUTHORIZATION_ENDPOINT, $sAuthToken . '/order');
        $currentSessionData = json_encode(
            array_merge(
                $this->_oKlarnaOrder->getOrderData(),
                $this->getUser()->getKlarnaPaymentData()
            )
        );


        $headers   = array('Klarna-Idempotency-Key' => $this->getSessionId());
        $oResponse = $this->post($url, $currentSessionData, $headers);
        $this->logKlarnaData(
            'Create KP Order',
            $currentSessionData,
            $url,
            $oResponse->body,
            $this->aSessionData['session_id'],
            $oResponse->status_code
        );

        return $this->handleNewOrderResponse($oResponse, __CLASS__, __FUNCTION__);

    }

    /**
     * @param \Requests_Response $oResponse
     * @param $class
     * @param $method
     * @return mixed
     */
    protected function handleNewOrderResponse(\Requests_Response $oResponse, $class, $method)
    {
        $successCodes = array(200, 201, 204);
        $errorCodes   = array(400, 500, 503);
        $message      = "%s";
        try {
            if (in_array($oResponse->status_code, $successCodes)) {
                KlarnaPayment::cleanUpSession();
                $result = json_decode($oResponse->body, true);
                Registry::getSession()->setVariable('kp_order_id', $result['order_id']);

                return $result;
            }
            if ($oResponse->status_code == 403) {
                throw new KlarnaWrongCredentialsException($oResponse->body, 403);
            }
            if ($oResponse->status_code == 404) {
                throw new KlarnaOrderNotFoundException($oResponse->body, 404);
            }
            if (in_array($oResponse->status_code, $errorCodes)) {
                throw new KlarnaClientException($oResponse->body, $oResponse->status_code);
            } else {
                throw new KlarnaClientException(sprintf($message, 'Unknown error.'), $oResponse->status_code);
            }
        } catch (KlarnaClientException $e) {
            $this->formatAndShowErrorMessage($oResponse);

            return false;
        }
    }

    /**
     * Returns data send to
     * @return array
     */
    protected function formatOrderData()
    {
        if ($this->_oKlarnaOrder instanceof KlarnaPayment) {
            $checkSums = $this->_oKlarnaOrder->fetchCheckSums();
            if ($this->_oKlarnaOrder->isAuthorized()
                //@codeCoverageIgnoreStart
                || $this->_oKlarnaOrder->getStatus() === 'authorize'
                //@codeCoverageIgnoreEnd
                || $checkSums['_aUserData']
            ) {
                $aChangedData = $this->_oKlarnaOrder->getChangedData();
                if ($aChangedData) {
                    $splitted = $this->splitUserData($aChangedData);

                    // update order data
                    return array(json_encode($aChangedData), $splitted);
                }
                //@codeCoverageIgnoreStart
                return null;
                //@codeCoverageIgnoreEnd
            }
        }

        // create order data
        return array(parent::formatOrderData(), null);  // without user information
    }

    /**
     * Splits update data on two parts order lines (orderData) and user data (userData)
     * @param $aChangedData
     * @return array
     */
    protected function splitUserData($aChangedData)
    {
        $userData   = array();
        $filterKeys = array('customer', 'attachment', 'billing_address', 'shipping_address');

        foreach ($aChangedData as $key => $item) {
            if (in_array($key, $filterKeys)) {
                $userData[$key] = $item;
                unset($aChangedData[$key]);
            }
        }

        return array(
            'userData'  => $userData,
            'orderData' => $aChangedData,
        );
    }

    protected function getTimeStamp()
    {
        $dt = new \DateTime();

        return $dt->getTimestamp();
    }
}