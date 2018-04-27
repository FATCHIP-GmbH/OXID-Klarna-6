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

namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\User;

/**
 * Class KlarnaEmail
 * @package TopConcepts\Klarna\Core
 */
class KlarnaEmail extends KlarnaEmail_parent
{
    /**
     * Password change reminder mail template
     *
     * @var string
     */
    protected $_sChangePwdTemplate = "changepwd.tpl";
    
    /**
     * Password change reminder plain mail template
     *
     * @var string
     */
    protected $_sChangePwdTemplatePlain = "changepwd_plain.tpl";

//    /**
//     * @codeCoverageIgnore
//     * KlarnaEmail constructor.
//     */
//    public function __construct()
//    {
//        parent::__construct();
//    }


    /**
     * @param $sEmailAddress
     * @param null $sSubject
     * @return bool|int
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function sendChangePwdEmail($sEmailAddress, $sSubject = null)
    {
        $myConfig = Registry::getConfig();
        $oDb = DatabaseProvider::getDb();

        // shop info
        $oShop = $this->_getShop();
        
        // add user defined stuff if there is any
        $oShop = $this->_addForgotPwdEmail($oShop);
        
        //set mail params (from, fromName, smtp)
        $this->_setMailParams($oShop);
        
        // user
        $sWhere = "oxuser.oxactive = 1 and oxuser.oxusername = " . $oDb->quote($sEmailAddress) . " and oxuser.oxpassword != ''";
        $sOrder = "";
        if ($myConfig->getConfigParam('blMallUsers')) {
            $sOrder = "order by oxshopid = '" . $oShop->getId() . "' desc";
        } else {
            $sWhere .= " and oxshopid = '" . $oShop->getId() . "'";
        }
        
        $sSelect = "select oxid from oxuser where $sWhere $sOrder";
        if (($sOxId = $oDb->getOne($sSelect))) {
            
            $oUser = oxNew(User::class);
            if ($oUser->load($sOxId)) {
                // create messages
                $oSmarty = $this->_getSmarty();
                $this->setUser($oUser);
                
                // Process view data array through oxoutput processor
                $this->_processViewArray();
                
                $this->setBody($oSmarty->fetch($this->_sChangePwdTemplate));
                
                $this->setAltBody($oSmarty->fetch($this->_sChangePwdTemplatePlain));
                
                //sets subject of email
                $this->setSubject(($sSubject !== null) ? $sSubject : $oShop->oxshops__oxforgotpwdsubject->getRawValue());
                
                $sFullName = $oUser->oxuser__oxfname->getRawValue() . " " . $oUser->oxuser__oxlname->getRawValue();
                
                $this->setRecipient($sEmailAddress, $sFullName);
                $this->setReplyTo($oShop->oxshops__oxorderemail->value, $oShop->oxshops__oxname->getRawValue());
                
                if (!$this->send()) {
                    return -1; // failed to send
                }
                
                return true; // success
            }
        }
        
        return false; // user with this email not found
    }
}