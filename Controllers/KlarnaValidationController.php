<?php

namespace TopConcepts\Klarna\Controllers;


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

        $redirectUrl      = null;
        $this->requestBody = $this->getRequestBody();
        $validator        = $this->getValidator();
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

//            header("", true, $responseStatus);
//            Registry::getUtils()->showMessageAndExit('');
            Registry::getUtils()->redirect("", false, $responseStatus);

        } else {
            $sid            = Registry::get(Request::class)->getRequestEscapedParameter('s');
            $redirectUrl    = Registry::getConfig()->getSslShopUrl() . "index.php?cl=basket&force_sid=$sid&klarnaInvalid=1&";
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
            'kl_logs__klmethod'      => $action,
            'kl_logs__klurl'         => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $url,
            'kl_logs__klorderid'     => $order_id,
            'kl_logs__klrequestraw'  => $requestBody,
            'kl_logs__klresponseraw' => "Code: " . $response .
                                        " \nHeader Location:" . $redirectUrl .
                                        " \nERRORS:" . var_export($errors, true),
            'kl_logs__kldate'        => date("Y-m-d H:i:s"),
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
        $this->order_id         = $aKlarnaOrderData['order_id'];
        return new KlarnaOrderValidator($aKlarnaOrderData);
    }
}