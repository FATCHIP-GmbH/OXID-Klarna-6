<?php


namespace TopConcepts\Klarna\Core\Adapters;


use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;

class DiscountAdapter extends BaseBasketItemAdapter
{
    /**
     * Compares Klarna Order Line to oxid basket object
     * @param $orderLine
     * @throws InvalidItemException
     */
    public function validateItem($orderLine)
    {
        $this->validateData(
            $orderLine,
            'total_amount',
            - $this->formatAsInt( // Discount is transferred to Klarna as negative value
                $this->formatPrice(
                    $this->oItem->dDiscount)
            )
        );
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
        $unitPrice = - $this->formatAsInt(
            $this->formatPrice(
                $this->oItem->dDiscount)
        );
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