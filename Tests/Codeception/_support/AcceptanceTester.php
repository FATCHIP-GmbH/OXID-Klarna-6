<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace TopConcepts\Klarna\Tests\Codeception;

use Exception;
use OxidEsales\Codeception\Page\Home;
use TopConcepts\Klarna\Tests\Codeception\Page\Admin;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Open shop first page.
     */
    public function openShop()
    {
        $I = $this;
        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);
        return $homePage;
    }

    /**
     * Open shop first page.
     */
    public function openShopAdminPanel()
    {
        $I = $this;
        $adminHome = new Admin($I);
        $I->amOnPage($adminHome->URL);
        return $adminHome;
    }

    public function switchCurrency($currency)
    {
        $this->click(".currencies-menu");
        $this->click($currency);
    }
}
