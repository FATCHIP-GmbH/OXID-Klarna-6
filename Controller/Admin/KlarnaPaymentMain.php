<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Model\KlarnaPayment;

class KlarnaPaymentMain extends KlarnaPaymentMain_parent
{
    public function render()
    {
        $isKlarnaPayment = KlarnaPayment::isKlarnaPayment($this->getEditObjectid());
        $this->addTplParam('isKlarnaPayment', $isKlarnaPayment);

        return parent::render();
    }
}