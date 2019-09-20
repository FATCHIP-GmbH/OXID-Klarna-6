<?php
namespace TopConcepts\Klarna\Tests\Codeception;

use Codeception\Util\Fixtures;
use Exception;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;

class CheckoutKCOCest {

    /**
     * @group KCO_frontend
     * @param AcceptanceTester $I
     * @throws Exception
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

        $I->fillKcoUserForm($I);
        //different delivery address
        $I->fillKcoShippingForm($I);

        $I->see('Create Customer Account AND subscribe to Newsletter');
        $I->executeJS('document.querySelector("#additional_checkbox_from_merchant__root>div input").click()');
        $I->executeJS('document.querySelector("[data-cid=\'button.buy_button\']").click()');
        $I->switchToIFrame(); // navigate back to to main document frame
        $I->waitForElement('#thankyouPage');
        $I->waitForPageLoad();

        $billEmail = Fixtures::get('gKCOEmail'); // recall generated and stored email
        $I->seeInDatabase('oxuser', ['oxusername' => $billEmail, 'oxpassword !=' => '']);
        $klarnaId = $I->grabFromDatabase('oxorder', 'TCKLARNA_ORDERID', ['OXBILLEMAIL' => $billEmail]);
        $I->assertNotEmpty($klarnaId);

        $inputDataMapper = [
            'sKCOFormPostCode' => 'OXBILLZIP',
            'sKCOFormGivenName' => 'OXBILLFNAME',
            'sKCOFormFamilyName' => 'OXBILLLNAME',
            'sKCOFormStreetName' => 'OXBILLSTREET',
            'sKCOFormStreetNumber' => 'OXBILLSTREETNR',
            'sKCOFormCity' => 'OXBILLCITY',
            'sKCOFormDelPostCode' => 'OXDELZIP',
            'sKCOFormDelStreetName' => 'OXDELSTREET',
            'sKCOFormDelStreetNumber' => 'OXDELSTREETNR',
            'sKCOFormDelCity' => 'OXDELCITY',
        ];

        $I->seeOrderInDb($klarnaId, $inputDataMapper);
        $I->seeInKlarnaAPI($klarnaId, "AUTHORIZED", true);
    }
}
