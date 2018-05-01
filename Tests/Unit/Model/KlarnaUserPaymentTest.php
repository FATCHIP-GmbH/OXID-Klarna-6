<?php

namespace TopConcepts\Klarna\Tests\Unit\Model;


use OxidEsales\Eshop\Application\Model\UserPayment;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaUserPaymentTest
 * @package TopConcepts\Klarna\Tests\Unit\Models
 * @covers \TopConcepts\Klarna\Model\KlarnaUserPayment
 */
class KlarnaUserPaymentTest extends ModuleUnitTestCase
{
    /**
     * @dataProvider paymentDataProvider
     * @param $payId
     * @param $expectedResult
     * @param $notUsed
     */
    public function testGetBadgeUrl($payId, $expectedResult, $notUsed)
    {
        $userPaymentModel                               = oxNew(UserPayment::class);
        $userPaymentModel->oxuserpayments__oxpaymentsid = new Field($payId, Field::T_RAW);

        $result = $userPaymentModel->getBadgeUrl();

        $this->assertEquals($result, $expectedResult);
    }

    public function paymentDataProvider()
    {
        return [
            ['klarna_checkout', '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_black.png', true],
            ['klarna_pay_now', '//cdn.klarna.com/1.0/shared/image/generic/badge/de_de/pay_now/standard/pink.png', true],
            ['klarna_pay_later', '//cdn.klarna.com/1.0/shared/image/generic/badge/de_de/pay_later/standard/pink.png', true],
            ['other', '//cdn.klarna.com/1.0/shared/image/generic/badge/de_de//standard/pink.png', false],
            ['klarna_fake', '//cdn.klarna.com/1.0/shared/image/generic/badge/de_de//standard/pink.png', false],
            ['klarna_slice_it', '//cdn.klarna.com/1.0/shared/image/generic/badge/de_de/slice_it/standard/pink.png', true],
        ];
    }

    /**
     * @dataProvider paymentDataProvider
     * @param $payId
     * @param $notUsed
     * @param $isKlarna
     */
    public function testIsKlarnaPayment($payId, $notUsed, $isKlarna)
    {
        $userPaymentModel                               = oxNew(UserPayment::class);
        $userPaymentModel->oxuserpayments__oxpaymentsid = new Field($payId, Field::T_RAW);

        $this->assertEquals($isKlarna, $userPaymentModel->isKlarnaPayment());
    }
}
