<?php


namespace TopConcepts\Klarna\Core\Adapters;


use Monolog\Registry;

class WrappingAdapter extends BasketCostAdapter
{

    protected function getReference()
    {
        return 'wrap';
    }

    protected function getName()
    {
        return 'Wrapping';
    }
}