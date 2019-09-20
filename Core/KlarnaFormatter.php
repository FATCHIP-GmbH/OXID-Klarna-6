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


use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Model\KlarnaUser;

/**
 * Class KlarnaFormatter
 */
class KlarnaFormatter extends Base
{
    static $aFieldMapper = array(
        'oxusername'    => 'email',
        'oxfname'       => 'given_name',
        'oxlname'       => 'family_name',
        'joinedAddress' => 'street_address',
        'oxstreet'      => 'street_name',
        'oxstreetnr'    => 'street_number',
        'oxzip'         => 'postal_code',
        'oxcity'        => 'city',
        'oxstateid'     => 'region',
        'oxmobfon'      => 'phone',
        'oxcountryid'   => 'country',
        'oxsal'         => 'title',
        'oxcompany'     => 'organization_name',
        'oxaddinfo'     => 'street_address2',
        'oxbirthdate'   => 'date_of_birth',
    );

    static $aMaleSalutations = array(
        'Mr'   => array('gb', 'other'),
        'Herr' => array('de', 'at', 'ch'),
        'Dhr.' => array('nl'),
    );

    static $aFemaleSalutations = array(
        'Ms'    => array('gb', 'other'),
        'Mrs'   => array('gb'),
        'Miss'  => array('gb'),
        'Frau'  => array('de', 'at', 'ch'),
        'Mevr.' => array('nl'),
    );

    /**
     * @param $aCheckoutData array  Klarna address
     * @param $sKey string klarna address key ('billing_address'|'shipping_address')
     * @return array
     */
    public static function klarnaToOxidAddress($aCheckoutData, $sKey)
    {
        if(!$aCheckoutData){
            return null;
        }

        $aAddressData = $aCheckoutData[$sKey];
        $sTable       = ($sKey == 'billing_address') ? 'oxuser__' : 'oxaddress__';

        $matches = array();
        preg_match('/([^0-9])+/', $aAddressData['street_address'], $matches);
        $aAddressData['street_name']   = $matches[0];
        $aAddressData['street_number'] = str_replace($aAddressData['street_name'], '', $aAddressData['street_address']);

        $oCountry                = oxNew(Country::class);
        $aAddressData['country'] = $oCountry->getIdByCode(strtoupper($aAddressData['country']));

        $aUserData = array();
        foreach (self::$aFieldMapper as $oxName => $klarnaName) {
            if ($klarnaName === 'street_address') {
                continue;
            } else if ($klarnaName === 'title') {
                $aUserData[$sTable . $oxName] = self::formatSalutation($aAddressData[$klarnaName], strtolower($aAddressData['country']));
            } else {
                $aUserData[$sTable . $oxName] = trim($aAddressData[$klarnaName]);
            }
        }

        return $aUserData;
    }

    /**
     * @param $oxObject KlarnaUser|User|Address
     * @return array
     * @throws \Exception
     */
    public static function oxidToKlarnaAddress($oxObject)
    {
        $sTable = self::validateInstance($oxObject);

        $aUserData   = array();
        $sCountryISO = strtolower(KlarnaUtils::getCountryISO($oxObject->{$sTable . 'oxcountryid'}->value));

        self::compileUserData($aUserData, $oxObject, $sTable, $sCountryISO);

        if (is_null($aUserData['phone'])) {
            $value              = $oxObject->{$sTable . 'oxfon'}->value;
            $aUserData['phone'] = !empty($value) ? $value : null;
        }

        //clean up
        foreach ($aUserData as $key => $value) {
            $aUserData[$key] = html_entity_decode($aUserData[$key], ENT_QUOTES);
            if (!$value) {
                unset($aUserData[$key]);
            }
        }

        return $aUserData;
    }

