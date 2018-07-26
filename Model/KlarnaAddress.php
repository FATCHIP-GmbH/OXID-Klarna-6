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


use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\StandardException;

class KlarnaAddress extends KlarnaAddress_parent
{

    /**
     * Checks if this address is on the user address list
     * If address is not on the list returns true
     * Else return oxAddressId
     * Always use === operator with this method
     *
     * @return bool
     * @throws StandardException
     */
    public function klExists()
    {
        // get user address list
        $oUser     = oxNew(User::class);
        $sUserOxid = $this->oxaddress__oxuserid->value;

        if ($sUserOxid) {

            $oUser->load($sUserOxid);
            $aAddressList = $oUser->getUserAddresses();

            // compare
            foreach ($aAddressList as $oAddress) {
                if ($this->compareObjectFields($oAddress)) {

                    return $oAddress->getId();
                }
            }

            return false;

        } else {
            throw new StandardException('oxaddress_oxuserid is empty');
        }
    }


    /**
     * Compare two oxAddress objects. If data filds are the same return true
     *
     * @param Address $that other object
     * @return boolean
     * @throws \oxSystemComponentException
     */
    protected function compareObjectFields($that)
    {
        return $this->tcklarna_getMergedAddressFields() === $that->tcklarna_getMergedAddressFields();
    }

    /**
     * Gets string from oxAddress fields value
     * Returns merged address fields.
     *
     * @return string
     */
    protected function tcklarna_getMergedAddressFields()
    {
        $sDelAddress = '';
        $sDelAddress .= $this->oxaddress__oxfname;
        $sDelAddress .= $this->oxaddress__oxlname;
        $sDelAddress .= $this->oxaddress__oxstreet;
        $sDelAddress .= $this->oxaddress__oxstreetnr;
        $sDelAddress .= $this->oxaddress__oxcity;
        $sDelAddress .= $this->oxaddress__oxcountryid;
        $sDelAddress .= $this->oxaddress__oxzip;

        return $sDelAddress;
    }

    /**
     * @return bool
     * @throws StandardException
     */
    public function isValid()
    {
        $aRequiredFields = array(
            'oxaddress__oxfname',
            'oxaddress__oxlname',
            'oxaddress__oxstreet',
            'oxaddress__oxstreetnr',
            'oxaddress__oxzip',
            'oxaddress__oxcity',
            'oxaddress__oxcountryid',
        );

        $aInvalidFields = array();
        foreach ($aRequiredFields as $sFieldName) {
            if (!$this->getFieldData($sFieldName)) {
                $aInvalidFields[] = $sFieldName;
            }
        }

        if ($aInvalidFields) {
            return false;
        }

        return true;
    }


    public function isTemporary()
    {
        return $this->oxaddress__tcklarna_temporary->value;
    }
}
