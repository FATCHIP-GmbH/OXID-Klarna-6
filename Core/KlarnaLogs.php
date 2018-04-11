<?php

namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Core\Model\BaseModel;

/**
 * Klarna model class for table 'kl_logs'
 */
class KlarnaLogs extends BaseModel
{
    /**
     * Class constructor, initiates parent constructor.
     * @codeCoverageIgnore
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('kl_logs');
    }

    /**
     * @throws \Exception
     * @return bool|string
     */
    public function save()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaLoggingEnabled')) {
            return parent::save();
        }
        return false;
    }
}