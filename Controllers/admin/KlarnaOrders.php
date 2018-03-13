<?php

namespace Klarna\Klarna\Controllers\Admin;


use Klarna\Klarna\Exception\KlarnaCaptureNotAllowedException;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use Klarna\Klarna\Core\KlarnaUtils;
use Klarna\Klarna\Exception\KlarnaOrderNotFoundException;
use Klarna\Klarna\Exception\KlarnaWrongCredentialsException;
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

    public $orderLang;

    /**
     * @return string
     */
    public function render()
    {
        $this->cur                 = $this->getEditObject()->getFieldData('');
        $this->_aViewData["sOxid"] = $this->getEditObjectId();
        if ($this->isKlarnaOrder()) {
            $this->orderLang = $this->getEditObject()->getFieldData('oxlang');

            $this->_aViewData['oOrder'] = $this->getEditObject();
            if (!$this->isCredentialsValid()) {
                $this->_aViewData['wrongCredentials'] =
                    sprintf(Registry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
                        $this->_aViewData['sMid'], $this->_aViewData['sCountryISO'], $this->_aViewData['currentMid']);

                return parent::render();
            }

            try {
                $klarnaOrderData = $this->retrieveKlarnaOrder($this->_aViewData['sCountryISO']);
            } catch (KlarnaWrongCredentialsException $e) {
                $this->_aViewData['unauthorizedRequest'] =
                    Registry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST");

                return parent::render();
            } catch (KlarnaOrderNotFoundException $e) {
                $this->_aViewData['unauthorizedRequest'] =
                    Registry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND");

                return parent::render();
            } catch (KlarnaCaptureNotAllowedException $e) {
                $this->_aViewData['unauthorizedRequest'] =
                    Registry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND");

                return parent::render();
            } catch (StandardException $e) {
                Registry::get('oxUtilsView')->addErrorToDisplay($e);

                return parent::render();
            }

            $sync = true;

            $this->_aViewData['sStatus'] = $klarnaOrderData['status'];
            if (strtolower($this->_aViewData['sStatus']) === 'cancelled') {
                if ($this->getEditObject()->oxorder__oxstorno->value == 1) {
                    $this->_aViewData['cancelled'] = true;
                } else {
                    $sync = false;
                }
            }

            if ($sync && $klarnaOrderData['order_amount'] === KlarnaUtils::parseFloatAsInt($this->getEditObject()->getTotalOrderSum() * 100)) {
                $this->getEditObject()->oxorder__klsync = new Field(1, Field::T_RAW);
            } else {
                $this->getEditObject()->oxorder__klsync = new Field(0, Field::T_RAW);
            }
            $this->getEditObject()->save();

            $this->_aViewData['aCaptures']  = $this->formatCaptures($klarnaOrderData['captures']);
            $this->_aViewData['aRefunds']   = $klarnaOrderData['refunds'];
            $this->_aViewData['sKlarnaRef'] = isset($klarnaOrderData['klarna_reference']) ? $klarnaOrderData['klarna_reference'] : " - ";
            $this->_aViewData['inSync']     = $this->getEditObject()->getFieldData('klsync') == 1;

        } else {
            $this->_aViewData['sMessage'] =
                Registry::getLang()->translateString("KLARNA_ONLY_FOR_KLARNA_PAYMENT");
        }

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

        return $this->getEditObject()->retrieveKlarnaOrder($this->getEditObject()->getFieldData('klorderid'), $sCountryISO);
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
            $orderRefund = $this->getEditObject()->createOrderRefund($data, $this->getEditObject()->getFieldData('klorderid'), $sCountryISO);
        } catch (\Exception $e) {
            Registry::get("oxUtilsView")->addErrorToDisplay($e->getMessage());
        }

        return $orderRefund;
    }

    /**
     *
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
}