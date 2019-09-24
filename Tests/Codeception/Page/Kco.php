<?php


namespace TopConcepts\Klarna\Tests\Codeception\Page;


use Codeception\Exception\ElementNotFound;
use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Page\Page;
use TopConcepts\Klarna\Tests\Codeception\AcceptanceTester;

class Kco extends Page
{
    /** @var AcceptanceTester */
    protected $user;

    protected $frames = [
        'main' => 'klarna-checkout-iframe',
        'full' => 'klarna-fullscreen-iframe'
    ];

    public function submitVoucher($vNumber) {
        $I = $this->user;
        $I->wait(2);
        $I->click("#klarnaVouchersWidget");
        $I->wait(2); // for animation end
        $I->fillField("#input_voucherNr", $vNumber);
        $I->click("#submitVoucher");
        $I->waitForPageLoad();
    }

    public function fillPayment() {
        $I = $this->user;
        if (!$I->isElementPresent("#payment-selector-pay_later__container")) {
            if (!$I->isElementPresent("#pgw-iframe-credit_card")) {
                $I->click("//div[@id='payment-selector']//*[text()='Card']");
            }
            $I->switchToIFrame('pgw-iframe-credit_card');
            foreach (str_split("4111111111111111") as $key) {
                $I->pressKey("//*[@id='cardNumber']", $key);
            }
            $I->fillField("securityCode", "111");
            $I->fillField("expire", "01/24");
            $I->switchToIFrame();
            $I->switchToIFrame('klarna-checkout-iframe');
        }
    }

    public function loginKlarnaWidget($country)
    {
        $I = $this->user;
        $I->wait(2);
        $I->click("#klarnaLoginWidget");
        $I->wait(2); // for animation end
        $userLogin = "user_".strtolower($country)."@oxid-esales.com";
        $I->fillField("//*[@id='klarnaLoginWidget']//input[@name='lgn_usr']", $userLogin);
        $I->fillField("//*[@id='klarnaLoginWidget']//input[@name='lgn_pwd']", "12345");
        $I->click("//*[@id='klarnaLoginWidget']//button");
        $I->waitForPageLoad();
    }

    /**
     * @param $phone
     * @param $number
     * @throws \Exception
     */
    public function fillKCOLoggedInUserForm($phone, $number)
    {
        $I = $this->user;
        $I->waitForElement('#' . $this->frames['main']);
        $I->switchToIFrame($this->frames['main']);
        $I->wait(1);
        // try to fill missing data
        try {
            $I->grabTextFrom("//input[@id='national_identification_number']");
            $I->clearField("//input[@id='national_identification_number']");
            $I->fillField("//input[@id='national_identification_number']", $number);
        } catch (\Exception $e) {}
        try {
            $I->grabTextFrom("//select[@id='title']");
            $I->selectOption("//select[@id='title']", 'Mr');
        } catch (\Exception $e) {}
        try {
            $I->grabTextFrom("//input[@id='phone']");
            $I->fillField("//input[@id='phone']", $phone);
        } catch (\Exception $exception) {}
        try {
            $I->grabTextFrom("//*[@id='date_of_birth']");
            $I->clearField("//*[@id='date_of_birth']");
            foreach (str_split('14041960') as $key) {
                $I->pressKey("//*[@id='date_of_birth']", $key);
            }
        } catch (\Exception $exception) {}
        $I->wait(1);
        try {
            $I->grabTextFrom("//*[@id='button-primary']");
            $I->click("//*[@id='button-primary']");
            $I->wait(2);
            $I->grabTextFrom("//*[@id='button-primary']");
            $I->click("//*[@id='button-primary']");
        } catch (\Exception $exception) {}

    }
    /**
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function fillKcoUserForm()
    {
        $I = $this->user;
        //generate and save email
        $generatedEmail = time()  . $I->getKlarnaDataByName('sKlarnaKCOEmail');
        $I->comment("Generated email: $generatedEmail");
        Fixtures::add('gKCOEmail', $generatedEmail);
        $I->waitForElement('#' . $this->frames['main']);
        $I->switchToIFrame($this->frames['main']);
        $I->waitForElementClickable('//*[@id="email"]');
        $I->wait(2);
        $I->fillField("//*[@id=\"postal_code\"]",$I->getKlarnaDataByName('sKCOFormPostCode'));
        $I->fillField("//*[@id=\"email\"]", $generatedEmail);
        $I->waitForElement('//*[@id="title__root"]', 20);
        $I->selectOption("//select[@id='title']", ['value' => 'frau']);
        $I->fillField("//*[@id=\"given_name\"]",$I->getKlarnaDataByName('sKCOFormGivenName'));
        $I->fillField("//*[@id='family_name']",$I->getKlarnaDataByName('sKCOFormFamilyName'));
        $I->fillField("//*[@id='street_address']",$I->getKlarnaDataByName('sKCOFormStreetName').' '.$I->getKlarnaDataByName('sKCOFormStreetNumber'));
        $I->fillField("//*[@id='city']",$I->getKlarnaDataByName('sKCOFormCity'));
        $I->fillField("//*[@id='phone']",$I->getKlarnaDataByName('sKCOFormPhone'));
        $I->fillField("//*[@id='date_of_birth']",$I->getKlarnaDataByName('sKCOFormDob'));
        $I->click("//*[@id=\"button-primary\"]");
    }

    /**
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function fillKcoShippingForm() {
        $I = $this->user;
        $I->wait(4); // wait for loaders and overlays to be hidden
        $I->click('#SHIPMO-container #preview__touchable');
        $I->switchToIFrame(); // go back to the main content
        $I->waitForElement('#' . $this->frames['full']);
        $I->switchToIFrame($this->frames['full']);
        $I->waitForElementVisible('//*[@id="fieldset"]');
        $I->fillField('//*[@id="postal_code"]', $I->getKlarnaDataByName('sKCOFormDelPostCode'));
        $I->fillField('//*[@id="street_address"]', $I->getKlarnaDataByName('sKCOFormDelStreetName') .' '. $I->getKlarnaDataByName('sKCOFormDelStreetNumber'));
        $I->fillField('//*[@id="city"]', $I->getKlarnaDataByName('sKCOFormDelCity'));
        $I->click('//*[@id="SHIPMO-dialog-submit-button"]/div/div[2]');
        $I->switchToIFrame();
        $I->switchToIFrame($this->frames['main']);
        $I->wait(3);
        $I->selectOption('#SHIPMO-container input[name=radio]', 'UPS 48');
        $I->wait(3);
    }
}