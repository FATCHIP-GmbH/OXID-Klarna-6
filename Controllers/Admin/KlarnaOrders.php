<?php

namespace TopConcepts\Klarna\Controllers\Admin;


use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Exception\KlarnaCaptureNotAllowedException;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Exception\KlarnaClientException;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;

class KlarnaOrders extends AdminDetailsController
{
    const KLARNA_PORTAL_PLAYGROUND_URL = 'https://orders.playground.eu.portal.klarna.com/merchants/%s/orders/%s';
    const KLARNA_PORTAL_LIVE_URL       = 'https://orders.eu.portal.klarna.com/merchants/%s/orders/%s';

    protected $_sThisTemplate = 'kl_klarna_orders.tpl';

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

        $this->orderLang = $this->getEditObject()->getFieldData('oxlang');

        $this->addTplParam('oOrder', $this->getEditObject());
        if (!$this->isCredentialsValid()) {
            $wrongCredsMsg = sprintf(
                Registry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
                $this->getViewDataElement('sMid'),
                $this->getViewDataElement('sCountryISO'),
                $this->getViewDataElement('currentMid')
            );
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
        $this->addTplParam('inSync', $this->getEditObject()->getFieldData('klsync') == 1);

        return parent::render();
    }

    /**
     * Returns editable order object
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
            $this->getEditObject()->captureKlarnaOrder($data, $this->getEditObject()->getFieldData('klorderid'), $sCountryISO);
            $this->getEditObject()->oxorder__klsync = new Field(1);
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

        return $client->getOrder($this->getEditObject()->getFieldData('klorderid'));
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
            $orderRefund = $client->createOrderRefund($data, $this->getEditObject()->getFieldData('klorderid'));
        } catch (\Exception $e) {
            Registry::get("oxUtilsView")->addErrorToDisplay($e->getMessage());
        }

        return $orderRefund;
    }

    /**
     * @codeCoverageIgnore
     */
    public function cancelOrder()
    {
        return $this->getEditObject()->cancelOrder();
    }

    /**
     *
     */
    public function getKlarnaPortalLink()
    {
        if ($this->getEditObject()->oxorder__klservermode->value === 'playground') {
            $url = self::KLARNA_PORTAL_PLAYGROUND_URL;
        } else {
            $url = self::KLARNA_PORTAL_LIVE_URL;
        }

        $mid     = $this->getEditObject()->oxorder__klmerchantid->value;
        $orderId = $this->getEditObject()->oxorder__klorderid->value;

        return sprintf($url, $mid, $orderId);
    }

    /**
     * @return bool
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function isCredentialsValid()
    {
        $currentMid = KlarnaUtils::getAPICredentials($this->getViewDataElement('sCountryISO'));

        $this->addTplParam('sMid', $this->getEditObject()->getFieldData('klmerchantid'));
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
            $this->getEditObject()->oxorder__klsync = new Field(1, Field::T_RAW);
        } else {
            $this->getEditObject()->oxorder__klsync = new Field(0, Field::T_RAW);
        }
        $this->getEditObject()->save();
    }

    /**
     * @return bool
     */
    protected function isOrderCancellationInSync()
    {
        if (strtolower($this->getViewDataElement('sStatus')) === 'cancelled' &&
            $this->getEditObject()->oxorder__oxstorno->value == 1) {
            $this->addTplParam('cancelled', true);

            return true;
        }

        return false;
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
}