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


use TopConcepts\Klarna\Core\Exception\KlarnaCaptureNotAllowedException;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use OxidEsales\Eshop\Core\Exception\StandardException;

class KlarnaOrderManagementClient extends KlarnaClientBase
{

    const ORDERS_ENDPOINT                     = '/ordermanagement/v1/orders/%s';
    const ACKNOWLEDGE_ORDER_ENDPOINT          = '/ordermanagement/v1/orders/%s/acknowledge';
    const CANCEL_ORDER_ENDPOINT               = '/ordermanagement/v1/orders/%s/cancel';
    const UPDATE_MERCHANT_REFERENCES_ENDPOINT = '/ordermanagement/v1/orders/%s/merchant-references';
    const UPDATE_ORDER_LINES_ENDPOINT         = '/ordermanagement/v1/orders/%s/authorization';
    const CAPTURE_ORDER                       = '/ordermanagement/v1/orders/%s/captures';
    const CREATE_ORDER_REFUND                 = '/ordermanagement/v1/orders/%s/refunds';
    const ADD_SHIPPING_TO_CAPTURE             = '/ordermanagement/v1/orders/%s/captures/%s/shipping-info';


    /**
     * @param string $order_id
     * @return mixed
     */
    public function getOrder($order_id)
    {
        $oResponse  = $this->get(sprintf(self::ORDERS_ENDPOINT, $order_id));
        $statusCode = $oResponse->status_code;

        $this->logKlarnaData(
            'Get Order',
            '',
            self::ORDERS_ENDPOINT,
            $oResponse->body,
            $order_id,
            $statusCode
        );

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * @param string $order_id
     * @return mixed
     */
    public function acknowledgeOrder($order_id)
    {
        $oResponse  = $this->post(sprintf(self::ACKNOWLEDGE_ORDER_ENDPOINT, $order_id));
        $statusCode = $oResponse->status_code;

        $this->logKlarnaData(
            'Acknowledge Order',
            '',
            self::ACKNOWLEDGE_ORDER_ENDPOINT,
            $oResponse->body,
            $order_id,
            $statusCode
        );

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * @param string $order_id
     * @return mixed
     * @throws KlarnaCaptureNotAllowedException
     * @throws KlarnaClientException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     */
    public function cancelOrder($order_id)
    {
        $oResponse  = $this->post(sprintf(self::CANCEL_ORDER_ENDPOINT, $order_id));
        $statusCode = $oResponse->status_code;

        $this->logKlarnaData(
            'Cancel Order',
            '',
            self::CANCEL_ORDER_ENDPOINT,
            $oResponse->body,
            $order_id,
            $statusCode
        );

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * @param $oxOrderNr
     * @param $order_id
     * @return array
     * @throws KlarnaCaptureNotAllowedException
     * @throws KlarnaClientException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     */
    public function sendOxidOrderNr($oxOrderNr, $order_id)
    {
        $oResponse  =
            $this->patch(
                sprintf(self::UPDATE_MERCHANT_REFERENCES_ENDPOINT, $order_id),
                json_encode(array('merchant_reference1' => $oxOrderNr))
            );
        $statusCode = $oResponse->status_code;

        $this->logKlarnaData(
            'Patch Order',
            '',
            self::UPDATE_MERCHANT_REFERENCES_ENDPOINT,
            $oResponse->body,
            $order_id,
            $statusCode
        );

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * @param $data
     * @param $order_id
     * @return array
     * @throws KlarnaCaptureNotAllowedException
     * @throws KlarnaClientException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     */
    public function updateOrderLines($data, $order_id)
    {
        $oResponse  = $this->patch(sprintf(self::UPDATE_ORDER_LINES_ENDPOINT, $order_id), json_encode($data));
        $statusCode = $oResponse->status_code;

        $this->logKlarnaData(
            'Update Existing Order',
            json_encode($data),
            self::UPDATE_ORDER_LINES_ENDPOINT,
            $oResponse->body,
            $order_id,
            $statusCode
        );

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * @param $data
     * @param $order_id
     * @return array
     * @throws KlarnaCaptureNotAllowedException
     * @throws KlarnaClientException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     */
    public function captureOrder($data, $order_id)
    {
        $oResponse  = $this->post(sprintf(self::CAPTURE_ORDER, $order_id), json_encode($data));
        $statusCode = $oResponse->status_code;

        $this->logKlarnaData(
            'Capture Order',
            json_encode($data),
            self::CAPTURE_ORDER,
            $oResponse->body,
            $order_id,
            $statusCode
        );

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * @param $order_id
     * @return array
     * @throws KlarnaCaptureNotAllowedException
     * @throws KlarnaClientException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     */
    public function getAllCaptures($order_id)
    {
        $oResponse = $this->get(sprintf(self::CAPTURE_ORDER, $order_id));

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * @param $data
     * @param $order_id
     * @return array
     * @throws KlarnaCaptureNotAllowedException
     * @throws KlarnaClientException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     */
    public function createOrderRefund($data, $order_id)
    {
        $oResponse  = $this->post(sprintf(self::CREATE_ORDER_REFUND, $order_id), json_encode($data));
        $statusCode = $oResponse->status_code;

        $this->logKlarnaData(
            'Refund Order',
            json_encode($data),
            self::CAPTURE_ORDER,
            $oResponse->body,
            $order_id,
            $statusCode
        );

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * @param $data
     * @param $order_id
     * @param $capture_id
     * @return array
     * @throws KlarnaCaptureNotAllowedException
     * @throws KlarnaClientException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     */
    public function addShippingToCapture($data, $order_id, $capture_id)
    {
        $oResponse  = $this->post(sprintf(self::ADD_SHIPPING_TO_CAPTURE, $order_id, $capture_id), json_encode($data));
        $statusCode = $oResponse->status_code;

        $this->logKlarnaData(
            'Add Shipping To Capture',
            json_encode($data),
            self::CAPTURE_ORDER,
            $oResponse->body,
            $order_id,
            $statusCode
        );

        return $this->handleResponse($oResponse, __CLASS__, __FUNCTION__);
    }

    /**
     * @param \Requests_Response $oResponse
     * @param $class
     * @param $method
     * @return array|bool
     * @throws KlarnaClientException
     * @throws KlarnaWrongCredentialsException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaCaptureNotAllowedException
     * @throws KlarnaOrderReadOnlyException
     */
    protected function handleResponse(\Requests_Response $oResponse, $class, $method)
    {
        $successCodes = array(200, 201, 204);
        $errorCodes   = array(400, 422, 500);
        $message      = "%s";
        if (in_array($oResponse->status_code, $successCodes)) {
            if ($oResponse->body) {
                $result = json_decode($oResponse->body, true);

                return $result;
            }

            return true;
        }
        if ($oResponse->status_code == 401) {
            throw new KlarnaWrongCredentialsException(sprintf($message, $this->formatErrorMessage($oResponse)), 401);
        }
        if ($oResponse->status_code == 404) {
            throw new KlarnaOrderNotFoundException(sprintf($message, $this->formatErrorMessage($oResponse)), 404);
        }
        if ($oResponse->status_code == 403) {
            $aResponse = json_decode($oResponse->body, true);
            if ($aResponse['error_code'] === "CAPTURE_NOT_ALLOWED") {
                throw new KlarnaCaptureNotAllowedException(sprintf($message, $this->formatErrorMessage($oResponse)), 403);
            } else {
                throw new KlarnaOrderReadOnlyException(sprintf($message, $this->formatErrorMessage($oResponse)), 403);
            }
        }
        if (in_array($oResponse->status_code, $errorCodes)) {
            throw new KlarnaClientException(sprintf($message, $this->formatAndShowErrorMessage($oResponse)), $oResponse->status_code);
        }

        throw new KlarnaClientException(sprintf($message, 'Unknown error.'), $oResponse->status_code);
    }

    /**
     * @param \Requests_Response $oResponse
     * @return mixed
     */
    protected function formatErrorMessage(\Requests_Response $oResponse)
    {
        $aResponse = json_decode($oResponse->body, true);
        if (is_array($aResponse)) {
            $original = $aResponse['error_messages'][0];
            if ($aResponse['error_code'] === "CAPTURE_NOT_ALLOWED") {
                preg_match(
                    '/(?P<text1>Captured amount )(?P<price1>\d{1,8})(?P<text2> .* )(?P<price2>\d{1,8})(?P<text3> .*\.)/',
                    $original, $matches
                );

                $price1 = ((float)$matches['price1']) / 100;
                $price2 = ((float)$matches['price2']) / 100;

                return $matches['text1'] . $price1 . ' ' . $matches['text2'] . $price2 . ' ' . $matches['text3'];
            }

            return $original;
        }
        $matches = array();
        preg_match('/\<title\>(?P<msg>.+)\<\/title\>/', $oResponse->body, $matches);

        return $matches['msg'];
    }
}