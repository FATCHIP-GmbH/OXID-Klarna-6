<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 29.08.2017
 * Time: 18:35
 */

class Klarna_oxEmail extends Klarna_oxEmail_parent
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
    
    //TODO: fix include paths in templates, design new templates
    
    public function __construct()
    {
        parent::__construct();
    }
    
    
    public function sendChangePwdEmail($sEmailAddress, $sSubject = null)
    {
        $myConfig = oxRegistry::getConfig();
        $oDb = oxDb::getDb();

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
            
            $oUser = oxNew('oxuser');
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