<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 09.04.2018
 * Time: 14:41
 */

namespace TopConcepts\Klarna\Tests\Unit\Models;

use OxidEsales\Eshop\Application\Model\Payment;
use TopConcepts\Klarna\Models\KlarnaPayment;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaPaymentTest extends ModuleUnitTestCase
{

    public function testIsKPPayment()
    {

    }

    public function testGetKPMethods()
    {

    }

    public function testSetActiveKPMethods()
    {

    }

    public function testGetPaymentCategoryName()
    {

    }

    public function testIsKlarnaPayment()
    {

    }

    public function testGetKlarnaPaymentsIds()
    {

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

        //$this->assertEquals($expectedResult, $result);
    }

    public function getBadgeUrlDataProvider()
    {
        $sessionData = [
            'payment_method_categories' => [
                [
                   'identifier' => 'pay_later',
                   'name' => 'Rechnung.',
                   'asset_urls' => [
                       'descriptive' => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/pay_later/descriptive/pink.svg',
                       'standard' => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/pay_later/standard/pink.svg'
                   ]
                ],
                [
                    'identifier' => 'pay_over_time',
                    'name' => 'Ratenkauf.',
                    'asset_urls' =>[
                        'descriptive' => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/slice_it/descriptive/pink.svg',
                        'standard' => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/slice_it/standard/pink.svg'
                    ]
                ]

            ]
        ];

        return [
            [$sessionData, 'pay_later', $sessionData['payment_method_categories'][0]['asset_urls']['standard']],
            [$sessionData, 'pay_over_time', $sessionData['payment_method_categories'][1]['asset_urls']['standard']],
        ];
    }
}
