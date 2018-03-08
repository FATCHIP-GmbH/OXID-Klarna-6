<?php

namespace Klarna\Klarna\Controllers\Admin;


/**
 * Class Klarna_Order_Address
 */
class KlarnaOrderAddress extends klarna_order_address_parent
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
        $this->setViewDataElement('readonly', klarna_oxpayment::isKlarnaPayment( $paymentId ));
    }

    /**
     * Set single element to view data
     *
     * @param string $key
     * @param mixed $value
     */
    protected function setViewDataElement($key, $value)
    {
        $viewData = $this->getViewData();
        $viewData[$key] = $value;

        $this->setViewData($viewData);
    }
}
