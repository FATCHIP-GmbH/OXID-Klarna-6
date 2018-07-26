<?php

namespace TopConcepts\Klarna\Controller\Admin;


use OxidEsales\Eshop\Core\Request;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\Actions;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class KlarnaDesign extends KlarnaBaseConfig
{

    protected $_sThisTemplate = 'tcklarna_design.tpl';

    /** @inheritdoc */
    protected $MLVars = ['sKlarnaBannerSrc_'];

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

        $this->addTplParam('settings', $this->getAdditionalSettings());
        $this->addTplParam('mode', $this->getActiveKlarnaMode());
        $this->addTplParam('locale', $locale);
        $this->addTplParam('aKlarnaFooterImgUrls', KlarnaConsts::getFooterImgUrls());

        return $this->_sThisTemplate;
    }

    /**
     * Save configuration values
     *
     * @return void
     * @throws \Exception
     */
    public function save()
    {
        parent::save();
        $this->saveAdditionalSetting();
    }

    /**
     *
     */
    protected function saveAdditionalSetting()
    {
        $oConfig   = Registry::getConfig();
        $oShop     = $oConfig->getActiveShop();
        $aSettings = Registry::get(Request::class)->getRequestEscapedParameter('settings');

        $oKlarnaTeaserAction = oxNew(Actions::class);
        $oKlarnaTeaserAction->load('klarna_teaser_' . $oShop->getId());
        $oKlarnaTeaserAction->oxactions__oxactive->setValue($aSettings['blKlarnaTeaserActive'], Field::T_RAW);
        $oKlarnaTeaserAction->save();
    }

    /**
     * @return array
     */
    protected function getAdditionalSettings()
    {
        $oConfig = Registry::getConfig();
        $oShop   = $oConfig->getActiveShop();

        $oKlarnaTeaserAction = oxNew(Actions::class);
        $oKlarnaTeaserAction->load('klarna_teaser_' . $oShop->getId());

        return array(
            'blKlarnaTeaserActive' => $oKlarnaTeaserAction->oxactions__oxactive->value,
            'sDefaultBannerSrc'    => json_encode(KlarnaConsts::getDefaultBannerSrc()),
        );
    }
}