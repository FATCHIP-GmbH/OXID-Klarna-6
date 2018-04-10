<?php

namespace TopConcepts\Klarna\Models;


use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Models\EmdPayload\KlarnaCustomerAccountInfo;
use TopConcepts\Klarna\Models\EmdPayload\KlarnaPassThrough;
use TopConcepts\Klarna\Models\EmdPayload\KlarnaPaymentHistoryFull;
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
     * @param User $oUser
     * @return array
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
     * @codeCoverageIgnore
     * @param User $oUser
     * @return array
     */
    protected function getCustomerAccountInfo(User $oUser)
    {
        /** @var KlarnaCustomerAccountInfo $oKlarnaPayload */
        $oKlarnaPayload = new KlarnaCustomerAccountInfo;

        return $oKlarnaPayload->getCustomerAccountInfo($oUser);
    }

    /**
     * Get payment history
     * @codeCoverageIgnore
     * @param User $oUser
     * @return array
     */
    protected function getPaymentHistoryFull(User $oUser)
    {
        /** @var KlarnaPaymentHistoryFull $oKlarnaPayload */
        $oKlarnaPayload = new KlarnaPaymentHistoryFull;

        return $oKlarnaPayload->getPaymentHistoryFull($oUser);
    }

    /**
     * To be implemented by the merchant
     * @codeCoverageIgnore
     * @return array
     */
    protected function getPassThroughField()
    {
        /** @var KlarnaPassThrough $oKlarnaPayload */
        $oKlarnaPayload = new KlarnaPassThrough;

        return $oKlarnaPayload->getPassThroughField();
    }
}
