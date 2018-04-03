<?php

namespace TopConcepts\Klarna\Core;


use TopConcepts\Klarna\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Models\KlarnaEMD;
use TopConcepts\Klarna\Models\KlarnaUser;
use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\EshopCommunity\Application\Model\PaymentList;

class KlarnaOrder extends BaseModel
{
    /**
     * @var array data to post to Klarna
     */
    protected $_aOrderData;

    /**
     *
     * @var User|KlarnaUser
     */
    protected $_oUser;

    /**
     * @var PaymentController
     */
    protected $_oPayment;

    /**
     * @var string
     */
    protected $_selectedShippingSetId;

    /**
     * List of available shipping methods for Klarna Checkout
     *
     * @var array
     */
    protected $_klarnaShippingSets;

    /**
     * @return array
     */
    public function getOrderData()
    {
        return $this->_aOrderData;
    }

    /**
     * KlarnaOrder constructor.
     * @param Basket $oBasket
     * @param User $oUser
     * @throws \oxSystemComponentException
     */
    public function __construct(Basket $oBasket, User $oUser)
    {
        parent::__construct();

        $this->_oUser = $oUser;

        $sSSLShopURL       = Registry::getConfig()->getSslShopUrl();
        $sCountryISO       = $this->_oUser->resolveCountry();
        $currencyName      = $oBasket->getBasketCurrency()->name;
        $sLocale           = $this->_oUser->resolveLocale($sCountryISO);
        $lang              = strtoupper(Registry::getLang()->getLanguageAbbr());
        $klarnaUserData    = $this->_oUser->getKlarnaData();
        $cancellationTerms = KlarnaUtils::getShopConfVar('sKlarnaCancellationRightsURI_' . $lang);
        $terms             = KlarnaUtils::getShopConfVar('sKlarnaTermsConditionsURI_' . $lang);

        if (empty($cancellationTerms) || empty($terms)) {
            Registry::getSession()->setVariable('wrong_merchant_urls', true);

            return false;
        }

        $sessionId         = Registry::getSession()->getId();
        $this->_aOrderData = array(
            "purchase_country"  => $sCountryISO,
            "purchase_currency" => $currencyName,
            "locale"            => $sLocale,
            "merchant_urls"     => array(
                "terms"        =>
                    $terms,
                "checkout"     =>
                    $sSSLShopURL . "?cl=KlarnaExpress",
                "confirmation" =>
                    $sSSLShopURL . "?cl=order&fnc=execute&klarna_order_id={checkout.order.id}",
                "push"         =>
                    $sSSLShopURL . "?cl=KlarnaAcknowledge&klarna_order_id={checkout.order.id}",

            ),
        );

        if ($this->isValidationEnabled()) {
            $this->_aOrderData["merchant_urls"]["validation"] =
                $sSSLShopURL . "?cl=KlarnaValidate&s=$sessionId&klarna_order_id={checkout.order.id}";
        }

        if (!empty($cancellationTerms)) {
            $this->_aOrderData["merchant_urls"]["cancellation_terms"] = $cancellationTerms;
        }

        $this->_aOrderData = array_merge(
            $this->_aOrderData,
            $klarnaUserData
        );

        //clean up in case of returning to the iframe with an open order
        Registry::getSession()->deleteVariable('externalCheckout');

        // merge with order_lines and totals
        $this->_aOrderData = array_merge(
            $this->_aOrderData,
            $oBasket->getKlarnaOrderLines()
        );

        // skip all other data if there are no items in the basket
        if (!empty($this->_aOrderData['order_lines'])) {

            $this->_aOrderData['shipping_countries'] = array_values($this->getKlarnaCountryList());

            $this->_aOrderData['shipping_options'] = $this->kl_getAllSets($oBasket);

            $externalMethods = $this->getExternalPaymentMethods($oBasket, $this->_oUser);

            $this->_aOrderData['external_payment_methods'] = $externalMethods['payments'];
            $this->_aOrderData['external_checkouts']       = $externalMethods['checkouts'];

            $this->addOptions();

            if (!$this->isAutofocusEnabled()) {
                $this->_aOrderData['gui']['options'] = array(
                    'disable_autofocus',
                );
            }

            $this->setAttachmentsData();
        }
    }

