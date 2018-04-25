<?php

namespace TopConcepts\Klarna\Controllers\Admin;


use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Exception\KlarnaClientException;
use TopConcepts\Klarna\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Exception\KlarnaWrongCredentialsException;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

class KlarnaOrderMain extends KlarnaOrderMain_parent
{
    protected $klarnaOrderData;
    protected $client;

    /**
     * @return mixed
     */
    public function init()
    {
        $result = parent::init();

        $oOrder = $this->getEditObject();
        if ($this->isKlarnaOrder() && $oOrder->getFieldData('oxstorno') == 0) {
            //check if credentials are valid
            if (!$this->isCredentialsValid()) {
                $oOrder->oxorder__klsync = new Field(0);
                $oOrder->save();

                return $result;
            }

            try {
                $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->getViewDataElement('sCountryISO'));
            } catch (KlarnaWrongCredentialsException $e) {
                $this->addTplParam('sErrorMessage', Registry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST"));

                $oOrder->oxorder__klsync = new Field(0);
                $oOrder->save();

                return $result;
            } catch (KlarnaOrderNotFoundException $e) {
                $this->addTplParam('sErrorMessage', Registry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND"));

                $oOrder->oxorder__klsync = new Field(0);
                $oOrder->save();

                return $result;
            } catch (StandardException $e) {
                $this->addTplParam('sErrorMessage', $e->getMessage());

                $oOrder->oxorder__klsync = new Field(0);
                $oOrder->save();

                return $result;
            }

            $this->setInitSyncStatus($oOrder);
        }

        return $result;
    }

    /**
     *
     */
    public function render()
    {
        $parent = parent::render();

        $this->addTplParam('isKlarnaOrder', $this->isKlarnaOrder());
        $oOrder = $this->getEditObject(true);

        if ($this->getViewDataElement('isKlarnaOrder') && $oOrder->getFieldData('oxstorno') == 0) {

            //check if credentials are valid
            if (!$this->isCredentialsValid()) {
                $this->addTplParam('sWarningMessage', sprintf(Registry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
                    $this->getViewDataElement('sMid'),
                    $this->getViewDataElement('sCountryISO'),
                    $this->getViewDataElement('currentMid')
                ));

                return $parent;
            }

            if (Registry::get(Request::class)->getRequestEscapedParameter('fnc')) {

                try {
                    $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->getViewDataElement('sCountryISO'));
                } catch (StandardException $e) {
                    $this->addTplParam('sErrorMessage', $e->getMessage());

                    $oOrder->oxorder__klsync = new Field(0);
                    $oOrder->save();

                    return $parent;
                }
            }

            $this->handleResponseOrderData($oOrder);
        }

        return $parent;
    }

