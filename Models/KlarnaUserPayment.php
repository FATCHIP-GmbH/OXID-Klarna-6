<?php

namespace TopConcepts\Klarna\Models;


use Klarna\Klarna\Core\KlarnaConsts;


class KlarnaUserPayment extends KlarnaUserPayment_parent
{
    private $klarnaBadgeUrl = "//cdn.klarna.com/1.0/shared/image/generic/badge/%s/%s/standard/pink.svg";

    private $klarnaPaymentsMap = array(
        'klarna_pay_later'    => 'pay_later',
        'klarna_slice_it'     => 'slice_it',
        'klarna_direct_debit' => 'pay_now',
        'klarna_sofort'       => 'pay_now',
    );

    /**
     * @return bool
     */
    public function isKlarnaPayment()
    {
        return KlarnaPayment::isKlarnaPayment($this->oxuserpayments__oxpaymentsid->value);
    }

    /**
     * @param $paymentId
     * @return string
     */
    public function getKlarnaBadge($paymentId)
    {
        if ($paymentId === 'klarna_checkout') {
            return '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_black.png';
        }

        $paymentSlug = $this->klarnaPaymentsMap[$paymentId];

        return sprintf($this->klarnaBadgeUrl, strtolower(KlarnaConsts::getLocale()), $paymentSlug);
    }
}