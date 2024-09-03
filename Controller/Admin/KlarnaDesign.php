<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class KlarnaDesign extends KlarnaBaseConfig
{

    protected $_sThisTemplate = 'tcklarna_design.tpl';

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

        if (KlarnaUtils::is_ajax()) {
            $output = $this->getMultiLangData();

            return Registry::getUtils()->showMessageAndExit(json_encode($output));
        }

        $from   = '/' . preg_quote('-', '/') . '/';
        $locale = preg_replace($from, '_', strtolower(KlarnaConsts::getLocale(true)), 1);

        $this->addTplParam('mode', $this->getActiveKlarnaMode());
        $this->addTplParam('locale', $locale);
        $this->addTplParam('aKlarnaFooterImgUrls', KlarnaConsts::getFooterImgUrls());

        $this->addTplParam('kebtheme', array('default', 'light', 'outlined'));
        $this->addTplParam('kebshape', array('default', 'rect', 'pill'));

        return $this->_sThisTemplate;
    }
}