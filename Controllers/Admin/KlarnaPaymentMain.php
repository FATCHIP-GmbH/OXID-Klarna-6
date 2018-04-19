<?php

namespace TopConcepts\Klarna\Controllers\Admin;


use TopConcepts\Klarna\Models\KlarnaPayment;

class KlarnaPaymentMain extends KlarnaPaymentMain_parent
{
    public function render()
    {
        $isKlarnaPayment = KlarnaPayment::isKlarnaPayment($this->getEditObjectid());
        $this->addTplParam('isKlarnaPayment', $isKlarnaPayment);

        return parent::render();
    }
}