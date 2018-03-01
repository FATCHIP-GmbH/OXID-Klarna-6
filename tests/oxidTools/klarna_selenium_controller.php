<?php

class klarna_selenium_controller extends oxUBase
{

    public function init()
    {
        if(!KlarnaUtils::getShopConfVar('blIsKlarnaTestMode')) {
            oxRegistry::getUtils()->showMessageAndExit('Not available. Please switch to Klarna Playground Mode.');
        }
    }

    public function getCurrentOrderData()
    {
        $oSession = oxRegistry::getSession();
        $oBasket = $oSession->getBasket();

        $oxidOrder = array();
        $oxidOrder['totalDiscount'] = $oBasket->getVoucherDiscValue();
        $oxidOrder['vouchers'] = $oBasket->getVouchers();

        $data = array(
            'oxidOrder' => $oxidOrder,
            'klarnaOrder' => $this->getCurrentKlarnaOrder()
        );

        return $this->jsonResponse($data);

    }

    /**
     * @return string json
     * @throws oxSystemComponentException
     */
    public function getFinalOrderData()
    {

        $oOrder = $this->getOxidOrder(
            oxRegistry::getConfig()->getRequestParameter('oxOrderNr')
        );
        $countryISO = $this->getDeliveryCountryISO($oOrder);
        $klOrderId = $oOrder->oxorder__klorderid->value;

        $data = array(
            'oxidOrder' => $oOrder,
            'klarnaOrder' => $this->getKlarnaOrder($klOrderId, $countryISO)
        );

        return $this->jsonResponse($data);
    }

    public function setShopConfig()
    {
        $confData = json_decode(file_get_contents('php://input'), true);
        $oConfig = oxNew('KlarnaOxidConfig');
        $oConfig->setShopConfig($confData);
        oxRegistry::getUtils()->showMessageAndExit('200');
    }

    /**
     * Gets oxid order by order number
     * @return oxOrder|false
     * @throws oxSystemComponentException
     */
    protected function getOxidOrder($oxOrderNr)
    {
        $oOrder = oxNew('oxOrder');
        $sSql     = $oOrder->buildSelectString(array('oxordernr' => $oxOrderNr));
        $assigned = $oOrder->assignRecord($sSql);

        if(!$assigned)
            return false;

        return $oOrder;
    }


    protected function getCurrentKlarnaOrder()
    {
        $oClient = KlarnaCheckoutClient::getInstance();
        $aKlarnaOrder = $oClient->getOrder();
        unset($aKlarnaOrder['html_snippet']);

        return $aKlarnaOrder;
    }

    protected function getKlarnaOrder($klOrderId, $countryISO)
    {
        $KOMClient = KlarnaOrderManagementClient::getInstance($countryISO);
        $aKlarnaOrder = $KOMClient->getOrder($klOrderId);

        return $aKlarnaOrder;
    }

    protected function getDeliveryCountryISO($oOrder)
    {
        $oxCountryId = $oOrder->oxorder__oxdelcountryid->value ?: $oOrder->oxorder__oxbillcountryid->value;
        return KlarnaUtils::getCountryISO($oxCountryId);
    }

    protected function jsonResponse($data)
    {
        echo json_encode($data);
    }
}