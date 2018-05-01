<?php
/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TopConcepts\Klarna\Model;


use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Model\EmdPayload\KlarnaCustomerAccountInfo;
use TopConcepts\Klarna\Model\EmdPayload\KlarnaPaymentHistoryFull;
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

        return $return;
    }

    /**
     * Get customer account info
     *
     * @param User $oUser
     * @return array
     */
    protected function getCustomerAccountInfo(User $oUser)
    {
        /** @var KlarnaCustomerAccountInfo $oKlarnaPayload */
        $oKlarnaPayload = oxNew(KlarnaCustomerAccountInfo::class);

        return $oKlarnaPayload->getCustomerAccountInfo($oUser);
    }

    /**
     * Get payment history
     *
     * @param User $oUser
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \oxSystemComponentException
     */
    protected function getPaymentHistoryFull(User $oUser)
    {
        /** @var KlarnaPaymentHistoryFull $oKlarnaPayload */
        $oKlarnaPayload = oxNew(KlarnaPaymentHistoryFull::class);

        return $oKlarnaPayload->getPaymentHistoryFull($oUser);
    }
}
