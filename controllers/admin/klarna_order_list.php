<?php

class klarna_order_list extends klarna_order_list_parent
{
    /**
     *
     * @throws oxException
     * @throws oxSystemComponentException
     */
    public function deleteEntry()
    {
        $orderId = $this->getEditObjectId();
        $oOrder  = oxNew('oxorder');
        $oOrder->load($orderId);

        if ($oOrder->isLoaded() && $oOrder->isKlarnaOrder() && !$oOrder->getFieldData('oxstorno')) {
            $orderId     = $oOrder->getFieldData('klorderid');
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));

            try {
                $result = $oOrder->cancelKlarnaOrder($orderId, $sCountryISO);
                $this->getEditObject()->oxorder__klsync = new oxField(1);
                $this->getEditObject()->save();
            } catch (oxException $e) {
                if (strstr($e->getMessage(), 'is canceled.')) {
                    $e->debugOut();
                    parent::deleteEntry();
                } else {
                    $e->debugOut();
                }

                return;
            }
        } else {
            parent::deleteEntry();

            return;
        }
    }


    /**
     * @throws oxSystemComponentException
     */
    public function storno()
    {
        $orderId = $this->getEditObjectId();
        $oOrder  = oxNew('oxorder');
        $oOrder->load($orderId);
        if ($oOrder->isLoaded() && $oOrder->isKlarnaOrder() && !$oOrder->getFieldData('oxstorno')) {
            $orderId     = $oOrder->getFieldData('klorderid');
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));

            try {
                $result = $oOrder->cancelKlarnaOrder($orderId, $sCountryISO);
                $this->getEditObject()->oxorder__klsync = new oxField(1);
                $this->getEditObject()->save();
            } catch (oxException $e) {
                if (strstr($e->getMessage(), 'is canceled.')) {
                    $e->debugOut();
                    parent::storno();
                } else {
                    $e->debugOut();
                }

                return;
            }
        } else {
            parent::storno();
        }
    }
}