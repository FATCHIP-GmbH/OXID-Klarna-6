<?php

namespace Klarna\Klarna\Controllers\Admin;


/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class KlarnaStart extends klarna_base_config
{

    protected $_sThisTemplate = 'kl_klarna_start.tpl';

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
        $sShopOXID = oxRegistry::getConfig()->getShopId();

        $this->setEditObjectId($sShopOXID);

        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * @return string
     * @throws oxSystemComponentException
     */
    public function getKlarnaModuleInfo()
    {
        $module = oxNew('oxModule');
        $module->load('klarna');

        $description = strtoupper($module->getInfo('description'));
        $version     = $module->getInfo('version');

        return $description . " VERSION " . $version;
    }
}