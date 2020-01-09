<?php


namespace TopConcepts\Klarna\Model;


use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

class KlarnaInstantBasket extends BaseModel
{
    const TABLE_NAME = 'tcklarna_instant_basket';

    const FINALIZED_STATUS = 'FINALIZED';
    const OPENED_STATUS = 'OPENED';

    const TYPE_SINGLE_PRODUCT = 'single_product';
    const TYPE_BASKET = 'basket';

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
     * @codeCoverageIgnore
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

    public function setHash($hash)
    {
        $this->tcklarna_instant_basket__hash = new Field($hash, Field::T_RAW);
    }

    public function getType()
    {
        return $this->tcklarna_instant_basket__type->value;
    }

    /**
     * @codeCoverageIgnore
     * @return bool
     */
    public function isFinalized()
    {
        return $this->tcklarna_instant_basket__status->value === self::FINALIZED_STATUS;
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

    /**
     * returns unique hash
     * @return string
     * @throws \Exception
     */
    public function createHash()
    {
        $sid = Registry::getSession()->getId();
        return md5($sid . '|' . (new \DateTime())->getTimestamp());
    }

    /**
     * Load by hash
     * @param string $hash
     */
    public function loadByHash($hash)
    {
        $query = $this->buildSelectString([
                $this->getViewName() . '.HASH' => $hash
            ]);
        $this->_isLoaded = $this->assignRecord($query);
    }
}