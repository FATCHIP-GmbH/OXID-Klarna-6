<?php

namespace TopConcepts\Klarna\Exception;


use OxidEsales\Eshop\Core\Registry;

class KlarnaOrderNotFoundException extends KlarnaClientException
{
    public function __construct($sMessage = "not set", $iCode = 0, \Exception $previous = null)
    {
        if($sMessage){
            $sMessage = sprintf(Registry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND"), $iCode);
        }
        parent::__construct($sMessage, $iCode = 0, $previous = null);
    }
}