<?php

/**
 * Class KlarnaPayment represents Klarna Payment Order.
 *
 */
class KlarnaPayment extends oxBase
{

    /** @var array Of session keys used in KP mode */
    static $aSessionKeys = array(
        'sCountryISO',
        'kpCheckSums',
        'sAuthToken',
        'klarna_session_data',
        'sTokenTimeStamp',
        'sSessionTimeStamp',
        'finalizeRequired',
        'reauthorizeRequired'
    );

    /**
     * @var array
     * Current checksum of private attributes are saved in the session after each update send to Klarna
     */
    private $_aOrderLines;
    private $_aUserData;

    /** @var mixed checksum of currently selected KP method stored in the session
     * Updated
     */
    private $_sPaymentMethod;

    /**
     * @var array Stores basic data send to Klarna required to begin new KP session
     */
    protected $_aOrderData;

    /** @var array
     * Stores data to send to Klarna with update call
     * Empty if there is nothing to update
     */
    protected $aUpdateData;

    /** @var array Stores order checksums array fetched from session */
    protected $checkSums;

    /** @var array Order error messages to display to the user */
    protected $errors;

    /** @var string Current order status to send in ajax response */
    protected $status;

    /** @var klarna_oxuser|oxUser  */
    protected $oUser;


    /** @var bool false if session time expired */
    protected $sessionValid;

    /** @var int Session timeout (24h) in seconds */
    protected $sessionTimeout = 86400;


    /** @var string current action for ajax request  */
    public $action;

    /** @var bool true if user changed KP method */
    public $paymentChanged;

    /** @var bool  */
    public $currencyToCountryMatch;

    /** @var string Url for front redirection send in ajax response if KP widget refresh needed  */
    public $refreshUrl;


    /**
     * KlarnaPayment constructor.
     * @param oxBasket | klarna_oxbasket $oBasket
     * @param oxUser | klarna_oxuser $oUser
     * @param array $aPost used to pass ajax request data like selected payment method
     * @throws oxArticleException
     * @throws oxArticleInputException
     * @throws oxException
     * @throws oxNoArticleException
     * @throws oxSystemComponentException
     * @throws TypeError
     * @throws ReflectionException
     */
    public function __construct(oxBasket $oBasket, oxUser $oUser, $aPost = array())
    {
        if (!$oUser instanceof oxUser) {
            $oUser = $this->getUser();
        }

        $controllerName = $this->isAuthorized() ? 'order' : 'payment';
        $this->refreshUrl = oxRegistry::getConfig()->getShopSecureHomeUrl() . "cl=$controllerName";

        $this->aUpdateData            = array();
        $this->oUser                  = $oUser;
        $this->errors                 = array();
        $this->status                 = 'submit';
        $this->paymentChanged         = false;
        $this->currencyToCountryMatch = true;
        $this->action = $aPost['action'];

        if (!(isset($aPost['paymentId']) && $this->_sPaymentMethod = $aPost['paymentId'])) {
            $this->_sPaymentMethod = oxRegistry::getSession()->getVariable('paymentid');
        }

        $sCountryISO = $oUser->resolveCountry();
        $sLocale     = $oUser->resolveLocale($sCountryISO);
        $currencyISO = $oBasket->getBasketCurrency()->name;

        if ($oUser->getKlarnaPaymentCurrency() !== $currencyISO) {
            $this->currencyToCountryMatch = false;
        }

        $oSession          = oxRegistry::getSession();
        $sToken            = $oSession->getSessionChallengeToken();
        $this->_aOrderData = array(
            "purchase_country"  => $sCountryISO,
            "purchase_currency" => $currencyISO,
            "locale"            => $sLocale, //'de-DE',
            "merchant_urls"     => array(
                "confirmation" => oxRegistry::getConfig()->getSslShopUrl() . "?cl=order&oxdownloadableproductsagreement=1&fnc=execute&stoken=" . $sToken,
            ),
        );

        $this->_aUserData   = $oUser->getKlarnaPaymentData();
        $this->_aOrderLines = $oBasket->getKlarnaOrderLines();
        $this->_aOrderData  = array_merge($this->_aOrderData, $this->_aOrderLines);
        $this->addOptions();

        $this->checksumCheck();

        if(!(KlarnaUtils::is_ajax()) && $this->isAuthorized() && $this->aUpdateData){
            oxRegistry::getSession()->setVariable('reauthorizeRequired', true);
        }

        $this->validateSessionTimeout();
        $this->validateCountryAndCurrency();
        $this->validateKlarnaUserData();

        parent::__construct();
    }

    /**
     * @return array
     */
    public function getOrderData()
    {
        return $this->_aOrderData;
    }

    /**
     * @return mixed
     */
    public function getUserData()
    {
        return $this->_aUserData;
    }

