<?php


namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Core\Price;
use TopConcepts\Klarna\Core\Exception\InvalidShippingException;

abstract class BasketCostAdapter extends BaseBasketItemAdapter
{
    public function validateItem()
    {
        /** @var Price $oShippingCost */
        $oBasketCost = $this->oBasket->getCosts($this->getType());
        $requestedCost = $this->itemData['total_amount'] / 100;
        if ((float)$requestedCost !== $oBasketCost->getBruttoPrice()) {
            throw new InvalidShippingException('INVALID_SHIPPING_COST');
        }
    }

    public function prepareItemData($iLang)
    {
        $oBasketCost = $this->oBasket->getCosts($this->getType());
        if (($oBasketCost)) {
            $taxRate = (int)($oBasketCost->getVat() * 100);
            $unitPrice = (int)($oBasketCost->getBruttoPrice() * 100);
            $quantity = 1;
            $this->itemData['type'] = $this->getKlarnaType();
            $this->itemData['reference'] = $this->getReference();
            $this->itemData['name'] = $this->getName();
            $this->itemData['quantity'] = $quantity;
            $this->itemData['total_amount'] = $unitPrice * $quantity;
            $this->itemData['total_discount_amount'] = 0;
            $this->itemData['total_tax_amount'] = (int)($unitPrice - round($unitPrice / ($taxRate / 10000 + 1), 0));
            $this->itemData['unit_price'] = $unitPrice;
            $this->itemData['tax_rate'] = $taxRate;
            $this->itemData = array_merge(static::DEFAULT_ITEM_DATA, $this->itemData);
        }

        return $this;
    }

    abstract protected function getName();

    abstract protected function getReference();
}