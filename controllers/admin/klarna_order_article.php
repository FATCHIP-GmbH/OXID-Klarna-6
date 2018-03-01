<?php

class klarna_order_article extends klarna_order_article_parent
{
    public $orderLang;

    protected $klarnaOrderData;

    /**
     * @return mixed
     * @throws oxSystemComponentException
     */
    public function init()
    {
        $result = parent::init();

        $oOrder = $this->getEditObject();
        if ($this->isKlarnaOrder() && $oOrder->getFieldData('oxstorno') == 0) {

            //check if credentials are valid
            if (!$this->isCredentialsValid()) {
                oxRegistry::get('oxUtilsView')->addErrorToDisplay(
                    sprintf(oxRegistry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
                        $this->_aViewData['sMid'],
                        $this->_aViewData['sCountryISO'],
                        $this->_aViewData['currentMid']
                    )
                );
                $oOrder->oxorder__klsync = new oxField(0);
                $oOrder->save();

                return $result;
            }

            try {
                $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->_aViewData['sCountryISO']);
            } catch (KlarnaWrongCredentialsException $e) {
                oxRegistry::get('oxUtilsView')->addErrorToDisplay("KLARNA_UNAUTHORIZED_REQUEST");
                $oOrder->oxorder__klsync = new oxField(0);
                $oOrder->save();

                return $result;
            } catch (KlarnaOrderNotFoundException $e) {
                oxRegistry::get('oxUtilsView')->addErrorToDisplay('KLARNA_ORDER_NOT_FOUND');
                $oOrder->oxorder__klsync = new oxField(0);
                $oOrder->save();

                return $result;
            } catch (oxException $e) {
                oxRegistry::get('oxUtilsView')->addErrorToDisplay($e);
                $oOrder->oxorder__klsync = new oxField(0);
                $oOrder->save();

                return $result;
            }

            if (is_array($this->klarnaOrderData)) {
                $klarnaOrderTotalSum = KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100);

                if ($this->klarnaOrderData['order_amount'] != $klarnaOrderTotalSum ||
                    ($this->klarnaOrderData['remaining_authorized_amount'] != $klarnaOrderTotalSum &&
                     $this->klarnaOrderData['remaining_authorized_amount'] != 0
                    ) || !$this->isCaptureInSync($this->klarnaOrderData)
                ) {
                    $oOrder->oxorder__klsync = new oxField(0);
                } else {
                    $oOrder->oxorder__klsync = new oxField(1);
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
                oxRegistry::get('oxUtilsView')->addErrorToDisplay(
                    sprintf(oxRegistry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
                        $this->_aViewData['sMid'],
                        $this->_aViewData['sCountryISO'],
                        $this->_aViewData['currentMid']
                    )
                );

                return $parent;
            }

            if (oxRegistry::getConfig()->getRequestParameter('fnc')) {

                try {
                    $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->_aViewData['sCountryISO']);
                } catch (KlarnaWrongCredentialsException $e) {
                    oxRegistry::get('oxUtilsView')->addErrorToDisplay("KLARNA_UNAUTHORIZED_REQUEST");
                    $oOrder->oxorder__klsync = new oxField(0);
                    $oOrder->save();

                    return $parent;
                } catch (KlarnaOrderNotFoundException $e) {
                    oxRegistry::get('oxUtilsView')->addErrorToDisplay('KLARNA_ORDER_NOT_FOUND');
                    $oOrder->oxorder__klsync = new oxField(0);
                    $oOrder->save();

                    return $parent;
                } catch (oxException $e) {
                    oxRegistry::get('oxUtilsView')->addErrorToDisplay($e);
                    $oOrder->oxorder__klsync = new oxField(0);
                    $oOrder->save();

                    return $parent;
                }
            }

