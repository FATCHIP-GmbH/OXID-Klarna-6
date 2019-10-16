<?php

namespace TopConcepts\Klarna\Controller\Admin;


use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class KlarnaStart extends KlarnaBaseConfig
{

    protected $_sThisTemplate = 'tcklarna_start.tpl';

    /**
     * Render logic
     *
     * @see admin/oxAdminDetails::render()
     * @return string
     */
    public function render()
    {
        // force shopid as parameter
        // Pass shop OXID so that shop object could be loaded
        $sShopOXID = Registry::getConfig()->getShopId();

        $this->setEditObjectId($sShopOXID);

        parent::render();
        $oCountryList = oxNew(CountryList::class);
        $countries = array('DE', 'GB', 'AT', 'NO', 'NL', 'FI', 'SE', 'DK');
        $oSupportedCountryList = $oCountryList->getKalarnaCountriesTitles(
            $this->getViewDataElement('adminlang'),
            $countries
        );

        $this->addTplParam('countries', $oSupportedCountryList);


        return $this->_sThisTemplate;
    }

    /**
     * @return string
     */
    public function getKlarnaModuleInfo()
    {
        /** @var Module $module */
        $module = oxNew(Module::class);
        $module->load('tcklarna');

        $version     = $module->getInfo('version');

        return " VERSION " . $version;
    }
}