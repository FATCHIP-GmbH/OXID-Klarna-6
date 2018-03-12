<?php

/**
 * Extends default OXID order controller logic.
 */
class Klarna_Order extends Klarna_Order_parent
{
    private $_aResultErrors;

    /**
     * @var oxUser
     */
    protected $_oUser;

    /**
     *
     * @var array data fetched from KlarnaCheckout
     */
    protected $_aOrderData;

    /** @var bool create new order on country change */
    protected $forceReloadOnCountryChange = false;

    /** @var  bool */
    public $loadKlarnaPaymentWidget = false;

    /**
     * @var bool
     */
    protected $isExternalCheckout = false;

    /**
     * @var array data fetched from KlarnaCheckout
     * @return string
     * @throws Exception
     */
    public function init()
    {
        parent::init();

        if (KlarnaUtils::isKlarnaCheckoutEnabled()) {
            if (oxRegistry::getConfig()->getRequestParameter('externalCheckout') == 1) {
                oxRegistry::getSession()->setVariable('externalCheckout', true);
            }
            $this->isExternalCheckout = oxRegistry::getSession()->getVariable('externalCheckout');

            if (
                (oxRegistry::getSession()->getBasket()->getPaymentId() == 'klarna_checkout' ||
                 KlarnaUtils::isKlarnaExternalPaymentMethod()
                )
                && !$this->isExternalCheckout
                && !$this->isPayPalAmazon()
            ) {
                $oClient = $this->getKlarnaCheckoutClient();

                try {
                    $this->_aOrderData = $oClient->getOrder();
                } catch (KlarnaClientException $oEx) {
                    if ($oEx->getCode() == 401 || $oEx->getCode() == 404) {
                        // create new order. restart session.
                        if (KlarnaUtils::is_ajax()) {
                            return $this->jsonResponse(__FUNCTION__, 'restart needed', $data = null);
                        }
                    }
                }

                $this->_initUser();
                $this->updateUserObject();
            }
        }
    }

    /**
     * Logging push state message to database
     * @param $action
     * @param string $requestBody
     * @param $url
     * @param $response
     * @param $errors
     * @param string $redirectUrl
     * @throws oxSystemComponentException
     * @internal param KlarnaOrderValidator $oValidator
     */
    protected function logKlarnaData($action, $requestBody, $url, $response, $errors, $redirectUrl = '')
    {
        $request  = json_decode($requestBody, true);
        $order_id = isset($request['order_id']) ? $request['order_id'] : '';

        $oKlarnaLog = oxNew('klarna_logs');
        $aData      = array(
            'kl_logs__klmethod'      => $action,
            'kl_logs__klurl'         => $url,
            'kl_logs__klorderid'     => $order_id,
            'kl_logs__klrequestraw'  => $requestBody .
                                        " \nERRORS:" . var_export($errors, true) .
                                        " \nHeader Location:" . $redirectUrl,
            'kl_logs__klresponseraw' => $response,
            'kl_logs__kldate'        => date("Y-m-d H:i:s"),
        );
        $oKlarnaLog->assign($aData);
        $oKlarnaLog->save();
    }

    /**
     * @return KlarnaCheckoutClient|KlarnaClientBase
     */
    protected function getKlarnaCheckoutClient()
    {
        return KlarnaCheckoutClient::getInstance();
    }

    /**
     * @return KlarnaPaymentsClient|KlarnaClientBase
     */
    protected function getKlarnaPaymentsClient()
    {
        return KlarnaPaymentsClient::getInstance();
    }

    /**
     * Klarna confirmation callback. Calls only parent execute (standard oxid order creation) if not klarna_checkout
     * @throws Exception
     * @throws oxException
     * @throws oxSystemComponentException
     */
    public function execute()
    {
        $oBasket = oxRegistry::getSession()->getBasket();
        if (KlarnaUtils::isKlarnaCheckoutEnabled()) {
            if ($oBasket->getPaymentId() == 'klarna_checkout') {
                $this->kcoBeforeExecute();
                $this->kcoExecute($oBasket);
            }
        }

        oxRegistry::getSession()->setVariable('sDelAddrMD5', $this->getDeliveryAddressMD5());
        $result = parent::execute();

        return $result;
    }

