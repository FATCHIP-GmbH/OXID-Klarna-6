<?php


namespace TopConcepts\Klarna\Core\Exception;


use OxidEsales\Eshop\Core\Exception\StandardException;
use TopConcepts\Klarna\Core\Adapters\BaseBasketItemAdapter;

/**
 * @codeCoverageIgnore
 */
class InvalidItemException extends StandardException
{
    /** @var BaseBasketItemAdapter */
    protected $itemAdapter;

    /**
     * @return BaseBasketItemAdapter
     */
    public function getItemAdapter(): BaseBasketItemAdapter
    {
        return $this->itemAdapter;
    }

    /**
     * @param BaseBasketItemAdapter $itemAdapter
     */
    public function setItemAdapter(BaseBasketItemAdapter $itemAdapter): void
    {
        $this->itemAdapter = $itemAdapter;
    }

}