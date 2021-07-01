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
        $I->wait(1);
        if (!$I->isElementPresent("#pgw-iframe-credit_card")) {
            $I->click("//div[@id='payment-selector']//*[text()='Card']");
        }
        $I->switchToIFrame();
        $I->wait(2);
        $I->switchToIFrame('klarna-checkout-iframe');
        $I->switchToIFrame('pgw-iframe-credit_card');
        foreach (str_split("4111111111111111") as $key) {
            $I->pressKey("//*[@id='cardNumber']", $key);
        }
        $I->fillField("//*[@id='securityCode']", "111");
        $I->fillField("//*[@id='expire']", "01/24");
        $I->switchToIFrame();
        $I->switchToIFrame('klarna-checkout-iframe');
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
        $I->waitForElementClickable('//*[@id="billing-email"]');
        $I->wait(2);
        $I->fillField("//*[@id=\"billing-postal_code\"]",$I->getKlarnaDataByName('sKCOFormPostCode'));
        $I->fillField("//*[@id=\"billing-email\"]", $generatedEmail);
        $I->waitForElementClickable('//*[@id="billing-given_name"]');
        $I->fillField("//*[@id=\"billing-given_name\"]",$I->getKlarnaDataByName('sKCOFormGivenName'));
        $I->fillField("//*[@id='billing-family_name']",$I->getKlarnaDataByName('sKCOFormFamilyName'));
        $I->fillField("//*[@id='billing-street_address']",$I->getKlarnaDataByName('sKCOFormStreetName').' '.$I->getKlarnaDataByName('sKCOFormStreetNumber'));
        $I->fillField("//*[@id='billing-city']",$I->getKlarnaDataByName('sKCOFormCity'));
        $I->fillField("//*[@id='billing-phone']",$I->getKlarnaDataByName('sKCOFormPhone'));
        $I->fillField("//*[@id='billing-date_of_birth']",$I->getKlarnaDataByName('sKCOFormDob'));
        $I->click("//*[@id='billing-title']");
        $I->click("//*[@id='billing-title__option__frau']");
        $I->click("//*[@id=\"button-primary\"]");
    }

    /**
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function fillKcoShippingForm($shippingMethod = 'UPS 48') {
        $I = $this->user;
        $I->wait(4); // wait for loaders and overlays to be hidden
        $I->click('//*[@id="klarna-checkout-shipping-details"]//*[@id="preview__link"]');
        $I->wait(2);
        $I->switchToIFrame(); // go back to the main content
        $I->waitForElement('#' . $this->frames['full']);
        $I->switchToIFrame($this->frames['full']);
        $I->waitForElementVisible('//*[@id="addressCollector-fieldset"]');
        $I->fillField('//*[@id="addressCollector-postal_code"]', $I->getKlarnaDataByName('sKCOFormDelPostCode'));
        $I->fillField('//*[@id="addressCollector-street_address"]', $I->getKlarnaDataByName('sKCOFormDelStreetName') .' '. $I->getKlarnaDataByName('sKCOFormDelStreetNumber'));
        $I->fillField('//*[@id="addressCollector-city"]', $I->getKlarnaDataByName('sKCOFormDelCity'));
        $I->click('//*[@id="SHIPMO-dialog-submit-button"]');
        $I->switchToIFrame();
        $I->switchToIFrame($this->frames['main']);
        $I->wait(5);
        $I->selectOption('#SHIPMO-container input[name=radio]', $shippingMethod);
        $I->wait(3);
    }
    
    public function submitPackstationOption() {
        $I = $this->user;
        $I->wait(3);
        $I->selectOption('#SHIPMO-container input[name=radio]', 'DHL Packstation');
        $I->wait(3);
        $I->click('//*[@id="shipping-option-content"]//*[@id="preview__link"]');
        // switch to form iframe
        $I->switchToIFrame(); // go back to the main content
        $I->waitForElement('#' . $this->frames['full']);
        $I->switchToIFrame($this->frames['full']);
        $I->waitForElementVisible('//*[@id="machine-id"]');
        $I->wait(2);

        $I->click('//*[@id="machine-id"]');
        $I->fillField('//*[@id="machine-id"]', $I->getKlarnaDataByName('sKCOFormDelMachineId'));
        $I->click('//*[@id="customer-number"]');
        $I->fillField('//*[@id="customer-number"]', $I->getKlarnaDataByName('sKCOFormDelCustomerNumber'));
        $I->click('Confirm');

        // switch to KCO iframe
        $I->switchToIFrame();
        $I->switchToIFrame($this->frames['main']);
    }

    /**
     * Fill the field character by character
     * @param string $input
     * @param string $msg
     * @param AcceptanceTester $I
     */
    protected function fillFieldSpecial(string $input, string $msg, AcceptanceTester $I)
    {
        foreach (str_split($msg) as $key) {
            $I->pressKey($input, $key);
        }
    }
}