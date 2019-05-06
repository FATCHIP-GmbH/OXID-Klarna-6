<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Model\KlarnaPaymentHelper;

class KlarnaPaymentMain extends KlarnaPaymentMain_parent
{
    public function render()
    {
        $isKlarnaPayment = KlarnaPaymentHelper::isKlarnaPayment($this->getEditObjectid());
        $this->addTplParam('isKlarnaPayment', $isKlarnaPayment);

        return parent::render();
    }
}