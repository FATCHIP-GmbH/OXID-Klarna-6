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
use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;

abstract class KlarnaClientBase extends Base
{
    const TEST_API_URL = 'https://api.playground.klarna.com/';
    const LIVE_API_URL = 'https://api.klarna.com/';

    /**
     * @var \Requests_Session
     */
    protected $session;

    /**
     * @var Base | KlarnaClientBase
     */
    private static $instance;

    /**
     * @var KlarnaOrder
     */
    protected $_oKlarnaOrder;

    /**
     * @var array
     */
    protected $aCredentials;

    /**
     * @param null $sCountryISO
     * @return KlarnaClientBase
     */
    static function getInstance($sCountryISO = null)
    {
        $calledClass = get_called_class();
        if (self::$instance === null || !self::$instance instanceof $calledClass) {

            self::$instance               = new $calledClass();
            $aKlarnaCredentials           = KlarnaUtils::getAPICredentials($sCountryISO);
            self::$instance->aCredentials = $aKlarnaCredentials;
            $test                         = KlarnaUtils::getShopConfVar('blIsKlarnaTestMode');
            $apiUrl                       = $test ? self::TEST_API_URL : self::LIVE_API_URL;
            $headers                      = array('Authorization' => 'Basic ' . base64_encode("{$aKlarnaCredentials['mid']}:{$aKlarnaCredentials['password']}"), 'Content-Type' => 'application/json');
            $headers                      = array_merge($headers, self::$instance->getApiClientHeader());
            self::$instance->loadHttpHandler(new \Requests_Session($apiUrl, $headers));
        }

        return self::$instance;
    }

    static function resetInstance()
    {
        self::$instance = null;
    }

    /**
     * @param \Requests_Session $session
     */
    protected function loadHttpHandler(\Requests_Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param string $endpoint
     * @param array $data json
     * @param array $headers
     * @return \Requests_Response
     */
    protected function post($endpoint, $data = array(), $headers = array())
    {
        return $this->session->post($endpoint, $headers, $data);
    }

    /**
     * @param string $endpoint
     * @param array $headers
     * @return \Requests_Response
     */
    protected function get($endpoint, $headers = array())
    {
        return $this->session->get($endpoint, $headers);
    }

    /**
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return \Requests_Response
     */
    protected function patch($endpoint, $data = array(), $headers = array())
    {
        return $this->session->patch($endpoint, $headers, $data);
    }

    /**
     * @param $endpoint
     * @param array $data
     * @param array $headers
     * @return \Requests_Response
     */
    protected function delete($endpoint, $data = array(), $headers = array())
    {
        return $this->session->delete($endpoint, $headers, $data);
    }

    /**
     * @param \Requests_Response $oResponse
     * @param $class
     * @param $method
     * @return array|bool
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     * @throws KlarnaOrderNotFoundException
     * @throws KlarnaOrderReadOnlyException
     * @throws KlarnaWrongCredentialsException
     * @throws KlarnaClientException
     */
    protected function handleResponse(\Requests_Response $oResponse, $class, $method)
    {
        $successCodes = array(200, 201, 204);
        $errorCodes   = array(400, 422, 500);
        $message      = "%s";

        if (in_array($oResponse->status_code, $successCodes)) {
            if ($oResponse->body) {
                return json_decode($oResponse->body, true);
            }

            return true;
        }
        if ($oResponse->status_code == 401) {
            throw new KlarnaWrongCredentialsException(sprintf($message, 'Unauthorized request'), $oResponse->status_code);
        }
        if ($oResponse->status_code == 404) {
            throw new KlarnaOrderNotFoundException($oResponse->body, 404);
        }
        if ($oResponse->status_code == 403) {
            throw new KlarnaOrderReadOnlyException($oResponse->body, 403);
        }
        if (in_array($oResponse->status_code, $errorCodes)) {
            $this->formatAndShowErrorMessage($oResponse);
            throw new KlarnaClientException($oResponse->body, $oResponse->status_code);
        }
        throw new KlarnaClientException(sprintf($message, 'Unknown error.'), $oResponse->status_code);
    }

    /**
     * @param $aErrors
     * @codeCoverageIgnore
     */
    public static function addErrors($aErrors)
    {
        foreach ($aErrors as $message) {
            Registry::get(UtilsView::class)->addErrorToDisplay($message);
        }
    }

    /**
     * @return array
     */
    protected function getApiClientHeader()
    {

        $php    = phpversion();
        $phpVer = 'PHP' . $php;

        $shopName = self::$instance->getConfig()->getActiveShop()->oxshops__oxname->value;

        $shopEdition = self::$instance->getConfig()->getActiveShop()->oxshops__oxedition->value;
        $shopRev     = self::$instance->getConfig()->getActiveShop()->oxshops__oxversion->value;
        $shopVer     = 'OXID_' . $shopEdition . '_' . $shopRev;

        $module = oxNew(Module::class);
        $module->loadByDir('tcklarna');
        $moduleTitlt = $module->getTitle();
        $moduleVer  = $module->getInfo('version');
        $moduleInfo = str_replace(' ', '_', $moduleTitlt . "_" . $moduleVer);

        $os = php_uname('s');
        $os .= "_" . php_uname('r');
        $os .= "_" . php_uname('m');

        return array('User-Agent' => 'OS/' . $os . ' Language/' . $phpVer . ' Cart/' . $shopVer . '-' . $shopName . ' Plugin/' . $moduleInfo);
    }

    /**
     * Logging push state message to database
     * @param $action
     * @param string $requestBody
     * @param $url
     * @param $responseRaw
     * @param string $order_id
     * @param $statusCode
     * @throws \Exception
     */
    protected function logKlarnaData($action, $requestBody, $url, $responseRaw, $order_id = '', $statusCode)
    {
        if (is_array($requestBody)) {
            $requestBody = json_encode($requestBody);
        }

        if ($order_id === '') {
            $response = json_decode($responseRaw, true);
            $order_id = isset($response['order_id']) ? $response['order_id'] : '';
        }
        $url = substr($this->session->url, 0, -1) . sprintf($url, $order_id);

        $mid        = $this->aCredentials['mid'];
        $oKlarnaLog = new KlarnaLogs;
        $aData      = array(
            'tcklarna_logs__tcklarna_method'      => $action,
            'tcklarna_logs__tcklarna_url'         => $url,
            'tcklarna_logs__tcklarna_orderid'     => $order_id,
            'tcklarna_logs__tcklarna_mid'         => $mid,
            'tcklarna_logs__tcklarna_statuscode'  => $statusCode,
            'tcklarna_logs__tcklarna_requestraw'  => $requestBody,
            'tcklarna_logs__tcklarna_responseraw' => $responseRaw,
            'tcklarna_logs__tcklarna_date'        => date("Y-m-d H:i:s"),
        );
        $oKlarnaLog->assign($aData);
        $oKlarnaLog->save();
    }

    /**
     * @return string
     */
    protected function formatOrderData()
    {
        return json_encode($this->_oKlarnaOrder->getOrderData());
    }

    /**
     * @param \Requests_Response $oResponse
     */
    protected function formatAndShowErrorMessage(\Requests_Response $oResponse)
    {
        $aResponse = json_decode($oResponse->body, true);
        if (is_array($aResponse)) {
            $this->addErrors($aResponse['error_messages']);

            return;
        }

        $matches = array();
        preg_match('/\<title\>(?P<msg>.+)\<\/title\>/', $oResponse->body, $matches);
        $this->addErrors(array($matches['msg']));

        return;
    }
}