    /**
     * @return mixed
     */
    public function getPaymentMethodCategory()
    {
        $mapping = array(
            'klarna_pay_later'    => 'pay_later',
            'klarna_slice_it'     => 'pay_over_time',
            'klarna_direct_debit' => 'direct_debit',
            'klarna_sofort'       => 'direct_bank_transfer',
        );

        return $mapping[$this->_sPaymentMethod];
    }

    /**
     * Adds options to the request data
     */
    protected function addOptions()
    {
        $options = array();

        /*** add design options ***/
        $options = array_merge(
            $options,
            KlarnaUtils::getShopConfVar('aKlarnaDesign'),
            KlarnaUtils::getShopConfVar('aKlarnaDesignKP')
        );

        if ($options) {
            $this->_aOrderData['options'] = $options;
        }
    }

    /**
     * Gets privet properties for checksum calculations
     * @return ReflectionProperty[]
     * @throws ReflectionException
     */
    protected function getPrivateProperties()
    {
        $reflect = new ReflectionClass($this);

        return $reflect->getProperties(ReflectionProperty::IS_PRIVATE);
    }

    /**
     * Checks if order is authorized
     * @return bool
     */
    public function isAuthorized()
    {
        return oxRegistry::getSession()->hasVariable('sAuthToken') || $this->requiresFinalization();
    }

    /**
     * Checks order state. returns true if there is something too update
     * @return bool
     */
    public function isOrderStateChanged()
    {
        if ($this->aUpdateData)
            return true;

        return false;
    }

    /**
     * Checks if token is valid
     * @return bool
     */
    public function isTokenValid()
    {
        if($this->requiresFinalization()){
            return true;
        }

        $tStamp = oxRegistry::getSession()->getVariable('sTokenTimeStamp');

        if ($tStamp) {
            $dt = new DateTime();

            if ($dt->getTimestamp() - $tStamp < 3590)

                return true;
        }

        return false;
    }

    /**
     * Gets changed order data
     * @return array
     */
    public function getChangedData()
    {
        return $this->aUpdateData;
    }

    /**
     * Sets order status
     * @param $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Gets order status
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Compares current country ISO with country ISO stored in the session
     * @param $oUser oxUser | klarna_oxuser
     * @return bool
     * @throws oxSystemComponentException
     */
    public static function countryWasChanged($oUser)
    {
        $savedCountryISO    = oxRegistry::getSession()->getVariable('sCountryISO');
        $sCurrentCountryISO = $oUser->resolveCountry();
        if ($savedCountryISO && $savedCountryISO !== $sCurrentCountryISO) {
            return true;
        }

        return false;
    }

    /**
     * Used to refresh KP widget when there was new session started
     * Compares KP widget client_token ($requestClientToken) with current client_token stored in the session
     * @param $requestClientToken
     * @return bool
     */
    public function validateClientToken($requestClientToken)
    {
        $oSession = oxRegistry::getSession();
        $aKPSessionData = $oSession->getVariable('klarna_session_data');
        if($requestClientToken !== $aKPSessionData['client_token']){
            $this->errors[] = "KL_INVALID_CLIENT_TOKEN";
            return false;
        }

        return true;
    }

    /**
     * Removes all session keys related to KlarnaPayment
     * Required before starting new KP session
     */
    static function cleanUpSession()
    {
        $oSession = oxRegistry::getSession();
        foreach (self::$aSessionKeys as $key) {
            $oSession->deleteVariable($key);
        }
    }

    /**
     * Checks if the currency match to selected country
     * @return void
     * @throws oxSystemComponentException
     */
    public function validateCountryAndCurrency()
    {
        $sCountryISO = KlarnaUtils::getCountryISO($this->oUser->getFieldData('oxcountryid'));
        if (!in_array($sCountryISO, KlarnaConsts::getKlarnaCoreCountries())) {
            $this->addErrorMessage('KL_KP_NOT_KLARNA_CORE_COUNTRY');
        }

        if (!$this->currencyToCountryMatch) {
            $this->addErrorMessage('KL_KP_CURRENCY_DONT_MATCH');
        }
    }

    /**
     * Checks if specific fields in billing and shipping address have the same values
     */
    public function validateKlarnaUserData()
    {
        $fieldNamesToCheck = array('country', 'given_name', 'family_name');
        foreach ($fieldNamesToCheck as $fName) {
            if ($this->_aUserData['billing_address'][$fName] !== $this->_aUserData['shipping_address'][$fName]) {
                $this->addErrorMessage('KL_KP_MATCH_ERROR');
                break;
            }
        }

        if ($this->_aUserData['billing_address']['street_address2']) {       // oxid fieldName invadr[oxuser__oxcompany]
            $this->addErrorMessage('KP_NOT_AVAILABLE_FOR_COMPANIES');
        }
    }

