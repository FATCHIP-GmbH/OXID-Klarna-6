<?php
namespace Klarna\Klarna\Controllers;

use Klarna\Klarna\Core\KlarnaPaymentsClient;
use Klarna\Klarna\Models\KlarnaPayment as KlarnaPaymentModel;
use Klarna\Klarna\Core\KlarnaConsts;
use Klarna\Klarna\Core\KlarnaPayment;
use Klarna\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry as oxRegistry;
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
     * @var \Klarna\Klarna\Core\KlarnaPayment
     */
    private $oKlarnaPayment;

    /** @var Request */
    private $oRequest;

    /**
     *
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function init()
    {
        $this->oRequest = oxRegistry::get(Request::class);

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
            !$this->oRequest->getRequestParameter('non_kco_global_country')
        ) {
            $redirectUrl = oxRegistry::getConfig()->getShopSecureHomeURL() . 'cl=KlarnaExpress';
            oxRegistry::getUtils()->redirect($redirectUrl, false, 302);
        }

        parent::init();
    }

    /**
     * @return string
     * @throws \oxSystemComponentException
     */
    public function render()
    {
        $sTplName = parent::render();
        if ($this->countKPMethods() && $this->loadKlarnaPaymentWidget) {

            $oUser    = $this->getUser();
            $oSession = oxRegistry::getSession();
            $oBasket  = $oSession->getBasket();

            if (KlarnaPayment::countryWasChanged($oUser)) {
                KlarnaPayment::cleanUpSession();
            }

            $this->oKlarnaPayment = new KlarnaPayment($oBasket, $oUser);

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
            $this->addTplParam("sLocale", strtolower(KlarnaConsts::getLocale()));
        }

        if($this->countKPMethods() === 0){
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
        if($this->aPaymentList) {
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

    /** Return shipping sets for KCO */
    public function getCheckoutShippingSets()
    {
        $sActShipSet = $this->oRequest->getRequestParameter('sShipSet');
        if (!$sActShipSet) {
            $sActShipSet = oxRegistry::getSession()->getVariable('sShipSet');
        }

        $oBasket = oxRegistry::getSession()->getBasket();
        list($aAllSets, ) =
            oxRegistry::get(DeliverySetList::class)->getDeliverySetData($sActShipSet, $this->getUser(), $oBasket);
        return $aAllSets;
    }


    /**
     * @return KlarnaPaymentsClient|KlarnaClientBase
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
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
            $aKlarnaPaymentMethods = KlarnaPaymentModel::getKlarnaPaymentsIds('KP');

            if (in_array($sPaymentId, $aKlarnaPaymentMethods)) {

                if ($this->oRequest->getRequestParameter('finalizeRequired')) {
                    oxRegistry::getSession()->setVariable('finalizeRequired', true);
                }

                if ($sAuthToken = $this->oRequest->getRequestParameter('sAuthToken')) {
                    oxRegistry::getSession()->setVariable('sAuthToken', $sAuthToken);
                    $dt = new \DateTime();
                    oxRegistry::getSession()->setVariable('sTokenTimeStamp', $dt->getTimestamp());

                }

                $this->oKlarnaPayment = new KlarnaPayment($oBasket, $this->getUser());

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
     * Render only methods recieved in the create session response
     *
     * @param $sessionData array create session response
     */
    protected function removeUnavailableKP($sessionData)
    {
        $klarnaIds = array();
        if($sessionData['payment_method_categories']) {
            $klarnaIds = array_map(function ($element) {
                return $element['identifier'];
            },
                $sessionData['payment_method_categories']
            );
        }

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
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
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