    /**
     * Template variable getter. Returns all delivery sets
     *
     * @param Basket $oBasket
     * @return mixed :
     */
    public function kl_getAllSets(Basket $oBasket)
    {
        if (is_null($this->_klarnaShippingSets)) {
            $this->_klarnaShippingSets = $this->getSupportedShippingMethods($oBasket);
        }

        return $this->_klarnaShippingSets;
    }


    /**
     * Get shipping methods that support Klarna Checkout payment
     * @param Basket $oBasket
     * @return array
     * @throws KlarnaConfigException
     * @throws \oxSystemComponentException
     */
    protected function getSupportedShippingMethods(Basket $oBasket)
    {
        $allSets  = $this->_getPayment()->getCheckoutShippingSets($this->_oUser);
        $currency = Registry::getConfig()->getActShopCurrencyObject();

        $methods = array();
        if (!is_array($allSets)) {
            return $methods;
        }

        $this->_selectedShippingSetId = $oBasket->getShippingId();

        $shippingOptions = array();

        foreach ($allSets as $shippingId => $shippingMethod) {
            $oBasket->setShipping($shippingId);
            $oPrice      = $oBasket->kl_calculateDeliveryCost();
            $basketPrice = $oBasket->getPriceForPayment() / $currency->rate;
            if ($this->doesShippingMethodSupportKCO($shippingId, $basketPrice)) {
                $method = clone $shippingMethod;

                $price             = KlarnaUtils::parseFloatAsInt($oPrice->getBruttoPrice() * 100);
                $tax_rate          = KlarnaUtils::parseFloatAsInt($oPrice->getVat() * 100);
                $tax_amount        = KlarnaUtils::parseFloatAsInt($price - round($price / ($tax_rate / 10000 + 1), 0));
                $shippingOptions[] = array(
                    "id"          => $shippingId,
                    //                    "id"          => 'SRV_DELIVERY',
                    "name"        => html_entity_decode($method->oxdeliveryset__oxtitle->value, ENT_QUOTES),
                    "description" => null,
                    "promo"       => null,
                    "tax_amount"  => $tax_amount,
                    'price'       => $price,
                    'tax_rate'    => $tax_rate,
                    'preselected' => $shippingId === $this->_selectedShippingSetId ? true : false,
                );
            }
        }

        // set basket back to selected shipping option
        $oBasket->setShipping($this->_selectedShippingSetId);

        if (empty($shippingOptions)) {

            $oCountry = oxNew(Country::class);
            $oCountry->load($this->_oUser->getActiveCountry());

            throw new KlarnaConfigException(sprintf(
                Registry::getLang()->translateString('KL_ERROR_NO_SHIPPING_METHODS_SET_UP'),
                $oCountry->oxcountry__oxtitle->value
            ));
        }

        return empty($shippingOptions) ? null : $shippingOptions;
    }

    /**
     * Creates new payment object
     *
     * @return null|object
     */
    protected function _getPayment()
    {
        if ($this->_oPayment === null) {
            $this->_oPayment = oxNew(PaymentController::class);
        }

        return $this->_oPayment;
    }

    /**
     * @param string $shippingId
     * @param float $basketPrice
     * @return bool
     */
    protected function doesShippingMethodSupportKCO($shippingId, $basketPrice)
    {
        $oPayList    = Registry::get(PaymentList::class);
        $paymentList = $oPayList->getPaymentList($shippingId, $basketPrice, $this->_oUser);

        return count($paymentList) && in_array('klarna_checkout', array_keys($paymentList));
    }


    /**
     *
     */
    public function getKlarnaCountryList()
    {
        $oCountryList = oxNew(CountryList::class);
        $oCountryList->loadActiveKlarnaCheckoutCountries();

        $aCountriesISO = array();
        foreach ($oCountryList as $oCountry) {
            $aCountriesISO[$oCountry->oxcountry__oxid->value] = $oCountry->oxcountry__oxisoalpha2->value;
        }

        return $aCountriesISO;
    }

