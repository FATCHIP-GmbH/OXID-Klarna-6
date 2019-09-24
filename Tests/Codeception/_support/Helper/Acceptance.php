<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace TopConcepts\Klarna\Tests\Codeception\_support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module\WebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;

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

    /**
     * @param $frameLocator
     * @throws \Codeception\Exception\ModuleException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function waitForFrame($frameLocator) {
        /** @var WebDriver $webDriverModule */
        $webDriverModule = $this->getModule('WebDriver');
        $webDriverModule->webDriver->wait()->until(WebDriverExpectedCondition::frameToBeAvailableAndSwitchToIt($frameLocator));
    }
}
