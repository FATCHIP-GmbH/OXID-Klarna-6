<?php

namespace Klarna\Klarna\Controllers;


use Klarna\Klarna\Core\KlarnaClientBase;
use Klarna\Klarna\Core\KlarnaOrderManagementClient;
use Klarna\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsObject;

/**
 * Controller for Klarna Checkout Acknowledge push request
 */
class KlarnaAcknowledgeController extends FrontendController
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
     *
     */
    public function init()
    {
        parent::init();

        $orderId = Registry::get(Request::class)->getRequestEscapedParameter('klarna_order_id');

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
        } catch (StandardException $e) {
            $e->debugOut();

            return;
        }
    }

    /**
     * @param $orderId
     * @return Order
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    protected function loadOrderByKlarnaId($orderId)
    {
        $oOrder = oxNew(Order::class);
        $oxid   = DatabaseProvider::getDb()->getOne('SELECT oxid from oxorder where klorderid=?', array($orderId));
        $oOrder->load($oxid);

        return $oOrder;
    }


    /**
     * Register Klarna request in DB
     * @param $orderId
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function registerKlarnaAckRequest($orderId)
    {
        $sql = 'INSERT INTO `kl_ack` (`oxid`, `klreceived`, `klorderid`) VALUES (?,?,?)';
        DatabaseProvider::getDb()->Execute(
            $sql,
            array(UtilsObject::getInstance()->generateUID(), date('Y-m-d H:i:s'), $orderId)
        );
    }

    /**
     * Get count of Klarna ACK requests for location ID
     *
     * @param $orderId
     * @return string
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    protected function getKlarnaAckCount($orderId)
    {
        $sql = 'SELECT COUNT(*) FROM `kl_ack` WHERE `klorderid` = ?';

        return DatabaseProvider::getDb()->getOne($sql, array($orderId));
    }
}