<?php

namespace TopConcepts\Klarna\Controllers;


use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaOrder;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Exception\KlarnaBasketTooLargeException;
use TopConcepts\Klarna\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Models\KlarnaUser;

use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsUrl;
use OxidEsales\Eshop\Core\UtilsView;

class KlarnaExpressController extends FrontendController
{
    /**
     * @var string
     */
    protected $_sThisTemplate = 'kl_klarna_checkout.tpl';

    /**
     * @var \TopConcepts\Klarna\Core\KlarnaOrder
     */
    protected $_oKlarnaOrder;

    /**
     * @var User|KlarnaUser
     */
    protected $_oUser;

    /**
     * @var bool
     */
    protected $blockIframeRender;

    /**
     * @var array
     */
    protected $_aOrderData;


    /** @var string country selected by the user in the popup */
    protected $selectedCountryISO;


    /** @var bool show select country popup to the user */
    protected $blShowPopup;

    /** @var Request */
    protected $_oRequest;


    /**
     *
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function init()
    {
        $oSession        = Registry::getSession();
        $oBasket         = $oSession->getBasket();
        $this->_oRequest = Registry::get(Request::class);
        $oUtils          = Registry::getUtils();

        /**
         * KCO is not enabled. redirect to legacy oxid checkout
         */
        if (KlarnaUtils::getShopConfVar('sKlarnaActiveMode') !== 'KCO') {
            $oUtils->redirect(Registry::getConfig()->getShopSecureHomeUrl() . 'cl=order', false, 302);
            return;
        }

        /**
         * Reset Klarna session if flag set by changing user address data in the User Controller earlier.
         */
        if (Registry::getSession()->getVariable('resetKlarnaSession') == 1) {
            KlarnaUtils::fullyResetKlarnaSession();
        }
        
