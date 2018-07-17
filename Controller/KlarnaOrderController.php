<?php
/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TopConcepts\Klarna\Controller;


use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\PayPalModule\Controller\ExpressCheckoutDispatcher;
use OxidEsales\PayPalModule\Controller\StandardDispatcher;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaFormatter;
use TopConcepts\Klarna\Core\KlarnaLogs;
use TopConcepts\Klarna\Core\KlarnaOrder;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Model\KlarnaUser;
use TopConcepts\Klarna\Model\KlarnaPayment as KlarnaPaymentModel;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsView;

/**
 * Extends default OXID order controller logic.
 */
class KlarnaOrderController extends KlarnaOrderController_parent
{
    protected $_aResultErrors;

    /** @var Request */
    protected $oRequest;

    /** @var string  KlarnaExpressController url */
    protected $selfUrl;

    /**
     * @var User|KlarnaUser
     */
    protected $_oUser;

    /**
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
     *
     * @return string
     * @throws StandardException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function init()
    {
        parent::init();

        if (KlarnaUtils::isKlarnaCheckoutEnabled()) {
            $this->oRequest = Registry::get(Request::class);
            $oBasket        = Registry::getSession()->getBasket();
            $this->selfUrl  = Registry::getConfig()->getShopSecureHomeUrl() . 'cl=KlarnaExpress';

            if ($this->oRequest->getRequestEscapedParameter('externalCheckout') == 1) {
                Registry::getSession()->setVariable('externalCheckout', true);
            }
            $this->isExternalCheckout = Registry::getSession()->getVariable('externalCheckout');

            if ($this->isKlarnaCheckoutOrder($oBasket)) {
                if ($newCountry = $this->isCountryChanged()) {
                    $this->_aOrderData = [
                        'merchant_urls'    => [
                            'checkout' => Registry::getConfig()->getSslShopUrl() . "?cl=KlarnaExpress",
                        ],
                        'billing_address'  => [
                            'country' => $newCountry,
                            'email'   => Registry::getSession()->getVariable('klarna_checkout_user_email'),
                        ],
                        'shipping_address' => [
                            'country' => $newCountry,
                            'email'   => Registry::getSession()->getVariable('klarna_checkout_user_email'),
                        ],
                    ];
                    KlarnaUtils::fullyResetKlarnaSession();
                    Registry::getSession()->setVariable('sCountryISO', $newCountry);
                } else {
                    $oClient = $this->getKlarnaCheckoutClient();
                    try {
                        $this->_aOrderData = $oClient->getOrder();
                    } catch (KlarnaClientException $oEx) {
                        $oEx->debugOut();
                    }

                    if (KlarnaUtils::is_ajax() && $this->_aOrderData['status'] === 'checkout_complete') {
                        $this->jsonResponse('ajax', 'read_only');
                    }
                }
                $this->_initUser();
                $this->updateUserObject();
            }
        }
    }

    /**
     * Logging push state message to database
     *
     *
     * @param $action
     * @param string $requestBody
     * @param $url
     * @param $response
     * @param $errors
     * @param string $redirectUrl
     * @throws \Exception
     * @internal param KlarnaOrderValidator $oValidator
     */
    protected function logKlarnaData($action, $requestBody, $url, $response, $errors, $redirectUrl = '')
    {
        $request  = json_decode($requestBody, true);
        $order_id = isset($request['order_id']) ? $request['order_id'] : '';

        $oKlarnaLog = new KlarnaLogs;
        $aData      = array(
            'tcklarna_logs__tcklarna_method'      => $action,
            'tcklarna_logs__tcklarna_url'         => $url,
            'tcklarna_logs__tcklarna_orderid'     => $order_id,
            'tcklarna_logs__tcklarna_requestraw'  => $requestBody .
                                                     " \nERRORS:" . var_export($errors, true) .
                                                     " \nHeader Location:" . $redirectUrl,
            'tcklarna_logs__tcklarna_responseraw' => $response,
            'tcklarna_logs__tcklarna_date'        => date("Y-m-d H:i:s"),
        );
        $oKlarnaLog->assign($aData);
        $oKlarnaLog->save();
    }


    protected function getKlarnaAllowedExternalPayments()
    {
        return KlarnaPayment::getKlarnaAllowedExternalPayments();
    }

