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

namespace TopConcepts\Klarna\Controller;


use OxidEsales\Eshop\Application\Controller\FrontendController;

/**
 * Class KlarnaEpmDispatcher
 * @package TopConcepts\Klarna\Controllers
 */
class KlarnaEpmDispatcher extends FrontendController
{
//
//    /**
//     * @codeCoverageIgnore
//     */
//    public function init()
//    {
//        parent::init();
//    }


    /**
     * @throws \oxFileException
     * @throws \oxSystemComponentException
     */
    public function amazonLogin()
    {
        $this->_sThisTemplate = 'tcklarna_amazon_login.tpl';
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