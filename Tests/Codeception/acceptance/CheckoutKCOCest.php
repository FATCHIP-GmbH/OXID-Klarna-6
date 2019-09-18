<?php
namespace TopConcepts\Klarna\Tests\Codeception;

use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\EshopCommunity\Tests\Codeception\AcceptanceTester;

class CheckoutKCOCest {

    /**
     * @group KCO_frontend
     *
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function createAccountAndSubscribe(AcceptanceTester $I)
    {
        $I->loadKlarnaAdminConfig('KCO');

        $basket = new Basket($I);
        $I->wantToTest('Checkout with newsletter');

        $homePage = $I->openShop();

        //add Product to basket
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $basket->addProductToBasket('058de8224773a1d5fd54d523f0c823e0', 1);

        $homePage->openMiniBasket();
        $I->click(Translator::translate('CHECKOUT'));
//        $I->canSee('Your chosen country');

        $I->fillKcoForm();

    }

    /**
     * @return mixed
     */
    private function getExistingUserData()
    {
        return \Codeception\Util\Fixtures::get('existingUser');
    }
}
