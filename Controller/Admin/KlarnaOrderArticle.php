<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Model\KlarnaOrder;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

class KlarnaOrderArticle extends KlarnaOrderArticle_parent
{
    public $orderLang;

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

            $this->setinitSyncStatus($oOrder);
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
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function updateOrder()
    {
        parent::updateOrder();
        $this->updateKlarnaOrder(true);
    }

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function deleteThisArticle()
    {
        parent::deleteThisArticle();
        $this->updateKlarnaOrder(true);
    }

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function storno()
    {
        parent::storno();
        $this->updateKlarnaOrder();
    }

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function addThisArticle()
    {
        parent::addThisArticle();
        $this->updateKlarnaOrder();
    }

    protected function updateKlarnaOrder($reset = false)
    {
        /** @var Order $oOrder */
        if ($this->isKlarnaOrder() && $this->getEditObject()->getFieldData('tcklarna_sync') == 1) {

            $orderLines  = $this->getEditObject($reset)->getNewOrderLinesAndTotals($this->orderLang);
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->oxorder__oxbillcountryid->value);

            $error = $this->getEditObject()->updateKlarnaOrder($orderLines, $this->getEditObject()->oxorder__tcklarna_orderid->value, $sCountryISO);

            if ($error) {
                $this->addTplParam('sErrorMessage', $error);
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
     * @param null $sCountryISO
     * @return mixed
     * @throws StandardException
     * @throws \TopConcepts\Klarna\Core\Exception\KlarnaClientException
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function retrieveKlarnaOrder($sCountryISO = null)
    {
        if (!$sCountryISO) {
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'));
        }

        /** @var KlarnaOrderManagementClient $client */
        $client = KlarnaOrderManagementClient::getInstance($sCountryISO);

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
     * Returns editable order object
     *
     * @param bool $reset
     * @return Order|KlarnaOrder
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
     * @param $oOrder
     */
    protected function setinitSyncStatus($oOrder)
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
                $this->addtplParam('sWarningMessage', $orderCancelled . $apiDisabled);

            } else if ($this->klarnaOrderData['order_amount'] != KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100)
                       || !$this->isCaptureInSync($this->klarnaOrderData)) {
                $oOrder->oxorder__tcklarna_sync = new Field(0);

                $orderNotInSync = Registry::getLang()->translateString("KLARNA_ORDER_NOT_IN_SYNC");
                $this->addtplParam('sWarningMessage', $orderNotInSync . $apiDisabled);

            } else {
                $oOrder->oxorder__tcklarna_sync = new Field(1);
            }
            $oOrder->save();
        }
    }
}