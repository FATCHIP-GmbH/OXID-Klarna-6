<?php

namespace TopConcepts\Klarna\Tests\Acceptance\Admin;

use OxidEsales\TestingLibrary\AcceptanceTestCase;

/**
 * Admin Module test
 */
class ModuleActivationTest extends AcceptanceTestCase
{
    /**
     * Testing module activation/deactivation
     *
     * @throws \Exception
     */
    public function testModuleSettings()
    {
        $testConfig = $this->getTestConfig();
        if ($testConfig->isSubShop()) {
            $this->markTestSkipped("Test is not for SubShop");
        }

        $this->loginAdmin("Extensions", "Modules", false, 'admin', 'admin');
        $this->activateModule('Klarna');
        $this->deactivateModule('Klarna');
    }

    /**
     * @param $moduleTitle
     * @throws \Exception
     */
    protected function activateModule($moduleTitle)
    {
        $this->openListItem($moduleTitle);
        $this->frame("edit");
        $this->clickAndWait("//form[@id='myedit']//input[@value='Activate']", 2);
        $this->waitForFrameToLoad('list');
        $this->assertElementPresent("//form[@id='myedit']//input[@value='Deactivate']");
        $this->assertTextPresent($moduleTitle);
        $this->assertTextPresent("4.0.0");
        $this->assertTextPresent("Klarna Extension");
    }

    /**
     * @param $moduleTitle
     * @throws \Exception
     */
    protected function deactivateModule($moduleTitle)
    {
        $this->openListItem($moduleTitle);
        $this->frame("edit");
        $this->clickAndWait("//form[@id='myedit']//input[@value='Deactivate']", 2);
        $this->waitForFrameToLoad('list');
        $this->assertElementPresent("//form[@id='myedit']//input[@value='Activate']");
    }



}
