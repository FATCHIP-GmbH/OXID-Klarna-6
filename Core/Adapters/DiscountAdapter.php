<?php


namespace TopConcepts\Klarna\Core\Adapters;


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
        // TODO: Implement validateItem() method.
    }

    /**
     * Prepares item data before adding it to Order Lines
     * @param $iLang
     * @return $this
     */
    protected function prepareItemData($iLang)
    {
        // TODO: Implement prepareItemData() method.
    }

    protected function getReference()
    {
        // TODO: Implement getReference() method.
    }
}