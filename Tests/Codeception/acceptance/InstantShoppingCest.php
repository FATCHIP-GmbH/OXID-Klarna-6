<?php

use TopConcepts\Klarna\Tests\Codeception\AcceptanceTester;
use OxidEsales\Codeception\Step\ProductNavigation;
use Codeception\Util\Fixtures;

class InstantShoppingCest
{
    /**
     * @group instant_shopping
     * @param AcceptanceTester $I
     */
    public function instantShopping(AcceptanceTester $I)
    {
        $I->loadKlarnaAdminConfig('KCO');
        $admin = $I->openShopAdminPanel();
        $admin->login();
        $admin->selectShop();
        $admin->navigateMenu(["Klarna", "Instant Shipping"]);
        $I->waitForFrame("basefrm");
        $I->wait(2);
        $I->click('//*[@id="instant-shopping-control"]');
        $I->wait(2);
        $I->click('//*[@id="toggle-button-placement-details"]');
        $I->click('//*[@id="toggle-button-placement-basket"]');
        $I->wait(2);
        $I->click("//*[@id='form-save-button']");
        $I->wait(3);
        $I->openShop();
        $I->wait(3);
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage('05848170643ab0deb9914566391c0c63');
        $I->wait(4);
        $I->click('//*[@class="instant-shopping-button"]');
        $I->wait(3);
        $this->fillInstantShoppingForm($I);
        $I->waitForElement('//*[@id="checkout-button"]');
        $I->click('//*[@id="checkout-button"]');
        $I->waitForElementClickable('//*[@id="confirmation__bottom"]');
        $I->click('//*[@id="confirmation__bottom"]');
        $I->wait(4);

    }

    protected function fillInstantShoppingForm($I)
    {
        $generatedEmail = time()  . $I->getKlarnaDataByName('sKlarnaKCOEmail');
        $I->waitForElement('#klarna-instant-shopping-fullscreen');
        $I->switchToIFrame("klarna-instant-shopping-fullscreen");
        $I->waitForElementClickable('//*[@id="email"]');
        $I->wait(3);
        $I->fillField("//*[@id=\"postal_code\"]",$I->getKlarnaDataByName('sKCOFormPostCode'));
        $I->fillField("//*[@id=\"email\"]", $generatedEmail);
        $I->fillField("//*[@id=\"given_name\"]",$I->getKlarnaDataByName('sKCOFormGivenName'));
        $I->fillField("//*[@id='family_name']",$I->getKlarnaDataByName('sKCOFormFamilyName'));
        $I->fillField("//*[@id='street_address']",$I->getKlarnaDataByName('sKCOFormStreetName').' '.$I->getKlarnaDataByName('sKCOFormStreetNumber'));
        $I->fillField("//*[@id='city']",$I->getKlarnaDataByName('sKCOFormCity'));
        $I->fillField("//*[@id='phone']",$I->getKlarnaDataByName('sKCOFormPhone'));
        $I->fillField("//*[@id='date_of_birth']",$I->getKlarnaDataByName('sKCOFormDob'));
        $I->waitForElement('//*[@id="title__root"]', 20);
        $I->selectOption("//select[@id='title']", ['value' => 'frau']);
        $I->click("//*[@id=\"address-button\"]");
    }

}