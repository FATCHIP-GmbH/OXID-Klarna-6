<?php

namespace TopConcepts\Klarna\Tests\Unit\Model\EmdPayload;


use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Model\EmdPayload\KlarnaCustomerAccountInfo;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaCustomerAccountInfoTest
 * @package TopConcepts\Klarna\Tests\Unit\Models\EmdPayload
 * @covers \TopConcepts\Klarna\Model\EmdPayload\KlarnaCustomerAccountInfo
 *
 */
class KlarnaCustomerAccountInfoTest extends ModuleUnitTestCase
{

    /**
     * @dataProvider customerDataProvider
     * @param $user
     * @param $expectedResult
     */
    public function testGetCustomerAccountInfo($user, $expectedResult)
    {
        $accInfo = oxNew(KlarnaCustomerAccountInfo::class);
        $result = $accInfo->getCustomerAccountInfo($user);

        $this->assertEquals($expectedResult,$result);
    }

    public function customerDataProvider()
    {
        $expectedResult = [
            'customer_account_info' =>
                [
                    [
                        'unique_account_identifier' => "testId",
                        'account_last_modified' => "2018-03-22T10:33:29Z",
                    ],
                ],
        ];

        $user1 = $this->createKlarnaUser();
        $user1->oxuser__oxcreate = new Field('2018-03-21T10:33:29Z', Field::T_RAW);
        $expectedResult1 = $expectedResult;
        $expectedResult1['customer_account_info'][0]['account_registration_date'] = "2018-03-21T10:33:29Z";

        $user2 = $this->createKlarnaUser();
        $user2->oxuser__oxregister = new Field('2018-03-20T10:33:29Z', Field::T_RAW);
        $expectedResult2 = $expectedResult;
        $expectedResult2['customer_account_info'][0]['account_registration_date'] = "2018-03-20T10:33:29Z";

        return [
            [$user1, $expectedResult1],
            [$user2, $expectedResult2]
        ];
    }

    protected function createKlarnaUser()
    {
        $user = oxNew(User::class);
        $user->setId('testId');
        $user->oxuser__oxtimestamp = new Field('2018-03-22 11:33:29', Field::T_RAW);

        return $user;
    }
}
