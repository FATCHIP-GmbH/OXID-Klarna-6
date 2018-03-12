<?php
namespace Klarna\Klarna\Controllers;

use Klarna\Klarna\Core\KlarnaLogs;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry as oxRegistry;
use OxidEsales\Eshop\Core\Request;

class KlarnaValidate extends FrontendController
{
    /**
     * Klarna order validation callback
     * @throws \Exception
     */
    public function init()
    {
        parent::init();

        $requestBody      = file_get_contents('php://input');
        $aKlarnaOrderData = json_decode($requestBody, true);
        $order_id         = $aKlarnaOrderData['order_id'];
        $validator        = oxNew('KlarnaOrderValidator', $aKlarnaOrderData);
        $redirectUrl      = null;
        $validator->validateOrder();

        if ($validator->isValid()) {
            $responseStatus = 200;
            $this->logKlarnaData(
                'Validate Order',
                $order_id,
                'FROMKLARNA: ' . $requestBody,
                $_SERVER['REQUEST_URI'],
                $responseStatus,
                $validator->getResultErrors() ?: '',
                $redirectUrl ?: ''
            );

            header("", true, $responseStatus);
            oxRegistry::getUtils()->showMessageAndExit('');
        } else {
            $sid         = oxRegistry::get(Request::class)->getRequestParameter('s');
            $redirectUrl = oxRegistry::getConfig()->getSslShopUrl() . "index.php?cl=basket&force_sid=$sid&klarnaInvalid=1&";
            $redirectUrl .= http_build_query($validator->getResultErrors());
            $responseStatus = 303;

            $this->logKlarnaData(
                'Validate Order',
                $order_id,
                'FROMKLARNA: ' . $requestBody,
                $_SERVER['REQUEST_URI'],
                $responseStatus,
                $validator->getResultErrors(),
                $redirectUrl
            );

            oxRegistry::getUtils()->redirect($redirectUrl, true, $responseStatus);
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
}