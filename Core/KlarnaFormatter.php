<?php
namespace Klarna\Klarna\Core;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class KlarnaFormatter
 */
class KlarnaFormatter
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
        'oxcompany'     => 'street_address2',
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
     * @throws oxSystemComponentException
     */
    public static function klarnaToOxidAddress($aCheckoutData, $sKey)
    {
        $aAddressData = $aCheckoutData[$sKey];
        $sTable       = ($sKey == 'billing_address') ? 'oxuser__' : 'oxaddress__';

        $matches = array();
        preg_match('/([^0-9])+/', $aAddressData['street_address'], $matches);
        $aAddressData['street_name']   = $matches[0];
        $aAddressData['street_number'] = str_replace($aAddressData['street_name'], '', $aAddressData['street_address']);

        $oCountry                = oxNew('oxCountry');
        $aAddressData['country'] = $oCountry->getIdByCode(strtoupper($aAddressData['country']));

        $aUserData = array();
        foreach (self::$aFieldMapper as $oxName => $klarnaName) {
            if (!empty($aAddressData[$klarnaName])) {
                if ($klarnaName === 'date_of_birth') {
                    if ($sKey === 'shipping_address') {
                        continue;
                    }
                } else if ($klarnaName === 'title') {
                    $aUserData[$sTable . $oxName] = self::formatSalutation($aAddressData[$klarnaName], strtolower($aAddressData['country']));
                } else if ($klarnaName === 'street_address') {
                    continue;
                } else {
                    $aUserData[$sTable . $oxName] = trim($aAddressData[$klarnaName]);
                }
            }
        }

        return $aUserData;
    }

    /**
     * @param $oxObject oxUser|oxAddress
     * @return array
     * @throws TypeError
     * @throws oxSystemComponentException
     */
    public static function oxidToKlarnaAddress($oxObject)
    {
        if ($oxObject instanceof User)
            $sTable = 'oxuser__';
        else if ($oxObject instanceof Address) {
            $sTable   = 'oxaddress__';
            $oxObject = self::completeUserData($oxObject);
        } else
            throw new \TypeError('Argument must be instance of oxUser|oxAddress.');

        $aUserData   = array();
        $sCountryISO = strtolower(KlarnaUtils::getCountryISO($oxObject->{$sTable . 'oxcountryid'}->value));

        foreach (self::$aFieldMapper as $oxName => $klarnaName) {

            if ($klarnaName === 'street_address') {
                $aUserData[$klarnaName] = "{$oxObject->{$sTable . 'oxstreet'}->value} {$oxObject->{$sTable . 'oxstreetnr'}->value}";
            } else if ($klarnaName === 'date_of_birth') {
                continue;
            } else if ($klarnaName === 'country') {
                $aUserData[$klarnaName] = $sCountryISO;
            } else if ($klarnaName === 'title'/* && KlarnaUtils::getShopConfVar('blKlarnaSalutationMandatory')*/) {
                if ($sTitle = self::formatSalutation($oxObject->{$sTable . 'oxsal'}->value, $sCountryISO))
                    $aUserData[$klarnaName] = $sTitle ?: null;
            } else if ($klarnaName === 'street_name' || $klarnaName === 'street_number') {
                continue;
            } else {
                $value                  = $oxObject->{$sTable . $oxName}->value;
                $aUserData[$klarnaName] = !empty($value) ? $value : null;
            }

        }

        if (is_null($aUserData['phone'])) {
            $value              = $oxObject->{$sTable . 'oxfon'}->value;
            $aUserData['phone'] = !empty($value) ? $value : null;
        }

        //clean up
        foreach ($aUserData as $key => $value) {
            if (!$value) {
                unset($aUserData[$key]);
            } else {
                $aUserData[$key] = html_entity_decode($aUserData[$key], ENT_QUOTES);
            }
        }

        return $aUserData;
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
    }
}
