<?php
namespace TopConcepts\Klarna\Tests\Codeception;

use Codeception\Example;
use Codeception\Util\Fixtures;
use Exception;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Tests\Codeception\Page\Kco;

class CheckoutKCOCest {

    /**
     * @group KCO_frontend
     * @param AcceptanceTester $I
     * @throws Exception
     */
    public function createAccountAndSubscribe(AcceptanceTester $I)
    {
        $I->clearShopCache();
        $I->wantToTest('Checkout with newsletter');
        $I->loadKlarnaAdminConfig('KCO');
        $homePage = $I->openShop();
        $basket = new Basket($I);
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $basket->addProductToBasket('058de8224773a1d5fd54d523f0c823e0', 1);
        $homePage->openMiniBasket();
        $I->click(Translator::translate('CHECKOUT'));
        $I->waitForPageLoad();
        $kco = new Kco($I);
        $kco->fillKcoUserForm();
        //different delivery address
        $kco->fillKcoShippingForm();

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
