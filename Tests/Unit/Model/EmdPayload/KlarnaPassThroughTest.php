<?php

namespace TopConcepts\Klarna\Tests\Unit\Model\EmdPayload;


use TopConcepts\Klarna\Model\EmdPayload\KlarnaPassThrough;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaPassThroughTest
 * @package TopConcepts\Klarna\Tests\Unit\Models\EmdPayload
 * @covers \TopConcepts\Klarna\Model\EmdPayload\KlarnaPassThrough
 */
class KlarnaPassThroughTest extends ModuleUnitTestCase
{

    public function testGetPassThroughField()
    {
        $passThrough = new KlarnaPassThrough();

        $this->assertEquals('To be implemented by the merchant.', $passThrough->getPassThroughField());
    }
}
