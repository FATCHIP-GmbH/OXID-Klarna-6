<?php

namespace TopConcepts\Klarna\Tests\Unit\Model;


use OxidEsales\Eshop\Application\Model\User;
use TopConcepts\Klarna\Model\EmdPayload\KlarnaCustomerAccountInfo;
use TopConcepts\Klarna\Model\EmdPayload\KlarnaPaymentHistoryFull;
use TopConcepts\Klarna\Model\KlarnaEMD;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaEMDTest extends ModuleUnitTestCase
{
    /**
     * @param $data
     * @dataProvider getAttachmentsDataProvider
     */
    public function testGetAttachments($data, $expectedResult)
    {
        $this->setModuleConfVar('tcklarna_blKlarnaEmdCustomerAccountInfo', $data['tcklarna_blKlarnaEmdCustomerAccountInfo']);
        $this->setModuleConfVar('tcklarna_blKlarnaEmdPaymentHistoryFull', $data['tcklarna_blKlarnaEmdPaymentHistoryFull']);

        $klarnaEMD = oxNew(KlarnaEMD::class);

        $oUser               = oxNew(User::class);
        $oMockCustomerInfo   = $this->createStub(KlarnaCustomerAccountInfo::class, [
            'getCustomerAccountInfo' =>
                ['customer_account_info' =>
                     [
                         [
                             'unique_account_identifier' => "test_id",
                             'account_registration_date' => "2018-04-20T15:53:40Z",
                             'account_last_modified'     => "2018-04-20T15:53:40Z",
                         ],
                     ],
                ],
        ]);
        $oMockPaymentHistory = $this->createStub(KlarnaPaymentHistoryFull::class, [
            'getPaymentHistoryFull' =>
                ['payment_history_full' =>
                     [
                         ['test' => 'orderhistory'],
                     ],
                ],
        ]);
        \oxTestModules::addModuleObject(KlarnaCustomerAccountInfo::class, $oMockCustomerInfo);
        \oxTestModules::addModuleObject(KlarnaPaymentHistoryFull::class, $oMockPaymentHistory);

        $result = $klarnaEMD->getAttachments($oUser);

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
                    'tcklarna_blKlarnaEmdCustomerAccountInfo' => 0,
                    'tcklarna_blKlarnaEmdPaymentHistoryFull'  => 0,
                ],
                [],
            ],
            [
                [
                    'tcklarna_blKlarnaEmdCustomerAccountInfo' => 1,
                    'tcklarna_blKlarnaEmdPaymentHistoryFull'  => 0,
                ],
                [
                    'customer_account_info' =>
                        [
                            [
                                'unique_account_identifier' => "test_id",
                                'account_registration_date' => "2018-04-20T15:53:40Z",
                                'account_last_modified'     => "2018-04-20T15:53:40Z",
                            ],
                        ],
                ],
            ], [
                [
                    'tcklarna_blKlarnaEmdCustomerAccountInfo' => 0,
                    'tcklarna_blKlarnaEmdPaymentHistoryFull'  => 1,
                ],
                [
                    'payment_history_full' => [
                        [
                            'test' => 'orderhistory',
                        ],
                    ],
                ],
            ], [
                [
                    'tcklarna_blKlarnaEmdCustomerAccountInfo' => 1,
                    'tcklarna_blKlarnaEmdPaymentHistoryFull'  => 1,
                ],
                [
                    'customer_account_info' => [
                        [
                            'unique_account_identifier' => "test_id",
                            'account_registration_date' => "2018-04-20T15:53:40Z",
                            'account_last_modified'     => "2018-04-20T15:53:40Z",
                        ],
                    ],
                    'payment_history_full'  => [
                        [
                            'test' => 'orderhistory',
                        ],
                    ],
                ],
            ],
        ];
    }
}