    /** Validates authorization token */
    public function validateToken()
    {
        if (!$this->isTokenValid()) {
            $this->addErrorMessage('KL_KP_INVALID_TOKEN');
        }
    }

    /**
     * Validates KP order (this)
     * @return bool
     * @throws oxSystemComponentException
     */
    public function validateOrder()
    {
        $this->errors = array();

        if ($this->isOrderStateChanged() || $this->paymentChanged) {
            $this->addErrorMessage('KL_KP_ORDER_DATA_CHANGED');
        }

        $this->validateToken();
        $this->validateKlarnaUserData();
        $this->validateCountryAndCurrency();

        if ($this->errors) {
            return false;
        }

        return true;
    }

    /**
     * Checks if KP session life time is not longer than 24h
     * @return bool
     */
    protected function validateSessionTimeout()
    {
        $this->sessionValid = true;
        $sessionData = oxRegistry::getSession()->getVariable('klarna_session_data');
        $tStamp = oxRegistry::getSession()->getVariable('sSessionTimeStamp');

        // skip if there is no session initialize
        if(!$sessionData)
            return true;

        if ($tStamp) {
            $dt = new DateTime();

            if ($dt->getTimestamp() - $tStamp < $this->sessionTimeout)      // 86400s = 24h

                return true;
        }
        $this->sessionValid = false;

        return false;

    }

    /** Public getter */
    public function isSessionValid()
    {
        return $this->sessionValid;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return (bool)$this->errors;
    }

    /** Adds Error message in current language
     * @param $translationKey string message key
     */
    public function addErrorMessage($translationKey)
    {
        $message        = oxRegistry::getLang()->translateString($translationKey);
        $this->errors[] = $message;
    }

    /** Passes internal errors to oxid in order to display theme to the user */
    public function displayErrors()
    {
        foreach ($this->errors as $message) {
            oxRegistry::get("oxUtilsView")->addErrorToDisplay($message);
        }
    }

    /**
     * Used to mark authorized orders in sofort methods
     * @return bool
     */
    public function requiresFinalization()
    {
        return oxRegistry::getSession()->getVariable('finalizeRequired');
    }

    /**
     * Compares order data checksums
     * If there is something to update it is added to $this->aUpdateData array
     *
     * @throws ReflectionException
     */
    public function checksumCheck()
    {
        $this->fetchCheckSums();
        foreach ($this->getPrivateProperties() as $oProperty) {
            $sPropertyName   = $oProperty->getName();
            $currentCheckSum = md5(json_encode($this->{$sPropertyName}));
            if ($this->checkSums[$sPropertyName] !== $currentCheckSum) {

                if ($sPropertyName !== '_sPaymentMethod') {
                    $this->aUpdateData = array_merge($this->aUpdateData, $this->{$sPropertyName});

                } else if($this->action !== 'addUserData') { // update selected KP method
                    $this->paymentChanged = true;
                    $this->setCheckSum('_sPaymentMethod', md5(json_encode($this->{$sPropertyName})));
                }
            }
        }
    }

    /**
     * Saves order checksums user and order data
     * KP method is saved earlier at the end of the constructor method (checkSmsCheck)
     * @param $splitedUpdateData
     * @throws ReflectionException
     */
    public function saveCheckSums($splitedUpdateData)
    {
        $aOrderCheckSums = $this->fetchCheckSums();
        if (!$aOrderCheckSums) {
            $aOrderCheckSums = array();
        }

        foreach ($this->getPrivateProperties() as $oProperty) {
            $sPropertyName                   = $oProperty->getName();
            if( $sPropertyName === '_aUserData' && $splitedUpdateData['userData'] ){
                $aOrderCheckSums[$sPropertyName] = md5(json_encode($splitedUpdateData['userData']));
                continue;
            }

            if( $sPropertyName === '_aOrderLines' && $splitedUpdateData['orderData'] ){
                $aOrderCheckSums[$sPropertyName] = md5(json_encode($splitedUpdateData['orderData']));
                continue;
            }
        }
        oxRegistry::getSession()->setVariable('kpCheckSums', $aOrderCheckSums);
    }

    /** Gets error messages array */
    public function getError()
    {
        return $this->errors;
    }

    /**
     * Retrieve checksums from session
     * @return array
     */
    public function fetchCheckSums()
    {
        $checkSums = oxRegistry::getSession()->getVariable('kpCheckSums');
        if(!$checkSums){
            $checkSums = array('_aOrderLines' => false, '_aUserData' => false, '_sPaymentMethod' => false);
        }

        return $this->checkSums = $checkSums;
    }

    /**
     * @param $propertyName
     * @param $value
     */
    public function setCheckSum($propertyName, $value)
    {
        $checkSums = $this->fetchCheckSums();
        $checkSums[$propertyName] = $value;
        oxRegistry::getSession()->setVariable('kpCheckSums', $checkSums);
    }
}