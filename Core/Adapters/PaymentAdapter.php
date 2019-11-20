<?php


namespace TopConcepts\Klarna\Core\Adapters;


class PaymentAdapter extends BasketCostAdapter
{
    public function prepareItemData($iLang)
    {
        return $this;
    }

    protected function getName()
    {
        return null;
    }

    protected function getReference()
    {
        return '';
    }

    public function addItemToBasket()
    {
        // TODO: Implement addItemToBasket() method.
    }

}