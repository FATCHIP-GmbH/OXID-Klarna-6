<?php

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class Klarna_Configuration extends klarna_base_config
{

    protected $_sThisTemplate = 'kl_klarna_kco_config.tpl';

    /** @inheritdoc */
    protected $MLVars = array('sKlarnaTermsConditionsURI_', 'sKlarnaCancellationRightsURI_', 'sKlarnaShippingDetails_');

    /**
     * Render logic
     *
     * @see admin/oxAdminDetails::render()
     * @return string
     * @throws oxSystemComponentException
     * @throws oxConnectionException
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

        $oPayment = oxNew('oxPayment');
        $this->addTplParam('aKPMethods', $oPayment->getKPMethods());
        $this->addTplParam('sLocale', KlarnaConsts::getLocale(true));

        $klarnaMode = $this->getActiveKlarnaMode();
        if ($klarnaMode === KlarnaConsts::MODULE_MODE_KCO) {
            if (oxRegistry::getConfig()->getConfigParam('sSSLShopURL') == null) {
                $this->addTplParam('sslNotSet', true);
            }
            $oxPayment = oxNew('oxpayment');
            $oxPayment->load('klarna_checkout');
            $klarnaActiveInOxid = $oxPayment->oxpayments__oxactive->value == 1;
            if (!$klarnaActiveInOxid) {
                $this->addTplParam('KCOinactive', true);
            }

            $this->addTplParam('blGermanyActive', $this->isGermanyActiveShopCountry());
            $this->addTplParam('blAustriaActive', $this->isAustriaActiveShopCountry());
            $this->addTplParam('activeCountries', KlarnaUtils::getAllActiveKCOGlobalCountryList($this->_aViewData['adminlang']));
            $this->addTplParam('kl_countryList', json_encode(KlarnaUtils::getKlarnaGlobalActiveShopCountries($this->_aViewData['adminlang'])));


            $this->_sThisTemplate = 'kl_klarna_kco_config.tpl';
        }
        if ($klarnaMode === KlarnaConsts::MODULE_MODE_KP) {
            $this->_sThisTemplate = 'kl_klarna_kp_config.tpl';
        }

        return $this->_sThisTemplate;
    }

    public function getErrorMessages()
    {
        return htmlentities(json_encode(array(
            'valueMissing'    => oxRegistry::getLang()->translateString('KL_EXTERNAL_IMAGE_URL_EMPTY'),
            'patternMismatch' => oxRegistry::getLang()->translateString('KL_EXTERNAL_IMAGE_URL_INVALID'),
        )));
    }

    /**
     * @return array
     */
    public function getKlarnaCheckboxOptions()
    {
        $selectValues = array(
            KlarnaConsts::EXTRA_CHECKBOX_NONE                =>
                oxRegistry::getLang()->translateString('KL_NO_CHECKBOX'),
            KlarnaConsts::EXTRA_CHECKBOX_CREATE_USER         =>
                oxRegistry::getLang()->translateString('KL_CREATE_USER_ACCOUNT'),
            KlarnaConsts::EXTRA_CHECKBOX_SIGN_UP             =>
                oxRegistry::getLang()->translateString('KL_SUBSCRIBE_TO_NEWSLETTER'),
            KlarnaConsts::EXTRA_CHECKBOX_CREATE_USER_SIGN_UP =>
                oxRegistry::getLang()->translateString('KL_CREATE_USER_ACCOUNT_AND_SUBSCRIBE'),
        );

        return $selectValues;
    }

    /**
     * @return int
     */
    public function getActiveCheckbox()
    {
        return (int)KlarnaUtils::getShopConfVar('iKlarnaActivecheckbox');
    }

    /**
     * @return array
     */
    public function getKlarnaValidationOptions()
    {
        $selectValues = array(
            KlarnaConsts::NO_VALIDATION            =>
                oxRegistry::getLang()->translateString('KL_NO_VALIDATION_NEEDED'),
            KlarnaConsts::VALIDATION_WITH_SUCCESS  =>
                oxRegistry::getLang()->translateString('KL_VALIDATION_IGNORE_TIMEOUTS_NEEDED'),
            KlarnaConsts::VALIDATION_WITH_NO_ERROR =>
                oxRegistry::getLang()->translateString('KL_SUCCESSFUL_VALIDATION_NEEDED'),
        );

        return $selectValues;
    }

    /**
     * @return int
     */
    public function getChosenValidation()
    {
        return (int)KlarnaUtils::getShopConfVar('iKlarnaValidation');
    }

    /**
     * @return bool
     * @throws oxSystemComponentException
     */
    public function isGermanyActiveShopCountry()
    {
        $activeCountries = KlarnaUtils::getActiveShopCountries();
        foreach ($activeCountries as $country) {
            if ($country->oxcountry__oxisoalpha2->value == 'DE')
                return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws oxSystemComponentException
     */
    public function isAustriaActiveShopCountry()
    {
        $activeCountries = KlarnaUtils::getActiveShopCountries();
        foreach ($activeCountries as $country) {
            if ($country->oxcountry__oxisoalpha2->value == 'AT')
                return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws oxSystemComponentException
     */
    public function isGBActiveShopCountry()
    {
        $activeCountries = KlarnaUtils::getActiveShopCountries();
        foreach ($activeCountries as $country) {
            if ($country->oxcountry__oxisoalpha2->value == 'GB')
                return true;
        }

        return false;
    }

    /**
     * @throws oxSystemComponentException
     */
    public function save()
    {
        parent::save();

        if (KlarnaUtils::isKlarnaPaymentsEnabled()) {
            $oPayment = oxNew('oxpayment');
            $oPayment->setActiveKPMethods();
        }
    }
}