        /**
         * A country has been selected from the country popup.
         */
        $this->selectedCountryISO = $this->_oRequest->getRequestEscapedParameter('selected-country');
        if ($this->selectedCountryISO) {
            /**
             * Remove delivery address on country change
             */
            Registry::getSession()->setVariable('blshowshipaddress', 0);
            $oSession->setVariable('sCountryISO', $this->selectedCountryISO);
            /**
             * If user logged in - save the new country choice.
             */
            if ($this->getUser()) {
                $oCountry                             = oxNew(Country::class);
                $sCountryId                           = $oCountry->getIdByCode($this->selectedCountryISO);
                $oCountry->load($sCountryId);
                $this->getUser()->oxuser__oxcountryid = new Field($sCountryId);
                $this->getUser()->oxuser__oxcountry   = new Field($oCountry->oxcountry__oxtitle->value);
                $this->getUser()->save();
            }
            /**
             * Restart klarna session on country change
             */
            if (KlarnaUtils::isCountryActiveInKlarnaCheckout($this->selectedCountryISO)) {
                $oSession->deleteVariable('klarna_checkout_order_id');  // force new session every country change
                $sUrl = Registry::getConfig()->getShopSecureHomeUrl() . 'cl=KlarnaExpress';
                $oUtils->redirect($sUrl, false, 302);
                /**
                 * Redirect to legacy oxid checkout if selected country is not a KCO country.
                 */
            } else {
                $this->redirectForNonKlarnaCountry($this->selectedCountryISO);
            }

            /**
             * Logged in user with a non KCO country attempting to render the klarna checkout.
             */
        } else if ($this->getUser() && $this->getUser()->getUserCountryISO2() && !KlarnaUtils::isCountryActiveInKlarnaCheckout($this->getUser()->getUserCountryISO2())) {
            /**
             * User is coming back from legacy oxid checkout wanting to change the country to one of KCO ones
             */
            if ($this->_oRequest->getRequestEscapedParameter('reset_klarna_country') == 1) {
                $oSession->setVariable('sCountryISO', KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry'));
                /**
                 * User is trying to access the klarna checkout for the first time and has to be redirected to legacy oxid checkout
                 */
            } else {
                $oSession->setVariable('sCountryISO', $this->getUser()->getUserCountryISO2());
                $this->redirectForNonKlarnaCountry($this->getUser()->getUserCountryISO2());
            }
            /**
             * Guest user attempting to render the klarna checkout when non KCO country is set to default.
             * Returning from legacy checkout for guest user in this scenario is done below where
             * request parameter reset_klarna_country is checked and $this->blockIframeRender is set.
             */
        } else if (!$oSession->getVariable('sCountryISO') &&
                   !KlarnaUtils::isCountryActiveInKlarnaCheckout(KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry')) &&
                   $this->_oRequest->getRequestEscapedParameter('reset_klarna_country') != 1
        ) {
            /**
             * Guest user is trying to access the klarna checkout for the first time and has to be redirected to legacy oxid checkout
             */
            $oSession->setVariable('sCountryISO', KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry'));
            $this->redirectForNonKlarnaCountry(KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry'));
        }

        /**
         * Default country is not KCO and we need the country popup without rendering the iframe.
         */
        if ($this->_oRequest->getRequestEscapedParameter('reset_klarna_country') == 1 /*&&
            !KlarnaUtils::isCountryActiveInKlarnaCheckout(KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry'))*/
        ) {
            $this->blockIframeRender = true;
        }

        $oBasket->setPayment('klarna_checkout');
        $oSession->setVariable('paymentid', 'klarna_checkout');

        parent::init();
    }

    /**
     * @return string
     */
    public function render()
    {
        $result   = parent::render();
        $oConfig  = $this->getConfig();
        $oUtils   = Registry::getUtils();
        $oSession = $this->getSession();
        $oBasket  = $oSession->getBasket();

        $blAlreadyRedirected = $this->_oRequest->getRequestEscapedParameter('sslredirect') == 'forced';

        if ($oConfig->getCurrentShopURL() != $oConfig->getSSLShopURL() && !$blAlreadyRedirected) {
            $sUrl = $oConfig->getShopSecureHomeUrl() . 'sslredirect=forced&cl=KlarnaExpress';
            $oUtils->redirect($sUrl, false, 302);
            return;
        }

        if ($this->_oUser = $this->getUser()) {
            $this->_oUser->kl_checkUserType();
        } else {
            $email        = $oSession->getVariable('klarna_checkout_user_email');
            $this->_oUser = KlarnaUtils::getFakeUser($email);
        }
        $this->blShowPopup = $this->showCountryPopup();
        $this->addTplParam("blShowPopUp", $this->blShowPopup);


        if ($this->blockIframeRender) {
            return $this->_sThisTemplate;
        }

        $this->addTplParam('blShowCountryReset', KlarnaUtils::isNonKlarnaCountryActive());

        try {
//            $oKlarnaOrder = new KlarnaOrder($oBasket, $this->_oUser);
            $oKlarnaOrder = $this->getKlarnaOrder($oBasket);
        } catch (KlarnaConfigException $e) {

            Registry::get(UtilsView::class)->addErrorToDisplay($e);
            KlarnaUtils::fullyResetKlarnaSession();

            return $this->_sThisTemplate;

        } catch (KlarnaBasketTooLargeException $e) {
            Registry::get(UtilsView::class)->addErrorToDisplay($e);

            $this->redirectForNonKlarnaCountry(Registry::getSession()->getVariable('sCountryISO'), false);
        }

        if ($oSession->getVariable('wrong_merchant_urls')) {

            $oSession->deleteVariable('wrong_merchant_urls');

            Registry::get(UtilsView::class)->addErrorToDisplay('KLARNA_WRONG_URLS_CONFIG', false, true);

            $this->addTplParam('confError', true);

            return $this->_sThisTemplate;
        }
        $orderData = $oKlarnaOrder->getOrderData();

        if (!KlarnaUtils::isCountryActiveInKlarnaCheckout(strtoupper($orderData['purchase_country']))) {
            $sUrl = Registry::getConfig()->getShopHomeURL() . 'cl=user';
            Registry::getUtils()->redirect($sUrl, false, 302);
        }

        try {
            $this->getKlarnaClient()
                ->initOrder($oKlarnaOrder)
                ->createOrUpdateOrder();

        } catch (KlarnaWrongCredentialsException $oEx) {
            KlarnaUtils::fullyResetKlarnaSession();
            Registry::get(UtilsView::class)->addErrorToDisplay(
                Registry::getLang()->translateString('KLARNA_UNAUTHORIZED_REQUEST', null, true));
            Registry::getUtils()->redirect(Registry::getConfig()->getShopHomeURL() . 'cl=start', true, 301);
        } catch (StandardException $oEx) {
            $oEx->debugOut();
            KlarnaUtils::fullyResetKlarnaSession();
            Registry::getUtils()->redirect(Registry::getConfig()->getShopSecureHomeURL() . 'cl=KlarnaExpress', false, 302);
        }

        $countryISO = $this->getKlarnaClient()->getLoadedPurchaseCountry();

        if (!KlarnaUtils::is_ajax()) {
            Registry::getSession()->setVariable('sCountryISO', $countryISO);
            $oCountry = oxNew(Country::class);
            $oCountry->load($oCountry->getIdByCode($countryISO));
            $this->addTplParam("sCountryName", $oCountry->oxcountry__oxtitle->value);

            $this->addTplParam("sPurchaseCountry", $countryISO);
            $this->addTplParam("sKlarnaIframe", $this->getKlarnaClient()->getHtmlSnippet());
            $this->addTplParam("sCurrentUrl", Registry::get(UtilsUrl::class)->getCurrentUrl());
            $this->addTplParam("shippingAddressAllowed", KlarnaUtils::getShopConfVar('blKlarnaAllowSeparateDeliveryAddress'));
        }

        return $result;
    }

    /**
     *
     * @return bool
     */
    protected function showCountryPopup()
    {
        $sCountryISO = $this->getSession()->getVariable('sCountryISO');
        $resetKlarnaCountry = $this->_oRequest->getRequestEscapedParameter('reset_klarna_country');

        if($resetKlarnaCountry){
            return true;
        }

        if(!KlarnaUtils::isNonKlarnaCountryActive()){
            return false;
        }

        if($this->isKLUserLoggedIn()){
            return false;
        }

        if($sCountryISO){
            return false;
        }

        return true;
    }

    /**
     *
     * @return bool
     * @throws \oxSystemComponentException
     */
    protected function isKLUserLoggedIn()
    {
        $oUser = $this->getUser();

        if($oUser && $oUser->kl_getType() === KlarnaUser::LOGGED_IN){
            return true;
        }

        return false;
    }

    /**
     * @return KlarnaCheckoutClient | \TopConcepts\Klarna\Core\KlarnaClientBase
     */
    public function getKlarnaClient()
    {
        return KlarnaCheckoutClient::getInstance();
    }

    /**
     * Get addresses saved by the user if any exist.
     * @return array|bool
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function getFormattedUserAddresses()
    {
        if ($this->_oUser->isFake()) {
            return false;
        }

        $db      = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sql     = 'SELECT oxid, oxfname, oxlname, oxstreet, oxstreetnr, oxzip, oxcity FROM oxaddress WHERE oxuserid=?';
        $results = $db->getAll($sql, array($this->_oUser->getId()));

        if (!is_array($results) || empty($results)) {
            return false;
        }

        $formattedResults = array();
        foreach ($results as $data) {
            $formattedResults[$data['oxid']] =
                $data['oxfname'] . ' ' .
                $data['oxlname'] . ', ' .
                $data['oxstreet'] . ' ' .
                $data['oxstreetnr'] . ', ' .
                $data['oxzip'] . ' ' .
                $data['oxcity'];
        }

        return $formattedResults;
    }

    /**
     *
     */
    public function getKlarnaModalFlagCountries()
    {
        $flagCountries = KlarnaConsts::getKlarnaPopUpFlagCountries();

        $result = array();
        foreach ($flagCountries as $isoCode) {
            $country = oxNew(Country::class);
            $id      = $country->getIdByCode($isoCode);
            $country->load($id);
            if ($country->oxcountry__oxactive->value == 1) {
                $result[] = $country;
            }
        }

        return $result;
    }

    /**
     *
     */
    public function getKlarnaModalOtherCountries()
    {
        $flagCountries               = KlarnaConsts::getKlarnaPopUpFlagCountries();
        $activeKlarnaGlobalCountries = KlarnaUtils::getKlarnaGlobalActiveShopCountries();

        $result = array();
        foreach ($activeKlarnaGlobalCountries as $country) {
            if (in_array($country->oxcountry__oxisoalpha2->value, $flagCountries)) {
                continue;
            }
            $result[] = $country;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isUserLoggedIn()
    {
        return $this->_oUser->kl_getType() === KlarnaUser::LOGGED_IN;
    }

    /**
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths        = array();
        $aPath         = array();
        $iBaseLanguage = Registry::getLang()->getBaseLanguage();

        $aPath['title'] = Registry::getLang()->translateString('KL_CHECKOUT', $iBaseLanguage, false);
        $aPath['link']  = $this->getLink();
        $aPaths[]       = $aPath;

        return $aPaths;
    }

    /**
     *
     */
    public function getActiveShopCountries()
    {
        $list = oxNew(CountryList::class);
        $list->loadActiveCountries();

        return $list;
    }

    /**
     *
     */
    public function cleanUpSession()
    {
        $oSession = Registry::getSession();
        $oSession->deleteVariable('sCountryISO');
        $oSession->deleteVariable('klarna_checkout_order_id');
        $oSession->deleteVariable('klarna_checkout_user_email');
    }

    /**
     *
     * @param $sCountryISO
     */
    protected function redirectForNonKlarnaCountry($sCountryISO, $blShippingOptionsSet = true)
    {
        if ($blShippingOptionsSet === false) {
            $sUrl = Registry::getConfig()->getShopSecureHomeUrl() . 'cl=basket';
        } else {
            $sUrl = Registry::getConfig()->getShopSecureHomeUrl() . 'cl=user&non_kco_global_country=' . $sCountryISO;
        }
        Registry::getUtils()->redirect($sUrl, false, 302);
    }

    /**
     *
     */
    public function setKlarnaDeliveryAddress()
    {
        $oxidAddress = $this->_oRequest->getRequestEscapedParameter('klarna_address_id');
        Registry::getSession()->setVariable('deladrid', $oxidAddress);
        Registry::getSession()->setVariable('blshowshipaddress', 1);
        Registry::getSession()->deleteVariable('klarna_checkout_order_id');
    }

    /**
     *
     * @param $oBasket
     * @return KlarnaOrder
     */
    protected function getKlarnaOrder($oBasket){

        return new KlarnaOrder($oBasket, $this->_oUser);
    }
}
