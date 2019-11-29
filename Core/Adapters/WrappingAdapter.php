<?php


namespace TopConcepts\Klarna\Core\Adapters;


use Monolog\Registry;

class WrappingAdapter extends BasketCostAdapter
{
    const REFERENCE = 'wrap';
    const NAME = 'Wrapping';

    protected function getReference()
    {
        return self::REFERENCE;
    }

    protected function getName()
    {
        return self::NAME;
    }
}