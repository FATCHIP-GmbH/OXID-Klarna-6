<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class KlarnaConfiguration extends KlarnaBaseConfig
{

    protected $_sThisTemplate = 'tcklarna_kco_config.tpl';

    /** @inheritdoc */
    protected $MLVars = ['sKlarnaTermsConditionsURI_', 'sKlarnaCancellationRightsURI_', 'sKlarnaShippingDetails_'];

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
            $output = $output = $this->getMultiLangData();

            return Registry::getUtils()->showMessageAndExit(json_encode($output));
        }

        $oPayment = oxNew(Payment::class);
        $this->addTplParam('aKPMethods', $oPayment->getKPMethods());
        $this->addTplParam('sLocale', KlarnaConsts::getLocale(true));

        $klarnaMode = $this->getActiveKlarnaMode();
        if ($klarnaMode === KlarnaConsts::MODULE_MODE_KCO) {
            if (Registry::getConfig()->getConfigParam('sSSLShopURL') == null) {
                $this->addTplParam('sslNotSet', true);
            }
            $oPayment->load('klarna_checkout');
            $klarnaActiveInOxid = $oPayment->oxpayments__oxactive->value == 1;
            if (!$klarnaActiveInOxid) {
                $this->addTplParam('KCOinactive', true);
            }

            $this->addTplParam('blGermanyActive', $this->isGermanyActiveShopCountry());
            $this->addTplParam('blAustriaActive', $this->isAustriaActiveShopCountry());
            $this->addTplParam('activeCountries', KlarnaUtils::getAllActiveKCOGlobalCountryList($this->getViewDataElement('adminlang')));
            $this->addTplParam('tcklarna_countryList', json_encode(KlarnaUtils::getKlarnaGlobalActiveShopCountries($this->getViewDataElement('adminlang'))));


            $this->_sThisTemplate = 'tcklarna_kco_config.tpl';
        }
        if ($klarnaMode === KlarnaConsts::MODULE_MODE_KP) {
            $this->_sThisTemplate = 'tcklarna_kp_config.tpl';
        }

        return $this->_sThisTemplate;
    }

    public function getErrorMessages()
    {
        return htmlentities(json_encode(array(
            'valueMissing'    => Registry::getLang()->translateString('TCKLARNA_EXTERNAL_IMAGE_URL_EMPTY'),
            'patternMismatch' => Registry::getLang()->translateString('TCKLARNA_EXTERNAL_IMAGE_URL_INVALID'),
        )));
    }

    /**
     * @return array
     */
    public function getKlarnaCheckboxOptions()
    {
        $selectValues = array(
            KlarnaConsts::EXTRA_CHECKBOX_NONE                =>
                Registry::getLang()->translateString('TCKLARNA_NO_CHECKBOX'),
            KlarnaConsts::EXTRA_CHECKBOX_CREATE_USER         =>
                Registry::getLang()->translateString('TCKLARNA_CREATE_USER_ACCOUNT'),
            KlarnaConsts::EXTRA_CHECKBOX_SIGN_UP             =>
                Registry::getLang()->translateString('TCKLARNA_SUBSCRIBE_TO_NEWSLETTER'),
            KlarnaConsts::EXTRA_CHECKBOX_CREATE_USER_SIGN_UP =>
                Registry::getLang()->translateString('TCKLARNA_CREATE_USER_ACCOUNT_AND_SUBSCRIBE'),
        );

        return $selectValues;
    }

    /**
     * @return int
     */
    public function getActiveCheckbox()
    {
        return (int)KlarnaUtils::getShopConfVar('iKlarnaActiveCheckbox');
    }

    /**
     * @return array
     */
    public function getKlarnaValidationOptions()
    {
        $selectValues = array(
            KlarnaConsts::NO_VALIDATION            =>
                Registry::getLang()->translateString('TCKLARNA_NO_VALIDATION_NEEDED'),
            KlarnaConsts::VALIDATION_WITH_SUCCESS  =>
                Registry::getLang()->translateString('TCKLARNA_VALIDATION_IGNORE_TIMEOUTS_NEEDED'),
            KlarnaConsts::VALIDATION_WITH_NO_ERROR =>
                Registry::getLang()->translateString('TCKLARNA_SUCCESSFUL_VALIDATION_NEEDED'),
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
     */
    public function isGermanyActiveShopCountry()
    {
        /** @var \OxidEsales\Eshop\Application\Model\CountryList $activeCountries */
        $activeCountries = KlarnaUtils::getActiveShopCountries();
        foreach ($activeCountries as $oCountry) {
            if ($oCountry->oxcountry__oxisoalpha2->value == 'DE')
                return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isAustriaActiveShopCountry()
    {
        /** @var \OxidEsales\Eshop\Application\Model\CountryList $activeCountries */
        $activeCountries = KlarnaUtils::getActiveShopCountries();
        foreach ($activeCountries as $oCountry) {
            if ($oCountry->oxcountry__oxisoalpha2->value == 'AT')
                return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isGBActiveShopCountry()
    {
        /** @var \OxidEsales\Eshop\Application\Model\CountryList $activeCountries */
        $activeCountries = KlarnaUtils::getActiveShopCountries();
        foreach ($activeCountries as $oCountry) {
            if ($oCountry->oxcountry__oxisoalpha2->value == 'GB')
                return true;
        }

        return false;
    }

    /**
     *
     */
    public function save()
    {
        parent::save();

        if (KlarnaUtils::isKlarnaPaymentsEnabled()) {
            $oPayment = oxNew(Payment::class);
            $oPayment->setActiveKPMethods();
        }
    }
}