<?php


namespace TopConcepts\Klarna\Model;


use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;

class KlarnaInstantBasket extends BaseModel
{
    const TABLE_NAME = 'tcklarna_instant_basket';

    /** @var string $oxuserid */
    protected $oxuserid;

    /** @var string $basket_info */
    protected $basket_info;

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

    public function save()
    {
        $this->tcklarna_instant_basket__oxuserid = new Field($this->getOxuserId(), Field::T_RAW);
        $this->tcklarna_instant_basket__basket_info = new Field($this->getBasketInfo(), Field::T_RAW);
        parent::save();
    }

    /**
     * @return string
     */
    public function getOxuserId()
    {
        return $this->oxuserid;
    }

    /**
     * @param string $oxuserid
     */
    public function setOxuserId(string $oxuserid)
    {
        $this->oxuserid = $oxuserid;
    }

    /**
     * @return string
     */
    public function getBasketInfo()
    {
        return $this->basket_info;
    }

    /**
     * @param string $basket_info
     */
    public function setBasketInfo(string $basket_info)
    {
        $this->basket_info = $basket_info;
    }

    public function loadByUser($userId)
    {
        $sql = "SELECT * FROM tcklarna_instant_basket ib WHERE ib.OXUSERID = ".
            DatabaseProvider::getDb()->quote($userId);

        $this->assignRecord($sql);

        return $this;
    }

}