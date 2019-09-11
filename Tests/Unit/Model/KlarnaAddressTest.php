<?php

namespace TopConcepts\Klarna\Tests\Unit\Model;


use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaAddressTest
 * @package TopConcepts\Klarna\Tests\Unit\Models
 * @covers \TopConcepts\Klarna\Model\KlarnaAddress
 */
class KlarnaAddressTest extends ModuleUnitTestCase
{

    public function testIsTemporary()
    {
        $address = $this->createKlarnaAddress();
        $tmp = new Field(1, Field::T_RAW);

        $this->assertNull($address->isTemporary());
        $address->oxaddress__tcklarna_temporary = $tmp;

        $this->assertEquals($address->isTemporary(), $tmp->value);
    }

    /**
     * @dataProvider klExistsDataProvider
     * @param $id
     * @param $expectedResult
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     */
    public function testKlExists($id, $expectedResult)
    {
        $address = $this->createKlarnaAddress();
        $address->oxaddress__oxuserid = new Field($id, Field::T_RAW);

        $this->assertEquals($expectedResult, $address->klExists());
    }

    public function klExistsDataProvider()
    {
        return [
            ['oxdefaultadmin', '41b545c65fe99ca2898614e563a7108f'],
            ['none', false],
        ];
    }

    public function testKlExistsException()
    {
        $this->expectException(StandardException::class);
        $address = $this->createKlarnaAddress();
        $address->klExists();
    }

    public function testIsValid()
    {
        $address = oxNew(Address::class);
        $this->assertFalse($address->isValid());

        $address = $this->createKlarnaAddress();
        $this->assertTrue($address->isValid());
    }

    protected function createKlarnaAddress()
    {

        $address = oxNew(Address::class);
        $address->oxaddress__oxfname = new Field('Gregory', Field::T_RAW);
        $address->oxaddress__oxlname = new Field('Dabrowski', Field::T_RAW);
        $address->oxaddress__oxstreet = new Field('Karnapp', Field::T_RAW);
        $address->oxaddress__oxstreetnr = new Field('25', Field::T_RAW);
        $address->oxaddress__oxzip = new Field('21079', Field::T_RAW);
        $address->oxaddress__oxcity = new Field('Hamburg', Field::T_RAW);
        $address->oxaddress__oxcountryid = new Field('a7c40f631fc920687.20179984', Field::T_RAW);

        return $address;
    }
}
