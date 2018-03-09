<?php
namespace Klarna\Klarna\Core;

use OxidEsales\Eshop\Core\Model\BaseModel;

/**
 * Klarna model class for table 'kl_logs'
 */
class KlarnaLogs extends BaseModel
{
    /**
     * Class constructor, initiates parent constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('kl_logs');
    }

    public function save()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaLoggingEnabled')) {
            $this->saveParent();
        }
    }

    /**
     * @codeCoverageIgnore
     * @return mixed
     * @throws \Exception
     */
    protected function saveParent()
    {
        return parent::save();
    }
}