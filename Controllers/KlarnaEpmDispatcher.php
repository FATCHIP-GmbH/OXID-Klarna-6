<?php

namespace TopConcepts\Klarna\Controllers;


use OxidEsales\Eshop\Application\Controller\FrontendController;

/**
 * Class KlarnaEpmDispatcher
 * @package TopConcepts\Klarna\Controllers
 */
class KlarnaEpmDispatcher extends FrontendController
{

    /**
     * @codeCoverageIgnore
     */
    public function init()
    {
        parent::init();
    }


    /**
     * @throws \oxFileException
     * @throws \oxSystemComponentException
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