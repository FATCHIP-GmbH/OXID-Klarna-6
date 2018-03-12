<?php
namespace Klarna\Klarna\Controllers;

use OxidEsales\Eshop\Application\Controller\FrontendController;
/**
 * Controller for Klarna Checkout Acknowledge push request
 */
class KlarnaAcknowledge extends FrontendController
{
    protected $aOrder;

    /**
     * @param string $sCountryISO
     * @return KlarnaOrderManagementClient|KlarnaClientBase $klarnaClient
     */
    protected function getKlarnaClient($sCountryISO)
    {
        return KlarnaOrderManagementClient::getInstance($sCountryISO);
    }

    /**
     * @throws oxConnectionException
     * @throws oxException
     */
    public function init()
    {
        parent::init();

        $orderId = oxRegistry::getConfig()->getRequestParameter('klarna_order_id');

        if (empty($orderId)) {
            return;
        }

        $this->registerKlarnaAckRequest($orderId);
        try {
            $oOrder     = $this->loadOrderByKlarnaId($orderId);
            $countryISO = KlarnaUtils::getCountryISO($oOrder->oxorder__oxbillcountryid->value);
            if ($oOrder->isLoaded()) {
                $this->getKlarnaClient($countryISO)->acknowledgeOrder($orderId);
            } elseif ($this->getKlarnaAckCount($orderId) > 1) {
                $this->getKlarnaClient($countryISO)->cancelOrder($orderId);
            }
        } catch (oxException $e) {
            $e->debugOut();

            return;
        }
    }

    /**
     * @param $orderId
     * @return oxOrder
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    protected function loadOrderByKlarnaId($orderId)
    {
        $oOrder = oxNew('oxorder');
        $oxid   = oxDb::getDb()->getOne('SELECT oxid from oxorder where klorderid=?', array($orderId));
        $oOrder->load($oxid);

        return $oOrder;
    }


    /**
     * Register Klarna request in DB
     * @param $orderId
     * @throws oxConnectionException
     */
    protected function registerKlarnaAckRequest($orderId)
    {
        $sql = 'INSERT INTO `kl_ack` (`oxid`, `klreceived`, `klorderid`) VALUES (?,?,?)';
        oxDb::getDb()->Execute(
            $sql,
            array(oxUtilsObject::getInstance()->generateUID(), date('Y-m-d H:i:s'), $orderId)
        );
    }

    /**
     * Get count of Klarna ACK requests for location ID
     *
     * @param $orderId
     * @return string
     * @throws oxConnectionException
     */
    protected function getKlarnaAckCount($orderId)
    {
        $sql = 'SELECT COUNT(*) FROM `kl_ack` WHERE `klorderid` = ?';

        return oxDb::getDb()->getOne($sql, array($orderId));
    }
}