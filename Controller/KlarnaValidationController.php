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


use TopConcepts\Klarna\Core\KlarnaLogs;
use TopConcepts\Klarna\Core\KlarnaOrderValidator;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

class KlarnaValidationController extends FrontendController
{
    /** @var string */
    protected $order_id;

    /** @var string */
    protected $requestBody;

    /**
     * Klarna order validation callback
     * @throws \Exception
     */
    public function init()
    {
        parent::init();

        $redirectUrl       = null;
        $this->requestBody = $this->getRequestBody();
        $validator         = $this->getValidator();
        $validator->validateOrder();

        if ($validator->isValid()) {
            $responseStatus = 200;
            $this->logKlarnaData(
                'Validate Order',
                $this->order_id,
                'FROMKLARNA: ' . $this->requestBody,
                $_SERVER['REQUEST_URI'],
                $responseStatus,
                $validator->getResultErrors() ?: '',
                $redirectUrl ?: ''
            );

            $this->setValidResponseHeader($responseStatus);
            Registry::getUtils()->showMessageAndExit('');
        } else {
            $sid            = Registry::get(Request::class)->getRequestEscapedParameter('s');
            $redirectUrl    = Registry::getConfig()->getShopSecureHomeURL() . "cl=basket&force_sid=$sid&klarnaInvalid=1&";
            $redirectUrl    .= http_build_query($validator->getResultErrors());
            $responseStatus = 303;

            $this->logKlarnaData(
                'Validate Order',
                $this->order_id,
                'FROMKLARNA: ' . $this->requestBody,
                $_SERVER['REQUEST_URI'],
                $responseStatus,
                $validator->getResultErrors(),
                $redirectUrl
            );

            Registry::getUtils()->redirect($redirectUrl, true, $responseStatus);
        }
    }

    /**
     * Logging push state message to database
     * @param $action
     * @param $order_id
     * @param string $requestBody
     * @param $url
     * @param $response
     * @param $errors
     * @param string $redirectUrl
     * @throws \Exception
     */
    protected function logKlarnaData($action, $order_id, $requestBody, $url, $response, $errors, $redirectUrl = '')
    {
        $oKlarnaLog = new KlarnaLogs;
        $aData      = array(
            'tcklarna_logs__tcklarna_method'      => $action,
            'tcklarna_logs__tcklarna_url'         => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $url,
            'tcklarna_logs__tcklarna_orderid'     => $order_id,
            'tcklarna_logs__tcklarna_requestraw'  => $requestBody,
            'tcklarna_logs__tcklarna_responseraw' => "Code: " . $response .
                                                     " \nHeader Location:" . $redirectUrl .
                                                     " \nERRORS:" . var_export($errors, true),
            'tcklarna_logs__tcklarna_date'        => date("Y-m-d H:i:s"),
        );
        $oKlarnaLog->assign($aData);
        $oKlarnaLog->save();
    }

    /**
     * @codeCoverageIgnore
     * @return bool|string
     */
    protected function getRequestBody()
    {
        return file_get_contents('php://input');
    }

    /**
     * @return KlarnaOrderValidator
     */
    protected function getValidator()
    {
        $aKlarnaOrderData = json_decode($this->requestBody, true);
        $this->order_id   = $aKlarnaOrderData['order_id'];

        return new KlarnaOrderValidator($aKlarnaOrderData);
    }

    /**
     * @codeCoverageIgnore
     * @param $responseStatus
     * @return bool
     */
    protected function setValidResponseHeader($responseStatus)
    {
        header("", true, $responseStatus);

        return true;
    }
}