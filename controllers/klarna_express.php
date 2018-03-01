<?php

class klarna_express extends oxUBase
{
    /**
     * @var string
     */
    protected $_sThisTemplate = 'kl_klarna_checkout.tpl';

    /**
     * @var KlarnaOrder
     */
    protected $_oKlarnaOrder;

    /**
     * @var klarna_oxuser
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

    /**
     *
     * @throws oxSystemComponentException
     * @throws Exception
     */
    public function init()
    {

        $oSession = oxRegistry::getSession();
        $oBasket  = $oSession->getBasket();
        $oConfig  = oxRegistry::getConfig();
        $oUtils   = oxRegistry::getUtils();


        /**
         * KCO is not enabled. redirect to legacy oxid checkout
         */
        if (KlarnaUtils::getShopConfVar('sKlarnaActiveMode') !== 'KCO') {
            $oUtils->redirect(oxRegistry::getConfig()->getShopSecureHomeUrl() . 'cl=order', false, 302);
        }
        /**
         * A country has been selected from the country popup.
         */
        $this->selectedCountryISO = $oConfig->getRequestParameter('selected-country');
        if ($this->selectedCountryISO) {
            /**
             * Remove delivery address on country change
             */
            oxRegistry::getSession()->setVariable('blshowshipaddress', 0);

            $oSession->setVariable('sCountryISO', $this->selectedCountryISO);
            /**
             * If user logged in - save the new country choice.
             */
            if ($this->getUser()) {
                $oCountry                             = oxNew('oxCountry');
                $sCountryId                           = $oCountry->getIdByCode($this->selectedCountryISO);
                $this->getUser()->oxuser__oxcountryid = new oxField($sCountryId);
                $this->getUser()->oxuser__oxcountry   = new oxField($oCountry->oxcountry__oxtitle->value);
                $this->getUser()->save();
            }
            /**
             * Restart klarna session on country change
             */
            if (KlarnaUtils::isCountryActiveInKlarnaCheckout($this->selectedCountryISO)) {
                $oSession->deleteVariable('klarna_checkout_order_id');  // force new session every country change
                $sUrl = oxRegistry::getConfig()->getShopSecureHomeUrl() . 'cl=klarna_express';
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
        } else if ($this->getUser() && !KlarnaUtils::isCountryActiveInKlarnaCheckout($this->getUser()->getUserCountryISO2())) {
            /**
             * User is coming back from legacy oxid checkout wanting to change the country to one of KCO ones
             */
            if ($oConfig->getRequestParameter('reset_klarna_country') == 1) {
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
                   $oConfig->getRequestParameter('reset_klarna_country') != 1
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
        if ($oConfig->getRequestParameter('reset_klarna_country') == 1 /*&&
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
     * @throws oxSystemComponentException
     * @throws oxConnectionException
     * @throws oxException
     */
    public function render()
    {
        $result   = parent::render();
        $oConfig  = oxRegistry::getConfig();
        $oSession = oxRegistry::getSession();
        $oBasket  = $oSession->getBasket();

        $blAlreadyRedirected = oxRegistry::getConfig()->getRequestParameter('sslredirect') == 'forced';

        if ($oConfig->getCurrentShopURL() != $oConfig->getSSLShopURL() && !$blAlreadyRedirected) {
            $sUrl = oxRegistry::getConfig()->getShopSecureHomeUrl() . 'sslredirect=forced&cl=klarna_express';
            oxRegistry::getUtils()->redirect($sUrl, false, 302);
        }


        if ($this->_oUser = $this->getUser()) {
            if ($this->getViewConfig()->isUserLoggedIn()) {
                $this->_oUser->kl_setType(Klarna_oxUser::LOGGED_IN);
            } else {
                $this->_oUser->kl_setType(Klarna_oxUser::NOT_REGISTERED);
            }
        } else {
            $email        = $oSession->getVariable('klarna_checkout_user_email');
            $this->_oUser = KlarnaUtils::getFakeUser($email);
        }

        $this->blShowPopup = KlarnaUtils::isNonKlarnaCountryActive() &&
                             ($this->getUser()->kl_getType() !== klarna_oxuser::LOGGED_IN &&
                              (!$oSession->getVariable('sCountryISO') ||
                               $oConfig->getRequestParameter('reset_klarna_country') == 1));
        $this->addTplParam("blShowPopUp", $this->blShowPopup);

        if ($this->blockIframeRender) {
            return $this->_sThisTemplate;
        }

        $this->addTplParam('blShowCountryReset', KlarnaUtils::isNonKlarnaCountryActive());


        try {
            $oKlarnaOrder = oxNew('KlarnaOrder', $oBasket, $this->_oUser);
        } catch (KlarnaConfigException $e) {
            oxRegistry::get("oxUtilsView")->addErrorToDisplay($e);
            KlarnaUtils::fullyResetKlarnaSession();

            return $this->_sThisTemplate;
//            $this->redirectForNonKlarnaCountry(oxRegistry::getSession()->getVariable('sCountryISO'), false);
//            $this->addTplParam('confError', true);
//            oxRegistry::getUtils()->redirect(oxRegistry::getConfig()->getShopHomeURL() . 'cl=start', true, 302);
        } catch (KlarnaBasketTooLargeException $e) {
            oxRegistry::get("oxUtilsView")->addErrorToDisplay($e);

            $this->redirectForNonKlarnaCountry(oxRegistry::getSession()->getVariable('sCountryISO'), false);
        }

        if ($oSession->getVariable('wrong_merchant_urls')) {
            $oSession->deleteVariable('wrong_merchant_urls');
            oxRegistry::get("oxUtilsView")->addErrorToDisplay('KLARNA_WRONG_URLS_CONFIG', false, true);
            $this->addTplParam('confError', true);

            return $this->_sThisTemplate;
        }
        $orderData = $oKlarnaOrder->getOrderData();

        if (!KlarnaUtils::isCountryActiveInKlarnaCheckout(strtoupper($orderData['purchase_country']))) {
            $sUrl = oxRegistry::getConfig()->getShopHomeURL() . 'cl=user';
            oxRegistry::getUtils()->redirect($sUrl, false, 302);
        }

        try {
            $this->getKlarnaClient()
                ->initOrder($oKlarnaOrder)
                ->createOrUpdateOrder();

        } catch (KlarnaWrongCredentialsException $oEx) {
            KlarnaUtils::fullyResetKlarnaSession();
            oxRegistry::get("oxUtilsView")->addErrorToDisplay(
                oxRegistry::getLang()->translateString('KLARNA_UNAUTHORIZED_REQUEST', null, true));
            oxRegistry::getUtils()->redirect(oxRegistry::getConfig()->getShopHomeURL() . 'cl=start', true, 301);
        } catch (oxException $oEx) {
            $oEx->debugOut();
            KlarnaUtils::fullyResetKlarnaSession();
            oxRegistry::getUtils()->redirect(oxRegistry::getConfig()->getShopSecureHomeURL() . 'cl=klarna_express', false, 302);
        }

        $countryISO = $this->getKlarnaClient()->getLoadedPurchaseCountry();

        if (!KlarnaUtils::is_ajax()) {
            oxRegistry::getSession()->setVariable('sCountryISO', $countryISO);
            $oCountry = oxNew('oxcountry');
            $oCountry->load($oCountry->getIdByCode($countryISO));
            $this->addTplParam("sCountryName", $oCountry->oxcountry__oxtitle->value);

            $this->addTplParam("sPurchaseCountry", $countryISO);
            $this->addTplParam("sKlarnaIframe", $this->getKlarnaClient()->getHtmlSnippet());
            $this->addTplParam("sCurrentUrl", oxRegistry::get('oxUtilsUrl')->getCurrentUrl());
            $this->addTplParam("shippingAddressAllowed", KlarnaUtils::getShopConfVar('blKlarnaAllowSeparateDeliveryAddress'));
        }

        return $result;
    }

    /**
     * @return KlarnaCheckoutClient|KlarnaClientBase
     */
    public function getKlarnaClient()
    {
        return KlarnaCheckoutClient::getInstance();
    }

    /**
     * Get addresses saved by the user if any exist.
     * @throws oxConnectionException
     */
    public function getFormattedUserAddresses()
    {
        if ($this->_oUser->isFake()) {
            return false;
        }

        $db = oxdb::getDb(oxDb::FETCH_MODE_ASSOC);

        $sql = 'SELECT oxid, oxfname, oxlname, oxstreet, oxstreetnr, oxzip, oxcity FROM oxaddress WHERE oxuserid=?';

        $result = $db->getAssoc($sql, array($this->_oUser->getId()));

        if (!is_array($result) || empty($result)) {
            return false;
        }

        foreach ($result as $oxid => $data) {
            $formattedResult[$oxid] =
                $data['oxfname'] . ' ' .
                $data['oxlname'] . ', ' .
                $data['oxstreet'] . ' ' .
                $data['oxstreetnr'] . ', ' .
                $data['oxzip'] . ' ' .
                $data['oxcity'];
        }

        return $formattedResult;
    }

    /**
     *
     * @throws oxSystemComponentException
     */
    public function getKlarnaModalFlagCountries()
    {
        $flagCountries = KlarnaConsts::getKlarnaPopUpFlagCountries();

        $result = array();
        foreach ($flagCountries as $isoCode) {
            $country = oxNew('oxcountry');
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
     * @throws oxSystemComponentException
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
        return $this->_oUser->kl_getType() === Klarna_oxUser::LOGGED_IN;
    }

    /**
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths        = array();
        $aPath         = array();
        $iBaseLanguage = oxRegistry::getLang()->getBaseLanguage();

        $aPath['title'] = oxRegistry::getLang()->translateString('KL_CHECKOUT', $iBaseLanguage, false);
        $aPath['link']  = $this->getLink();
        $aPaths[]       = $aPath;

        return $aPaths;
    }

    /**
     * @throws oxSystemComponentException
     */
    public function getActiveShopCountries()
    {
        $list = oxNew('oxCountryList');
        $list->loadActiveCountries();

        return $list;
    }

    /**
     *
     */
    public function cleanUpSession()
    {
        $oSession = oxRegistry::getSession();
        $oSession->deleteVariable('sCountryISO');
        $oSession->deleteVariable('klarna_checkout_order_id');
        $oSession->deleteVariable('klarna_checkout_user_email');
    }

    /**
     * @param $sCountryISO
     */
    protected function redirectForNonKlarnaCountry($sCountryISO, $blShippingOptionsSet = true)
    {
        if ($blShippingOptionsSet === false) {
            $sUrl = oxRegistry::getConfig()->getShopSecureHomeUrl() . 'cl=basket';
        } else {
            $sUrl = oxRegistry::getConfig()->getShopSecureHomeUrl() . 'cl=user&non_kco_global_country=' . $sCountryISO;
        }
        oxRegistry::getUtils()->redirect($sUrl, false, 302);
    }

    /**
     *
     */
    public function setKlarnaDeliveryAddress()
    {
        $oxidAddress = oxRegistry::getConfig()->getRequestParameter('klarna_address_id');
        oxRegistry::getSession()->setVariable('deladrid', $oxidAddress);
        oxRegistry::getSession()->setVariable('blshowshipaddress', 1);
        oxRegistry::getSession()->deleteVariable('klarna_checkout_order_id');
    }
}
