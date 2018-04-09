<?php

namespace TopConcepts\Klarna\Tests\Unit\Models;


use OxidEsales\Eshop\Application\Model\Payment;
use TopConcepts\Klarna\Models\KlarnaPayment;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaPaymentTest extends ModuleUnitTestCase
{

    /**
     * @dataProvider isKPPaymentDataProvider
     */
    public function testIsKPPayment($paymentId, $expectedResult)
    {
        /** @var KlarnaPayment $oPayment */
        $oPayment = oxNew(Payment::class);
        $oPayment->setId($paymentId);

        $result = $oPayment->isKPPayment();
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function isKPPaymentDataProvider()
    {
        return [
            [KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_NOW, true],
            [KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID, false],
            ['bestitamazon', false],
            ['oxidcashondel', false],
        ];
    }

    public function testGetKPMethods()
    {

    }

    public function testSetActiveKPMethods()
    {

    }

    /**
     * @param $paymentId
     * @param $expectedResult
     * @dataProvider PaymentCategoryNameDataProvider
     */
    public function testGetPaymentCategoryName($paymentId, $expectedResult)
    {
        /** @var KlarnaPayment $oPayment */
        $oPayment = oxNew(Payment::class);
        $oPayment->setId($paymentId);
        $result   = $oPayment->getPaymentCategoryName();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     *
     */
    public function PaymentCategoryNameDataProvider()
    {
        return [
            [KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID, 'pay_over_time'],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID, 'pay_later'],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_NOW, 'pay_now'],
            [KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID, false],
            ['bestitamazon', false],
            ['oxidcashondel', false],
        ];
    }

    /**
     * @dataProvider isKlarnaPaymentDataProvider
     */
    public function testIsKlarnaPayment($paymentId, $expectedResult)
    {
        $result = KlarnaPayment::isKlarnaPayment($paymentId);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function isKlarnaPaymentDataProvider()
    {
        return [
            [KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID, true],
            [KlarnaPayment::KLARNA_PAYMENT_PAY_NOW, true],
            [KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID, true],
            ['bestitamazon', false],
            ['oxidcashondel', false],
        ];
    }


    /**
     * @dataProvider KlarnaPaymentsIdDataProvider
     */
    public function testGetKlarnaPaymentsIds($filter, $expectedResult)
    {
        $result = KlarnaPayment::getKlarnaPaymentsIds($filter);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function KlarnaPaymentsIdDataProvider()
    {
        $expectedResult_1 = [
            KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID,
            KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID,
            KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID,
            KlarnaPayment::KLARNA_PAYMENT_PAY_NOW,
        ];
        $expectedResult_2 = [
            KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID,
            KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID,
            KlarnaPayment::KLARNA_PAYMENT_PAY_NOW,
        ];

        return [
            [null, $expectedResult_1],
            ['KP', $expectedResult_2],
        ];
    }

    /**
     * @dataProvider getBadgeUrlDataProvider
     * @param $sessionData
     * @param $klPaymentName
     * @param $expectedResult
     */
    public function testGetBadgeUrl($sessionData, $klPaymentName, $expectedResult)
    {
        $this->setSessionParam('klarna_session_data', $sessionData);

        $oPayment = $this->getMock(Payment::class, ['getPaymentCategoryName']);
        $oPayment->expects($this->once())->method('getPaymentCategoryName')->willReturn($klPaymentName);

        $result = $oPayment->getBadgeUrl();

        $this->assertEquals($expectedResult, $result);
    }

    public function getBadgeUrlDataProvider()
    {
        $sessionData = [
            'payment_method_categories' => [
                [
                    'identifier' => 'pay_later',
                    'name'       => 'Rechnung.',
                    'asset_urls' => [
                        'descriptive' => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/pay_later/descriptive/pink.svg',
                        'standard'    => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/pay_later/standard/pink.svg',
                    ],
                ],
                [
                    'identifier' => 'pay_over_time',
                    'name'       => 'Ratenkauf.',
                    'asset_urls' => [
                        'descriptive' => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/slice_it/descriptive/pink.svg',
                        'standard'    => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/slice_it/standard/pink.svg',
                    ],
                ],
            ],
        ];

        return [
            [$sessionData, 'pay_later', $sessionData['payment_method_categories'][0]['asset_urls']['standard']],
            [$sessionData, 'pay_over_time', $sessionData['payment_method_categories'][1]['asset_urls']['standard']],
        ];
    }
}