            if (is_array($this->klarnaOrderData)) {
                $apiDisabled = oxRegistry::getLang()->translateString("KL_NO_REQUESTS_WILL_BE_SENT");
                if ($this->klarnaOrderData['status'] === 'CANCELLED') {
                    $oOrder->oxorder__klsync = new oxField(0);

                    $orderCancelled = oxRegistry::getLang()->translateString("KLARNA_ORDER_IS_CANCELLED");
                    oxRegistry::get('oxUtilsView')->addErrorToDisplay($orderCancelled . $apiDisabled);
                } else if ($this->klarnaOrderData['order_amount'] != KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100)
                           || !$this->isCaptureInSync($this->klarnaOrderData)) {
                    $oOrder->oxorder__klsync = new oxField(0);

                    $orderNotInSync = oxRegistry::getLang()->translateString("KLARNA_ORDER_NOT_IN_SYNC");
                    oxRegistry::get('oxUtilsView')->addErrorToDisplay($orderNotInSync . $apiDisabled);
                } else {
                    $oOrder->oxorder__klsync = new oxField(1);
                }
                $oOrder->save();
            }
        }

        return $parent;
    }

    /**
     * @throws oxException
     * @throws oxSystemComponentException
     */
    public function updateOrder()
    {
        parent::updateOrder();
        /** @var oxorder $oOrder */
        if ($this->isKlarnaOrder()) {

            $orderLines  = $this->getEditObject(true)->getNewOrderLinesAndTotals($this->orderLang);
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->oxorder__oxbillcountryid->value);

            $this->getEditObject()->updateKlarnaOrder($orderLines, $this->getEditObject()->oxorder__klorderid->value, $sCountryISO);
        }
    }

    /**
     * @throws oxSystemComponentException
     */
    public function deleteThisArticle()
    {
        parent::deleteThisArticle();
        /** @var oxorder $oOrder */
        if ($this->isKlarnaOrder()) {

            $orderLines  = $this->getEditObject(true)->getNewOrderLinesAndTotals($this->orderLang);
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->oxorder__oxbillcountryid->value);

            $this->getEditObject()->updateKlarnaOrder($orderLines, $this->getEditObject()->oxorder__klorderid->value, $sCountryISO);
        }
    }

    /**
     * @throws oxSystemComponentException
     */
    public function storno()
    {
        parent::storno();
        /** @var oxorder $oOrder */
        if ($this->isKlarnaOrder()) {

            $orderLines  = $this->getEditObject(true)->getNewOrderLinesAndTotals($this->orderLang);
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->oxorder__oxbillcountryid->value);

            $this->getEditObject()->updateKlarnaOrder($orderLines, $this->getEditObject()->oxorder__klorderid->value, $sCountryISO);
        }
    }

    /**
     *
     * @throws oxSystemComponentException
     */
    public function addThisArticle()
    {
        parent::addThisArticle();
        /** @var oxOrder $oOrder */
        if ($this->isKlarnaOrder(true)) {

            $orderLines  = $this->getEditObject()->getNewOrderLinesAndTotals($this->orderLang);
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->oxorder__oxbillcountryid->value);

            $this->getEditObject()->updateKlarnaOrder($orderLines, $this->getEditObject()->oxorder__klorderid->value, $sCountryISO);
        }
    }

    /**
     * Method checks is order was made with Klarna module
     *
     * @return bool
     * @throws oxSystemComponentException
     */
    public function isKlarnaOrder()
    {
        $blActive = false;

        if ($this->getEditObject() && stripos($this->getEditObject()->getFieldData('oxpaymenttype'), 'klarna_') !== false) {
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
     * @throws oxException
     * @throws oxSystemComponentException
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
     * @throws oxSystemComponentException
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
     * @return fcPayOneOrder|InvoicepdfOxOrder|Klarna_oxOrder|null|object|oePayPalOxOrder|oxOrder|OxpsPaymorrowOxOrder|tc_cleverreach_oxorder
     * @throws oxSystemComponentException
     */
    public function getEditObject($reset = false)
    {
        $soxId = $this->getEditObjectId();
        if (($this->_oEditObject === null && isset($soxId) && $soxId != '-1') || $reset) {
            $this->_oEditObject = oxNew('oxOrder');
            $this->_oEditObject->load($soxId);
        }

        return $this->_oEditObject;
    }
}