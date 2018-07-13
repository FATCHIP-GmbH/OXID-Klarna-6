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


use OxidEsales\Eshop\Application\Model\Order;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaFormatter;
use TopConcepts\Klarna\Core\KlarnaOrder;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Model\KlarnaUser;
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
     * @throws StandardException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     * @throws \oxSystemComponentException
     */
    public function init()
    {
        if (!KlarnaUtils::is_ajax()){
            $this->jsonResponse(__METHOD__, 'Invalid request');
        }

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

            if ($this->_aOrderData['status'] === 'checkout_complete'){
                $this->jsonResponse('ajax', 'read_only');
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
     * Update User object
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
        }else {
            $this->getSession()->setVariable('oFakeKlarnaUser', $this->_oUser);
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
        $oKlarnaOrder = oxNew(KlarnaOrder::class, $oBasket, $this->_oUser);
        $oClient      = $this->getKlarnaCheckoutClient();
        $aOrderData   = $oKlarnaOrder->getOrderData();

        return $oClient->createOrUpdateOrder(
            json_encode($aOrderData)
        );
    }

    public function setKlarnaDeliveryAddress()
    {
        $oxidAddress = Registry::get(Request::class)->getRequestEscapedParameter('klarna_address_id');
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
        $this->_sThisTemplate = 'tcklarna_json.tpl';
        $includes             = array(
            'vouchers' => 'tcklarna_checkout_voucher_data.tpl',
            'error'    => 'tcklarna_checkout_voucher_errors.tpl',
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
    protected function jsonResponse($action, $status, $data = null)
    {
        return Registry::getUtils()->showMessageAndExit(json_encode(array(
            'action' => $action,
            'status' => $status,
            'data'   => $data,
        )));
    }

    /**
     * Gets data from request body
     * @codeCoverageIgnore
     * @return array
     */
    protected function getJsonRequest()
    {
        $requestBody = file_get_contents('php://input');

        return json_decode($requestBody, true);
    }
}