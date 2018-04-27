<?php

namespace TopConcepts\Klarna\Controller\Admin;


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

        $description = strtoupper($module->getInfo('description'));
        $version     = $module->getInfo('version');

        return $description . " VERSION " . $version;
    }
}