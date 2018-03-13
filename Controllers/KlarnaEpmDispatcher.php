<?php

namespace Klarna\Klarna\Controllers;


use OxidEsales\Eshop\Application\Controller\FrontendController;

/**
 * Class klarnaEpmDispatcher
 * @package Klarna\Klarna\Controllers
 */
class klarnaEpmDispatcher extends FrontendController
{

    public function init()
    {
        parent::init();
    }


    /**
     * @throws \oxFileException
     */
    public function amazonLogin()
    {
        $this->_sThisTemplate = 'kl_amazon_login.tpl';
        $oViewConf = $this->getViewConfig();

        /** @var AmazonViewConfig $oViewConf */
        $this->addTplParam('sAmazonWidgetUrl', $oViewConf->getAmazonProperty('sAmazonLoginWidgetUrl'));
        $this->addTplParam('sAmazonSellerId', $oViewConf->getAmazonConfigValue('sAmazonSellerId'));
        $this->addTplParam('sModuleUrl', $oViewConf->getModuleUrl('bestitamazonpay4oxid'));
    }

    /**
     * @return null|string
     */
    public function render(){
        parent::render();

        return $this->_sThisTemplate;
    }

}