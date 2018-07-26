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

namespace TopConcepts\Klarna\Core\Exception;


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