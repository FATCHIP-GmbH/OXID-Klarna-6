<?php


namespace TopConcepts\Klarna\Tests\Codeception\Page;


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

    public function loginKlarnaWidget($country)
    {
        $I = $this->user;
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
        $text = $I->grabTextFrom("//*[@id='title']");
        var_dump($text);
        $I->selectOption("//select[@id='title']", ['value' => 'frau']);
        $I->fillField("//*[@id='date_of_birth']",$I->getKlarnaDataByName('sKCOFormDob'));
        //$I->fillField("//input[@id='national_identification_number']", $number);
        $I->click("//*[@id='button-primary']");
        $I->wait(30);

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