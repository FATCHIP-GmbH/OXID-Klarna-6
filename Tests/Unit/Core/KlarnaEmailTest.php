<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use TopConcepts\Klarna\Core\KlarnaEmail;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaEmailTest extends ModuleUnitTestCase
{

    public function testSendChangePwdEmail()
    {
        $klarnaEmail = oxNew(KlarnaEmail::class);
        $result = $klarnaEmail->sendChangePwdEmail('invalidemail');

        $this->assertFalse($result);

        $klarnaEmail = $this->createStub(KlarnaEmail::class, ['send' => false]);
        $this->setConfigParam('blMallUsers', true);
        $result = $klarnaEmail->sendChangePwdEmail('info@topconcepts.de');
        $this->assertEquals(-1, $result);

        $klarnaEmail = $this->createStub(KlarnaEmail::class, ['send' => true]);
        $result = $klarnaEmail->sendChangePwdEmail('info@topconcepts.de');
        $this->assertTrue($result);

    }
}
