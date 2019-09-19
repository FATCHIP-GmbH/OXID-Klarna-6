<?php


namespace TopConcepts\Klarna\Tests\Codeception\Modules;


use Codeception\Module;
use TopConcepts\Klarna\Tests\Codeception\AcceptanceTester;

class FormPopulator extends Module
{
    protected $frames = [
        'main' => 'klarna-checkout-iframe',
        'full' => 'klarna-fullscreen-iframe'
    ];
    /**
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function fillKcoUserForm(AcceptanceTester $I)
    {
        $I->waitForElement('#' . $this->frames['main']);
        $I->switchToIFrame($this->frames['main']);
        $I->waitForElementClickable('//*[@id="email"]');
        $I->wait(2);
        $I->fillField("//*[@id=\"postal_code\"]",$I->getKlarnaDataByName('sKCOFormPostCode'));
        $I->fillField("//*[@id=\"email\"]", rand(0, 1000) . $I->getKlarnaDataByName('sKlarnaKCOEmail'));
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
    public function fillKcoShippingForm(AcceptanceTester $I) {
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
        $I->wait(1);
        $I->click('//*[@id="SHIPMO-dialog-submit-button"]/div/div[2]');
        $I->switchToIFrame();
        $I->switchToIFrame($this->frames['main']);
        $I->wait(2);
        $I->selectOption('#SHIPMO-container input[name=radio]', 'UPS 48');
        $I->wait(2);
    }
}