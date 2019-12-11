<?php

namespace TopConcepts\Klarna\Core\Adapters;

use OxidEsales\Eshop\Core\Registry;

class WrappingAdapter extends BaseBasketItemAdapter
{
    const REFERENCE = 'wrap';
    const NAME = 'GIFT_WRAPPING';

    protected function getReference()
    {
        return self::REFERENCE;
    }

    protected function getName()
    {
        return Registry::getLang()->translateString(self::NAME);
    }

    public function validateItem($orderLine)
    {
        $this->validateData($orderLine,
            'total_amount',
            $this->formatAsInt($this->oItem->getPrice()->getBruttoPrice())
        );
    }

    /**
     * @inheritDoc
     */
    protected function prepareItemData($iLang)
    {
        $wrapping = $this->oItem->getWrapping();
        $taxRate = $this->formatAsInt($wrapping->getWrappingPrice()->getVat());
        $unitPrice = $this->formatAsInt($wrapping->getWrappingPrice()->getBruttoPrice());
        $quantity = $this->oItem->getAmount();
        $this->itemData['type'] = $this->getKlarnaType();
        $this->itemData['reference'] = $this->getReference();
        $this->itemData['name'] = $this->getName();
        $this->itemData['quantity'] = $quantity;
        $this->itemData['total_amount'] = $unitPrice * $quantity;
        $this->itemData['total_discount_amount'] = 0;
        $this->itemData['total_tax_amount'] = $this->calcTax($this->itemData['total_amount'], $taxRate);
        $this->itemData['unit_price'] = $unitPrice;
        $this->itemData['tax_rate'] = $taxRate;
        $this->itemData["image_url"] = $wrapping->getPictureUrl();
        $this->itemData = array_merge(static::DEFAULT_ITEM_DATA, $this->itemData);

        return $this;
    }
}