    /**
     * Sends order. Captures klarna order.
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function sendorder()
    {
        $cancelled = $this->getEditObject()->getFieldData('oxstorno') == 1;

        $result = parent::sendorder();

        if (!$this->isKlarnaOrder()) {
            return $result;
        }

        //force reload
        $oOrder = $this->getEditObject(true);
        $inSync = $oOrder->getFieldData('klsync') == 1;

        if (!$cancelled && $inSync && $this->klarnaOrderData['remaining_authorized_amount'] != 0) {
            $orderLang   = (int)$oOrder->getFieldData('oxlang');
            $orderLines  = $oOrder->getNewOrderLinesAndTotals($orderLang, true);
            $data        = array(
                'captured_amount' => KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100),
                'order_lines'     => $orderLines['order_lines'],
            );
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));

            try {
                $this->addTplParam('sErrorMessage', '');

                $client   = $this->getKlarnaMgmtClient($sCountryISO);
                $response = $client->captureOrder($data, $oOrder->getFieldData('klorderid'));
            } catch (StandardException $e) {
                $this->addTplParam('sErrorMessage', $e->getMessage());

                return $result;
            }
            if ($response === true) {
                $this->addTplParam('sMessage', Registry::getLang()->translateString("KLARNA_CAPTURE_SUCCESSFULL"));
            }
            $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->getViewDataElement('sCountryISO'));

            return $result;

        }
        $this->addTplParam('sErrorMessage', Registry::getLang()->translateString("KL_CAPUTRE_FAIL_ORDER_CANCELLED"));

        return $result;
    }


    /**
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function save()
    {
        $oldDiscountVal = $this->getEditObject()->getFieldData('oxdiscount');
        $oldOrderNum    = $this->getEditObject()->getFieldData('oxordernr');
        parent::save();

        if (!$this->isKlarnaOrder()) {
            return;
        }

        //force reload
        $oOrder    = $this->getEditObject(true);
        $inSync    = $oOrder->getFieldData('klsync') == 1;
        $cancelled = $oOrder->getFieldData('oxstorno') == 1;
        $oLang     = Registry::getLang();

        if ($cancelled || !$inSync) {
            return;
        }

        $orderLang       = (int)$oOrder->getFieldData('oxlang');
        $klorderid       = $oOrder->getFieldData('klorderid');
        $sCountryISO     = $oOrder->getFieldData('oxbillcountryid');
        $captured        = $this->klarnaOrderData['captured_amount'] > 0;
        $discountChanged = $this->discountChanged($oldDiscountVal);

        //new discount
        if ($discountChanged) {
            if ($captured) {
                $this->addTplParam('sErrorMessage', $oLang->translateString('KL_ORDER_UPDATE_CANT_BE_SENT_TO_KLARNA'));

                return;
            }

            Registry::getSession()->deleteVariable('Errors');
            $orderLines = $oOrder->getNewOrderLinesAndTotals($orderLang);
            $error      = $oOrder->updateKlarnaOrder($orderLines, $klorderid, $sCountryISO);
            if ($error) {
                $this->addTplParam('sErrorMessage', $error);
            }
        }

        $this->handleKlarnaUpdates($sCountryISO, $oldOrderNum, $klorderid, $oLang, $captured, $oOrder);
    }

    /**
     * @param $oldDiscountVal
     * @return bool
     */
    protected function discountChanged($oldDiscountVal)
    {
        $edit = Registry::get(Request::class)->getRequestEscapedParameter('editval');

        return $edit['oxorder__oxdiscount'] != $oldDiscountVal;
    }

    /**
     * @return bool
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function isCredentialsValid()
    {
        $this->addTplParam('sMid', $this->getEditObject()->getFieldData('klmerchantid'));
        $countryISO = KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'));
        $this->addTplParam('sCountryISO', $countryISO);
        $currentMid = KlarnaUtils::getAPICredentials($this->getViewDataElement('sCountryISO'));
        $this->addTplParam('currentMid', $currentMid['mid']);

        if (strstr($this->getViewDataElement('currentMid'), $this->getViewDataElement('sMid'))) {
            return true;
        }

        return false;
    }

    /**
     * Returns editable order object
     *
     * @param bool $reset
     * @return Order
     */
    public function getEditObject($reset = false)
    {
        $soxId = $this->getEditObjectId();
        if (($this->_oEditObject === null && isset($soxId) && $soxId != '-1') || $reset) {
            $this->_oEditObject = oxNew(Order::class);
            $this->_oEditObject->load($soxId);
        }

        return $this->_oEditObject;
    }

    /**
     * Method checks is order was made with Klarna module
     *
     * @return bool
     */
    public function isKlarnaOrder()
    {
        $blActive = false;

        if ($this->getEditObject() && stripos($this->getEditObject()->getFieldData('oxpaymenttype'), 'klarna_') !== false) {
            $blActive = true;
        }

        return $blActive;
    }

    /**
     * @param null $sCountryISO
     * @return mixed
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function retrieveKlarnaOrder($sCountryISO = null)
    {
        if (!$sCountryISO) {
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'));
        }
        $client = $this->getKlarnaMgmtClient($sCountryISO);

        return $client->getOrder($this->getEditObject()->getFieldData('klorderid'));
    }

    /**
     * @param $klarnaOrderData
     * @return bool
     */
    public function isCaptureInSync($klarnaOrderData)
    {
        if ($klarnaOrderData['status'] === 'PART_CAPTURED') {
            if ($this->getEditObject()->getFieldData('oxsenddate') != "-") {
                return true;
            }

            return false;
        }
        if ($klarnaOrderData['status'] === 'AUTHORIZED') {

            return true;
        }

        return true;
    }

