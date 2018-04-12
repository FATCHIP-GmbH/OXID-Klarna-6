<?php

namespace TopConcepts\Klarna\Controllers;


use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Models\KlarnaPayment as KlarnaPaymentModel;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Models\KlarnaUser;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

class KlarnaPaymentController extends KlarnaPaymentController_parent
{
    public $loadKlarnaPaymentWidget = true;

    /**
     * @var array of available payment methods
     * Added for performance optimization
     */
    private $aPaymentList;

    /**
     * @var \TopConcepts\Klarna\Core\KlarnaPayment
     */
    private $oKlarnaPayment;

    /** @var Request */
    private $oRequest;

    /**
     *
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function init()
    {
        $this->oRequest = Registry::get(Request::class);

        if ($this->getUser()) {
            $sCountryISO = KlarnaUtils::getCountryISO($this->getUser()->getFieldData('oxcountryid'));
        }
        if (Registry::getSession()->getVariable('amazonOrderReferenceId')) {
            $this->loadKlarnaPaymentWidget = false;
        }

        if (
            KlarnaUtils::isKlarnaCheckoutEnabled() &&
            (KlarnaUtils::isCountryActiveInKlarnaCheckout(Registry::getSession()->getVariable('sCountryISO')) ||
             KlarnaUtils::isCountryActiveInKlarnaCheckout($sCountryISO)) &&
            !Registry::getSession()->getVariable('amazonOrderReferenceId') &&
            !$this->oRequest->getRequestEscapedParameter('non_kco_global_country')
        ) {
            $redirectUrl = Registry::getConfig()->getShopSecureHomeURL() . 'cl=KlarnaExpress';
            Registry::getUtils()->redirect($redirectUrl, false, 302);
        }

        parent::init();
    }

    /**
     * @return string
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     * @throws \ReflectionException
     * @throws \oxSystemComponentException
     */
    public function render()
    {
        $sTplName = parent::render();
        if ($this->countKPMethods() && $this->loadKlarnaPaymentWidget) {

            /** @var User|KlarnaUser $oUser */
            $oUser    = $this->getUser();
            $oSession = Registry::getSession();
            $oBasket  = $oSession->getBasket();

            if (KlarnaPayment::countryWasChanged($oUser)) {
                KlarnaPayment::cleanUpSession();
            }

            $this->oKlarnaPayment = new KlarnaPayment($oBasket, $oUser);

            if (!$this->oKlarnaPayment->isSessionValid()) {
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

                    // update KP options, remove unavailable klarna payments
                    $this->removeUnavailableKP($sessionData);

                } catch (StandardException $e) {
                    $e->debugOut();

                    return $sTplName;
                }
            } else {
                // show only first
                $this->addTplParam("kpError", $errors[0]);
            }

            $from   = '/' . preg_quote('-', '/') . '/';
            $locale = preg_replace($from, '_', strtolower(KlarnaConsts::getLocale(true)), 1);

            $this->addTplParam("sLocale", $locale);
        }

        if ($this->countKPMethods() === 0) {
            $this->loadKlarnaPaymentWidget = false;
        }

        return $sTplName;
    }

    /**
     * @return array
     */
    public function getPaymentList()
    {
        /**
         * This method is called in the template
         * We will remove some methods in the render method after getting payment_method_categories
         * This will pass modified list to the tempalte
         */
        if ($this->aPaymentList) {
            return $this->aPaymentList;
        }

        $this->aPaymentList = parent::getPaymentList();
        if (KlarnaUtils::isKlarnaPaymentsEnabled()) {
            // remove needless methods from the list
            unset($this->aPaymentList[KlarnaPaymentModel::KLARNA_PAYMENT_CHECKOUT_ID]);

        } else {
            $klarnaPayments = KlarnaPaymentModel::getKlarnaPaymentsIds();
            foreach ($klarnaPayments as $paymentId) {
                unset($this->aPaymentList[$paymentId]);
            }
        }

        return $this->aPaymentList;
    }

    /** Return shipping sets for KCO
     * @param $oUser
     * @return
     */
    public function getCheckoutShippingSets($oUser)
    {
        $sActShipSet = Registry::get(Request::class)->getRequestEscapedParameter('sShipSet');
        if (!$sActShipSet) {
            $sActShipSet = Registry::getSession()->getVariable('sShipSet');
        }

        $oBasket = Registry::getSession()->getBasket();
        list($aAllSets,) =
            Registry::get(DeliverySetList::class)->getDeliverySetData($sActShipSet, $oUser, $oBasket);

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
     * @return null
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     * @throws \ReflectionException
     * @throws \oxSystemComponentException
     */
    public function validatepayment()
    {
        if (!$sControllerName = parent::validatepayment())
            return null;

        if (KlarnaUtils::isKlarnaPaymentsEnabled()) {
            $oSession              = Registry::getSession();
            $oBasket               = $oSession->getBasket();
            $sPaymentId            = $oBasket->getPaymentId();
            $aKlarnaPaymentMethods = KlarnaPaymentModel::getKlarnaPaymentsIds('KP');

            if (in_array($sPaymentId, $aKlarnaPaymentMethods)) {

                if ($this->oRequest->getRequestEscapedParameter('finalizeRequired')) {
                    Registry::getSession()->setVariable('finalizeRequired', true);
                }

                if ($sAuthToken = $this->oRequest->getRequestEscapedParameter('sAuthToken')) {
                    Registry::getSession()->setVariable('sAuthToken', $sAuthToken);
                    $dt = new \DateTime();
                    Registry::getSession()->setVariable('sTokenTimeStamp', $dt->getTimestamp());

                }

                $this->oKlarnaPayment = new KlarnaPayment($oBasket, $this->getUser());

                if (!$this->oKlarnaPayment->isAuthorized() || $this->oKlarnaPayment->isError()) {
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
     * Render only methods recieved in the create session response
     *
     * @param $sessionData array create session response
     */
    protected function removeUnavailableKP($sessionData)
    {
        $klarnaIds = array();
        if ($sessionData['payment_method_categories']) {
            $klarnaIds = array_map(function ($element) {
                return $element['identifier'];
            },
                $sessionData['payment_method_categories']
            );
        }

        foreach ($this->aPaymentList as $payid => $oxPayment) {
            $klarnaName = $oxPayment->getPaymentCategoryName();
            if ($klarnaName && !in_array($klarnaName, $klarnaIds)) {
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
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
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
        foreach ($this->aPaymentList as $payid => $oxPayment) {
            if ($oxPayment->isKPPayment()) {
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