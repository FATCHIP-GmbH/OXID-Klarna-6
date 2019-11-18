<?php


namespace TopConcepts\Klarna\Model;


use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;

class KlarnaInstantBasket extends BaseModel
{
    const TABLE_NAME = 'tcklarna_instant_basket';

    const FINALIZED_STATUS = 'FINALIZED';
    /**
     * Class constructor, initiates parent constructor.
     * @codeCoverageIgnore
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->init(self::TABLE_NAME);
    }

    /**
     * @return string
     */
    public function getOxuserId()
    {
        return $this->tcklarna_instant_basket__oxuserid->value;
    }

    /**
     * @param string $oxuserid
     */
    public function setOxuserId(string $oxuserid)
    {
        $this->tcklarna_instant_basket__oxuserid = new Field($oxuserid, Field::T_RAW);
    }

    /**
     * @return string
     */
    public function getBasketInfo()
    {
        return $this->tcklarna_instant_basket__basket_info->rawValue;
    }

    /**
     * @param string $basket_info
     */
    public function setBasketInfo(string $basket_info)
    {
        $this->tcklarna_instant_basket__basket_info = new Field($basket_info, Field::T_RAW);
    }

    /**
     * @param $newStatus
     */
    public function setStatus($newStatus)
    {
        $this->tcklarna_instant_basket__status = new Field($newStatus, Field::T_RAW);
    }

    public function isFinalized()
    {
        return $this->tcklarna_instant_basket__status->value === self::FINALIZED_STATUS;
    }

    /**
     * @param $userId
     * @return $this
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function loadByUser($userId)
    {
        $sql = "SELECT * FROM tcklarna_instant_basket ib WHERE ib.OXUSERID = ".
            DatabaseProvider::getDb()->quote($userId);

        $this->assignRecord($sql);

        return $this;
    }

    public function getBasket()
    {
        return unserialize($this->tcklarna_instant_basket__basket_info->rawValue);
    }
}