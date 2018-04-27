<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use TopConcepts\Klarna\Core\KlarnaLogs;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaLogsTest extends ModuleUnitTestCase
{

    public function testSave()
    {
        $klarnaLogs = oxNew(KlarnaLogs::class);
        $result = $klarnaLogs->save();
        $this->assertFalse($result);

        $this->setModuleConfVar('blKlarnaLoggingEnabled', true, 'bool');
        $result = $klarnaLogs->save();
        $this->assertNotFalse($result);
    }
}
