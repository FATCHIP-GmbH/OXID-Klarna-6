<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Core\KlarnaFormatter;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaFormatterTest extends ModuleUnitTestCase
{

    /**
     * @dataProvider oxidtoklarnaDataprovider
     * @param $object
     * @param $expectedResult
     * @throws \OxidEsales\EshopCommunity\Core\Exception\SystemComponentException
     * @throws \Exception
     */
    public function testOxidToKlarnaAddress($object, $expectedResult)
    {
        if ($object == null) {
            $this->setExpectedException(\Exception::class, $expectedResult);
            KlarnaFormatter::oxidToKlarnaAddress('invalid');
        } else {
            $result = KlarnaFormatter::oxidToKlarnaAddress($object);

            $this->assertEquals($result, $expectedResult);
        }
    }

    public function oxidtoklarnaDataprovider()
    {
        $addressMock = $this->createStub(Address::class, []);

        $userMock = $this->getMock(User::class, ['getFieldData']);
        $userMock->oxuser__oxcountryid = new Field('a7c40f632a0804ab5.18804076', Field::T_RAW);
        $userMock->oxuser__oxstreet = new Field('street', Field::T_RAW);
        $userMock->oxuser__oxstreetnr = new Field('streetnr', Field::T_RAW);
        $userMock->oxuser__oxsal = new Field('Mr', Field::T_RAW);
        $userMock->oxuser__oxmobfon = new Field('000', Field::T_RAW);

        $expectedResultUser = [
            'street_address' => "street streetnr",
            'phone' => "000",
            'title' => "Mr",
            'country' => "gb",
        ];

        $expectedResultAddress = ['street_address' => ' '];

        $expectedExceptionMessage = 'Argument must be instance of User|Address.';

        return [
            [$userMock, $expectedResultUser],
            [$addressMock, $expectedResultAddress],
            [null, $expectedExceptionMessage],
        ];
    }

    /**
     * @dataProvider klarnaToOxidAddressDataprovider
     */
    public function testKlarnaToOxidAddress($address, $addressData, $expected)
    {

        $result = KlarnaFormatter::klarnaToOxidAddress($addressData, $address);
        $this->assertEquals($expected, $result);

    }

    public function klarnaToOxidAddressDataprovider()
    {

        $addressDataBilling['billing_address'] = [
            'street_address' => '01 test',
        ];

        $expectedBilling = [
            'oxuser__oxstreet' => "test",
            'oxuser__oxstreetnr' => "01",
            'oxuser__oxcountryid' => "2db455824e4a19cc7.14731328",
        ];

        $addressDataShipping['shipping_address'] = [
            'date_of_birth' => '01 test',
        ];

        $expectedShipping = [
            'oxaddress__oxcountryid' => "2db455824e4a19cc7.14731328",
            'oxaddress__oxbirthdate' => '01 test'
        ];

        $addressDataShippingWithTitle['shipping_address'] = [
            'title' => 'Mr',
        ];

        $expectedShippingWithTitle = [
            'oxaddress__oxcountryid' => "2db455824e4a19cc7.14731328",
            'oxaddress__oxsal' => "Mr",
        ];

        return [
            [null, null, null],
            ['billing_address', $addressDataBilling, $expectedBilling],
            ['shipping_address', $addressDataShipping, $expectedShipping],
            ['shipping_address', $addressDataShippingWithTitle, $expectedShippingWithTitle],
        ];
    }

    /**
     * @dataProvider formatSalutationDataProvider
     * @param $title
     * @param $country
     * @param $expected
     */
    public function testFormatSalutation($title, $country, $expected)
    {
        $result = KlarnaFormatter::formatSalutation($title, $country);
        $this->assertEquals($result, $expected);

    }

    public function formatSalutationDataProvider()
    {
        return [
            [null, null, false],
            ['Miss', null, 'Ms'],
            ['Frau', 'de', 'Frau'],
        ];

    }
}
