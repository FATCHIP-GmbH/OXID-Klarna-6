<?php

namespace Klarna\Klarna\Models;


use Klarna\Klarna\Core\KlarnaConsts;

class KlarnaCountryList extends KlarnaCountryList_parent
{
    /**
     * Selects and loads all active countries that are Klarna Global countries
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