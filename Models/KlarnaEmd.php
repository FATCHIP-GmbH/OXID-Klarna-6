<?php
namespace Klarna\Klarna\Models;

use Klarna\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Application\Model\User;


/**
 * Class KlarnaEMD
 *
 * @package Klarna
 */
class KlarnaEMD
{
    /**
     * Date format
     *
     * @var string
     */
    const EMD_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * Get attachments from basket
     *
     * @param oxUser $oUser
     * @return array
     * @throws oxSystemComponentException
     */
    public function getAttachments(User $oUser)
    {
        $return = array();

        if (KlarnaUtils::getShopConfVar('blKlarnaEmdCustomerAccountInfo')) {
            $return = array_merge($return, $this->getCustomerAccountInfo($oUser));
        }
        if (KlarnaUtils::getShopConfVar('blKlarnaEmdPaymentHistoryFull')) {
            $return = array_merge($return, $this->getPaymentHistoryFull($oUser));
        }
        if (KlarnaUtils::getShopConfVar('blKlarnaEmdPassThrough')) {
            $return = array_merge($return, $this->getPassThroughField());
        }

        return $return;
    }

    /**
     * Get customer account info
     *
     * @param oxUser $oUser
     * @return array
     * @throws oxSystemComponentException
     */
    protected function getCustomerAccountInfo(User $oUser)
    {
        /** @var Klarna_Customer_Account_Info $oKlarnaPayload */
        $oKlarnaPayload = oxNew('Klarna_Customer_Account_Info');

        return $oKlarnaPayload->getCustomerAccountInfo($oUser);
    }

    /**
     * Get payment history
     *
     * @param oxUser $oUser
     * @return array
     * @throws oxSystemComponentException
     */
    protected function getPaymentHistoryFull(oxUser $oUser)
    {
        /** @var Klarna_Payment_History_Full $oKlarnaPayload */
        $oKlarnaPayload = oxNew('Klarna_Payment_History_Full');

        return $oKlarnaPayload->getPaymentHistoryFull($oUser);
    }

    /**
     * To be implemented by the merchant
     *
     * @return array
     * @throws oxSystemComponentException
     */
    protected function getPassThroughField()
    {
        /** @var Klarna_Pass_Through $oKlarnaPayload */
        $oKlarnaPayload = oxNew('Klarna_Pass_Through');

        return $oKlarnaPayload->getPassThroughField();
    }
}
