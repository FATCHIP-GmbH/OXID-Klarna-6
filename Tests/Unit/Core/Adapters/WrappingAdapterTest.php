<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\Adapters;

use TopConcepts\Klarna\Core\Adapters\WrappingAdapter;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class WrappingAdapterTest extends ModuleUnitTestCase
{

    public function testGetName()
    {
        $adapter = $this->getMockBuilder(WrappingAdapter::class)->disableOriginalConstructor()->setMethods(
            ['getKlarnaType']
        )->getMock();

        $prepareItemData = self::getMethod('getName', WrappingAdapter::class);
        $result = $prepareItemData->invokeArgs($adapter, []);

        $this->assertSame(WrappingAdapter::NAME, $result);
    }

    public function getReference()
    {
        $adapter = $this->getMockBuilder(WrappingAdapter::class)->disableOriginalConstructor()->setMethods(
            ['getKlarnaType']
        )->getMock();

        $prepareItemData = self::getMethod('getReference', WrappingAdapter::class);
        $result = $prepareItemData->invokeArgs($adapter, []);

        $this->assertSame(WrappingAdapter::REFERENCE, $result);
    }

}