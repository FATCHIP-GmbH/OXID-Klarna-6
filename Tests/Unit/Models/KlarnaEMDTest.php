<?php

namespace TopConcepts\Klarna\Tests\Unit\Models;


use OxidEsales\Eshop\Application\Model\User;
use TopConcepts\Klarna\Models\KlarnaEMD;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaEMDTest extends ModuleUnitTestCase
{
    /**
     * @param $data
     * @dataProvider getAttachmentsDataProvider
     */
    public function testGetAttachments($data, $expectedResult)
    {
        $this->setModuleConfVar('blKlarnaEmdCustomerAccountInfo', $data['blKlarnaEmdCustomerAccountInfo']);
        $this->setModuleConfVar('blKlarnaEmdPaymentHistoryFull', $data['blKlarnaEmdPaymentHistoryFull']);
        $this->setModuleConfVar('blKlarnaEmdPassThrough', $data['blKlarnaEmdPassThrough']);

        $oUser     = oxNew(User::class);
        $klarnaEMD = $this->createStub(KlarnaEMD::class, [
            'getCustomerAccountInfo' => ['blKlarnaEmdCustomerAccountInfo' => $expectedResult['blKlarnaEmdCustomerAccountInfo']],
            'getPaymentHistoryFull'  => ['blKlarnaEmdPaymentHistoryFull' => $expectedResult['blKlarnaEmdPaymentHistoryFull']],
            'getPassThroughField'    => ['blKlarnaEmdPassThrough' => $expectedResult['blKlarnaEmdPassThrough']],
        ]);
        $result    = $klarnaEMD->getAttachments($oUser);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     *
     */
    public function getAttachmentsDataProvider()
    {
        return [
            [
                [
                    'blKlarnaEmdCustomerAccountInfo' => false,
                    'blKlarnaEmdPaymentHistoryFull'  => false,
                    'blKlarnaEmdPassThrough'         => false,
                ],
                [],
            ],
            [
                [
                    'blKlarnaEmdCustomerAccountInfo' => true,
                    'blKlarnaEmdPaymentHistoryFull'  => false,
                    'blKlarnaEmdPassThrough'         => false,
                ],
                [
                    'blKlarnaEmdCustomerAccountInfo' => ['blKlarnaEmdCustomerAccountInfo' => true],
                ],
            ], [
                [
                    'blKlarnaEmdCustomerAccountInfo' => true,
                    'blKlarnaEmdPaymentHistoryFull'  => true,
                    'blKlarnaEmdPassThrough'         => false,
                ],
                [
                    'blKlarnaEmdCustomerAccountInfo' => ['blKlarnaEmdCustomerAccountInfo' => true],
                    'blKlarnaEmdPaymentHistoryFull'  => ['blKlarnaEmdPaymentHistoryFull' => true],
                ],

            ], [
                [
                    'blKlarnaEmdCustomerAccountInfo' => true,
                    'blKlarnaEmdPaymentHistoryFull'  => true,
                    'blKlarnaEmdPassThrough'         => true,
                ],
                [
                    'blKlarnaEmdCustomerAccountInfo' => ['blKlarnaEmdCustomerAccountInfo' => true],
                    'blKlarnaEmdPaymentHistoryFull'  => ['blKlarnaEmdPaymentHistoryFull' => true],
                    'blKlarnaEmdPassThrough'         => ['blKlarnaEmdPassThrough' => true],
                ],
            ],
        ];
    }
}
