<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 30.11.2017
 * Time: 13:31
 */

class klarna_epm_dispatcher extends oxUBase
{

    public function init()
    {
        parent::init();
    }


    public function amazonLogin()
    {
        $this->_sThisTemplate = 'kl_amazon_login.tpl';
        $oViewConf = $this->getViewConfig();
        $this->addTplParam('sAmazonWidgetUrl', $oViewConf->getAmazonProperty('sAmazonLoginWidgetUrl'));
        $this->addTplParam('sAmazonSellerId', $oViewConf->getAmazonConfigValue('sAmazonSellerId'));
        $this->addTplParam('sModuleUrl', $oViewConf->getModuleUrl('bestitamazonpay4oxid'));
    }


    public function render(){
        parent::render();

        return $this->_sThisTemplate;
    }

}