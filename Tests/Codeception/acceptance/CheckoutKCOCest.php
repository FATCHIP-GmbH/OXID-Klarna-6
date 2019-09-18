<?php
namespace TopConcepts\Klarna\Tests\Codeception;

use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;

class CheckoutKCOCest {

    /**
     * @group KCO_frontend
     *
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function createAccountAndSubscribe(AcceptanceTester $I)
    {
        $I->wantToTest('Checkout with newsletter');
        $I->loadKlarnaAdminConfig('KCO');
        $basket = new Basket($I);
        $homePage = $I->openShop();
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $basket->addProductToBasket('058de8224773a1d5fd54d523f0c823e0', 1);
        $homePage->openMiniBasket();
        $I->click(Translator::translate('CHECKOUT'));
        $I->fillKcoForm($I);



    }

    /**
     * @return mixed
     */
    private function getExistingUserData()
    {
        return \Codeception\Util\Fixtures::get('existingUser');
    }
}
