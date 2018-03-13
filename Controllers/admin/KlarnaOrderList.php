<?php

namespace Klarna\Klarna\Controllers\Admin;


use Klarna\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;

class KlarnaOrderList extends KlarnaOrderList_parent
{
    /**
     *
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function deleteEntry()
    {
        $orderId = $this->getEditObjectId();
        $oOrder  = oxNew(Order::class);
        $oOrder->load($orderId);

        if ($oOrder->isLoaded() && $oOrder->isKlarnaOrder() && !$oOrder->getFieldData('oxstorno')) {
            $orderId     = $oOrder->getFieldData('klorderid');
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));

            try {
                $result                                 = $oOrder->cancelKlarnaOrder($orderId, $sCountryISO);
                $this->getEditObject()->oxorder__klsync = new Field(1);
                $this->getEditObject()->save();
            } catch (StandardException $e) {
                if (strstr($e->getMessage(), 'is canceled.')) {
                    parent::deleteEntry();
                } else {
                    Registry::get(UtilsView::class)->addErrorToDisplay($e);
                }
            }
        } else {
            parent::deleteEntry();
        }
    }


    /**
     *
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     */
    public function storno()
    {
        $orderId = $this->getEditObjectId();
        $oOrder  = oxNew(Order::class);
        $oOrder->load($orderId);

        if ($oOrder->isLoaded() && $oOrder->isKlarnaOrder() && !$oOrder->getFieldData('oxstorno')) {
            $orderId     = $oOrder->getFieldData('klorderid');
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));

            try {
                $result = $oOrder->cancelKlarnaOrder($orderId, $sCountryISO);

                $this->getEditObject()->oxorder__klsync = new Field(1);
                $this->getEditObject()->save();
            } catch (StandardException $e) {

                if (strstr($e->getMessage(), 'is canceled.')) {
                    $e->debugOut();
                    parent::storno();
                } else {
                    Registry::get(UtilsView::class)->addErrorToDisplay($e);
                }
            }
        } else {
            parent::storno();
        }
    }
}