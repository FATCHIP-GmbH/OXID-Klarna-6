<?php


namespace TopConcepts\Klarna\Model;


use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;

class KlarnaInstantBasket extends BaseModel
{
    const TABLE_NAME = 'tcklarna_instant_basket';

    const FINALIZED_STATUS = 'FINALIZED';

    const TYPE_SINGLE_PRODUCT = 'single_product';
    const TYPE_BASKET = 'bakset';

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

    public function setType($type)
    {
        $this->tcklarna_instant_basket__type = new Field($type, Field::T_RAW);
    }

    public function getType()
    {
        return $this->tcklarna_instant_basket__type->value;
    }



    public function isFinalized()
    {
        return $this->tcklarna_instant_basket__status->value === self::FINALIZED_STATUS;
    }

    /**
     * @param $userId
     * @return bool
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function loadByUser($userId)
    {
        $query = $this->buildSelectString([$this->getViewName() . '.OXUSERID' => $userId]);
        $this->_isLoaded = $this->assignRecord($query);

        return $this->_isLoaded;
    }

    public function getBasket()
    {
        return unserialize($this->tcklarna_instant_basket__basket_info->rawValue);
    }

    public function save()
    {
        $now = date('Y-m-d H:i:s', \OxidEsales\Eshop\Core\Registry::getUtilsDate()->getTime());
        $this->tcklarna_instant_basket__timestamp = new Field($now, Field::T_RAW);

        return parent::save();
    }
}