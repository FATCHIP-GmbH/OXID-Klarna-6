<?php

/**
 * Class Klarna_oxPayment extends OXID default oxPayment class to add additional
 * parameters and payment logic required by specific Klarna payments.
 *
 * @package Klarna
 * @extend oxPayment
 */
class klarna_oxpayment extends klarna_oxpayment_parent
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
     * Oxid value of Klarna Direct Debit payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_DIRECT_DEBIT = 'klarna_direct_debit';

    /**
     * Oxid value of Klarna Sofort (direct_bank_transfer) payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_SOFORT = 'klarna_sofort';

    /**
     * Get list of Klarna payments ids
     *
     * @param null||string $filter KP - Klarna Payment Options
     * @return array
     */
    public static function getKlarnaPaymentsIds($filter = null)
    {
        if(!$filter) {
            return array(
                self::KLARNA_PAYMENT_CHECKOUT_ID,
                self::KLARNA_PAYMENT_SLICE_IT_ID,
                self::KLARNA_PAYMENT_PAY_LATER_ID,
                self::KLARNA_PAYMENT_DIRECT_DEBIT,
                self::KLARNA_PAYMENT_SOFORT,
            );
        }
        if($filter === 'KP'){
            return array(
                self::KLARNA_PAYMENT_SLICE_IT_ID,
                self::KLARNA_PAYMENT_PAY_LATER_ID,
                self::KLARNA_PAYMENT_DIRECT_DEBIT,
                self::KLARNA_PAYMENT_SOFORT,
            );
        }
    }


    public function getKlarnaBadgeName()
    {
        $names = array(
            self::KLARNA_PAYMENT_SLICE_IT_ID => 'slice_it',
            self::KLARNA_PAYMENT_PAY_LATER_ID => 'pay_later',
            self::KLARNA_PAYMENT_DIRECT_DEBIT => 'pay_now',
            self::KLARNA_PAYMENT_SOFORT => 'pay_now'
        );

        return $names[$this->oxpayments__oxid->value];
    }


    public function getPaymentCategoryName()
    {
        if(in_array($this->getId(), self::getKlarnaPaymentsIds('KP'))){
            $names = array(
                self::KLARNA_PAYMENT_SLICE_IT_ID => 'slice_it',
                self::KLARNA_PAYMENT_PAY_LATER_ID => 'pay_later',
                self::KLARNA_PAYMENT_DIRECT_DEBIT => 'direct_debit',
                self::KLARNA_PAYMENT_SOFORT => 'direct_bank_transfer'
            );

            return $names[$this->getId()];
        }

        return false;
    }


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
     * @throws oxConnectionException
     */
    public function getKPMethods()
    {
        $db = oxdb::getDb(oxDb::FETCH_MODE_ASSOC);
        $sql = 'SELECT oxid, oxactive 
                FROM oxpayments
                WHERE oxid IN ("'. join('","',$this->getKlarnaPaymentsIds('KP')) .'")';
        $oRs = $db->execute($sql);

        $kpMethods = array();
        while (!$oRs->EOF){
            $kpMethods[$oRs->fields['oxid']] = $oRs->fields['oxactive'];
            $oRs->moveNext();
        }
        return $kpMethods;
    }

    public function setActiveKPMethods()
    {
        $oConfig = oxRegistry::getConfig();
        $aKPmethods = $oConfig->getRequestParameter('kpMethods');

            foreach($aKPmethods as $oxId => $value){
                $this->load($oxId);
                $this->oxpayments__oxactive = new oxField($value, oxField::T_RAW);
                $this->save();
            }
    }


}
