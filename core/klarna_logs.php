<?php

/**
 * Klarna model class for table 'kl_logs'
 */
class Klarna_Logs extends oxBase
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
     */
    protected function saveParent()
    {
        return parent::save();
    }
}