<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Tests\Codeception;

use Exception;
use OxidEsales\Codeception\Page\Home;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Open shop first page.
     */
    public function openShop()
    {
        $I = $this;
        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);
        return $homePage;
    }

    /**
     * @throws Exception
     */
    public function fillKcoForm()
    {
        $I = $this;
        $I->waitForElement('#klarna-checkout-iframe');
        $I->switchToIFrame("klarna-checkout-iframe");
        $I->fillField("//div[@id='klarna-checkout-customer-details']//input[@id='email']", $this->getKlarnaDataByName('sKlarnaKCOEmail'));
        $I->fillField("//div[@id='klarna-checkout-customer-details']//input[@id='postal_code']",$this->getKlarnaDataByName('sKCOFormPostCode'));
        $I->click("//select[@id='title']");
        $I->click("//option[@id='title__option__herr']");
        $I->fillField("//div[@id='klarna-checkout-customer-details']//input[@id='given_name']",$this->getKlarnaDataByName('sKCOFormGivenName'));
        $I->fillField("//div[@id='klarna-checkout-customer-details']//input[@id='family_name']",$this->getKlarnaDataByName('sKCOFormFamilyName'));
        $I->fillField("//div[@id='klarna-checkout-customer-details']//input[@id='street_address']",$this->getKlarnaDataByName('sKCOFormStreetName').' '.$this->getKlarnaDataByName('sKCOFormStreetNumber'));
        $I->fillField("//div[@id='klarna-checkout-customer-details']//input[@id='city']",$this->getKlarnaDataByName('sKCOFormCity'));
        $I->fillField("//div[@id='klarna-checkout-customer-details']//input[@id='phone']",$this->getKlarnaDataByName('sKCOFormPhone'));
        $I->fillField("//div[@id='klarna-checkout-customer-details']//input[@id='date_of_birth']",$this->getKlarnaDataByName('sKCOFormDob'));
//        $this->delayLoad();
        if($I->waitForElementClickable("//div[@id='klarna-checkout-customer-details']//*[text()='Submit']")){
            $this->click("//div[@id='klarna-checkout-customer-details']//*[text()='Submit']");
        }
    }

    public function switchCurrency($currency)
    {
        $this->click("css=.currencies-menu");
        $this->click("//ul//*[text()='$currency']");
    }
}
