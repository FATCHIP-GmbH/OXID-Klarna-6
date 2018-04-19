<?php

namespace TopConcepts\Klarna\Models;


use OxidEsales\Eshop\Application\Model\Payment;
use TopConcepts\Klarna\Core\KlarnaConsts;

class KlarnaUserPayment extends KlarnaUserPayment_parent
{
    /**
     * @return bool
     */
    public function isKlarnaPayment()
    {
        return KlarnaPayment::isKlarnaPayment($this->oxuserpayments__oxpaymentsid->value);
    }

    /**
     * @return string
     */
    public function getBadgeUrl()
    {
        $paymentId = $this->oxuserpayments__oxpaymentsid->value;
        if ($paymentId === 'klarna_checkout') {
            return '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_black.png';
        }

        $oPayment = oxNew(Payment::class);
        $oPayment->load($paymentId);

        $from   = '/' . preg_quote('-', '/') . '/';
        $locale = preg_replace($from, '_', strtolower(KlarnaConsts::getLocale()), 1);

        $name = $oPayment->getPaymentCategoryName();
        if ($name === 'pay_over_time') {
            $name = 'slice_it';
        }

        return sprintf("//cdn.klarna.com/1.0/shared/image/generic/badge/%s/%s/standard/pink.png",
            $locale,
            $name
        );
    }
}