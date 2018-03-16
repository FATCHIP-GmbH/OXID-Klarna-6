<?php

namespace TopConcepts\Klarna\Controllers;


use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaFormatter;
use TopConcepts\Klarna\Core\KlarnaOrder;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Exception\KlarnaClientException;
use TopConcepts\Klarna\Models\KlarnaUser;
use OxidEsales\Eshop\Application\Controller\BasketController;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

class KlarnaAjaxController extends FrontendController
{

    /**
     * @var string
     */
    protected $_sThisTemplate = null;

    /** @var User|KlarnaUser */
    protected $_oUser;


    /** @var array */
    protected $_aOrderData;

    /** @var \Exception[] */
    protected $_aErrors;

    /**
     * @return string|void
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function init()
    {
        $oSession = Registry::getSession();
        $oBasket  = $oSession->getBasket();

        if ($oBasket->getPaymentId() === 'klarna_checkout') {
            $oClient = $this->getKlarnaCheckoutClient();
            try {
                $this->_aOrderData = $oClient->getOrder();
            } catch (KlarnaClientException $oEx) {
                if ($oEx->getCode() == 401 || $oEx->getCode() == 404) {
                    // create new order. restart session.
                    return $this->jsonResponse(__FUNCTION__, 'restart needed', $data = null);
                }
            }

            $this->_initUser();
            $this->updateUserObject();

        } else {
            Registry::getUtils()->showMessageAndExit('Invalid payment ID');
        }

        parent::init();
    }

    /**
     * Updates Klarna API
     * @return null
     */
    public function render()
    {
        // request update klarna order if no errors
        if (!$this->_aErrors) {
            try {
                $this->updateKlarnaOrder();
            } catch (StandardException $e) {
                $e->debugOut();
            }
        }

        return parent::render();

    }

    /**
     * @return KlarnaCheckoutClient|KlarnaClientBase
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function getKlarnaCheckoutClient()
    {
        return KlarnaCheckoutClient::getInstance();
    }

    /**
     * Initialize oxUser object and get order data from Klarna
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \oxSystemComponentException
     */
    protected function _initUser()
    {
        if ($this->_oUser = $this->getUser()) {
            if ($this->getViewConfig()->isUserLoggedIn()) {
                $this->_oUser->kl_setType(KlarnaUser::LOGGED_IN);
            } else {
                $this->_oUser->kl_setType(KlarnaUser::NOT_REGISTERED);
            }
        } else {
            $this->_oUser                      = KlarnaUtils::getFakeUser($this->_aOrderData['billing_address']['email']);
            $oCountry                          = oxNew(Country::class);
            $this->_oUser->oxuser__oxcountryid = new Field(
                $oCountry->getIdByCode(
                    strtoupper($this->_aOrderData['purchase_country'])
                ),
                Field::T_RAW
            );
        }
    }

    /**
     * Update User object
     */
    protected function updateUserObject()
    {
        if ($this->_aOrderData['billing_address'] !== $this->_aOrderData['shipping_address'])
            $this->_oUser->updateDeliveryAddress(KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'shipping_address'));
        else
            $this->_oUser->clearDeliveryAddress();

        $this->_oUser->assign(KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'billing_address'));
        if (in_array($this->_oUser->kl_getType(), array(KlarnaUser::LOGGED_IN, KlarnaUser::NOT_REGISTERED))) {
            $this->_oUser->save();
        }
        if (isset($this->_aOrderData['customer']['date_of_birth'])) {
            $this->_oUser->oxuser__oxbirthdate = new Field($this->_aOrderData['customer']['date_of_birth']);
        }
    }

    /**
     * Sends update request to checkout API
     * @return array order data
     * @throws StandardException
     * @internal param oxBasket $oBasket
     * @internal param oxUser $oUser
     */
    protected function updateKlarnaOrder()
    {
        $oSession     = $this->getSession();
        $oBasket      = $oSession->getBasket();
        $oKlarnaOrder = new KlarnaOrder($oBasket, $this->_oUser);
        $oClient      = $this->getKlarnaCheckoutClient();
        $aOrderData   = $oKlarnaOrder->getOrderData();

        return $oClient->createOrUpdateOrder(
            json_encode($aOrderData)
        );
    }

    public function setKlarnaDeliveryAddress()
    {
        $oxidAddress = Registry::get(Request::class)->getRequestEscpaedParameter('klarna_address_id');
        Registry::getSession()->setVariable('deladrid', $oxidAddress);
        Registry::getSession()->setVariable('blshowshipaddress', 1);
        Registry::getSession()->deleteVariable('klarna_checkout_order_id');

        $this->_sThisTemplate = null;
    }

    /**
     * Add voucher
     *
     * @see Basket::addVoucher
     */
    public function addVoucher()
    {
        Registry::get(BasketController::class)->addVoucher();
        $this->updateVouchers();
    }

    /**
     * Remove voucher
     *
     * @see Basket::removeVoucher
     */
    public function removeVoucher()
    {
        Registry::get(BasketController::class)->removeVoucher();
        $this->updateVouchers();
    }

    /**
     * Sets partial templates to render
     * Rendered content will be return in json format in ajax response
     * and will replace document elements. This way vouchers widget will be updated
     */
    public function updateVouchers()
    {
        $this->_sThisTemplate = 'kl_klarna_json.tpl';
        $includes             = array(
            'vouchers' => 'kl_klarna_checkout_voucher_data.tpl',
            'error'    => 'kl_klarna_checkout_voucher_errors.tpl',
        );
        $this->addTplParam('aIncludes', $includes);
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
        return Registry::getUtils()->showMessageAndExit(json_encode(array(
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

}