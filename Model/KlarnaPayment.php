<?php
/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TopConcepts\Klarna\Model;


use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Request;
use TopConcepts\Klarna\Core\KlarnaConsts;

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
     * Oxid value of Klarna Pay Now payment
     *
     * @var string
     */
    const KLARNA_DIRECTDEBIT = 'klarna_directdebit';

    /**
     * Oxid value of Klarna card
     *
     * @var string
     */
    const KLARNA_CARD = 'klarna_card';

    /**
     * Oxid value of Klarna Pay Now payment
     *
     * @var string
     */
    const KLARNA_SOFORT = 'klarna_sofort';


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
                self::KLARNA_DIRECTDEBIT,
                self::KLARNA_CARD,
                self::KLARNA_SOFORT,
            );
        }
        if ($filter === 'KP') {
            return array(
                self::KLARNA_PAYMENT_SLICE_IT_ID,
                self::KLARNA_PAYMENT_PAY_LATER_ID,
                self::KLARNA_PAYMENT_PAY_NOW,
                self::KLARNA_DIRECTDEBIT,
                self::KLARNA_CARD,
                self::KLARNA_SOFORT,
            );
        }
    }

    /**
     * Returns Klarna Payment Category Name
     * @return bool|mixed
     */
    public function getPaymentCategoryName()
    {
        if (in_array($this->getId(), self::getKlarnaPaymentsIds('KP'))) {
            $names = array(
                self::KLARNA_PAYMENT_SLICE_IT_ID  => 'pay_over_time',
                self::KLARNA_PAYMENT_PAY_LATER_ID => 'pay_later',
                self::KLARNA_PAYMENT_PAY_NOW      => 'pay_now',
                self::KLARNA_DIRECTDEBIT          => 'direct_debit',
                self::KLARNA_CARD                 => 'card',
                self::KLARNA_SOFORT               => 'direct_bank_transfer',
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
     * @deprecated use KlarnaPaymentHelper
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

    /**
     * Activate KP Methods
     */
    public function setActiveKPMethods()
    {
        $aKPmethods = Registry::get(Request::class)->getRequestEscapedParameter('kpMethods');

        foreach ($aKPmethods as $oxId => $value) {
            $this->load($oxId);
            $this->oxpayments__oxactive = new Field($value, Field::T_RAW);
            $this->save();
            if($oxId == "klarna_pay_now") {
                $this->updatePayNowSubPayments($value);
            }
        }
    }

    /**
     * Activate/Deactivate Pay now sub payments
     * @param $value
     */
    protected function updatePayNowSubPayments($value)
    {
        $this->load("klarna_directdebit");
        $this->oxpayments__oxactive = new Field($value, Field::T_RAW);
        $this->save();

        $this->load("klarna_sofort");
        $this->oxpayments__oxactive = new Field($value, Field::T_RAW);
        $this->save();

        $this->load("klarna_card");
        $this->oxpayments__oxactive = new Field($value, Field::T_RAW);
        $this->save();
    }

    /**
     * Fetch badge url from klarna session data kept in the user session object.
     * @param string $variant
     * @return string
     */
    public function getBadgeUrl($variant = 'standard')
    {
        $klName = $this->getPaymentCategoryName();

        $oSession = Registry::getSession();
        if ($sessionData = $oSession->getVariable('klarna_session_data')) {
            $methodData = array_search($klName, array_column($sessionData['payment_method_categories'], 'identifier'));
            if ($methodData !== null) {

                return $sessionData['payment_method_categories'][$methodData]['asset_urls'][$variant];
            }
        }
        $from   = '/' . preg_quote('-', '/') . '/';
        $locale = preg_replace($from, '_', strtolower(KlarnaConsts::getLocale()), 1);

        //temp fix for payment name mismatch slice_it -> pay_over_time
        if ($klName === 'pay_over_time') {
            $klName = 'slice_it';
        }

        if ($this->checkUrl(
                sprintf(
                    "https://cdn.klarna.com/1.0/shared/image/generic/badge/%s/%s/standard/pink.png",
                    $locale,
                    $klName
                )
            ) == false) {
            $locale = preg_replace($from, '_', strtolower(KlarnaConsts::getLocale(true)), 1);
        }

        return sprintf("https://cdn.klarna.com/1.0/shared/image/generic/badge/%s/%s/standard/pink.png",
            $locale,
            $klName
        );

    }

    /**
     * @param $url
     * @return bool
     */
    protected function checkUrl($url) {
        if (!$url) { return false; }
        $curl_resource = curl_init($url);
        curl_setopt($curl_resource, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl_resource);
        if(curl_getinfo($curl_resource, CURLINFO_HTTP_CODE) == 404) {
            curl_close($curl_resource);
            return false;
        } else {
            curl_close($curl_resource);
            return true;
        }
    }
}
