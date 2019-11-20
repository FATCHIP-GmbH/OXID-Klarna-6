<?php


namespace TopConcepts\Klarna\Core\Adapters;


use TopConcepts\Klarna\Core\Exception\InvalidItemException;

class DiscountAdapter extends BaseBasketItemAdapter
{

    /**
     * Adds Klarna Order Line to oBasket
     *
     * @return mixed
     */
    public function addItemToBasket()
    {
        // TODO: Implement addItemToBasket() method.
    }

    /**
     * Compares Klarna Order Line to oxid basket object
     * @param $orderLine
     */
    public function validateItem($orderLine)
    {
        if ($orderLine['total_amount'] + $this->formatAsInt($this->oItem->dDiscount) !== 0) {
            throw new InvalidItemException("INVALID_DISCOUNT_VALUE: " . $orderLine['name']);
        }
    }

    /**
     * Prepares item data before adding it to Order Lines
     * @param $iLang
     * @return $this
     */
    protected function prepareItemData($iLang)
    {
        $quantity = 1;
        $taxRate = $this->formatAsInt($this->oBasket->getAdditionalServicesVatPercent());
        $unitPrice = - $this->formatAsInt($this->oItem->dDiscount);
        $this->itemData['type'] = $this->getKlarnaType();
        $this->itemData['reference'] =  $this->oItem->sOXID;
        $this->itemData['name'] = $this->oItem->sDiscount;
        $this->itemData['quantity'] = $quantity;
        $this->itemData['total_amount'] = $unitPrice * $quantity;
        $this->itemData['total_discount_amount'] = 0;
        $this->itemData['total_tax_amount'] = $this->calcTax($this->itemData['total_amount'], $taxRate);
        $this->itemData['unit_price'] = $unitPrice;
        $this->itemData['tax_rate'] = $taxRate;
        $this->itemData = array_merge(static::DEFAULT_ITEM_DATA, $this->itemData);

        return $this;
    }

    protected function getReference()
    {
        if(isset($this->itemData['reference'])) {
            return $this->itemData['reference'];
        }

        return $this->oItem->sOXID;
    }
}