    protected static function compileUserData(&$aUserData, $oxObject, $sTable, $sCountryISO)
    {
        $ignoreNames = ['date_of_birth', 'street_name', 'street_number'];
        $aExtendFieldMapper = self::$aFieldMapper;
        $aExtendFieldMapper['oxaddinfo'] = "care_of";
        
        //Remove unwanted fields
        $validMappedFields = array_diff($aExtendFieldMapper, $ignoreNames);

        foreach ($validMappedFields as $oxName => $klarnaName) {
            switch ($klarnaName) {
                case 'street_address':
                    $aUserData[$klarnaName] = "{$oxObject->{$sTable . 'oxstreet'}->value} {$oxObject->{$sTable . 'oxstreetnr'}->value}";
                    break;
                case 'country':
                    $aUserData[$klarnaName] = $sCountryISO;
                    break;
                case 'title':
                    $aUserData[$klarnaName] = null;
                    $sTitle = self::formatSalutation($oxObject->{$sTable.'oxsal'}->value, $sCountryISO);
                    if (!empty($sTitle)) {
                        $aUserData[$klarnaName] = $sTitle;
                    }
                    break;
                default:
                    $value = $oxObject->{$sTable.$oxName}->value;
                    $aUserData[$klarnaName] = null;
                    if (!empty($value)) {
                        $aUserData[$klarnaName] = $value;
                    }
            }
        }
        if (!empty($aUserData["care_of"])) {
            $aUserData["care_of"] = preg_replace("/^c\/o /", "", $aUserData["care_of"]);
        }
    }

    /**
     * @param $oxObject
     * @return string
     * @throws \Exception
     */
    protected static function validateInstance(&$oxObject)
    {
        if ($oxObject instanceof User) {
            $sTable = 'oxuser__';
        } else if ($oxObject instanceof Address) {
            $sTable   = 'oxaddress__';
            $oxObject = self::completeUserData($oxObject);
        } else{
            throw new \Exception('Argument must be instance of User|Address.');
        }

        return $sTable;
    }

    /**
     * @param Address $oAddress
     * @return Address
     */
    public static function completeUserData(Address $oAddress)
    {
        $oUser  = Registry::getConfig()->getUser();
        $sEmail = $oUser->oxuser__oxusername->value;
        if (!$oUser) {
            $sEmail = Registry::getSession()->getVariable('klarna_checkout_user_email');
        }
        $oAddress->oxaddress__oxusername = new Field($sEmail, Field::T_RAW);

        return $oAddress;
    }

    /**
     * Resolve the proper salutation for any country.
     *
     * @param $title
     * @param $sCountryISO
     * @return string
     */
    public static function formatSalutation($title, $sCountryISO)
    {
        if (!$title) {
            return false;
        }

        $title = ucfirst(strtolower($title));
        if (!in_array(strtolower($sCountryISO), array('gb', 'de', 'at', 'ch', 'nl'))) {
            $sCountryISO = 'other';
        }

        if (key_exists($title, self::$aMaleSalutations)) {
            $table = self::$aMaleSalutations;
        } else {
            $table = self::$aFemaleSalutations;
        }

        if (in_array(strtolower($sCountryISO), $table[$title])) {
            return $title;
        }

        foreach ($table as $sSal => $aCountries) {
            if (in_array(strtolower($sCountryISO), $aCountries)) {
                return $sSal;
            }
        }
        //@codeCoverageIgnoreStart
        return false;
        //@codeCoverageIgnoreEnd
    }


    public static function getFormattedUserAddresses($_oUser)
    {
        $db      = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sql     = 'SELECT oxid, oxfname, oxlname, oxstreet, oxstreetnr, oxzip, oxcity FROM oxaddress WHERE oxuserid=?';
        $results = $db->getAll($sql, array($_oUser->getId()));

        if (!is_array($results) || empty($results)) {
            return false;
        }

        $formattedResults = array();
        foreach ($results as $data) {
            $formattedResults[$data['oxid']] =
                $data['oxfname'] . ' ' .
                $data['oxlname'] . ', ' .
                $data['oxstreet'] . ' ' .
                $data['oxstreetnr'] . ', ' .
                $data['oxzip'] . ' ' .
                $data['oxcity'];
        }

        return $formattedResults;
    }

}
