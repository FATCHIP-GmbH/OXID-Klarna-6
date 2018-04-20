<?php

namespace TopConcepts\Klarna\Tests\Unit\Models;


use OxidEsales\Eshop\Application\Model\User;
use TopConcepts\Klarna\Models\EmdPayload\KlarnaCustomerAccountInfo;
use TopConcepts\Klarna\Models\KlarnaEMD;
use TopConcepts\Klarna\Tests\Unit\Models\EmdPayload\KlarnaPaymentHistoryFullTest;
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

        $klarnaEMD = oxNew(KlarnaEMD::class);

        $oUser = oxNew(User::class);
        $this->createStub(KlarnaCustomerAccountInfo::class, [
            'getCustomerAccountInfo' =>
                ['blKlarnaEmdCustomerAccountInfo' =>
                     ['customer_account_info' =>
                          [
                              [
                                  'unique_account_identifier' => "",
                                  'account_registration_date' => "2018-04-20T15:53:40Z",
                                  'account_last_modified'     => "2018-04-20T15:53:40Z",
                              ],
                          ],
                     ],
                ],
        ]);
        $this->createStub(KlarnaPaymentHistoryFullTest::class, [
            'getPaymentHistoryFull' => [
                'blKlarnaEmdPaymentHistoryFull' => [
                    'payment_history_full' => [
                        ['test' => 'orderhistory'],
                    ],
                ],
            ],
        ]);

        $result = $klarnaEMD->getAttachments($oUser);
//        var_dump($result);
//        die;
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
                    'blKlarnaEmdCustomerAccountInfo' => 0,
                    'blKlarnaEmdPaymentHistoryFull'  => 0,
                ],
                [],
            ],
            [
                [
                    'blKlarnaEmdCustomerAccountInfo' => 1,
                    'blKlarnaEmdPaymentHistoryFull'  => 0,
                ],
                ['customer_account_info' =>
                     [
                         [
                             'unique_account_identifier' => "",
                             'account_registration_date' => "2018-04-20T15:53:40Z",
                             'account_last_modified'     => "2018-04-20T15:53:40Z",
                         ],
                     ],
                ],
            ], [
                [
                    'blKlarnaEmdCustomerAccountInfo' => 0,
                    'blKlarnaEmdPaymentHistoryFull'  => 1,
                ],
                [
                    'payment_history_full' => [
                        ['test' => [
                            'orderhistory' => true,
                        ]],
                    ],
                ],
            ], [
                [
                    'blKlarnaEmdCustomerAccountInfo' => 1,
                    'blKlarnaEmdPaymentHistoryFull'  => 1,
                ],
                ['customer_account_info' =>
                     [
                         'unique_account_identifier' => "",
                         'account_registration_date' => "2018-04-20T15:53:40Z",
                         'account_last_modified'     => "2018-04-20T15:53:40Z",
                     ],
                 'payment_history_full'  => [
                     ['test' => [
                         'orderhistory' => true,
                     ]],
                 ],
                ],
            ],
        ];
    }
}

