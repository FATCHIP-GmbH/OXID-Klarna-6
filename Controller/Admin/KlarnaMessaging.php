<?php

namespace TopConcepts\Klarna\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;

class KlarnaMessaging extends KlarnaBaseConfig
{
    protected $_sThisTemplate = 'tcklarna_messaging.tpl';

    /**
     * @codeCoverageIgnore
     */
    public function render()
    {
        // force shopid as parameter
        // Pass shop OXID so that shop object could be loaded
        $sShopOXID = Registry::getConfig()->getShopId();

        $this->setEditObjectId($sShopOXID);

        parent::render();

        return $this->_sThisTemplate;

    }

}