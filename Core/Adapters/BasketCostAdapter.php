<?php


namespace TopConcepts\Klarna\Core\Adapters;


use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;

abstract class BasketCostAdapter extends BaseBasketItemAdapter
{
    /**
     * @param $orderLine
     * @throws InvalidItemException
     */
    public function validateItem($orderLine)
    {
        /** @var Price $oBasketCost */
        $oBasketCost = $this->oBasket->getCosts($this->getType());
        $this->validateData(
            $orderLine,
            'total_amount',
            $this->formatAsInt($oBasketCost->getBruttoPrice())
        );
    }

    public function prepareItemData($iLang)
    {
        $oBasketCost = $this->oBasket->getCosts($this->getType());
        if ($oBasketCost) {
            $taxRate = $this->formatAsInt($oBasketCost->getVat());
            $unitPrice = $this->formatAsInt($oBasketCost->getBruttoPrice());
            $quantity = 1;
            $this->itemData['type'] = $this->getKlarnaType();
            $this->itemData['reference'] = $this->getReference();
            $this->itemData['name'] = $this->getName();
            $this->itemData['quantity'] = $quantity;
            $this->itemData['total_amount'] = $unitPrice * $quantity;
            $this->itemData['total_discount_amount'] = 0;
            $this->itemData['total_tax_amount'] = $this->calcTax($this->itemData['total_amount'], $taxRate);
            $this->itemData['unit_price'] = $unitPrice;
            $this->itemData['tax_rate'] = $taxRate;
            $this->itemData = array_merge(static::DEFAULT_ITEM_DATA, $this->itemData);
        }

        return $this;
    }

    abstract protected function getName();
}