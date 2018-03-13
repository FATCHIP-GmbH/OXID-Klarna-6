<?php

namespace Klarna\Klarna\Models;


use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Request;

/**
 * Class Klarna_oxPayment extends OXID default oxPayment class to add additional
 * parameters and payment logic required by specific Klarna payments.
 *
 * @package Klarna
 * @extend oxPayment
 */
class KlarnaPayment extends KlarnaPayment_parent
{
    /**
     * Oxid value of Klarna Part payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_SLICE_IT_ID = 'klarna_slice_it';

    /**
     * Oxid value of Klarna Invoice payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_PAY_LATER_ID = 'klarna_pay_later';

    /**
     * Oxid value of Klarna Checkout payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_CHECKOUT_ID = 'klarna_checkout';

    /**
     * Oxid value of Klarna Pay Now payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_PAY_NOW = 'klarna_pay_now';

    /**
     * Get list of Klarna payments ids
     *
     * @param null||string $filter KP - Klarna Payment Options
     * @return array
     */
    public static function getKlarnaPaymentsIds($filter = null)
    {
        if (!$filter) {
            return array(
                self::KLARNA_PAYMENT_CHECKOUT_ID,
                self::KLARNA_PAYMENT_SLICE_IT_ID,
                self::KLARNA_PAYMENT_PAY_LATER_ID,
                self::KLARNA_PAYMENT_PAY_NOW,

            );
        }
        if ($filter === 'KP') {
            return array(
                self::KLARNA_PAYMENT_SLICE_IT_ID,
                self::KLARNA_PAYMENT_PAY_LATER_ID,
                self::KLARNA_PAYMENT_PAY_NOW,
            );
        }
    }

    /**
     * @return bool|mixed
     */
    public function getPaymentCategoryName()
    {
        if (in_array($this->getId(), self::getKlarnaPaymentsIds('KP'))) {
            $names = array(
                self::KLARNA_PAYMENT_SLICE_IT_ID  => 'pay_over_time',
                self::KLARNA_PAYMENT_PAY_LATER_ID => 'pay_later',
                self::KLARNA_PAYMENT_PAY_NOW      => 'pay_now',
            );

            return $names[$this->getId()];
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isKPPayment()
    {
        return in_array($this->oxpayments__oxid->value, self::getKlarnaPaymentsIds('KP'));
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

    /**
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function getKPMethods()
    {
        $db  = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sql = 'SELECT oxid, oxactive 
                FROM oxpayments
                WHERE oxid IN ("' . join('","', $this->getKlarnaPaymentsIds('KP')) . '")';
        /** @var \OxidEsales\Eshop\Core\Database\Adapter\Doctrine\ResultSet $oRs */
        $oRs = $db->select($sql);

        $kpMethods = array();
        foreach ($oRs->getIterator() as $dataRow) {
            $kpMethods[$dataRow['oxid']] = $dataRow['oxactive'];
        }

        return $kpMethods;
    }

    public function setActiveKPMethods()
    {
        $aKPmethods = Registry::get(Request::class)->getRequestEscapedParameter('kpMethods');

        foreach ($aKPmethods as $oxId => $value) {
            $this->load($oxId);
            $this->oxpayments__oxactive = new Field($value, Field::T_RAW);
            $this->save();
        }
    }


}
