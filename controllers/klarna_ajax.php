<?php

class klarna_ajax extends oxUBase
{

    /**
     * @var string
     */
    protected $_sThisTemplate = null;

    /** @var klarna_oxuser|oxuser */
    protected $_oUser;


    /** @var array */
    protected $_aOrderData;

    /** @var oxException */
    protected $_aErrors;

    /**
     * @return string|void
     * @throws oxConnectionException
     * @throws oxException
     * @throws oxSystemComponentException
     */
    public function init()
    {
        $oSession = oxRegistry::getSession();
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
            oxRegistry::getUtils()->showMessageAndExit('Invalid payment ID');
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
            } catch (oxException $e) {
                $e->debugOut();
            }
        }

        return parent::render();

    }

    /**
     * @return KlarnaCheckoutClient|KlarnaClientBase
     */
    public function getKlarnaCheckoutClient()
    {
        return KlarnaCheckoutClient::getInstance();
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
     * @throws oxException
     * @throws oxSystemComponentException
     */
    protected function updateUserObject()
    {
        if ($this->_aOrderData['billing_address'] !== $this->_aOrderData['shipping_address'])
            $this->_oUser->updateDeliveryAddress(KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'shipping_address'));
        else
            $this->_oUser->clearDeliveryAddress();

        $this->_oUser->assign(KlarnaFormatter::klarnaToOxidAddress($this->_aOrderData, 'billing_address'));
        if (in_array($this->_oUser->kl_getType(), array(Klarna_oxUser::LOGGED_IN, Klarna_oxUser::NOT_REGISTERED))) {
            $this->_oUser->save();
        }
        if (isset($this->_aOrderData['customer']['date_of_birth'])) {
            $this->_oUser->oxuser__oxbirthdate = new oxField($this->_aOrderData['customer']['date_of_birth']);
        }
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

        return $oClient->createOrUpdateOrder(
            json_encode($aOrderData)
        );
    }

    public function setKlarnaDeliveryAddress()
    {
        $oxidAddress = oxRegistry::getConfig()->getRequestParameter('klarna_address_id');
        oxRegistry::getSession()->setVariable('deladrid', $oxidAddress);
        oxRegistry::getSession()->setVariable('blshowshipaddress', 1);
        oxRegistry::getSession()->deleteVariable('klarna_checkout_order_id');

        $this->_sThisTemplate = null;
    }

    /**
     * Add voucher
     *
     * @see Basket::addVoucher
     */
    public function addVoucher()
    {
        oxRegistry::get('basket')->addVoucher();
        $this->updateVouchers();
    }

    /**
     * Remove voucher
     *
     * @see Basket::removeVoucher
     */
    public function removeVoucher()
    {
        oxRegistry::get('basket')->removeVoucher();
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

}