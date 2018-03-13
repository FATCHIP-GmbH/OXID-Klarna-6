<?php

namespace Klarna\Klarna\Components;


use Klarna\Klarna\Core\KlarnaClientBase;
use Klarna\Klarna\Core\KlarnaOrderManagementClient;
use Klarna\Klarna\Core\KlarnaUtils;
use Klarna\Klarna\Core\KlarnaCheckoutClient;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Basket component
 *
 * @package Klarna
 * @extend OxCmp_basket
 */
class KlarnaBasketComponent extends KlarnaBasketComponent_parent
{
    /**
     * Redirect controller name
     *
     * @var string
     */
    protected $_sRedirectController = 'KlarnaExpress';

    /**
     * Executing action from details page
     */
    public function actionKlarnaExpressCheckoutFromDetailsPage()
    {
        Registry::getSession()->deleteVariable('_newitem');

        $this->tobasket();

        if (Registry::getSession()->getVariable('_newitem') !== null) {
            Registry::getUtils()->redirect(
                Registry::getConfig()->getShopSecureHomeUrl() . 'cl=' . $this->_sRedirectController . '',
                false,
                302
            );
        }
    }

    /**
     * @param null $sProductId
     * @param null $dAmount
     * @param null $aSel
     * @param null $aPersParam
     * @param bool $blOverride
     */
    public function changebasket($sProductId = null, $dAmount = null, $aSel = null, $aPersParam = null, $blOverride = true)
    {
        parent::changebasket($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);
//        $this->kpOrderChanged();

        if (KlarnaUtils::isKlarnaCheckoutEnabled() && Registry::getSession()->hasVariable('klarna_checkout_order_id')) {
            try {
                $this->updateKlarnaOrder();
            } catch (StandardException $e) {
                $e->debugOut();
                KlarnaUtils::fullyResetKlarnaSession();
            }
        }
    }

    /**
     * @param null $sProductId
     * @param null $dAmount
     * @param null $aSel
     * @param null $aPersParam
     * @param bool $blOverride
     */
    public function tobasket($sProductId = null, $dAmount = null, $aSel = null, $aPersParam = null, $blOverride = false)
    {
        parent::tobasket($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);
//        $this->kpOrderChanged();

        if (KlarnaUtils::isKlarnaCheckoutEnabled() && Registry::getSession()->hasVariable('klarna_checkout_order_id')) {
            try {
                $this->updateKlarnaOrder();
            } catch (StandardException $e) {
                $e->debugOut();
                KlarnaUtils::fullyResetKlarnaSession();

            }
        }
    }

    /**
     * Sends update request to checkout API
     * @return array order data
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     * @throws \oxSystemComponentException
     * @internal param oxBasket $oBasket
     * @internal param oxUser $oUser
     */
    protected function updateKlarnaOrder()
    {
        $orderLines = Registry::getSession()->getBasket()->getKlarnaOrderLines();
        $oClient    = $this->getKlarnaCheckoutClient();

        return $oClient->createOrUpdateOrder(json_encode($orderLines), $oClient->getOrderId());
    }

    /**
     * @return KlarnaCheckoutClient|KlarnaClientBase
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    protected function getKlarnaCheckoutClient()
    {
        return KlarnaCheckoutClient::getInstance();
    }

    /**
     * @return KlarnaOrderManagementClient|KlarnaClientBase
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    protected function getKlarnaOrderClient()
    {
        return KlarnaOrderManagementClient::getInstance();
    }
}
