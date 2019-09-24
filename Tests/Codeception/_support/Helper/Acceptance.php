<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace TopConcepts\Klarna\Tests\Codeception\_support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{

    /**
     * @param $locator WebDriver locator
     * @return mixed
     * @throws \Codeception\Exception\ModuleException
     */
    public function isElementPresent($locator) {
        return $els = $this->getModule('WebDriver')->_findElements($locator);
    }
}
