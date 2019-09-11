<?php
namespace TopConcepts\Klarna\Tests\Codeception;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\EshopCommunity\Tests\Codeception\AcceptanceTester;

class CheckoutKCOCest {

    /**
     * @group KCO_frontend
     *
     * @param AcceptanceTester $I
     */
    public function createAccountAndSubscribe(AcceptanceTester $I)
    {
        $basket = new Basket($I);
        $I->wantToTest('Checkout ith newsletter');

        $homePage = $I->openShop();

        //add Product to basket
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $basket->addProductToBasket('058de8224773a1d5fd54d523f0c823e0', 1);


//        $userCheckoutPage = $homePage->seeMiniBasketContains([$basketItem1, $basketItem2], '200,00 €', 3);

        $homePage->openMiniBasket();
        $homePage->openCheckout();
        $this->assertTextPresent('Your chosen country');

//
//        $breadCrumbName = Translator::translate("ADDRESS");
//        $userCheckoutPage->seeOnBreadCrumb($breadCrumbName);
//
//        $userData = $this->getExistingUserData();
//        $homePage = $userCheckoutPage->openHomePage()
//            ->loginUser($userData['userLoginName'], $userData['userPassword']);
//
//        $paymentCheckoutPage = $homePage->openMiniBasket()->openCheckout();
//
//        $breadCrumbName = Translator::translate("PAY");
//        $paymentCheckoutPage->seeOnBreadCrumb($breadCrumbName);
    }

    /**
     * @return mixed
     */
    private function getExistingUserData()
    {
        return \Codeception\Util\Fixtures::get('existingUser');
    }
}
