<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class KlarnaGeneral extends KlarnaBaseConfig
{

    protected $_sThisTemplate = 'tcklarna_general.tpl';

    protected $_aKlarnaCountryCreds = array();

    protected $_aKlarnaCountries = array();

    /** @inheritdoc */
    protected $MLVars = ['sKlarnaAnonymizedProductTitle_'];

    /**
     * Render logic
     *
     * @see admin/oxAdminDetails::render()
     * @return string
     */
    public function render()
    {
        parent::render();
        // force shopid as parameter
        // Pass shop OXID so that shop object could be loaded
        $sShopOXID = Registry::getConfig()->getShopId();

        $this->setEditObjectId($sShopOXID);

        if(KlarnaUtils::is_ajax()){
            $output = $this->getMultiLangData();
            return Registry::getUtils()->showMessageAndExit(json_encode($output));
        }

        $this->addTplParam('tcklarna_countryCreds', $this->getKlarnaCountryCreds());
        $this->addTplParam('tcklarna_countryList', json_encode($this->getKlarnaCountryAssocList()));
        $this->addTplParam(
            'tcklarna_notSetUpCountries',
            array_diff_key($this->_aKlarnaCountries, $this->_aKlarnaCountryCreds) ?: false
        );
        $this->addTplParam('activeCountries', KlarnaUtils::getKlarnaGlobalActiveShopCountries($this->getViewDataElement('adminlang')));

        return $this->_sThisTemplate;
    }

    /**
     * @return array|false
     */
    public function getKlarnaCountryCreds()
    {
        if($this->_aKlarnaCountryCreds){
            return $this->_aKlarnaCountryCreds;
        }
        $this->_aKlarnaCountryCreds = array();
        foreach ($this->getViewDataElement('confaarrs') as $sKey => $serializedArray) {
            if (strpos($sKey, 'aKlarnaCreds_') === 0) {

                $this->_aKlarnaCountryCreds[substr($sKey, -2)] = $serializedArray;
            }
        }
        
        return $this->_aKlarnaCountryCreds ?: false;
    }

    protected function convertNestedParams($nestedArray)
    {
        /*** get Country Specific Credentials Config Keys for all Klarna Countries ***/
        $aCountrySpecificCredsConfigKeys = array_map(
            function ($countryISO) {
                return 'aKlarnaCreds_' . $countryISO;
            },
            array_keys($this->getKlarnaCountryAssocList())
        );

        if (is_array($nestedArray)) {
            foreach ($nestedArray as $key => $arr) {
                if (strpos($key, 'aKlarnaCreds_') === 0) {
                    /*** remove key from the list if present in POST data ***/
                    unset($aCountrySpecificCredsConfigKeys[array_search($key, $aCountrySpecificCredsConfigKeys)]);
                }
                /*** serialize all assoc arrays ***/
                $nestedArray[$key] = $this->_aarrayToMultiline($arr);
            }
        }

        if ($aCountrySpecificCredsConfigKeys)
            /*** drop all keys that was not passed with POST data ***/
            $this->removeConfigKeys($aCountrySpecificCredsConfigKeys);

        return $nestedArray;
    }

    /**
     * @return mixed
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function getKlarnaCountryAssocList()
    {
        if ($this->_aKlarnaCountries) {
            return $this->_aKlarnaCountries;
        }
        $sViewName = getViewName('oxcountry', $this->getViewDataElement('adminlang'));
        $isoList   = KlarnaUtils::getKlarnaGlobalActiveShopCountryISOs();

        /** @var \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database $db */
        $db  = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sql = 'SELECT oxisoalpha2, oxtitle 
                FROM ' . $sViewName . ' 
                WHERE oxisoalpha2 IN ("' . implode('","', $isoList) . '")';

        /** @var \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\ResultSet $oResult */
        $oResult = $db->select($sql);
        foreach($oResult->getIterator() as $aCountry){
            $this->_aKlarnaCountries[$aCountry['OXISOALPHA2']] = $aCountry['OXTITLE'];
        }

        return $this->_aKlarnaCountries;
    }


}