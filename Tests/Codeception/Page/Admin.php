<?php


namespace TopConcepts\Klarna\Tests\Codeception\Page;


use OxidEsales\Codeception\Page\Page;
use TopConcepts\Klarna\Tests\Codeception\AcceptanceTester;

class Admin extends Page
{
    /** @var AcceptanceTester */
    protected $user;

    /**
     * @var string
     */
    public $URL = '/admin';

    public function login() {
        $I = $this->user;
        $I->fillField('//*[@id="usr"]', $I->getKlarnaDataByName('sKlarnaAdminUser'));
        $I->fillField('//*[@id="pwd"]', $I->getKlarnaDataByName('sKlarnaAdminPsw'));
        $I->click("//input[@type='submit']");
        $I->waitForFrame("navigation");
        $I->switchToIFrame(); // default frame
    }

    public function selectShop() {
        $I = $this->user;
        $shopId = null;
        if ($shopId) {
            $I->switchToIFrame("navigation");
            $I->waitForElement("selectshop");
            $I->selectOption("selectshop", "label=subshop");
            $I->waitForFrame("basefrm");
        }
        $I->switchToIFrame(); // default frame
    }

    public function selectListItem($label) {
        $I = $this->user;
        $I->waitForFrame("basefrm");
        $I->waitForFrame("list");
        $I->waitForText($label);
        $I->click($label);
        $I->switchToIFrame();
    }

    public function selectDetailsTab($label) {
        $I = $this->user;
        $I->waitForFrame("basefrm");
        $I->waitForFrame("list");
        $I->waitForText($label);
        $I->wait(4);
        $I->click($label);
        $I->switchToIFrame();
    }

    /**
     * In this example we will make a series of clicks to navigation sidebar
     *
     * ``` php
     * <?php
     * $I->navigateTo([$locator1, $locator2]]);
     * ?>
     * ```
     * @param $targetArray array of locators
     */
    public function navigateMenu($targetArray) {
        $I = $this->user;
        $I->switchToIFrame("navigation");
        $I->switchToIFrame("adminnav");
        foreach ($targetArray as $label) {
            $I->click($label);
        }
        $I->switchToIFrame();
    }
}