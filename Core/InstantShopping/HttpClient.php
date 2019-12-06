<?php
namespace TopConcepts\Klarna\Core\InstantShopping;


use Requests;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaUtils;
use Requests_Response;

class HttpClient extends KlarnaClientBase
{
    const INSTANT_SHOPPING_ENDPOINT = '/instantshopping/v1';

    public function resolveCredentials($sCountryISO = null) {

        if (!$sCountryISO) {
            $sCountryISO = KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry');
        }

        if (!$aCredentials = KlarnaUtils::getShopConfVar('aKlarnaCreds_' . $sCountryISO)) {
            $aCredentials = array(
                'mid'      => KlarnaUtils::getShopConfVar('sKlarnaMerchantId'),
                'password' => KlarnaUtils::getShopConfVar('sKlarnaPassword'),
            );
        }

        $this->aCredentials = $aCredentials;
    }


    public function createButton($requestParams) {
        $url = self::INSTANT_SHOPPING_ENDPOINT . '/buttons';
        $requestBody = json_encode($requestParams);
        $oResponse = $this->post($url,  $requestBody);
        $this->logKlarnaData(
            'CreateButtonKey',
            $requestBody,
            $url,
            $oResponse->body,
            '',
            $oResponse->status_code
        );

        return $this->handleResponse($oResponse, __CLASS__, __METHOD__);
    }

    public function updateButton($buttonKey, $requestParams) {
        $url = self::INSTANT_SHOPPING_ENDPOINT . "/buttons/$buttonKey";
        $requestBody = json_encode($requestParams);
        $oResponse = $this->put($url, $requestBody);
        $this->logKlarnaData(
            'UpdateButtonKey',
            $requestBody,
            $url,
            $oResponse->body,
            '',
            $oResponse->status_code
        );

        return $this->handleResponse($oResponse, __CLASS__, __METHOD__);
    }

    public function getButton($buttonKey) {
        $url = self::INSTANT_SHOPPING_ENDPOINT . "/buttons/$buttonKey";
        $oResponse = $this->get($url);
        $this->logKlarnaData(
            'GetButtonKey',
            [],
            $url,
            $oResponse->body,
            '',
            $oResponse->status_code
        );

        return $this->handleResponse($oResponse, __CLASS__, __METHOD__);
    }

    public function getOrder($token) {
        $url = self::INSTANT_SHOPPING_ENDPOINT . "/authorizations/$token";
        $oResponse = $this->get($url);
        $this->logKlarnaData(
            'GetOrder',
            '',
            $url,
            $oResponse->body,
            $token,
            $oResponse->status_code
        );

        return $this->handleResponse($oResponse, __CLASS__, __METHOD__);
    }

    /**
     * @param $token
     * @param $requestParams
     * @return array|bool|mixed
     * @throws KlarnaClientException
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException
     */
    public function approveOrder($token, $requestParams) {
        $url = self::INSTANT_SHOPPING_ENDPOINT . "/authorizations/$token/orders";
        $requestBody = json_encode($requestParams);
        $oResponse = $this->post($url, $requestBody);
        $this->logKlarnaData(
            'ApproveOrder',
            $requestBody,
            $url,
            $oResponse->body,
            $token,
            $oResponse->status_code
        );

        return $this->handleResponse($oResponse, __CLASS__, __METHOD__);
    }

    public function declineOrder($token, $requestParams) {
        $url = self::INSTANT_SHOPPING_ENDPOINT . "/authorizations/$token";
        $requestBody = json_encode($requestParams);
        $oResponse = $this->delete($url, $requestBody);
        $this->logKlarnaData(
            'declineOrder',
            $requestBody,
            $url,
            $oResponse->body,
            $token,
            $oResponse->status_code
        );

        return $this->handleResponse($oResponse, __CLASS__, __METHOD__);
    }


    protected function handleResponse(Requests_Response $oResponse, $class, $method) {
        $successCodes = array(200, 201, 204);

        if (in_array($oResponse->status_code, $successCodes)) {
            if ($oResponse->body) {
                return json_decode($oResponse->body, true);
            }

            return true;
        }

        throw new KlarnaClientException($method . $oResponse->body, $oResponse->status_code);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function delete($endpoint, $data = array(), $headers = array())
    {
        return $this->session->request($endpoint, $headers, $data, Requests::DELETE, ['data_format' => 'body']);
    }
}