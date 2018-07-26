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

namespace TopConcepts\Klarna\Model;


use TopConcepts\Klarna\Core\KlarnaConsts;

class KlarnaCountryList extends KlarnaCountryList_parent
{
    /**
     * Selects and loads all active countries that are assigned to klarna_checkout
     *
     * @param integer $iLang language
     */
    public function loadActiveKlarnaCheckoutCountries($iLang = null)
    {
        $sViewName = getViewName('oxcountry', $iLang);
        $isoList   = KlarnaConsts::getKlarnaGlobalCountries();
        $isoList   = implode("','", $isoList);
        $sSelect   = "SELECT {$sViewName}.oxid, {$sViewName}.oxtitle, {$sViewName}.oxisoalpha2 FROM {$sViewName}
                      JOIN oxobject2payment 
                      ON oxobject2payment.oxobjectid = {$sViewName}.oxid
                      WHERE oxobject2payment.oxpaymentid = 'klarna_checkout'
                      AND oxobject2payment.oxtype = 'oxcountry'
                      AND {$sViewName}.oxactive=1 
                      AND {$sViewName}.oxisoalpha2 IN ('{$isoList}')";
        $this->selectString($sSelect);
    }

    /**
     * Selects and loads all active countries that are NOT Klarna Global countries
     *
     * @param integer $iLang language
     */
    public function loadActiveNonKlarnaCheckoutCountries($iLang = null)
    {
        $sViewName = getViewName('oxcountry', $iLang);
        $isoList   = KlarnaConsts::getKlarnaGlobalCountries();
        $isoList   = implode("','", $isoList);
        $sSelect   = "SELECT oxid, oxtitle, oxisoalpha2 FROM {$sViewName}
                      WHERE oxactive=1 
                      AND (
                      oxisoalpha2 NOT IN ('{$isoList}')
                      OR oxid NOT IN (SELECT oxobjectid FROM oxobject2payment WHERE oxpaymentid = 'klarna_checkout')
                      )
                      ORDER BY oxorder, oxtitle";
        $this->selectString($sSelect);
    }

    /**
     * Selects and loads all active countries that are on Klarna's KCO Global list
     * @param null $iLang
     */
    public function loadActiveKCOGlobalCountries($iLang = null)
    {
        $sViewName = getViewName('oxcountry', $iLang);
        $isoList   = KlarnaConsts::getKlarnaGlobalCountries();
        $isoList   = implode("','", $isoList);
        $sSelect   = "SELECT {$sViewName}.oxid, {$sViewName}.oxtitle, {$sViewName}.oxisoalpha2 FROM {$sViewName}
                      WHERE {$sViewName}.oxactive=1 
                      AND {$sViewName}.oxisoalpha2 IN ('{$isoList}')";
        $this->selectString($sSelect);
    }

}