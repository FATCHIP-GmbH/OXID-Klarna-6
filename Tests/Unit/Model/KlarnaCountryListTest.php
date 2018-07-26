<?php

namespace TopConcepts\Klarna\Tests\Unit\Model;


use TopConcepts\Klarna\Model\KlarnaCountryList;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaCountryListTest extends ModuleUnitTestCase
{

    /**
     * @param $data
     */
    public function testLoadActiveKCOGlobalCountries()
    {
        $expectedCountries = ['8f241f11095363464.89657222', 'a7c40f6320aeb2ec2.72885259', 'a7c40f631fc920687.20179984'];
        $klarnaCountryList = oxNew(KlarnaCountryList::class);
        $klarnaCountryList->loadActiveKlarnaCheckoutCountries();
        foreach ($klarnaCountryList as $country) {
            $result[] = $country->getId();
        }
        $this->assertEquals($expectedCountries, $result);
    }

    /**
     * @param $data
     */
    public function testLoadActiveNonKlarnaCheckoutCountries()
    {
        $expectedCountries = [
            'a7c40f6321c6f6109.43859248',
            '8f241f11096877ac0.98748826',
            'a7c40f632a0804ab5.18804076',
        ];
        $klarnaCountryList = oxNew(KlarnaCountryList::class);
        $klarnaCountryList->loadActiveNonKlarnaCheckoutCountries();
        foreach ($klarnaCountryList as $country) {
            $result[] = $country->getId();
        }

        $this->assertEquals($expectedCountries, $result);
    }

    /**
     * @param $data
     */
    public function testLoadActiveKlarnaCheckoutCountries()
    {
        $expectedCountries = [
            '8f241f11095363464.89657222',
            '8f241f11096877ac0.98748826',
            'a7c40f631fc920687.20179984',
            'a7c40f6320aeb2ec2.72885259',
            'a7c40f6321c6f6109.43859248',
            'a7c40f632a0804ab5.18804076',
        ];
        $klarnaCountryList = oxNew(KlarnaCountryList::class);
        $klarnaCountryList->loadActiveKCOGlobalCountries();
        foreach ($klarnaCountryList as $country) {
            $result[] = $country->getId();
        }

        $this->assertEquals($expectedCountries, $result);
    }
}
