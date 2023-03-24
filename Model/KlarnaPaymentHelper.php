<?php


namespace TopConcepts\Klarna\Model;


class KlarnaPaymentHelper
{
    /**
     * Oxid value of Klarna One payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_ID = 'klarna';

    /**
     * Oxid value of Klarna Checkout payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_CHECKOUT_ID = 'klarna_checkout';

    /**
     * Get list of Klarna payments ids
     *
     * @param null|string $filter KP - Klarna Payment Options
     * @return array
     */
    public static function getKlarnaPaymentsIds($filter = null)
    {
        if ($filter === 'KP') {
            return array(
                self::KLARNA_PAYMENT_ID,
            );
        }

        $allPayments = array(
            self::KLARNA_PAYMENT_CHECKOUT_ID,
            self::KLARNA_PAYMENT_ID,
        );

        return $filter === null ? $allPayments : [];
    }

    /**
     * Check if payment is Klarna payment
     *
     * @param string $paymentId
     * @return bool
     */
    public static function isKlarnaPayment($paymentId)
    {
        return in_array($paymentId, self::getKlarnaPaymentsIds());
    }

}