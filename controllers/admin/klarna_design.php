<?php

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class Klarna_Design extends klarna_base_config
{

    protected $_sThisTemplate = 'kl_klarna_design.tpl';

    /** @inheritdoc */
    protected $MLVars = array('sKlarnaBannerSrc_');

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
        $sShopOXID = oxRegistry::getConfig()->getShopId();

        $this->setEditObjectId($sShopOXID);

        if (KlarnaUtils::is_ajax()) {
            $output = $output = $this->getMultiLangData();

            return oxRegistry::getUtils()->showMessageAndExit(json_encode($output));
        }

        $this->addTplParam('settings', $this->getAdditionalSettings());
        $this->addTplParam('mode', $this->getActiveKlarnaMode());
        $this->addTplParam('locale', strtolower(KlarnaConsts::getLocale(true)));
        $this->addTplParam('aKlarnaFooterImgUrls', KlarnaConsts::getFooterImgUrls());

        return $this->_sThisTemplate;
    }

    /**
     * Save configuration values
     *
     * @return void
     * @throws oxSystemComponentException
     */
    public function save()
    {
        parent::save();
        $this->saveAdditionalSetting();
    }

    /**
     * @throws oxSystemComponentException
     */
    protected function saveAdditionalSetting()
    {
        $oConfig   = oxRegistry::getConfig();
        $oShop     = $oConfig->getActiveShop();
        $aSettings = $this->getConfig()->getRequestParameter('settings');

        $oKlarnaTeaserAction = oxnew('oxActions');
        $oKlarnaTeaserAction->load('klarna_teaser_' . $oShop->getId());
        $oKlarnaTeaserAction->oxactions__oxactive->setValue($aSettings['blKlarnaTeaserActive'], oxField::T_RAW);
        $oKlarnaTeaserAction->save();
    }

    protected function getAdditionalSettings()
    {
        $oConfig = oxRegistry::getConfig();
        $oShop   = $oConfig->getActiveShop();

        $oKlarnaTeaserAction = oxnew('oxActions');
        $oKlarnaTeaserAction->load('klarna_teaser_' . $oShop->getId());

        return array(
            'blKlarnaTeaserActive' => $oKlarnaTeaserAction->oxactions__oxactive->value,
            'sDefaultBannerSrc'    => json_encode(KlarnaConsts::getDefaultBannerSrc()),
        );
    }
}