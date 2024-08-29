<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\DeliverySetList;

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
        $this->addTplParam('kebshippingmethods', $this->getShippingMethods());

        return $this->_sThisTemplate;
    }

    public function getShippingMethods() {

        $list = Registry::get(DeliverySetList::class);
        $viewName = $list->getBaseObject()->getViewName();

        $sql = "
            select 
                $viewName.*
            from
                $viewName
            join
                oxobject2payment o2p 
                on $viewName.oxid = o2p.oxobjectid
                and o2p.oxtype = 'oxdelset'
            where 
                " . $list->getBaseObject()->getSqlActiveSnippet() . "
            order by oxpos"

        ;
        $list->selectString($sql);

        return $list;
    }
}