    /**
     * Gets an array of all countries the given payment type can be used in.
     *
     * @param Payment $oPayment
     * @param $aActiveCountries
     * @return array
     */
    public function getKlarnaCountryListByPayment(Payment $oPayment, $aActiveCountries)
    {
        $result            = array();
        $aPaymentCountries = $oPayment->getCountries();
        foreach ($aPaymentCountries as $oxid) {
            if (isset($aActiveCountries[$oxid]))
                $result[] = $aActiveCountries[$oxid];
        }

        return empty($result) ? array_values($aActiveCountries) : $result;
    }

    /**
     * @param Basket $oBasket
     * @param User $oUser
     * @return array
     */
    public function getExternalPaymentMethods(Basket $oBasket, User $oUser)
    {
        $oPayList     = Registry::get(PaymentList::class);
        $dBasketPrice = $oBasket->getPriceForPayment();

        $externalPaymentMethods  = array();
        $externalCheckoutMethods = array();

        $paymentList = $oPayList->getPaymentList($oBasket->getShippingId(), $dBasketPrice, $oUser);

        foreach ($paymentList as $paymentId => $oPayment) {
            $oPayment->calculate($oBasket);
            $aCountryISO = $this->getKlarnaCountryListByPayment($oPayment, $this->getKlarnaCountryList());
            $oPrice      = $oPayment->getPrice();
            if ($oPayment->oxpayments__klexternalpayment->value) {

                $requestParams = '';
                if ($paymentId === 'oxidpaypal') {
                    $requestParams = '&displayCartInPayPal=1';
                }

                $externalPaymentMethods[] = array(
                    'name'         => $oPayment->oxpayments__klexternalname->value,
                    'redirect_url' => Registry::getConfig()->getSslShopUrl() .
                                      'index.php?cl=order&fnc=klarnaExternalPayment&payment_id=' . $paymentId . $requestParams,
                    'image_url'    => $this->resolveImageUrl($oPayment),
                    'fee'          => KlarnaUtils::parseFloatAsInt($oPrice->getBruttoPrice() * 100),
                    'description'  => KlarnaUtils::stripHtmlTags($oPayment->oxpayments__oxlongdesc->getRawValue()),
                    'countries'    => $aCountryISO,
                );
            }

            if ($oPayment->oxpayments__klexternalcheckout->value) {
                $requestParams             = '&externalCheckout=1';
                $externalCheckoutMethods[] = array(
                    'name'         => $oPayment->oxpayments__klexternalname->value,
                    'redirect_url' => Registry::getConfig()->getSslShopUrl() .
                                      'index.php?cl=order&fnc=klarnaExternalPayment&payment_id=' . $paymentId . $requestParams,
                    'image_url'    => $this->resolveImageUrl($oPayment, true),
                    'fee'          => KlarnaUtils::parseFloatAsInt($oPrice->getBruttoPrice() * 100),
                    'description'  => KlarnaUtils::stripHtmlTags($oPayment->oxpayments__oxlongdesc->getRawValue()),
                    'countries'    => $aCountryISO,
                );
            }
        }

        return array('payments' => $externalPaymentMethods, 'checkouts' => $externalCheckoutMethods);
    }

    /**
     *
     */
    public function addOptions()
    {
        $options = array();

        $options['additional_checkbox']               = $this->getAdditionalCheckboxData();
        $options['allow_separate_shipping_address']   = $this->isSeparateDeliveryAddressAllowed();
        $options['phone_mandatory']                   = $this->isPhoneMandatory();
        $options['date_of_birth_mandatory']           = $this->isBirthDateMandatory();
        $options['require_validate_callback_success'] = $this->isValidateCallbackSuccessRequired();
        $options['shipping_details']                  =
            $this->getShippingDetailsMsg();

//        $sCountryISO = strtoupper(Registry::getSession()->getVariable('sCountryISO'));
//        if ($sCountryISO == 'GB') {
//            $options['title_mandatory'] = $this->isSalutationMandatory();
//        }

        /*** add design settings ***/
        if (!$designSettings = KlarnaUtils::getShopConfVar('aKlarnaDesign')) {
            $designSettings = array();
        }
        $options = array_merge($options, $designSettings);

        $this->_aOrderData['options'] = $options;
    }

    /**
     * @return bool
     */
    public function isAutofocusEnabled()
    {
        return KlarnaUtils::getShopConfVar('blKlarnaEnableAutofocus');
    }

