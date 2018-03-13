<?php
namespace Klarna\Klarna\Models;

use OxidEsales\EshopCommunity\Core\Exception\StandardException;
use OxidEsales\PayPalModule\Model\User;

class KlarnaAddress extends KlarnaAddress_parent
{

    /**
     * Checks if this address is on the user address list
     * If address is not on the list returns true
     * Else return oxAddressId
     * Always use === operator with this method
     *
     * @return true | int
     * @throws oxException
     * @throws StandardException
     */
    public function isNew()
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
                    return false;
                }
            }

            return true;

        } else {
            throw new StandardException('oxaddress_oxuserid is empty');
        }
    }

    /**
     * Gets string from oxAddress fields value
     *
     * @return string
     */
    public function convertFieldsToString()
    {
        return $this->kl_getMergedAddressFields();
    }


    /**
     * Compare two oxAddress objects. If data filds are the same return true
     *
     * @param oxAddress $that other object
     * @return boolean
     * @throws oxSystemComponentException
     */
    protected function compareObjectFields($that)
    {
        return $this->convertFieldsToString() === $that->convertFieldsToString();
    }

    /**
     * Returns merged address fields.
     *
     * @return string
     */
    protected function kl_getMergedAddressFields()
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

        if ($aInvalidFields)
            return false;

        if ($this->isNew())
            return true;
    }
}
