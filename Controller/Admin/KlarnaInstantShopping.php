<?php

namespace TopConcepts\Klarna\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\InstantShopping\Button;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;
use TopConcepts\Klarna\Core\KlarnaConsts;

class KlarnaInstantShopping extends KlarnaBaseConfig {

    protected $_sThisTemplate = 'tcklarna_instant_shipping.tpl';

    /** @var HttpClient */
    protected $instantShoppingClient;

    protected $buttonStyleOptions = [
        'variation' => ['label' => 'TCKLARNA_IS_BUTTON_SETTINGS_VARIATION', 'values' => ['klarna', 'light', 'dark']],
        'tagline' => ['label' => 'TCKLARNA_IS_BUTTON_SETTINGS_TAGLINE', 'values' => ['light', 'dark']],
        'type' => ['label' => 'TCKLARNA_IS_BUTTON_SETTINGS_TYPE', 'values' => ['pay', 'express', 'buy']]
    ];

    protected $buttonPlacement = ['details', 'basket'];

    protected $buttonSettings = [
        'allow_separate_shipping_address',
        'date_of_birth_mandatory',
        'national_identification_number_mandatory',
        'phone_mandatory'
    ];

    protected function isReplaceButtonRequest() {
        return $this->_oRequest->getRequestParameter('replaceButton') === '1';
    }

    public function init() {
        parent::init();
        $this->instantShoppingClient = HttpClient::getInstance();
    }

    public function render() {
        // force shopid as parameter
        // Pass shop OXID so that shop object could be loaded
        $oConfig = Registry::getConfig();
        $sShopOXID = Registry::getConfig()->getShopId();
        $this->setEditObjectId($sShopOXID);
        parent::render();


        $instantShippingEnabled = $oConfig->getConfigParam('blKlarnaInstantShippingEnabled');
        if ($instantShippingEnabled) {
            $buttonKey = $oConfig->getConfigParam('strKlarnaISButtonKey');
            if (empty($buttonKey)) {
                $this->generateAndSaveButtonKey();
            }
        }
        if ($this->isReplaceButtonRequest()) {
            $this->generateAndSaveButtonKey();
        }
        $this->addTplParam('buttonStyleOptions', $this->buttonStyleOptions);
        $this->addTplParam('buttonPlacement', $this->buttonPlacement);
        $this->addTplParam('buttonSettings', $this->buttonSettings);

        return $this->_sThisTemplate;
    }

    protected function generateAndSaveButtonKey() {

        $oConfig = Registry::getConfig();
        try {
            $buttonData = $this->instantShoppingClient->createButton(
                $this->getButtonRequestData()
            );
        } catch (KlarnaClientException $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception);
            return;
        }

        $oConfig->saveShopConfVar(
            'strs',
            'strKlarnaISButtonKey',
            $buttonData['button_key'],
            $this->getEditObjectId(),
            $this->_getModuleForConfigVars()
        );
    }

    protected function getButtonRequestData() {
        $oConfig = Registry::getConfig();
        $defaultShopCountry = $oConfig->getConfigParam('sKlarnaDefaultCountry');
        $currencies = KlarnaConsts::getCountry2CurrencyArray();
        $currency = isset($currencies[$defaultShopCountry]) ? $currencies[$defaultShopCountry] : 'EUR';
        $boolFilter = function ($i) { return (bool)$i; };

        $button = oxNew(Button::class);
        return [
            'merchant_urls' => $button->getMerchantUrls(),
            'purchase_country' => $defaultShopCountry,
            'purchase_currency' => $currency,
            'options' => array_map($boolFilter, $oConfig->getConfigParam('aarrKlarnaISButtonSettings')) ?: [],
            'styling' => ['theme' => $oConfig->getConfigParam('aarrKlarnaISButtonStyle')] ?: []
        ];
    }
}