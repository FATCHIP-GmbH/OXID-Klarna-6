<?php


namespace TopConcepts\Klarna\Core\Adapters;

/**
 * Class PaymentAdapter
 * @package TopConcepts\Klarna\Core\Adapters
 *
 * Adapter stub, required to skipp empty payment cost appended to basket by default.
 * Requires implementation if Adapters abstraction will be used with other Klarna services (KCO and KP)
 */
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