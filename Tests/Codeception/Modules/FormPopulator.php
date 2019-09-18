<?php


namespace TopConcepts\Klarna\Tests\Codeception\Modules;


use Codeception\Module;
use TopConcepts\Klarna\Tests\Codeception\AcceptanceTester;

class FormPopulator extends Module
{
    /**
     * @param AcceptanceTester $I
     * @throws \Exception
     */
    public function fillKcoForm(AcceptanceTester $I)
    {
        $I->waitForElement('#klarna-checkout-iframe');
        $I->switchToIFrame("klarna-checkout-iframe");
        $I->waitForElementClickable('//*[@id="email"]');
        $I->wait(2);
        $I->fillField("//*[@id=\"postal_code\"]",$I->getKlarnaDataByName('sKCOFormPostCode'));
        $I->fillField("//*[@id=\"email\"]", rand(0, 1000) . $I->getKlarnaDataByName('sKlarnaKCOEmail'));
        $I->waitForElement('//*[@id="title__root"]');
        $I->selectOption("//select[@id='title']", ['value' => 'frau']);
        $I->fillField("//*[@id=\"given_name\"]",$I->getKlarnaDataByName('sKCOFormGivenName'));
        $I->fillField("//*[@id='family_name']",$I->getKlarnaDataByName('sKCOFormFamilyName'));
        $I->fillField("//*[@id='street_address']",$I->getKlarnaDataByName('sKCOFormStreetName').' '.$I->getKlarnaDataByName('sKCOFormStreetNumber'));
        $I->fillField("//*[@id='city']",$I->getKlarnaDataByName('sKCOFormCity'));
        $I->fillField("//*[@id='phone']",$I->getKlarnaDataByName('sKCOFormPhone'));
        $I->fillField("//*[@id='date_of_birth']",$I->getKlarnaDataByName('sKCOFormDob'));
        $I->click("//*[@id=\"button-primary\"]");
    }
}