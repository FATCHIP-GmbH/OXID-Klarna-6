<?php

class klarna_validate extends oxUBase
{
    /**
     * Klarna order validation callback
     * @throws oxSystemComponentException
     * @throws oxConnectionException
     */
    public function init()
    {
        parent::init();

        $requestBody      = file_get_contents('php://input');
        $aKlarnaOrderData = json_decode($requestBody, true);
        $order_id         = $aKlarnaOrderData['order_id'];
        $validator        = oxNew('KlarnaOrderValidator', $aKlarnaOrderData);
        $redirectUrl      = null;
        if ($validator->validateOrder()) {
            $this->logKlarnaData(
                'Validate Order',
                $order_id,
                'FROMKLARNA: ' . $requestBody,
                $_SERVER['REQUEST_URI'],
                200,
                $validator->getResultErrors() ?: '',
                $redirectUrl ?: ''
            );

            header("", true, 200);
            oxRegistry::getUtils()->showMessageAndExit('');
        } else {
            $sid         = oxRegistry::getConfig()->getRequestParameter('s');
            $redirectUrl = oxRegistry::getConfig()->getShopHomeURL() . "cl=basket&force_sid=$sid&";
            $redirectUrl .= http_build_query($validator->getResultErrors(), 'error_msg_');

            $this->logKlarnaData(
                'Validate Order',
                $order_id,
                'FROMKLARNA: ' . $requestBody,
                $_SERVER['REQUEST_URI'],
                303,
                $validator->getResultErrors(),
                $redirectUrl
            );

            oxRegistry::getUtils()->redirect($redirectUrl, true, 302);
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
     * @throws oxSystemComponentException
     */
    protected function logKlarnaData($action, $order_id, $requestBody, $url, $response, $errors, $redirectUrl = '')
    {
        $oKlarnaLog = oxNew('klarna_logs');
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