    /**
     * @param $oOrder
     */
    protected function setInitSyncStatus($oOrder)
    {
        if (is_array($this->klarnaOrderData)) {
            $klarnaOrderTotalSum = KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100);

            if ($this->klarnaOrderData['order_amount'] != $klarnaOrderTotalSum ||
                ($this->klarnaOrderData['remaining_authorized_amount'] != $klarnaOrderTotalSum &&
                 $this->klarnaOrderData['remaining_authorized_amount'] != 0
                ) || !$this->isCaptureInSync($this->klarnaOrderData)
                || $this->klarnaOrderData['status'] === 'CANCELLED'
            ) {
                $oOrder->oxorder__klsync = new Field(0);
            } else {
                $oOrder->oxorder__klsync = new Field(1);
            }
            $oOrder->save();
        }
    }

    /**
     * @param $oOrder
     */
    protected function handleResponseOrderData($oOrder)
    {
        if (is_array($this->klarnaOrderData)) {
            $apiDisabled = Registry::getLang()->translateString("KL_NO_REQUESTS_WILL_BE_SENT");
            if ($this->klarnaOrderData['status'] === 'CANCELLED') {
                $oOrder->oxorder__klsync = new Field(0);

                $orderCancelled = Registry::getLang()->translateString("KLARNA_ORDER_IS_CANCELLED");
                $this->addTplParam('sWarningMessage', $orderCancelled . $apiDisabled);

            } else if ($this->klarnaOrderData['order_amount'] != KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100)
                       || !$this->isCaptureInSync($this->klarnaOrderData)) {
                $oOrder->oxorder__klsync = new Field(0);

                $orderNotInSync = Registry::getLang()->translateString("KLARNA_ORDER_NOT_IN_SYNC");
                $this->addTplParam('sWarningMessage', $orderNotInSync . $apiDisabled);

            } else {
                $oOrder->oxorder__klsync = new Field(1);
            }
            $oOrder->save();
        }
    }

    /**
     * @param $sCountryISO
     * @return \TopConcepts\Klarna\Core\KlarnaClientBase
     */
    protected function getKlarnaMgmtClient($sCountryISO)
    {
        return KlarnaOrderManagementClient::getInstance($sCountryISO);
    }

    /**
     * @param $sCountryISO
     * @param $edit
     * @param $oldOrderNum
     * @param $klorderid
     * @param $oLang
     * @param $captured
     * @param $oOrder
     */
    protected function handleKlarnaUpdates($sCountryISO, $oldOrderNum, $klorderid, $oLang, $captured, $oOrder)
    {
        $edit   = Registry::get(Request::class)->getRequestEscapedParameter('editval');
        $client = $this->getKlarnaMgmtClient($sCountryISO);
        //new order number
        if ((int)$edit['oxorder__oxordernr'] !== $oldOrderNum) {
            try {
                $client->sendOxidOrderNr((int)$edit['oxorder__oxordernr'], $klorderid);
            } catch (StandardException $e) {
                $this->addTplParam('sErrorMessage', $oLang->translateString('KL_ORDER_UPDATE_CANT_BE_SENT_TO_KLARNA'));
            }
        }
        //shipment tracking number
        if ($edit['oxorder__oxtrackcode'] && $captured) {
            $data       = [
                'shipping_info' => [
                    [
                        'tracking_number' => $edit['oxorder__oxtrackcode'],
                    ],
                ],
            ];
            $capture_id = $this->klarnaOrderData['captures'][0]['capture_id'];

            try {
                $client->addShippingToCapture($data, $klorderid, $capture_id);
            } catch (StandardException $e) {
                $this->addTplParam('sErrorMessage', $oLang->translateString('KL_ORDER_UPDATE_CANT_BE_SENT_TO_KLARNA'));
            }
        }

        $oOrder->oxorder__klsync = new Field(1);
        $oOrder->save();
    }
}