    /**
     * @return string
     */
    public function getShippingDetailsMsg()
    {
        $langTag = strtoupper(Registry::getLang()->getLanguageAbbr());

        return KlarnaUtils::getShopConfVar('sKlarnaShippingDetails_' . $langTag);
    }

    /**
     * @return int
     * @throws \oxSystemComponentException
     */
    protected function getAdditionalCheckbox()
    {
        $iActiveCheckbox = KlarnaUtils::getShopConfVar('iKlarnaActiveCheckbox');

        if (!$this->_oUser->isFake() || $this->_oUser->kl_getType() === KlarnaUser::REGISTERED) {
            if ($this->_oUser->getNewsSubscription()->getOptInStatus() == 1) {

                return KlarnaConsts::EXTRA_CHECKBOX_NONE;
            }

            if ($iActiveCheckbox > KlarnaConsts::EXTRA_CHECKBOX_CREATE_USER) {

                return KlarnaConsts::EXTRA_CHECKBOX_SIGN_UP;
            } else {

                return KlarnaConsts::EXTRA_CHECKBOX_NONE;
            }
        }

        return (int)$iActiveCheckbox;
    }

    /**
     *
     * @throws \oxSystemComponentException
     */
    protected function setAttachmentsData()
    {
        if (!$this->_oUser->isFake()) {
            /** @var Klarna_EMD $klarnaEmd */
            $klarnaEmd = new KlarnaEMD;
            $emd       = $klarnaEmd->getAttachments($this->_oUser);

            if (!empty($emd)) {
                $this->_aOrderData['attachment'] = array(
                    'content_type' => 'application/vnd.klarna.internal.emd-v2+json',
                    'body'         => json_encode($emd),
                );
            }
        }
    }

    /**
     * @return mixed
     */
    protected function isSeparateDeliveryAddressAllowed()
    {
        return KlarnaUtils::getShopConfVar('blKlarnaAllowSeparateDeliveryAddress');
    }

//    /**
//     * @return mixed
//     */
//    protected function isSalutationMandatory()
//    {
//        return KlarnaUtils::getShopConfVar('blKlarnaSalutationMandatory');
//    }

    /**
     * Check if user already has an account and if he's subscribed to the newsletter
     * Don't add the extra checkbox if not needed.
     */
    protected function getAdditionalCheckboxData()
    {
        $activeCheckbox = $this->getAdditionalCheckbox();

        switch ($activeCheckbox) {
            case 0:
                return null;
                break;
            case 1:
                return array(
                    'text'     => Registry::getLang()->translateString('KL_CREATE_USER_ACCOUNT'),
                    'checked'  => false,
                    'required' => false,
                );
                break;
            case 2:
                return array(
                    'text'     => Registry::getLang()->translateString('KL_SUBSCRIBE_TO_NEWSLETTER'),
                    'checked'  => false,
                    'required' => false,
                );
                break;
            case 3:
                return array(
                    'text'     => Registry::getLang()->translateString('KL_CREATE_USER_ACCOUNT_AND_SUBSCRIBE'),
                    'checked'  => false,
                    'required' => false,
                );
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * @return bool
     */
    protected function isPhoneMandatory()
    {
        return KlarnaUtils::getShopConfVar('blKlarnaMandatoryPhone');
    }

    /**
     * @return bool
     */
    protected function isBirthDateMandatory()
    {
        return KlarnaUtils::getShopConfVar('blKlarnaMandatoryBirthDate');
    }

    /**
     * @return bool
     */
    protected function isValidateCallbackSuccessRequired()
    {
        return KlarnaUtils::getShopConfVar('iKlarnaValidation') == 2;
    }

    /**
     * @return bool
     */
    protected function isValidationEnabled()
    {
        return KlarnaUtils::getShopConfVar('iKlarnaValidation') != 0;
    }

    /**
     * @param $oPayment
     * @param bool $checkoutImgUrl
     * @return mixed
     */
    protected function resolveImageUrl($oPayment, $checkoutImgUrl = false)
    {
        if ($checkoutImgUrl) {
            $url = $oPayment->oxpayments__klcheckoutimageurl->value;
        } else {
            $url = $oPayment->oxpayments__klpaymentimageurl->value;
        }

        $result = preg_replace('/http:/', 'https:', $url);

        return $result ?: null;
    }
}