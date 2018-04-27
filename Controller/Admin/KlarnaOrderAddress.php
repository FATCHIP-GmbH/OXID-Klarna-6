<?php

namespace TopConcepts\Klarna\Controller\Admin;


use TopConcepts\Klarna\Model\KlarnaPayment;

/**
 * Class Klarna_Order_Address
 */
class KlarnaOrderAddress extends KlarnaOrderAddress_parent
{
    /**
     * Executes parent method parent::render(), creates oxorder and
     * oxuserpayment objects, passes data to Smarty engine and returns
     * name of template file "order_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        $parentOutput = parent::render();

        $order = $this->getViewDataElement('edit');
        $this->setReadonlyValue($order->oxorder__oxpaymenttype->value);

        return $parentOutput;
    }

    /**
     * @param string $paymentId
     */
    protected function setReadonlyValue($paymentId)
    {
        $this->addTplParam('readonly', KlarnaPayment::isKlarnaPayment( $paymentId ));
    }
}
