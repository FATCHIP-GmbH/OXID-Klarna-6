<?php
namespace TopConcepts\Klarna\Tests\Codeception;

use Codeception\Example;
use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Tests\Codeception\Page\Kco;

class CheckoutKCOCest {

    /**
     * Test new order guest user
     * @group KCO_frontend
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function frontendKcoOrderNachnahme(AcceptanceTester $I)
    {
        $I->loadKlarnaAdminConfig('KCO');
        $I->setExternalPayment('oxidcashondel', ['TCKLARNA_EXTERNALNAME' => 'Nachnahme', 'TCKLARNA_EXTERNALPAYMENT' => 1]);
        $homePage = $I->openShop();
        $I->waitForPageLoad();
        $basket = new Basket($I);
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $basket->addProductToBasket('058de8224773a1d5fd54d523f0c823e0', 1);
        $homePage->openMiniBasket();
        $I->click(Translator::translate('CHECKOUT'));
        $I->waitForPageLoad();
        $I->selectOption("#other-countries", 'DE');

        //Fill order info
        $kco = new Kco($I);
        $kco->fillKcoUserForm();
        $I->wait(4);
        $I->click("//*[@id='payment-selector-external_nachnahme']");
        $I->wait(1);
        $I->selectOption("//*[@name='payment-selector']", "external_nachnahme");
        $I->wait(1);
        $I->executeJS('document.querySelector("[data-cid=\'button.buy_button\']").click()');
        $I->switchToIFrame(); // navigate back to to main document frame
        $I->waitForPageLoad();
        if ($I->isElementPresent("#orderAddress")) {
            $I->waitForPageLoad();
            $I->click(Translator::translate('SUBMIT_ORDER'));
        }
        $I->waitForElement('#thankyouPage');
        $I->waitForPageLoad();

        $billEmail = Fixtures::get('gKCOEmail'); // recall generated and stored email
        $I->seeInDatabase('oxuser', ['oxusername' => $billEmail, 'oxpassword' => '']);
        $orderNumber = $I->grabFromDatabase('oxorder', 'OXORDERNR', ['OXBILLEMAIL' => $billEmail]);
        $I->seeInPageSource(
            sprintf(Translator::translate('REGISTERED_YOUR_ORDER'), $orderNumber)
        );
    }

    /**
     * @group KCO_frontend
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function KcoWithDHLPackstation(AcceptanceTester $I) {
        $I->loadKlarnaAdminConfig('KCO');
        $I->assignDHLPackStation();
        
        $homePage = $I->openShop();
        $I->waitForPageLoad();
        $basket = new Basket($I);
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $homePage->openMiniBasket();
        $I->click(Translator::translate('CHECKOUT'));
        $I->waitForPageLoad();
        $I->selectOption("#other-countries", 'DE');
        
        $kco = new Kco($I);
        $kco->fillKcoUserForm();
        $I->wait(4);
        $kco->submitPackstationOption();
        $I->wait(5);
        $I->executeJS('document.querySelector("[data-cid=\'button.buy_button\']").click()');
        $I->wait(10);
        $I->switchToIFrame(); // navigate back to to main document frame
        $I->waitForElement('#thankyouPage');
        $I->waitForPageLoad();

        $billEmail = Fixtures::get('gKCOEmail'); // recall generated and stored email
        $I->seeInDatabase('oxuser', ['oxusername' => $billEmail, 'oxpassword' => '']);
        $klarnaId = $I->grabFromDatabase('oxorder', 'TCKLARNA_ORDERID', ['OXBILLEMAIL' => $billEmail]);
        $I->assertNotEmpty($klarnaId);

        $inputDataMapper = [
            'sKCOFormPostCode' => 'OXBILLZIP',
            'sKCOFormGivenName' => 'OXBILLFNAME',
            'sKCOFormFamilyName' => 'OXBILLLNAME',
            'sKCOFormStreetName' => 'OXBILLSTREET',
            'sKCOFormStreetNumber' => 'OXBILLSTREETNR',
            'sKCOFormCity' => 'OXBILLCITY',
            'sKCOFormDelPackstation' => 'OXDELSTREET',
            'sKCOFormDelMachineId' => 'OXDELSTREETNR',
            'sKCOFormDelCustomerNumber' => 'OXDELADDINFO',
            'sKCOFormDelCity' => 'OXDELCITY'
        ];

        $I->seeOrderInDb($klarnaId, $inputDataMapper);
        $I->seeInKlarnaAPI($klarnaId, "AUTHORIZED", true);
    }

    /**
     * @group KCO_frontend
     * @dataProvider klarnaKCOMethodsProvider
     * @param AcceptanceTester $I
     * @param Example $dataSet
     * @throws \Exception
     */
    public function loginAsDifferentCountry(AcceptanceTester $I, Example $dataSet) {

        list($country, $phone, $number) = $dataSet;
        $I->loadKlarnaAdminConfig('KCO');
        $homePage = $I->openShop();
        $I->waitForPageLoad();
        $basket = new Basket($I);
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $basket->addProductToBasket('058de8224773a1d5fd54d523f0c823e0', 1);
        $currency = KlarnaConsts::getCountry2CurrencyArray()[$country];
        $I->comment("Currency: $currency\n");
        $I->switchCurrency($currency);
        $I->waitForPageLoad();
        $homePage->openMiniBasket();
        $I->click(Translator::translate('CHECKOUT'));
        $I->waitForPageLoad();
        $I->wait(2); // fixes issue with popup showing later
        $I->selectOption("#other-countries", $country);
        $I->wait(2);

        $kco = new Kco($I);
        $kco->loginKlarnaWidget($country);
        $I->waitForPageLoad();
        $kco->fillKCOLoggedInUserForm($phone, $number);
        $I->wait(7);
        $I->selectOption('#SHIPMO-container input[name=radio]', 'UPS 48');
        $I->wait(4);
        if($country == 'GB' || $country == 'BE') {
            $kco->fillPayment();
        }

        $I->executeJS('document.querySelector("[data-cid=\'button.buy_button\']").click()');

        if($country == 'SE') {
            $I->wait(4);
            $I->switchToIFrame();
            $I->wait(1);
            if ($I->isElementPresent("#klarna-fullscreen-iframe")) {
                $I->switchToIFrame('klarna-fullscreen-iframe');
                $I->wait(1);
                $I->waitForElement('#clearingNumber');
                $I->fillField("//input[@id='clearingNumber']", '1234');
                $I->fillField("//input[@id='accountNumber']", '12345');
                $I->click('Continue');
                $I->wait(60);
                $I->click('Confirm with Mobile BankID');
            }
        }

        $I->wait(10);
        $I->switchToIFrame(); // navigate back to to main document frame
        $I->waitForElement('#thankyouPage');
        $I->waitForPageLoad();

        $klarnaId = $I->grabFromDatabase('oxorder', 'TCKLARNA_ORDERID', ['OXBILLEMAIL' => "user_".strtolower($country)."@oxid-esales.com"]);
        $I->assertNotEmpty($klarnaId);
        $I->seeInKlarnaAPI($klarnaId, "AUTHORIZED", false);
    }

