<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use TopConcepts\Klarna\Model\KlarnaOrder;

class KlarnaOrderOverview extends KlarnaOrderOverview_parent
{
    protected $klarnaOrderData;

    /**
     * @return mixed
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function init()
    {
        $result = parent::init();

        $oOrder = $this->getEditObject();
        if ($this->isKlarnaOrder() && $oOrder->getFieldData('oxstorno') == 0) {
            //check if credentials are valid
            if (!$this->isCredentialsValid()) {
                $oOrder->oxorder__tcklarna_sync = new Field(0);
                $oOrder->save();

                return $result;
            }

            try {
                $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->getViewDataElement('sCountryISO'));
            } catch (KlarnaWrongCredentialsException $e) {
                $this->addTplParam('sErrorMessage', Registry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST"));

                $oOrder->oxorder__tcklarna_sync = new Field(0);
                $oOrder->save();

                return $result;
            } catch (KlarnaOrderNotFoundException $e) {
                $this->addTplParam('sErrorMessage', Registry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND"));

                $oOrder->oxorder__tcklarna_sync = new Field(0);
                $oOrder->save();

                return $result;
            } catch (StandardException $e) {
                $this->addTplParam('sErrorMessage', $e->getMessage());

                $oOrder->oxorder__tcklarna_sync = new Field(0);
                $oOrder->save();

                return $result;
            }

            $this->setInitSyncStatus($oOrder);
        }

        return $result;
    }

    /**
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
                } catch (KlarnaWrongCredentialsException $e) {
                    $this->addTplParam('sErrorMessage', Registry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST"));
                    $oOrder->oxorder__tcklarna_sync = new Field(0);
                    $oOrder->save();

                    return $parent;
                } catch (KlarnaOrderNotFoundException $e) {
                    $this->addTplParam('sErrorMessage', Registry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND"));

                    $oOrder->oxorder__tcklarna_sync = new Field(0);
                    $oOrder->save();

                    return $parent;
                } catch (StandardException $e) {
                    $this->addTplParam('sErrorMessage', $e->getMessage());

                    $oOrder->oxorder__tcklarna_sync = new Field(0);
                    $oOrder->save();

                    return $parent;
                }
            }

            $this->handleWarningMessages($oOrder);
        }

        return $parent;
    }

    /**
     * Sends order. Captures klarna order.
     * @throws StandardException
     */
    public function sendorder()
    {
        $cancelled = $this->getEditObject()->getFieldData('oxstorno') == 1;

        $result = parent::sendorder();

        if (!$this->isKlarnaOrder()) {
            return $result;
        }

        //force reload
        /** @var KlarnaOrder|Order $oOrder */
        $oOrder = $this->getEditObject(true);
        $inSync = $oOrder->getFieldData('tcklarna_sync') == 1;

        if ($cancelled) {
            $this->addTplParam('sErrorMessage', Registry::getLang()->translateString("TCKLARNA_CAPUTRE_FAIL_ORDER_CANCELLED"));

            return $result;
        }

        if ($inSync && $this->klarnaOrderData['remaining_authorized_amount'] != 0) {
            $orderLang   = (int)$oOrder->getFieldData('oxlang');
            $orderLines  = $oOrder->getNewOrderLinesAndTotals($orderLang, true);
            $data        = array(
                'captured_amount' => KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100),
                'order_lines'     => $orderLines['order_lines'],
            );
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));

            try {
                $this->addTplParam('sErrorMessage', '');

                $response = $oOrder->captureKlarnaOrder($data, $oOrder->getFieldData('tcklarna_orderid'), $sCountryISO);
            } catch (StandardException $e) {
                $this->addTplParam('sErrorMessage', $e->getMessage());

                return $result;
            }

            if ($response === true) {
                $this->addTplParam('sMessage', Registry::getLang()->translateString("KLARNA_CAPTURE_SUCCESSFULL"));
            }
            $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->getViewDataElement('sCountryISO'));
        }

        return $result;
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
     * @param null $sCountryISO
     * @return mixed
     */
    public function retrieveKlarnaOrder($sCountryISO = null)
    {
        if (!$sCountryISO) {
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'));
        }
        $client = $this->getKlarnaMgmtClient($sCountryISO);

        return $client->getOrder($this->getEditObject()->getFieldData('tcklarna_orderid'));
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
     * @return bool
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function isCredentialsValid()
    {
        $this->addTplParam('sMid', $this->getEditObject()->getFieldData('tcklarna_merchantid'));
        $this->addTplParam('sCountryISO', KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid')));
        $currentMid = KlarnaUtils::getAPICredentials($this->getViewDataElement('sCountryISO'));
        $this->addTplParam('currentMid', $currentMid['mid']);

        if (strstr($this->getViewDataElement('currentMid'), $this->getViewDataElement('sMid'))) {
            return true;
        }

        return false;
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
                $oOrder->oxorder__tcklarna_sync = new Field(0);
            } else {
                $oOrder->oxorder__tcklarna_sync = new Field(1);
            }
            $oOrder->save();
        }
    }

    /**
     * @param $oOrder
     */
    protected function handleWarningMessages($oOrder)
    {
        if (is_array($this->klarnaOrderData)) {
            $apiDisabled = Registry::getLang()->translateString("TCKLARNA_NO_REQUESTS_WILL_BE_SENT");
            if ($this->klarnaOrderData['status'] === 'CANCELLED') {
                $oOrder->oxorder__tcklarna_sync = new Field(0);

                $orderCancelled = Registry::getLang()->translateString("KLARNA_ORDER_IS_CANCELLED");
                $this->addTplParam('sWarningMessage', $orderCancelled . $apiDisabled);

            } else if ($this->klarnaOrderData['order_amount'] != KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100)
                       || !$this->isCaptureInSync($this->klarnaOrderData)) {
                $oOrder->oxorder__tcklarna_sync = new Field(0);

                $orderNotInSync = Registry::getLang()->translateString("KLARNA_ORDER_NOT_IN_SYNC");
                $this->addTplParam('sWarningMessage', $orderNotInSync . $apiDisabled);

            } else {
                $oOrder->oxorder__tcklarna_sync = new Field(1);
            }
            $oOrder->save();
        }
    }

    /**
     * @param $sCountryISO
     * @return KlarnaClientBase|KlarnaOrderManagementClient
     */
    protected function getKlarnaMgmtClient($sCountryISO)
    {
        return KlarnaOrderManagementClient::getInstance($sCountryISO);
    }
}
