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

namespace TopConcepts\Klarna\Model\EmdPayload;


use TopConcepts\Klarna\Model\KlarnaEMD;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\ShopVersion;

/**
 * Class for getting customer information
 *
 * @package Klarna
 */
class KlarnaCustomerAccountInfo
{
    /**
     * Max length of user ID (_sOXID value)
     *
     * @var int
     */
    const MAX_IDENTIFIER_LENGTH = 24;

    /**
     * "type": "string",
     * "maxLength": 24
     *
     * @var string
     */
    protected $unique_account_identifier;

    /**
     * "description": "ISO 8601 e.g. 2012-11-24T15:00",
     * "type": "string",
     * "format": "date-time",
     * "pattern": "^[0-9][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]T[0-2][0-9]:[0-5][0-9](:[0-5][0-9]){0,1}Z{0,1}$"
     *
     * @var string
     */
    protected $account_registration_date;

    /**
     * "description": "ISO 8601 e.g. 2012-11-24T15:00",
     * "type": "string",
     * "format": "date-time",
     * "pattern": "^[0-9][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]T[0-2][0-9]:[0-5][0-9](:[0-5][0-9]){0,1}Z{0,1}$"
     *
     * @var string
     */
    protected $account_last_modified;

    /**
     * unique_account_identifier - OXUSER.OXID
     * account_registration_date - OXUSER.OXCREATE
     * account_last_modified - OXUSER.OXTIMESTAMP
     *
     * @param User $user
     * @return array
     */
    public function getCustomerAccountInfo(User $user)
    {
        $oxCreate = $user->oxuser__oxcreate->value;

        if (isset($oxCreate) && $oxCreate != '-') {
            $registration = new \DateTime($user->oxuser__oxcreate->value);
        } else {
            $registration = new \DateTime($user->oxuser__oxregister->value);
        }

        $registration->setTimezone(new \DateTimeZone('Europe/London'));
        $customerInfo = array(
            "unique_account_identifier" => substr($user->getId(), 0, self::MAX_IDENTIFIER_LENGTH),
            "account_registration_date" => $registration->format(KlarnaEMD::EMD_FORMAT),
        );

        // if OXID version >= 4.7.0
        if (version_compare(ShopVersion::getVersion(), '4.7.0') >= 0) {
            $modification = new \DateTime($user->oxuser__oxtimestamp->value);
            $modification->setTimezone(new \DateTimeZone('Europe/London'));
            $customerInfo["account_last_modified"] = $modification->format(KlarnaEMD::EMD_FORMAT);
        }

        $customerInfo = array($customerInfo);

        return array(
            "customer_account_info" => $customerInfo,
        );
    }
}