    /**
     * Runs before oxid execute in KP mode
     * Saves authorization token
     * Runs final validation
     * Creates order on Klarna side
     *
     * @throws oxSystemComponentException
     */
    public function kpBeforeExecute()
    {

        // downloadable product validation for sofort
        if (!$termsValid = $this->_validateTermsAndConditions()) {
            oxRegistry::get('oxUtilsView')->addErrorToDisplay('KL_PLEASE_AGREE_TO_TERMS');
            oxRegistry::getUtils()->redirect(oxRegistry::getConfig()->getShopSecureHomeUrl() . 'cl=order', false, 302);
        }

        if ($sAuthToken = oxRegistry::getConfig()->getRequestParameter('sAuthToken')) {
            oxRegistry::getSession()->setVariable('sAuthToken', $sAuthToken);
            $dt = new DateTime();
            oxRegistry::getSession()->setVariable('sTokenTimeStamp', $dt->getTimestamp());
        }


        if ($sAuthToken || oxRegistry::getSession()->hasVariable('sAuthToken')) {

            $oBasket = oxRegistry::getSession()->getBasket();
            /** @var  $oKlarnaPayment KlarnaPayment */
            $oKlarnaPayment = oxNew('KlarnaPayment', $oBasket, $this->getUser());

            $oClient = $this->getKlarnaPaymentsClient();

            $created = false;
            $oKlarnaPayment->validateOrder();

            $valid = !$oKlarnaPayment->isError() && $termsValid;
            if ($valid) {
                $created = $oClient->initOrder($oKlarnaPayment)->createNewOrder();

            } else {
                $oKlarnaPayment->displayErrors();
            }

            if (!$valid || !$created) {
                oxRegistry::getUtils()->redirect(oxRegistry::getConfig()->getShopSecureHomeUrl() . 'cl=order', false, 302);
            }

            oxRegistry::getSession()->setVariable('klarna_last_KP_order_id', $created['order_id']);
            oxRegistry::getUtils()->redirect($created['redirect_url'], false, 302);
        }
    }

    /**
     * @throws oxException
     * @throws oxSystemComponentException
     */
    protected function kcoBeforeExecute()
    {
        try {
            $this->_validateUser($this->_aOrderData);
        } catch (oxException $exception) {
            $this->_aResultErrors[] = $exception->getMessage();
            $this->logKlarnaData(
                'Order Execute',
                $this->_aOrderData,
                '',
                '',
                $this->_aResultErrors,
                ''
            );
        }

        // send newsletter confirmation
        if ($this->isNewsletterSignupNeeded()) {
            if ($oUser = $this->getUser()) {
                $oUser->setNewsSubscription(true, true);  // args = [value, send_confirmation]
            } else {
                throw new oxException('no user object');
            }
        }
    }


    /**
     * Check if user is logged in, if not check if user is in oxid and log them in
     * or create a user
     * @return bool
     * @throws oxException
     */
    protected function _validateUser()
    {
        switch ($this->_oUser->kl_getType()) {

            case Klarna_oxUser::NOT_EXISTING:
            case Klarna_oxUser::NOT_REGISTERED:
                // create regular account with password or temp account - empty password
                $result = $this->_createUser();

                return $result;

            default:
                break;
        }
    }

