<?php

namespace TopConcepts\Klarna\Exception;


use OxidEsales\Eshop\Core\Registry;

class KlarnaWrongCredentialsException extends KlarnaClientException
{
    public function __construct($sMessage = "not set", $iCode = 0, \Exception $previous = null)
    {
        if($sMessage){
            $sMessage = sprintf(Registry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST"), $iCode);
        }
        parent::__construct($sMessage, $iCode = 0, $previous = null);
    }
}