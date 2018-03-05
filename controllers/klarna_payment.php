<?php

class klarna_payment extends klarna_payment_parent
{
    public $loadKlarnaPaymentWidget = true;

    /**
     * @var array of available payment methods
     * Added for performance optimization
     */
    private $aPaymentList;

    /**
     * @var KlarnaPayment object
     */
    private $oKlarnaPayment;

    /**
     *
     * @throws oxSystemComponentException
     */
    public function init()
    {
        if ($this->getUser()) {
            $sCountryISO = KlarnaUtils::getCountryISO($this->getUser()->getFieldData('oxcountryid'));
        }
        if (oxRegistry::getSession()->getVariable('amazonOrderReferenceId')) {
            $this->loadKlarnaPaymentWidget = false;
        }

        if (
            KlarnaUtils::isKlarnaCheckoutEnabled() &&
            (KlarnaUtils::isCountryActiveInKlarnaCheckout(oxRegistry::getSession()->getVariable('sCountryISO')) ||
             KlarnaUtils::isCountryActiveInKlarnaCheckout($sCountryISO)) &&
            !oxRegistry::getSession()->getVariable('amazonOrderReferenceId') &&
            !oxRegistry::getConfig()->getRequestParameter('non_kco_global_country')
        ) {
            $redirectUrl = oxRegistry::getConfig()->getShopSecureHomeURL() . 'cl=klarna_express';
            oxRegistry::getUtils()->redirect($redirectUrl, false, 302);
        }

        parent::init();
    }

    /**
     * @return string
     * @throws ReflectionException
     * @throws oxSystemComponentException
     */
    public function render()
    {
        $sTplName = parent::render();
        $oUser    = $this->getUser();
        if ($this->countKPMethods()) {

            if ($this->loadKlarnaPaymentWidget) {
                $oSession = oxRegistry::getSession();
                $oBasket  = $oSession->getBasket();

                if (KlarnaPayment::countryWasChanged($oUser)) {
                    KlarnaPayment::cleanUpSession();
                }

                $this->oKlarnaPayment = oxNew('KlarnaPayment', $oBasket, $oUser);

                if(!$this->oKlarnaPayment->isSessionValid()){
                    KlarnaPayment::cleanUpSession();
                }

                $this->oKlarnaPayment->isOrderStateChanged();
                $errors = $this->oKlarnaPayment->getError();
                if (!$errors) {
                    try {
                        $this->getKlarnaClient()
                            ->initOrder($this->oKlarnaPayment)
                            ->createOrUpdateSession();

                        $sessionData = $oSession->getVariable('klarna_session_data');
                        $this->addTplParam("client_token", $sessionData['client_token']);

                    } catch (oxException $e) {
                        $e->debugOut();

                        return $sTplName;
                    }
                } else {
                    // show only first
                    $this->addTplParam("kpError", $errors[0]);
                }

                // remove unavailable klarna payments
                $this->removeUnavailableKP($sessionData);

                $this->addTplParam("sLocale", strtolower(KlarnaConsts::getLocale()));
            }
        }

        return $sTplName;
    }

    /**
     * @return array
     */
    public function getPaymentList()
    {
        $this->aPaymentList = parent::getPaymentList();

        if (KlarnaUtils::isKlarnaPaymentsEnabled()) {
            // remove needless methods from the list
            unset($this->aPaymentList[Klarna_oxPayment::KLARNA_PAYMENT_CHECKOUT_ID]);

        } else {
            $klarnaPayments = klarna_oxpayment::getKlarnaPaymentsIds();
            foreach ($klarnaPayments as $paymentId) {
                unset($this->aPaymentList[$paymentId]);
            }
        }

        return $this->aPaymentList;
    }

    /** Return shipping sets for KCO */
    public function getCheckoutShippingSets()
    {
        $sActShipSet = oxRegistry::getConfig()->getRequestParameter('sShipSet');
        if (!$sActShipSet) {
            $sActShipSet = oxRegistry::getSession()->getVariable('sShipSet');
        }

        $oBasket = oxRegistry::getSession()->getBasket();
        list($aAllSets, ) =
            oxRegistry::get("oxDeliverySetList")->getDeliverySetData($sActShipSet, $this->getUser(), $oBasket);
        return $aAllSets;
    }


    /**
     * @return KlarnaPaymentsClient|KlarnaClientBase
     */
    public function getKlarnaClient()
    {
        return KlarnaPaymentsClient::getInstance();
    }

    /** Saves Klarna Payment authorization_token or deleting an existing authorization
     * if payment method was changed to not KP method
     */
    public function validatepayment()
    {

        if (!$sControllerName = parent::validatepayment())
            return null;

        if (KlarnaUtils::isKlarnaPaymentsEnabled()) {
            $oSession = oxRegistry::getSession();
            $oBasket               = $oSession->getBasket();
            $sPaymentId            = $oBasket->getPaymentId();
            $aKlarnaPaymentMethods = Klarna_oxPayment::getKlarnaPaymentsIds('KP');

            if (in_array($sPaymentId, $aKlarnaPaymentMethods)) {

                if (oxRegistry::getConfig()->getRequestParameter('finalizeRequired')) {
                    oxRegistry::getSession()->setVariable('finalizeRequired', true);
                }

                if ($sAuthToken = oxRegistry::getConfig()->getRequestParameter('sAuthToken')) {
                    oxRegistry::getSession()->setVariable('sAuthToken', $sAuthToken);
                    $dt = new DateTime();
                    oxRegistry::getSession()->setVariable('sTokenTimeStamp', $dt->getTimestamp());

                }

                $this->oKlarnaPayment = oxNew('KlarnaPayment', $oBasket, $this->getUser());

                if(!$this->oKlarnaPayment->isAuthorized() || $this->oKlarnaPayment->isError()){
                    return null;
                }

            } else {
                KlarnaPayment::cleanUpSession();
            }
        }

        return $sControllerName;
    }

    /**
     * Removes unavailable Klarna Payments Categories
     * We will render only methods recieved in the create session response
     *
     * @param $sessionData array create session response
     */
    protected function removeUnavailableKP($sessionData)
    {
        $klarnaIds = array_map(function($element){
                return $element['identifier'];
            },
            $sessionData['payment_method_categories']
        );

        foreach($this->aPaymentList as $payid => $oxPayment ){
            $klarnaName = $oxPayment->getPaymentCategoryName();
            if($klarnaName && !in_array($klarnaName, $klarnaIds)){
                unset($this->aPaymentList[$payid]);
            }
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    public function removeKlarnaPrefix($name)
    {
        return str_replace('Klarna ', '', $name);
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
     * @return int
     */
    protected function countKPMethods()
    {
        $counter = 0;
        foreach($this->aPaymentList as $payid => $oxPayment ){
            if($oxPayment->isKPPayment()){
                $counter++;
            }
        }

        return $counter;
    }

    /**
     * @return int
     */
    public function includeKPWidget()
    {
        return $this->countKPMethods();
    }
}