    protected function isKlarnaExternalPaymentMethod($paymentId, $sCountryISO)
    {
        if (!in_array($paymentId, $this->getKlarnaAllowedExternalPayments())) {
            return false;
        }
        if (!KlarnaUtils::isCountryActiveInKlarnaCheckout($sCountryISO)) {
            return false;
        }

        return true;
    }

    /**
     * @param $oBasket Basket
     * @return bool
     */
    protected function isKlarnaCheckoutOrder($oBasket)
    {
        $paymentId   = $oBasket->getPaymentId();
        $sCountryISO = Registry::getSession()->getVariable('sCountryISO');

        if (!($paymentId === 'klarna_checkout' || $this->isKlarnaExternalPaymentMethod($paymentId, $sCountryISO))) {
            return false;
        }

        if ($this->isExternalCheckout) {
            return false;
        }

        if ($this->isPayPalAmazon()) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return KlarnaCheckoutClient|KlarnaClientBase
     */
    protected function getKlarnaCheckoutClient()
    {
        return KlarnaCheckoutClient::getInstance();
    }

    /**
     *
     * @return KlarnaPaymentsClient|KlarnaClientBase
     */
    protected function getKlarnaPaymentsClient()
    {
        return KlarnaPaymentsClient::getInstance();
    }

    /**
     * Klarna confirmation callback. Calls only parent execute (standard oxid order creation) if not klarna_checkout
     * @return
     * @throws StandardException
     */
    public function execute()
    {
        $oBasket = Registry::getSession()->getBasket();
        Registry::getSession()->setVariable('sDelAddrMD5', $this->getDeliveryAddressMD5());

        if (KlarnaUtils::isKlarnaCheckoutEnabled()) {
            if ($oBasket->getPaymentId() == 'klarna_checkout') {
                $this->kcoBeforeExecute();
                $iSuccess = $this->kcoExecute($oBasket);

                return $this->_getNextStep($iSuccess);
            }
        }

        $result = parent::execute();

        return $result;
    }

    /**
     * Runs before oxid execute in KP mode
     * Saves authorization token
     * Runs final validation
     * Creates order on Klarna side
     *
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     * @throws \oxSystemComponentException
     */
    public function kpBeforeExecute()
    {

        // downloadable product validation for sofort
        if (!$termsValid = $this->_validateTermsAndConditions()) {
            Registry::get(UtilsView::class)->addErrorToDisplay('TCKLARNA_PLEASE_AGREE_TO_TERMS');
            Registry::getUtils()->redirect(Registry::getConfig()->getShopSecureHomeUrl() . 'cl=order', false, 302);

            return;
        }

        if ($sAuthToken = Registry::get(Request::class)->getRequestEscapedParameter('sAuthToken')) {
            Registry::getSession()->setVariable('sAuthToken', $sAuthToken);
            $dt = new \DateTime();
            Registry::getSession()->setVariable('sTokenTimeStamp', $dt->getTimestamp());
        }

        if ($sAuthToken || Registry::getSession()->hasVariable('sAuthToken')) {

            $oBasket        = Registry::getSession()->getBasket();
            $oKlarnaPayment = oxNew(KlarnaPayment::class, $oBasket, $this->getUser());

            $created = false;
            $oKlarnaPayment->validateOrder();

            $valid = $this->validatePayment($created, $oKlarnaPayment, $termsValid);

            if (!$valid || !$created) {
                Registry::getUtils()->redirect(Registry::getConfig()->getShopSecureHomeUrl() . 'cl=order', false, 302);

                return;
            }

            Registry::getSession()->setVariable('klarna_last_KP_order_id', $created['order_id']);
            Registry::getUtils()->redirect($created['redirect_url'], false, 302);
        }
    }

    /**
     * @param $created
     * @param KlarnaPayment $oKlarnaPayment
     * @param $termsValid
     * @throws \oxSystemComponentException
     * @return bool
     */
    protected function validatePayment(&$created, KlarnaPayment $oKlarnaPayment, $termsValid)
    {
        $oClient = $this->getKlarnaPaymentsClient();
        $valid   = !$oKlarnaPayment->isError() && $termsValid;
        if ($valid) {
            $created = $oClient->initOrder($oKlarnaPayment)->createNewOrder();
        } else {
            $oKlarnaPayment->displayErrors();
        }

        return $valid;
    }

    /**
     *
     * @throws StandardException
     */
    protected function kcoBeforeExecute()
    {
        try {
            $this->_validateUser($this->_aOrderData);
        } catch (StandardException $exception) {
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
                throw new StandardException('no user object');
            }
        }
    }


