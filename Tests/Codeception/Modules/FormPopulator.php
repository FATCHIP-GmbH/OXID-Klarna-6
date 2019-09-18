<?php


namespace TopConcepts\Klarna\Tests\Codeception\Modules;


use Codeception\Module;
use TopConcepts\Klarna\Tests\Codeception\AcceptanceTester;

class FormPopulator extends Module
{
    /**
     * @throws Exception
     */
    public function fillKcoForm(AcceptanceTester $I)
    {
        $I->waitForElement('#klarna-checkout-iframe');
        $I->switchToIFrame("klarna-checkout-iframe");
        $I->waitForElementClickable('//*[@id="email"]');
        $I->wait(2);
        $I->fillField("//*[@id=\"postal_code\"]",$this->getKlarnaDataByName('sKCOFormPostCode'));
        $I->fillField("//*[@id=\"email\"]", rand(0, 1000) . $this->getKlarnaDataByName('sKlarnaKCOEmail'));
        $I->waitForElement('//*[@id="title__root"]');
        $I->selectOption("//select[@id='title']", ['value' => 'frau']);
        $I->fillField("//*[@id=\"given_name\"]",$this->getKlarnaDataByName('sKCOFormGivenName'));
        $I->fillField("//*[@id='family_name']",$this->getKlarnaDataByName('sKCOFormFamilyName'));
        $I->fillField("//*[@id='street_address']",$this->getKlarnaDataByName('sKCOFormStreetName').' '.$this->getKlarnaDataByName('sKCOFormStreetNumber'));
        $I->fillField("//*[@id='city']",$this->getKlarnaDataByName('sKCOFormCity'));
        $I->fillField("//*[@id='phone']",$this->getKlarnaDataByName('sKCOFormPhone'));
        $I->fillField("//*[@id='date_of_birth']",$this->getKlarnaDataByName('sKCOFormDob'));
        $I->click("//*[@id=\"button-primary\"]");
    }
}