    /**
     * @return array
     */
    protected function klarnaKCOMethodsProvider()
    {
        return [
            ['BE', '0488836320', ''],
            ['GB', '07907920647', ''],
            ['FI', '30306900', '311280999J'],
            ['AT', '0676 2600000', ''],
            ['SE', '30306900', '8803307019'],
            ['NO', '48404583', '01018043587'],
            ['NL', '0642227516', ''],
            ['DK', '41468007', '0801363945'],
        ];
    }

    /**
     * @group KCO_frontend
     * @param AcceptanceTester $I
     * @throws \Exception
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
        $I->click("//form[@id='select-country-form']//button[@value='DE']");
        $I->wait(2);

        $kco = new Kco($I);
        $kco->fillKcoUserForm();
        $I->wait(2);
        //different delivery address
        $kco->fillKcoShippingForm();
        $I->wait(4);
        $I->see('Create Customer Account AND subscribe to Newsletter');
        $I->wait(4);
        // js clicks - the only working way to click Newsletter checkbox and PlaceOrder
        $I->executeJS('document.querySelector("#additional_checkbox_from_merchant__root>div input").click()');
        $I->wait(2);
        $I->executeJS('document.querySelector("[data-cid=\'button.buy_button\']").click()');
        $I->wait(2);
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

    /**
     * @group KCO_frontend
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function differentShippingMethod(AcceptanceTester $I)
    {
        $I->clearShopCache();
        $I->wantToTest('Checkout with different shipping method');
        $I->loadKlarnaAdminConfig('KCO');
        $homePage = $I->openShop();
        $basket = new Basket($I);
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $basket->addProductToBasket('058de8224773a1d5fd54d523f0c823e0', 1);
        $homePage->openMiniBasket();
        $I->click(Translator::translate('CHECKOUT'));
        $I->waitForPageLoad();
        $I->click("//form[@id='select-country-form']//button[@value='DE']");
        $I->wait(2);

        $kco = new Kco($I);
        $kco->fillKcoUserForm();
        $I->wait(2);
        //Test if shipping method price is correct
        $I->see('12,90 €');
        $I->wait(2);
        //different shipping method
        $kco->fillKcoShippingForm('UPS Express 24');
        $I->wait(2);
        $I->executeJS('document.querySelector("[data-cid=\'button.buy_button\']").click()');
        $I->wait(2);
        $I->switchToIFrame(); // navigate back to to main document frame
        $I->waitForElement('#thankyouPage');
        $I->waitForPageLoad();

        $billEmail = Fixtures::get('gKCOEmail'); // recall generated and stored email
        $I->seeInDatabase('oxuser', ['oxusername' => $billEmail, 'oxpassword' => '']);
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