    /**
     * Check if user is logged in, if not check if user is in oxid and log them in
     * or create a user
     *
     *
     * @return bool
     */
    protected function _validateUser()
    {
        switch ($this->_oUser->getType()) {

            case KlarnaUser::NOT_EXISTING:
            case KlarnaUser::NOT_REGISTERED:
                // create regular account with password or temp account - empty password
                $result = $this->_createUser();

                return $result;

            default:
                break;
        }
    }

    /**
     * Create a user in oxid from klarna checkout data
     *
     *
     * @return bool
     * @throws \oxUserException
     * @throws \oxSystemComponentException
     */
    protected function _createUser()
    {
        $aBillingAddress  = KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'billing_address');

        $aDeliveryAddress = null;
        if($this->_aOrderData['billing_address'] !== $this->_aOrderData['shipping_address']){
            $aDeliveryAddress = KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'shipping_address');
        }

        $this->_oUser->oxuser__oxusername = new Field($this->_aOrderData['billing_address']['email'], Field::T_RAW);
        $this->_oUser->oxuser__oxactive   = new Field(1, Field::T_RAW);

        if (isset($this->_aOrderData['customer']['date_of_birth'])) {
            $this->_oUser->oxuser__oxbirthdate = new Field($this->_aOrderData['customer']['date_of_birth']);
        }

        $this->_oUser->createUser();

        //NECESSARY to have all fields initialized.
        $this->_oUser->load($this->_oUser->getId());

        $password = $this->isRegisterNewUserNeeded() ? $this->getRandomPassword(8) : null;
        $this->_oUser->setPassword($password);

        $this->_oUser->changeUserData($this->_oUser->oxuser__oxusername->value, $password, $password, $aBillingAddress, $aDeliveryAddress);

        // login only if registered a new account with password
        if ($this->isRegisterNewUserNeeded()) {
            Registry::getSession()->setVariable('usr', $this->_oUser->getId());
            Registry::getSession()->setVariable('blNeedLogout', true);
        }

        $this->setUser($this->_oUser);

        if($aDeliveryAddress){
            $this->_oUser->updateDeliveryAddress($aDeliveryAddress);
        }

        return true;
    }

    /**
     * Save order to database, delete order_id from session and redirect to thank you page
     *
     *
     * @param Basket $oBasket
     */
    protected function kcoExecute(Basket $oBasket)
    {
        $oBasket->calculateBasket(true);

        $oOrder = oxNew(Order::class);
        try {
            $iSuccess = $oOrder->finalizeOrder($oBasket, $this->_oUser);
        } catch (StandardException $e) {
            Registry::getSession()->deleteVariable('klarna_checkout_order_id');

            Registry::get(UtilsView::class)->addErrorToDisplay($e);
        }

        if ($iSuccess === 1) {
            if (
                ($this->_oUser->getType() === KlarnaUser::NOT_REGISTERED ||
                 $this->_oUser->getType() === KlarnaUser::NOT_EXISTING) &&
                $this->isRegisterNewUserNeeded()
            ) {
                $this->_oUser->save();
            }
            if ($this->_oUser->isFake())
                $this->_oUser->clearDeliveryAddress();
            // performing special actions after user finishes order (assignment to special user groups)
            $this->_oUser->onOrderExecute($oBasket, $iSuccess);

            if ($this->isRegisterNewUserNeeded()) {
                $oEmail = oxNew(\OxidEsales\Eshop\Core\Email::class);
                $oEmail->sendForgotPwdEmail($this->_oUser->oxuser__oxusername->value);
            }

            Registry::getSession()->setVariable('paymentid', 'klarna_checkout');

//            Registry::getUtils()->redirect(Registry::getConfig()->getShopSecureHomeUrl() . "cl=thankyou", false);
        }

        return $iSuccess;
    }


    /**
     * General Ajax entry point for this controller
     * @throws KlarnaClientException
     * @throws StandardException
     * @throws \ReflectionException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException
     */
    public function updateKlarnaAjax()
    {
        $aPost = $this->getJsonRequest();

        $sessionData = Registry::getSession()->getVariable('klarna_session_data');
        if (KlarnaUtils::isKlarnaPaymentsEnabled() && !$sessionData) {
            $this->resetKlarnaPaymentSession('basket');

            return;
        }

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
                break;

            default:
                $this->jsonResponse('undefined action', 'error');
        }
    }


    /**
     * Ajax call for Klarna Payment. Tracks changes and controls frontend Widget by status message
     *
     *
     * @param $aPost
     * @throws KlarnaClientException
     * @throws StandardException
     * @throws \ReflectionException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaOrderReadOnlyException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException
     * @return string
     */
    protected function checkOrderStatus($aPost)
    {
        if (!KlarnaUtils::isKlarnaPaymentsEnabled()) {
            return $this->jsonResponse(__FUNCTION__, 'submit');
        }

        $oSession = Registry::getSession();
        $oBasket  = $oSession->getBasket();
        $oUser    = $this->getUser();

        if (KlarnaPayment::countryWasChanged($oUser)) {
            $this->resetKlarnaPaymentSession();
        }

        /** @var KlarnaPayment $oKlarnaPayment */
        $oKlarnaPayment = oxNew(KlarnaPayment::class, $oBasket, $oUser, $aPost);

        if (!$oKlarnaPayment->isSessionValid()) {
            $this->resetKlarnaPaymentSession();
        }

        if (!$oKlarnaPayment->validateClientToken($aPost['client_token'])) {
            return $this->jsonResponse(
                __METHOD__,
                'refresh',
                array('refreshUrl' => $oKlarnaPayment->refreshUrl)
            );
        }

        $oKlarnaPayment->setStatus('submit');

        if ($oKlarnaPayment->isAuthorized()) {
            $this->handleAuthorizedPayment($oKlarnaPayment);
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
            'refreshUrl'    => $oKlarnaPayment->refreshUrl,
        );

        return $this->jsonResponse(
            __METHOD__,
            $oKlarnaPayment->getStatus(),
            $responseData
        );
    }

    /**
     * @param KlarnaPayment $oKlarnaPayment
     */
    protected function handleAuthorizedPayment(KlarnaPayment &$oKlarnaPayment)
    {
        $reauthorizeRequired = Registry::getSession()->getVariable('reauthorizeRequired');

        if ($reauthorizeRequired || $oKlarnaPayment->isOrderStateChanged() || !$oKlarnaPayment->isTokenValid()) {
            $oKlarnaPayment->setStatus('reauthorize');
            Registry::getSession()->deleteVariable('reauthorizeRequired');

        } else if ($oKlarnaPayment->requiresFinalization()) {
            $oKlarnaPayment->setStatus('finalize');
            // front will ignore this status if it's payment page
        }
    }

    /**
     *
     * @param $aPost
     * @return string
     */
    protected function addUserData($aPost)
    {
        $oSession = Registry::getSession();
        $oBasket  = $oSession->getBasket();
        $oUser    = $this->getUser();

        if (KlarnaPayment::countryWasChanged($oUser)) {
            $this->resetKlarnaPaymentSession();
        }

        /** @var  $oKlarnaPayment KlarnaPayment */
        $oKlarnaPayment = oxNew(KlarnaPayment::class, $oBasket, $oUser, $aPost);

        if (!$oKlarnaPayment->isSessionValid()) {
            $this->resetKlarnaPaymentSession();
        }

        if (!$oKlarnaPayment->validateClientToken($aPost['client_token'])) {
            return $this->jsonResponse(
                __METHOD__,
                'refresh',
                array('refreshUrl' => $oKlarnaPayment->refreshUrl)
            );
        }

        $responseData           = array();
        $responseData['update'] = $oKlarnaPayment->getChangedData();
        $savedCheckSums         = $oKlarnaPayment->fetchCheckSums();
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
     *
     */
    protected function updateSession($aPost)
    {
        $responseData   = array();
        $responseStatus = 'success';

        if ($aPost['country']) {

            $oCountry = oxNew(Country::class);
            $sSql     = $oCountry->buildSelectString(array('oxisoalpha3' => $aPost['country']));
            $oCountry->assignRecord($sSql);

            // force new session for the new country
            $this->resetKlarnaCheckoutSession();
            Registry::getSession()->setVariable('sCountryISO', $oCountry->oxcountry__oxisoalpha2->value);
            $this->forceReloadOnCountryChange = true;

            try {
                $this->updateKlarnaOrder();
            } catch (StandardException $e) {
                $e->debugOut();
            }

            $responseData['url'] = $this->_aOrderData['merchant_urls']['checkout'];
            $responseStatus      = 'redirect';
        }

        return Registry::getUtils()->showMessageAndExit(
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
            $oSession = Registry::getSession();
            $oBasket  = $oSession->getBasket();
            $oBasket->setShipping($aPost['id']);

            // update klarna order
            try {
                $this->updateKlarnaOrder();
            } catch (StandardException $e) {
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
     */
    protected function shipping_address_change()
    {
        $status = null;
        try {
            $oSession = Registry::getSession();
            $oBasket  = $oSession->getBasket();
            if($vouchersCount = count($oBasket->getVouchers())){
                $oBasket->klarnaValidateVouchers();
                // update widget if there was some invalid vouchers
                if($vouchersCount !== count($oBasket->getVouchers())){
                    $status = 'update_voucher_widget';
                }
            }
            $this->updateKlarnaOrder();
            $status = isset($status) ? $status : 'changed';
        } catch (StandardException $e) {
            $e->debugOut();
        }

        return $this->jsonResponse(__FUNCTION__, $status);

    }

    /**
     * Sends update request to checkout API
     * @return array|bool order data
     * @throws \oxSystemComponentException
     */
    protected function updateKlarnaOrder()
    {
        if ($this->_oUser) {
            $oSession = $this->getSession();
            /** @var Basket|\TopConcepts\Klarna\Model\KlarnaBasket $oBasket */
            $oBasket = $oSession->getBasket();
            $oKlarnaOrder = new KlarnaOrder($oBasket, $this->_oUser);
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

        return true;
    }

    /**
     * Initialize oxUser object and get order data from Klarna
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    protected function _initUser()
    {
        $oSession = $this->getSession();

        if ($this->_oUser = $this->getUser()) {
            if ($this->getViewConfig()->isUserLoggedIn()) {
                $this->_oUser->setType(KlarnaUser::LOGGED_IN);
            } else {
                $this->_oUser->setType(KlarnaUser::NOT_REGISTERED);
            }
        } else if ($oSession->hasVariable('oFakeKlarnaUser')) {
            $this->_oUser = $oSession->getVariable('oFakeKlarnaUser');
        } else {
            $this->_oUser = KlarnaUtils::getFakeUser($this->_aOrderData['billing_address']['email']);
        }
        $oCountry                          = oxNew(Country::class);
        $this->_oUser->oxuser__oxcountryid = new Field(
            $oCountry->getIdByCode(
                strtoupper($this->_aOrderData['billing_address']['country'])
            ),
            Field::T_RAW
        );

        if ($this->_oUser->isWritable()) {
            $this->_oUser->save();
        } else {
            $oSession->setVariable('oFakeKlarnaUser', $this->_oUser);
        }

        $oBasket = Registry::getSession()->getBasket();
        $oBasket->setBasketUser($this->_oUser);
    }

    /**
     * Update oxUser object
     */
    protected function updateUserObject()
    {
        if ($this->_aOrderData['billing_address'] !== $this->_aOrderData['shipping_address']) {
            $this->_oUser->updateDeliveryAddress(KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'shipping_address'));
        } else {
            $this->_oUser->clearDeliveryAddress();
        }

        $this->_oUser->assign(KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'billing_address'));

        if (isset($this->_aOrderData['customer']['date_of_birth'])) {
            $this->_oUser->oxuser__oxbirthdate = new Field($this->_aOrderData['customer']['date_of_birth']);
        }

        if ($this->_oUser->isWritable()) {
            $this->_oUser->save();
        } else {
            $this->getSession()->setVariable('oFakeKlarnaUser', $this->_oUser);
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
        Registry::getSession()->deleteVariable('klarna_checkout_order_id');
    }

    /**
     * Handles external payment
     * @throws \oxSystemComponentException
     * @throws \oxUserException
     */
    public function klarnaExternalPayment()
    {
        $oSession = Registry::getSession();

        $orderId   = $oSession->getVariable('klarna_checkout_order_id');
        $paymentId = Registry::get(Request::class)->getRequestEscapedParameter('payment_id');
        if (!$orderId || !$paymentId || !$this->isActivePayment($paymentId)) {
            Registry::get(UtilsView::class)->addErrorToDisplay('KLARNA_WENT_WRONG_TRY_AGAIN', false, true);
            Registry::getUtils()->redirect($this->selfUrl, true, 302);

            return;
        }

        $oBasket = $oSession->getBasket();

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
            Registry::getUtils()->redirect(Registry::getConfig()->getShopSecureHomeUrl() . "cl=KlarnaEpmDispatcher&fnc=amazonLogin", false);

            return;
        }

        if ($paymentId === 'oxidpaypal') {
            if ($this->_oUser->getType() === KlarnaUser::LOGGED_IN) {

                return Registry::get(StandardDispatcher::class)->setExpressCheckout();//todo: check if other options possible. user not available.
            }

            return Registry::get(ExpressCheckoutDispatcher::class)->setExpressCheckout();//todo: check if other options possible. user not available.
        }
    }

    /**
     * @param $paymentId
     */
    public function klarnaExternalCheckout($paymentId)
    {
        if ($paymentId === 'bestitamazon') {
            Registry::getUtils()->redirect(Registry::getConfig()->getShopSecureHomeUrl() . "cl=KlarnaEpmDispatcher&fnc=amazonLogin", false);
        } else if ($paymentId === 'oxidpaypal') {
            Registry::get(ExpressCheckoutDispatcher::class)->setExpressCheckout();
        } else {
            KlarnaUtils::fullyResetKlarnaSession();
            Registry::get(UtilsView::class)->addErrorToDisplay('KLARNA_WENT_WRONG_TRY_AGAIN', false, true);
            Registry::getUtils()->redirect($this->selfUrl, true, 302);
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
    private function jsonResponse($action, $status = null, $data = null)
    {
        return Registry::getUtils()->showMessageAndExit(json_encode(array(
            'action' => $action,
            'status' => $status,
            'data'   => $data,
        )));
    }

    /**
     * Gets data from request body
     * @return array
     * @codeCoverageIgnore
     */
    protected function getJsonRequest()
    {
        $requestBody = file_get_contents('php://input');

        return json_decode($requestBody, true);
    }

    /**
     * @param $paymentId
     * @return bool
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    protected function isActivePayment($paymentId)
    {
        $oPayment = oxNew(Payment::class);
        $oPayment->load($paymentId);

        return (boolean)$oPayment->oxpayments__oxactive->value;
    }

    /**
     * @return null|string
     */
    public function render()
    {
        if (Registry::getSession()->getVariable('paymentid') === "klarna_checkout") {
            Registry::getSession()->deleteVariable('paymentid');
            Registry::getUtils()->redirect(
                Registry::getConfig()->getShopSecureHomeUrl() . "cl=basket", false
            );

            return;
        }

        $template = parent::render();

        if (KlarnaUtils::isKlarnaPaymentsEnabled() && $this->isCountryHasKlarnaPaymentsAvailable($this->_oUser)) {
            $oSession              = Registry::getSession();
            $oBasket               = $oSession->getBasket();
            $payment_id            = $oBasket->getPaymentId();
            $aKlarnaPaymentMethods = KlarnaPaymentModel::getKlarnaPaymentsIds('KP');

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

        $sPaymentUrl = htmlspecialchars_decode(Registry::getConfig()->getShopSecureHomeUrl() . "cl=$controller");
        if (KlarnaUtils::is_ajax()) {
            $this->jsonResponse(__FUNCTION__, 'redirect', array('url' => $sPaymentUrl));
        }

        Registry::getUtils()->redirect($sPaymentUrl, false, 302);
    }

    /**
     * @param null $sCountryISO
     * @return \TopConcepts\Klarna\Core\KlarnaClientBase
     */
    protected function getKlarnaOrderClient($sCountryISO = null)
    {
        return KlarnaOrderManagementClient::getInstance($sCountryISO);
    }

    /**
     *
     * @param $oUser
     * @return bool
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
        $paymentId = Registry::getSession()->getBasket()->getPaymentId();

        return in_array($paymentId, KlarnaPaymentModel::getKlarnaPaymentsIds('KP'));
    }

    /**
     * @return bool
     */
    protected function isPayPalAmazon()
    {
        return in_array(Registry::getSession()->getBasket()->getPaymentId(), array('oxidpaypal', 'bestitamazon'));
    }

    /**
     * @return bool|false|string
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    protected function isCountryChanged()
    {
        $requestData = $this->getJsonRequest();
        $newCountry  = KlarnaUtils::getCountryIso2fromIso3(strtoupper($requestData['country']));
        $oldCountry  = Registry::getSession()->getVariable('sCountryISO');

        if (!$newCountry) {
            return false;
        }

        return $newCountry != $oldCountry ? $newCountry : false;
    }
}
