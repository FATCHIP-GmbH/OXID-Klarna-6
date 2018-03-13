<?php

namespace Klarna\Klarna\Controllers\Admin;


use Klarna\Klarna\Core\KlarnaUtils;
use Klarna\Klarna\Exception\KlarnaOrderNotFoundException;
use Klarna\Klarna\Exception\KlarnaWrongCredentialsException;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

class KlarnaOrderArticle extends KlarnaOrderArticle_parent
{
    public $orderLang;

    protected $klarnaOrderData;

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
//                Registry::get('oxUtilsView')->addErrorToDisplay(
//                    sprintf(Registry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
//                        $this->_aViewData['sMid'],
//                        $this->_aViewData['sCountryISO'],
//                        $this->_aViewData['currentMid']
//                    )
//                );

                $oOrder->oxorder__klsync = new Field(0);
                $oOrder->save();

                return $result;
            }

            try {
                $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->_aViewData['sCountryISO']);
            } catch (KlarnaWrongCredentialsException $e) {
//                Registry::get('oxUtilsView')->addErrorToDisplay("KLARNA_UNAUTHORIZED_REQUEST");
                $this->_aViewData['sErrorMessage'] = Registry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST");

                $oOrder->oxorder__klsync = new Field(0);
                $oOrder->save();

                return $result;
            } catch (KlarnaOrderNotFoundException $e) {
//                Registry::get('oxUtilsView')->addErrorToDisplay('KLARNA_ORDER_NOT_FOUND');
                $this->_aViewData['sErrorMessage'] = Registry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND");

                $oOrder->oxorder__klsync = new Field(0);
                $oOrder->save();

                return $result;
            } catch (oxException $e) {
//                Registry::get('oxUtilsView')->addErrorToDisplay($e);
                $this->_aViewData['sErrorMessage'] = $e->getMessage();

                $oOrder->oxorder__klsync = new Field(0);
                $oOrder->save();

                return $result;
            }

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

        return $result;
    }

    /**
     * @throws oxSystemComponentException
     */
    public function render()
    {
        $parent = parent::render();

        $this->_aViewData['isKlarnaOrder'] = $this->isKlarnaOrder();
        $oOrder                            = $this->getEditObject(true);

        if ($this->_aViewData['isKlarnaOrder'] && $oOrder->getFieldData('oxstorno') == 0) {

            //check if credentials are valid
            if (!$this->isCredentialsValid()) {
//                Registry::get('oxUtilsView')->addErrorToDisplay(
//                    sprintf(Registry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
//                        $this->_aViewData['sMid'],
//                        $this->_aViewData['sCountryISO'],
//                        $this->_aViewData['currentMid']
//                    )
//                );

                $this->_aViewData['sWarningMessage'] = sprintf(Registry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
                    $this->_aViewData['sMid'],
                    $this->_aViewData['sCountryISO'],
                    $this->_aViewData['currentMid']
                );

                return $parent;
            }

            if (Registry::getConfig()->getRequestParameter('fnc')) {

                try {
                    $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->_aViewData['sCountryISO']);
                } catch (KlarnaWrongCredentialsException $e) {
//                    Registry::get('oxUtilsView')->addErrorToDisplay("KLARNA_UNAUTHORIZED_REQUEST");
                    $this->_aViewData['sErrorMessage'] = Registry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST");
                    $oOrder->oxorder__klsync           = new Field(0);
                    $oOrder->save();

                    return $parent;
                } catch (KlarnaOrderNotFoundException $e) {
//                    Registry::get('oxUtilsView')->addErrorToDisplay('KLARNA_ORDER_NOT_FOUND');
                    $this->_aViewData['sErrorMessage'] = Registry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND");

                    $oOrder->oxorder__klsync = new Field(0);
                    $oOrder->save();

                    return $parent;
                } catch (oxException $e) {
//                    Registry::get('oxUtilsView')->addErrorToDisplay($e);
                    $this->_aViewData['sErrorMessage'] = $e->getMessage();

                    $oOrder->oxorder__klsync = new Field(0);
                    $oOrder->save();

                    return $parent;
                }
            }

            if (is_array($this->klarnaOrderData)) {
                $apiDisabled = Registry::getLang()->translateString("KL_NO_REQUESTS_WILL_BE_SENT");
                if ($this->klarnaOrderData['status'] === 'CANCELLED') {
                    $oOrder->oxorder__klsync = new Field(0);

                    $orderCancelled = Registry::getLang()->translateString("KLARNA_ORDER_IS_CANCELLED");
//                    Registry::get('oxUtilsView')->addErrorToDisplay($orderCancelled . $apiDisabled);
                    $this->_aViewData['sWarningMessage'] = $orderCancelled . $apiDisabled;

                } else if ($this->klarnaOrderData['order_amount'] != KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100)
                           || !$this->isCaptureInSync($this->klarnaOrderData)) {
                    $oOrder->oxorder__klsync = new Field(0);

                    $orderNotInSync = Registry::getLang()->translateString("KLARNA_ORDER_NOT_IN_SYNC");
//                    Registry::get('oxUtilsView')->addErrorToDisplay($orderNotInSync . $apiDisabled);
                    $this->_aViewData['sWarningMessage'] = $orderNotInSync . $apiDisabled;

                } else {
                    $oOrder->oxorder__klsync = new Field(1);
                }
                $oOrder->save();
            }
        }

        return $parent;
    }

    /**
     *
     */
    public function updateOrder()
    {
        parent::updateOrder();
        /** @var Order $oOrder */
        if ($this->isKlarnaOrder() && $this->getEditObject()->getFieldData('klsync') == 1) {

            $orderLines  = $this->getEditObject(true)->getNewOrderLinesAndTotals($this->orderLang);
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->oxorder__oxbillcountryid->value);

            $error = $this->getEditObject()->updateKlarnaOrder($orderLines, $this->getEditObject()->oxorder__klorderid->value, $sCountryISO);
            if ($error) {
                $this->_aViewData['sErrorMessage'] = $error;
            }
        }
    }

    /**
     * @throws oxSystemComponentException
     */
    public function deleteThisArticle()
    {
        parent::deleteThisArticle();
        /** @var Order $oOrder */
        if ($this->isKlarnaOrder() && $this->getEditObject()->getFieldData('klsync') == 1) {

            $orderLines  = $this->getEditObject(true)->getNewOrderLinesAndTotals($this->orderLang);
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->oxorder__oxbillcountryid->value);

            $error = $this->getEditObject()->updateKlarnaOrder($orderLines, $this->getEditObject()->oxorder__klorderid->value, $sCountryISO);
            if ($error) {
                $this->_aViewData['sErrorMessage'] = $error;
            }
        }
    }

    /**
     * @throws oxSystemComponentException
     */
    public function storno()
    {
        parent::storno();
        /** @var Order $oOrder */
        if ($this->isKlarnaOrder() && $this->getEditObject()->getFieldData('klsync') == 1) {

            $orderLines  = $this->getEditObject()->getNewOrderLinesAndTotals($this->orderLang);
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->oxorder__oxbillcountryid->value);

            $error = $this->getEditObject()->updateKlarnaOrder($orderLines, $this->getEditObject()->oxorder__klorderid->value, $sCountryISO);
            if ($error) {
                $this->_aViewData['sErrorMessage'] = $error;
            }
        }
    }

    /**
     *
     * @throws oxSystemComponentException
     */
    public function addThisArticle()
    {
        parent::addThisArticle();
        /** @var Order $oOrder */
        if ($this->isKlarnaOrder() && $this->getEditObject()->getFieldData('klsync') == 1) {

            $orderLines  = $this->getEditObject()->getNewOrderLinesAndTotals($this->orderLang);
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->oxorder__oxbillcountryid->value);

            $error = $this->getEditObject()->updateKlarnaOrder($orderLines, $this->getEditObject()->oxorder__klorderid->value, $sCountryISO);
            if ($error) {
                $this->_aViewData['sErrorMessage'] = $error;
            }
        }
    }

    /**
     * Method checks is order was made with Klarna module
     *
     * @return bool
     */
    public function isKlarnaOrder()
    {
        $blActive = false;

        if ($this->getEditObject(true) && stripos($this->getEditObject()->getFieldData('oxpaymenttype'), 'klarna_') !== false) {
            $blActive = true;
        }

        return $blActive && $this->getEditObject()->getFieldData('oxstorno') == 0;
    }

    /**
     * @return bool
     * @throws oxSystemComponentException
     */
    public function isCredentialsValid()
    {
        $this->_aViewData['sMid']        = $this->getEditObject()->getFieldData('klmerchantid');
        $this->_aViewData['sCountryISO'] = KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'));
        $currentMid                      = KlarnaUtils::getAPICredentials($this->_aViewData['sCountryISO']);
        $this->_aViewData['currentMid']  = $currentMid['mid'];

        if (strstr($this->_aViewData['currentMid'], $this->_aViewData['sMid'])) {
            return true;
        }

        return false;
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

        return $this->getEditObject()->retrieveKlarnaOrder($this->getEditObject()->getFieldData('klorderid'), $sCountryISO);
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
     * Returns editable order object
     *
     * @param bool $reset
     * @return Order|\Klarna\Klarna\Models\KlarnaOrder
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
}