    /**
     * Create a user in oxid from klarna checkout data
     * @return bool
     * @throws oxException
     */
    protected function _createUser()
    {
        $aBillingAddress  = KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'billing_address');
        $aDeliveryAddress = KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'shipping_address');

        $this->_oUser->oxuser__oxusername = new oxField($this->_aOrderData['billing_address']['email'], oxField::T_RAW);
        $this->_oUser->oxuser__oxactive   = new oxField(1, oxField::T_RAW);

        if (isset($this->_aOrderData['customer']['date_of_birth'])) {
            $this->_oUser->oxuser__oxbirthdate = new oxField($this->_aOrderData['customer']['date_of_birth']);
        }

        $this->_oUser->createUser();
        //NECESSARY to have all fields initialized.
        $this->_oUser->load($this->_oUser->getId());

        $password = $this->isRegisterNewUserNeeded() ? $this->getRandomPassword(8) : null;
        $this->_oUser->setPassword($password);

        try {
            $this->_oUser->changeUserData($this->_oUser->oxuser__oxusername->value, $password, $password, $aBillingAddress, $aDeliveryAddress);
            if ($password)
                $this->sendChangePasswordEmail();

        } catch (oxException $oException) {
            $this->_aResultErrors[] = 'User could not be updated/loaded';//todo:translate

            return false;
        }

        // login only if registered a new account with password
        if ($this->isRegisterNewUserNeeded()) {
            oxRegistry::getSession()->setVariable('usr', $this->_oUser->getId());
            oxRegistry::getSession()->setVariable('blNeedLogout', true);
        }

        $this->setUser($this->_oUser);
        $this->_oUser->updateDeliveryAddress(KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'shipping_address'));

        return true;
    }

    /**
     * Save order to database, delete order_id from session and redirect to thank you page
     *
     * @param oxBasket $oBasket
     * @throws oxSystemComponentException
     */
    protected function kcoExecute(oxBasket $oBasket)
    {
        $oBasket->calculateBasket(true);

        $oOrder = oxNew('oxorder');
        try {
            $iSuccess = $oOrder->finalizeOrder($oBasket, $this->_oUser);
        } catch (oxException $e) {
            oxRegistry::getSession()->deleteVariable('klarna_checkout_order_id');

            oxRegistry::get("oxUtilsView")->addErrorToDisplay($e);

        }

        if ($iSuccess === 1) {
            if (
                ($this->_oUser->kl_getType() === klarna_oxuser::NOT_REGISTERED ||
                 $this->_oUser->kl_getType() === klarna_oxuser::NOT_EXISTING) &&
                $this->isRegisterNewUserNeeded()
            ) {
                $this->_oUser->save();
            }
            if ($this->_oUser->isFake())
                $this->_oUser->clearDeliveryAddress();
            // performing special actions after user finishes order (assignment to special user groups)
            $this->_oUser->onOrderExecute($oBasket, $iSuccess);

            oxRegistry::getSession()->setVariable('paymentid', 'klarna_checkout');

            oxRegistry::getUtils()->redirect(oxRegistry::getConfig()->getShopSecureHomeUrl() . "cl=thankyou", false);
        }
    }


    /**
     * General Ajax entry point for this controller
     */
    public function updateKlarnaAjax()
    {
        $aPost = $this->getJsonRequest();


        if (KlarnaUtils::isKlarnaPaymentsEnabled()) {
            $sessionData = oxRegistry::getSession()->getVariable('klarna_session_data');
            if (!$sessionData) {
                $this->resetKlarnaPaymentSession('basket');
            }
        }

        if (is_null($aPost['action'])) {
            $this->jsonResponse('undefined action', 'error');
        } else {
            switch ($aPost['action']) {
                case 'shipping_option_change':
                    $this->shipping_option_change($aPost);
                    break;

                case 'shipping_address_change':
                    $this->shipping_address_change();
                    break;

                case 'change':
                    $this->updateSession($aPost);
                    break;

                case 'checkOrderStatus':
                    $this->checkOrderStatus($aPost);
                    break;

                case 'addUserData':
                    $this->addUserData($aPost);

                default:
                    $this->jsonResponse('undefined action', 'error');
            }
        }
    }


    /**
     * Ajax call for Klarna Payment. Tracks changes and controls frontend Widget by status message
     * @param $aPost
     * @return string
     * @throws KlarnaClientException
     * @throws oxException
     * @throws oxSystemComponentException
     * @throws ReflectionException
     */
    protected function checkOrderStatus($aPost)
    {
        if (!KlarnaUtils::isKlarnaPaymentsEnabled()) {
            return $this->jsonResponse(__FUNCTION__, 'submit');
        }

        $oSession = oxRegistry::getSession();
        $oBasket  = $oSession->getBasket();
        $oUser    = $this->getUser();

        if (KlarnaPayment::countryWasChanged($oUser)) {
            $this->resetKlarnaPaymentSession();
        }

        /** @var  $oKlarnaPayment KlarnaPayment */
        $oKlarnaPayment = oxNew('KlarnaPayment', $oBasket, $oUser, $aPost);

        if(!$oKlarnaPayment->isSessionValid()){
            $this->resetKlarnaPaymentSession();
        }

        if(!$oKlarnaPayment->validateClientToken($aPost['client_token'])){
            return $this->jsonResponse(
                __METHOD__,
                'refresh',
                array('refreshUrl' => $oKlarnaPayment->refreshUrl)
            );
        }


        $oKlarnaPayment->setStatus('submit');

        if ($oKlarnaPayment->isAuthorized()) {

            $reauthorizeRequired = oxRegistry::getSession()->getVariable('reauthorizeRequired');

            if ($reauthorizeRequired || $oKlarnaPayment->isOrderStateChanged() || !$oKlarnaPayment->isTokenValid()) {
                $oKlarnaPayment->setStatus('reauthorize');
                oxRegistry::getSession()->deleteVariable('reauthorizeRequired');

            } else if ($oKlarnaPayment->requiresFinalization()) {
                $oKlarnaPayment->setStatus('finalize');
                // front will ignore this status if it's payment page
            }

        } else {
            $oKlarnaPayment->setStatus('authorize');
        }

        if ($oKlarnaPayment->paymentChanged) {
            $oKlarnaPayment->setStatus('authorize');
            $oSession->deleteVariable('sAuthToken');
            $oSession->deleteVariable('finalizeRequired');
        }

        $this->getKlarnaPaymentsClient()
            ->initOrder($oKlarnaPayment)
            ->createOrUpdateSession();

        $responseData = array(
            'update'        => $aPost,
            'paymentMethod' => $oKlarnaPayment->getPaymentMethodCategory(),
            'refreshUrl'      => $oKlarnaPayment->refreshUrl,
        );

        return $this->jsonResponse(
            __METHOD__,
            $oKlarnaPayment->getStatus(),
            $responseData
        );
    }

    /**
     * @param $aPost
     * @return string
     * @throws KlarnaClientException
     * @throws oxException
     * @throws oxSystemComponentException
     * @throws ReflectionException
     */
    protected function addUserData($aPost)
    {
        $oSession = oxRegistry::getSession();
        $oBasket  = $oSession->getBasket();
        $oUser    = $this->getUser();

        if (KlarnaPayment::countryWasChanged($oUser)) {
            $this->resetKlarnaPaymentSession();
        }

        /** @var  $oKlarnaPayment KlarnaPayment */
        $oKlarnaPayment         = oxNew('KlarnaPayment', $oBasket, $oUser, $aPost);

        if(!$oKlarnaPayment->isSessionValid()){
            $this->resetKlarnaPaymentSession();
        }

        if(!$oKlarnaPayment->validateClientToken($aPost['client_token'])){
            return $this->jsonResponse(
                __METHOD__,
                'refresh',
                array('refreshUrl' => $oKlarnaPayment->refreshUrl)
            );
        }

        $responseData           = array();
        $responseData['update'] = $oKlarnaPayment->getChangedData();
//        $responseData['debug'] = $oKlarnaPayment->r;
        $savedCheckSums = $oKlarnaPayment->fetchCheckSums();
        if ($savedCheckSums['_aUserData'] === false) {
            $oKlarnaPayment->setCheckSum('_aUserData', true);
        }

        $result = $this->getKlarnaPaymentsClient()
            ->initOrder($oKlarnaPayment)
            ->createOrUpdateSession();


        $this->jsonResponse(__METHOD__, 'updateUser', $responseData);
    }

    /**
     * Ajax - updates country heading above iframe
     * @param $aPost
     * @return string
     * @throws oxSystemComponentException
     */
    protected function updateSession($aPost)
    {
        $responseData   = array();
        $responseStatus = 'success';

        if ($aPost['country']) {

            $oCountry = oxNew('oxCountry');
            $sSql     = $oCountry->buildSelectString(array('oxisoalpha3' => $aPost['country']));
            $oCountry->assignRecord($sSql);

            // force new session for the new country
            $this->resetKlarnaCheckoutSession();
            oxRegistry::getSession()->setVariable('sCountryISO', $oCountry->oxcountry__oxisoalpha2->value);
            $this->forceReloadOnCountryChange = true;

            try {
                $this->updateKlarnaOrder();
            } catch (oxException $e) {
                $e->debugOut();
            }

            $responseData['url'] = $this->_aOrderData['merchant_urls']['checkout'];
            $responseStatus      = 'redirect';
        }

        return oxRegistry::getUtils()->showMessageAndExit(
            $this->jsonResponse(__FUNCTION__, $responseStatus, $responseData)
        );
    }

    /**
     * Ajax shipping_option_change action
     * @param $aPost
     * @return null
     */
    protected function shipping_option_change($aPost)
    {
        if (isset($aPost['id'])) {

            // update basket
            $oSession = oxRegistry::getSession();
            $oBasket  = $oSession->getBasket();
            $oBasket->setShipping($aPost['id']);

            // update klarna order
            try {
                $this->updateKlarnaOrder();
            } catch (oxException $e) {
                $e->debugOut();
            }

            $responseData = array();
            $this->jsonResponse(__FUNCTION__, 'changed', $responseData);
        } else {
            $this->jsonResponse(__FUNCTION__, 'error');
        }
    }

    /**
     * Ajax shipping_address_change action
     * @throws oxException
     * @throws oxSystemComponentException
     */
    protected function shipping_address_change()
    {
        $this->updateUserObject();
        try {
            $this->updateKlarnaOrder();
        } catch (oxException $e) {
            $e->debugOut();
        }

        return $this->jsonResponse(__FUNCTION__, 'changed');

    }

    /**
     * Sends update request to checkout API
     * @return array order data
     * @throws KlarnaClientException
     * @throws oxException
     * @throws oxSystemComponentException
     * @internal param oxBasket $oBasket
     * @internal param oxUser $oUser
     */
    protected function updateKlarnaOrder()
    {
        $oSession     = $this->getSession();
        $oBasket      = $oSession->getBasket();
        $oKlarnaOrder = oxNew('KlarnaOrder', $oBasket, $this->_oUser);
        $oClient      = $this->getKlarnaCheckoutClient();
        $aOrderData   = $oKlarnaOrder->getOrderData();

        if ($this->forceReloadOnCountryChange && isset($this->_aOrderData['billing_address']) && isset($this->_aOrderData['shipping_address'])) {
            $aOrderData['billing_address']  = $this->_aOrderData['billing_address'];
            $aOrderData['shipping_address'] = $this->_aOrderData['shipping_address'];
        }

        return $oClient->createOrUpdateOrder(
            json_encode($aOrderData)
        );
    }

    /**
     * Initialize oxUser object and get order data from Klarna
     * @throws oxSystemComponentException
     * @throws oxConnectionException
     */
    protected function _initUser()
    {
        if ($this->_oUser = $this->getUser()) {
            if ($this->getViewConfig()->isUserLoggedIn()) {
                $this->_oUser->kl_setType(Klarna_oxUser::LOGGED_IN);
            } else {
                $this->_oUser->kl_setType(Klarna_oxUser::NOT_REGISTERED);
            }
        } else {
            $this->_oUser                      = KlarnaUtils::getFakeUser($this->_aOrderData['billing_address']['email']);
            $oCountry                          = oxNew('oxCountry');
            $this->_oUser->oxuser__oxcountryid = new oxField(
                $oCountry->getIdByCode(
                    strtoupper($this->_aOrderData['purchase_country'])
                ),
                oxField::T_RAW
            );
        }
    }

    /**
     * Update oxUser object
     * @throws oxSystemComponentException
     */
    protected function updateUserObject()
    {
        if ($this->_oUser) {
            if ($this->_aOrderData['billing_address'] !== $this->_aOrderData['shipping_address']) {
                $this->_oUser->updateDeliveryAddress(KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'shipping_address'));
            } else {
                $this->_oUser->clearDeliveryAddress();
            }
            $this->_oUser->assign(KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'billing_address'));

            if (isset($this->_aOrderData['customer']['date_of_birth'])) {
                $this->_oUser->oxuser__oxbirthdate = new oxField($this->_aOrderData['customer']['date_of_birth']);
            }

            if ($this->_oUser->kl_getType() !== klarna_oxuser::REGISTERED) {
                $this->_oUser->save();
            }
        }
    }

    /**
     * Clear KCO session.
     * Destroy client instance / force to use new credentials. This allow us to
     * create new order (using new merchant account) in this request
     *
     */
    protected function resetKlarnaCheckoutSession()
    {
        KlarnaCheckoutClient::resetInstance(); // we need new instance with new credentials
        oxRegistry::getSession()->deleteVariable('klarna_checkout_order_id');
    }

    /**
     * Handles external payment
     * @throws oxException
     */
    public function klarnaExternalPayment()
    {
        $orderId   = oxRegistry::getSession()->getVariable('klarna_checkout_order_id');
        $paymentId = oxRegistry::getConfig()->getRequestParameter('payment_id');
        if (!$orderId || !$paymentId || !$this->isActivePayment($paymentId)) {
            oxRegistry::get("oxUtilsView")->addErrorToDisplay('KLARNA_WENT_WRONG_TRY_AGAIN', false, true);
            $redirectUrl = oxRegistry::getConfig()->getShopSecureHomeURL() . 'cl=KlarnaExpress';
            oxRegistry::getUtils()->redirect($redirectUrl, true, 302);
        }

        $oSession = oxRegistry::getSession();
        $oBasket  = $oSession->getBasket();

        $oSession->setVariable("paymentid", $paymentId);
        $oBasket->setPayment($paymentId);

        if ($this->isExternalCheckout) {
            $this->klarnaExternalCheckout($paymentId);
        }

        $oBasket->setPayment($paymentId);

        if ($this->_oUser->isCreatable()) {
            $this->_createUser();
        }

        // make sure we have the right shipping option
        $oBasket->setShipping($this->_aOrderData['selected_shipping_option']['id']);
        $oBasket->onUpdate();

        if ($paymentId === 'bestitamazon') {
            oxRegistry::getUtils()->redirect(oxRegistry::getConfig()->getShopSecureHomeUrl() . "cl=klarna_epm_dispatcher&fnc=amazonLogin", false);
        }
        if ($paymentId === 'oxidpaypal') {
            oxRegistry::get('oePayPalStandardDispatcher')->setExpressCheckout();
        }
    }

    /**
     * @param $paymentId
     */
    public function klarnaExternalCheckout($paymentId)
    {
        if ($paymentId === 'bestitamazon') {
            oxRegistry::getUtils()->redirect(oxRegistry::getConfig()->getShopSecureHomeUrl() . "cl=klarna_epm_dispatcher&fnc=amazonLogin", false);
        } else if ($paymentId === 'oxidpaypal') {
            oxRegistry::get('oePayPalExpressCheckoutDispatcher')->setExpressCheckout();
        } else {
            KlarnaUtils::fullyResetKlarnaSession();
            oxRegistry::get("oxUtilsView")->addErrorToDisplay('KLARNA_WENT_WRONG_TRY_AGAIN', false, true);
            $redirectUrl = oxRegistry::getConfig()->getShopSecureHomeURL() . 'cl=KlarnaExpress';
            oxRegistry::getUtils()->redirect($redirectUrl, true, 302);
        }
    }

    /**
     * Should we register a new user account with the order?
     * @return bool
     * @internal param $aOrderData
     */
    protected function isRegisterNewUserNeeded()
    {
        $checked          = $this->_aOrderData['merchant_requested']['additional_checkbox'] === true;
        $checkboxFunction = KlarnaUtils::getShopConfVar('iKlarnaActiveCheckbox');

        return $checkboxFunction > 0 && $checked;
    }

    /**
     * Should we sign the user up for the newsletter?
     * @return bool
     * @internal param $aOrderData
     */
    protected function isNewsletterSignupNeeded()
    {
        $checked          = $this->_aOrderData['merchant_requested']['additional_checkbox'] === true;
        $checkboxFunction = KlarnaUtils::getShopConfVar('iKlarnaActiveCheckbox');

        return $checkboxFunction > 1 && $checked;
    }

    /**
     * @return bool
     * @throws oxSystemComponentException
     */
    protected function sendChangePasswordEmail()
    {
        $sEmail = $this->_oUser->oxuser__oxusername->value;
        $oEmail = oxNew('oxemail');

        $iSuccess = false;
        if ($sEmail) {
            $iSuccess = $oEmail->sendChangePwdEmail($sEmail, 'Set password for your new account.');
        }

        if ($iSuccess !== true) {
            $sError = ($iSuccess === false) ? 'ERROR_MESSAGE_PASSWORD_EMAIL_INVALID' : 'MESSAGE_NOT_ABLE_TO_SEND_EMAIL';
            oxRegistry::get("oxUtilsView")->addErrorToDisplay($sError, false, true);

            return false;
        }

        return true;
    }

    /**
     * @param $len int
     * @return string
     */
    protected function getRandomPassword($len)
    {
        $alphabet    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass        = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < $len; $i++) {
            $n      = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }

        return implode($pass);
    }

    /**
     * Formats Json response
     * @param $action
     * @param $status
     * @param $data
     * @return string
     */
    private function jsonResponse($action, $status, $data = null)
    {
        return oxRegistry::getUtils()->showMessageAndExit(json_encode(array(
            'action' => $action,
            'status' => $status,
            'data'   => $data,
        )));
    }

    /**
     * Gets data from request body
     * @return array
     */
    private function getJsonRequest()
    {
        $requestBody = file_get_contents('php://input');

        return json_decode($requestBody, true);
    }

    /**
     * Compares credentials specified for two countries (old one and new)
     * Returns true if they are different
     * "return bool
     */
    protected function isCountryChanged()
    {
        $sOldCountryISO     = oxRegistry::getSession()->getVariable('sCountryISO');
        $sCurrentCountryISO = strtoupper($this->_aOrderData['shipping_address']['country']);

        if ($sOldCountryISO === $sCurrentCountryISO)
            return false;

        $oCountry                          = oxNew('oxCountry');
        $this->_oUser->oxuser__oxcountryid = new oxField($oCountry->getIdByCode($sCurrentCountryISO), oxField::T_RAW);
        oxRegistry::getSession()->setVariable('sCountryISO', $sCurrentCountryISO);

        return true;
    }

    /**
     * @param $paymentId
     * @return bool
     * @throws oxSystemComponentException
     */
    protected function isActivePayment($paymentId)
    {
        $oPayment = oxNew('oxpayment');
        $oPayment->load($paymentId);

        return (boolean)$oPayment->oxpayments__oxactive->value;
    }

    /**
     * @return null|string
     * @throws oxSystemComponentException
     */
    public function render()
    {
        if (oxRegistry::getSession()->getVariable('paymentid') === "klarna_checkout") {
            oxRegistry::getSession()->deleteVariable('paymentid');
            oxRegistry::getUtils()->redirect(
                oxRegistry::getConfig()->getShopSecureHomeUrl() . "cl=basket", false
            );
        }

        $template = parent::render();

        if (KlarnaUtils::isKlarnaPaymentsEnabled() && $this->isCountryHasKlarnaPaymentsAvailable($this->_oUser)) {
            $oSession              = oxRegistry::getSession();
            $oBasket               = $oSession->getBasket();
            $payment_id            = $oBasket->getPaymentId();
            $aKlarnaPaymentMethods = klarnaOxPayment::getKlarnaPaymentsIds('KP');

            if (in_array($payment_id, $aKlarnaPaymentMethods)) {
                // add KP js to the page
                $aKPSessionData = $oSession->getVariable('klarna_session_data');
                if ($aKPSessionData) {
                    $this->loadKlarnaPaymentWidget = true;
                    $this->addTplParam("client_token", $aKPSessionData['client_token']);
                }
            }
            $this->addTplParam("sLocale", strtolower(KlarnaConsts::getLocale()));
        }

        return $template;
    }

    /**
     * @param string $controller
     * @return void
     */
    protected function resetKlarnaPaymentSession($controller = 'payment')
    {
        KlarnaPayment::cleanUpSession();

        $sPaymentUrl = htmlspecialchars_decode(oxRegistry::getConfig()->getShopSecureHomeUrl() . "cl=$controller");
        if (KlarnaUtils::is_ajax()) {
            $this->jsonResponse(__FUNCTION__, 'redirect', array('url' => $sPaymentUrl));
        }

        oxRegistry::getUtils()->redirect($sPaymentUrl, false, 302);
    }

    /**
     * @param null $sCountryISO
     * @return KlarnaOrderManagementClient|KlarnaClientBase
     */
    protected function getKlarnaOrderClient($sCountryISO = null)
    {
        return KlarnaOrderManagementClient::getInstance($sCountryISO);
    }

    /**
     *
     * @param $oUser
     * @return bool
     * @throws oxSystemComponentException
     */
    public function isCountryHasKlarnaPaymentsAvailable($oUser = null)
    {
        if ($oUser === null) {
            $oUser = $this->getUser();
        }
        $sCountryISO = KlarnaUtils::getCountryISO($oUser->getFieldData('oxcountryid'));
        if (in_array($sCountryISO, KlarnaConsts::getKlarnaCoreCountries())) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function includeKPWidget()
    {
        $paymentId = oxRegistry::getSession()->getBasket()->getPaymentId();

        return in_array($paymentId, klarnaOxPayment::getKlarnaPaymentsIds('KP'));
    }

    /**
     * @return bool
     */
    public function isPayPalAmazon()
    {
        return in_array(oxRegistry::getSession()->getBasket()->getPaymentId(), array('oxidpaypal', 'bestitamazon'));
    }
}
