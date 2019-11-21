<?php


namespace TopConcepts\Klarna\Core\Adapters;


class PaymentAdapter extends BasketCostAdapter
{
    protected function getName()
    {
        return null;
    }

    protected function getReference()
    {
        return '';
    }

}