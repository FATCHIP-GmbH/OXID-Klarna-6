<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\Exception\KlarnaCaptureNotAllowedException;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;
use TopConcepts\Klarna\Model\KlarnaOrder;

class KlarnaOrders extends AdminDetailsController
{
    const KLARNA_PORTAL_PLAYGROUND_URL = 'https://orders.playground.eu.portal.klarna.com/merchants/%s/orders/%s';
    const KLARNA_PORTAL_LIVE_URL       = 'https://orders.eu.portal.klarna.com/merchants/%s/orders/%s';

    protected $_sThisTemplate = 'tcklarna_orders.tpl';

    public    $orderLang;
    protected $client;

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     * @return string
     */
    public function render()
    {
        $this->addTplParam("sOxid", $this->getEditObjectId());

        if (!$this->isKlarnaOrder()) {
            $this->addTplParam(
                'sMessage',
                Registry::getLang()->translateString("KLARNA_ONLY_FOR_KLARNA_PAYMENT")
            );

            return parent::render();
        }
        $oOrder          = $this->getEditObject();
        $this->orderLang = $oOrder->getFieldData('oxlang');

        $this->addTplParam('oOrder', $oOrder);
        $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));

        if (!$this->isCredentialsValid($sCountryISO)) {
            $wrongCredsMsg = sprintf(
                Registry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
                $this->getViewDataElement('sMid'),
                $this->getViewDataElement('sCountryISO'),
                $this->getViewDataElement('currentMid'));

            $this->addTplParam('wrongCredentials', $wrongCredsMsg);

            return parent::render();
        }

        try {
            $klarnaOrderData = $this->retrieveKlarnaOrder($this->getViewDataElement('sCountryISO'));
        } catch (KlarnaCaptureNotAllowedException $e) {
            $this->addTplParam('unauthorizedRequest',
                Registry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND")
            );

            return parent::render();
        } catch (KlarnaClientException $e) {
            $this->addTplParam('unauthorizedRequest', $e->getMessage());

            return parent::render();
        } catch (StandardException $e) {
            Registry::get('oxUtilsView')->addErrorToDisplay($e);

            return parent::render();
        }

        $this->addTplParam('sStatus', $klarnaOrderData['status']);

        $this->setOrderSync($klarnaOrderData);

        $this->addTplParam('aCaptures', $this->formatCaptures($klarnaOrderData['captures']));
        $this->addTplParam('aRefunds', $klarnaOrderData['refunds']);
        $klarnaRef = $klarnaOrderData['klarna_reference'] ?: " - ";
        $this->addTplParam('sKlarnaRef', $klarnaRef);
        $this->addTplParam('inSync', $this->getEditObject()->getFieldData('tcklarna_sync') == 1);

        return parent::render();
    }

    /**
     * Returns editable order object
     * @return KlarnaOrder|Order
     */
    public function getEditObject()
    {
        $soxId = $this->getEditObjectId();
        if ($this->_oEditObject === null && isset($soxId) && $soxId != '-1') {
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
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function captureFullOrder()
    {
        $orderLines = $this->getEditObject()->getNewOrderLinesAndTotals($this->orderLang, true);

        $data = array(
            'captured_amount' => KlarnaUtils::parseFloatAsInt($this->getEditObject()->getTotalOrderSum() * 100),
            'order_lines'     => $orderLines['order_lines'],
        );

        $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'));
        try {
            $this->getEditObject()->captureKlarnaOrder($data, $this->getEditObject()->getFieldData('tcklarna_orderid'), $sCountryISO);
            $this->getEditObject()->oxorder__tcklarna_sync = new Field(1);
            $this->getEditObject()->save();
        } catch (StandardException $e) {
            Registry::get(UtilsView::class)->addErrorToDisplay($e->getMessage());
        }
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

        return $client->getOrder($this->getEditObject()->getFieldData('tcklarna_orderid'));
    }

    /**
     * @param $price
     * @return string
     */
    public function formatPrice($price)
    {
        return Registry::getLang()->formatCurrency($price / 100, $this->getEditObject()->getOrderCurrency())
               . " {$this->getEditObject()->oxorder__oxcurrency->value}";
    }

    /**
     * @param $amount
     * @return array
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function refundOrderAmount($amount)
    {
        $orderRefund = null;
        $data        = array(
            'refunded_amount' => $amount,
        );

        $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'));

        try {
            $client      = $this->getKlarnaMgmtClient($sCountryISO);
            $orderRefund = $client->createOrderRefund($data, $this->getEditObject()->getFieldData('tcklarna_orderid'));
        } catch (\Exception $e) {
            Registry::get("oxUtilsView")->addErrorToDisplay($e->getMessage());
        }

        return $orderRefund;
    }

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function cancelOrder()
    {
        $oOrder = $this->getEditObject();
        $result = $this->cancelKlarnaOrder($oOrder);
        if ($result) {
            $oOrder->cancelOrder();
        }

        $this->getSession()->setVariable($oOrder->getId().'orderCancel', $result);
    }

    /**
     *
     */
    public function getKlarnaPortalLink()
    {
        if ($this->getEditObject()->oxorder__tcklarna_servermode->value === 'playground') {
            $url = self::KLARNA_PORTAL_PLAYGROUND_URL;
        } else {
            $url = self::KLARNA_PORTAL_LIVE_URL;
        }

        $mid     = $this->getEditObject()->oxorder__tcklarna_merchantid->value;
        $orderId = $this->getEditObject()->oxorder__tcklarna_orderid->value;

        return sprintf($url, $mid, $orderId);
    }

    /**
     * @return bool
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function isCredentialsValid($sCountryISO)
    {
        $currentMid = KlarnaUtils::getAPICredentials($sCountryISO);

        $this->addTplParam('sMid', $this->getEditObject()->getFieldData('tcklarna_merchantid'));
        $this->addTplParam(
            'sCountryISO',
            KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'))
        );
        $this->addTplParam('currentMid', $currentMid['mid']);

        if (strstr($currentMid['mid'], $this->getViewDataElement('sMid'))) {
            return true;
        }

        return false;
    }

    /**
     * @param $klarnaOrderData
     */
    protected function setOrderSync($klarnaOrderData)
    {
        $sync = $this->isOrderCancellationInSync();

        $totalOrderSum = KlarnaUtils::parseFloatAsInt($this->getEditObject()->getTotalOrderSum() * 100);
        if ($sync && $klarnaOrderData['order_amount'] === $totalOrderSum) {
            $this->getEditObject()->oxorder__tcklarna_sync = new Field(1, Field::T_RAW);
        } else {
            $this->getEditObject()->oxorder__tcklarna_sync = new Field(0, Field::T_RAW);
        }
        $this->getEditObject()->save();
    }

    /**
     * @return bool
     */
    protected function isOrderCancellationInSync()
    {
        if (strtolower($this->getViewDataElement('sStatus')) === 'cancelled') {
            if ($this->getEditObject()->oxorder__oxstorno->value == 1) {
                $this->addTplParam('cancelled', true);

                return true;
            }

            return false;
        }
        if ($this->getEditObject()->getFieldData('oxstorno') == 1) {

            return false;
        }

        return true;
    }

    /**
     * @param $aCaptures
     * @return array
     */
    public function formatCaptures($aCaptures)
    {
        if (!is_array($aCaptures)) {
            return array();
        }
        foreach ($aCaptures as $i => $capture) {
            $klarnaTime = new \DateTime($capture['captured_at']);
            $klarnaTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));

            $aCaptures[$i]['captured_at'] = $klarnaTime->format('Y-m-d H:m:s');
            unset($klarnaTime);
        }

        return $aCaptures;
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
     * @param KlarnaOrder|Order $oOrder
     * @return bool
     * @throws \oxSystemComponentException
     */
    protected function cancelKlarnaOrder($oOrder)
    {
        if (!$oOrder->isLoaded()) {
            return false;
        }

        if ($oOrder->isKlarnaOrder() && !$oOrder->getFieldData('oxstorno')) {
            $orderId     = $oOrder->getFieldData('tcklarna_orderid');
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));

            try {
                $oOrder->cancelKlarnaOrder($orderId, $sCountryISO);
                $oOrder->oxorder__tcklarna_sync = new Field(1);
                $oOrder->save();
            } catch (StandardException $e) {
                if (strstr($e->getMessage(), 'is canceled.')) {

                    return true;
                }

                Registry::get(UtilsView::class)->addErrorToDisplay($e);
                $this->resetCache();

                return false;
            }
        }

        return true;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function resetCache()
    {
        $this->resetContentCache();
        $this->init();
    }
}