<?php

namespace TopConcepts\Klarna\Controllers\Admin;


use TopConcepts\Klarna\Core\KlarnaUtils;
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
                $oOrder->cancelKlarnaOrder($orderId, $sCountryISO);
                $oOrder->oxorder__klsync = new Field(1);
                $oOrder->save();
            } catch (StandardException $e) {
                if (!strstr($e->getMessage(), 'is canceled.')) {
                    Registry::get(UtilsView::class)->addErrorToDisplay($e);
                    $_POST['oxid'] = -1;
                    $this->resetContentCache();
                    $this->init();
                    return;
                }
            }
        }

        parent::deleteEntry();
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
                $oOrder->cancelKlarnaOrder($orderId, $sCountryISO);
                $oOrder->oxorder__klsync = new Field(1);
                $oOrder->save();
            } catch (StandardException $e) {

                if (!strstr($e->getMessage(), 'is canceled.')) {
                    Registry::get(UtilsView::class)->addErrorToDisplay($e);
                    $_POST['oxid'] = -1;
                    $this->resetContentCache();
                    $this->init();

                    return;
                }
                $e->debugOut();
            }
        }

        parent::storno();
    }
}