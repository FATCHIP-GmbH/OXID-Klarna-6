<?php


namespace TopConcepts\Klarna\Core\Adapters;


class PaymentAdapter extends BasketCostAdapter
{
    protected $oCard;

    public function prepareItemData($iLang)
    {
        //TODO: implement
        return $this;
    }

    protected function getName()
    {
        return null;
    }

    protected function getReference()
    {
        return null;
    }

    public function addItemToBasket()
    {
        // TODO: Implement addItemToBasket() method.
    }

}