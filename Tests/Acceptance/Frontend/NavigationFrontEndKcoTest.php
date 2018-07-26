<?php


namespace TopConcepts\Klarna\Tests\Acceptance\Frontend;


use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Model\KlarnaUser;
use TopConcepts\Klarna\Tests\Acceptance\AcceptanceKlarnaTest;

class NavigationFrontEndKcoTest extends AcceptanceKlarnaTest
{
    /**
     * Test new order guest user
     * @throws \Exception
     */
    public function testFrontendKcoOrderCreateAccountAndSubscribe()
    {
        $this->prepareKlarnaDatabase('KCO');

        $this->openNewWindow($this->getTestConfig()->getShopUrl(), false);
        $this->addToBasket('05848170643ab0deb9914566391c0c63');
        $this->addToBasket('058de8224773a1d5fd54d523f0c823e0');
        $this->clickAndWait("link=Go to Checkout");
        $this->assertTextPresent('Please choose your shipping country');
        $this->clickAndWait("//form[@id='select-country-form']//button[@value='DE']");
        if($this->isTextPresent('Your chosen country')) {
            $this->assertTextPresent('Your chosen country');
        }

        //Fill order info
        $this->fillKcoForm();

        //diferent delivery address
        $this->click("//div[@id='klarna-checkout-shipping-address']//*[text()='Ship to a different address']");
        $this->delayLoad();
        $this->waitForEditable("//div[@id='klarna-checkout-shipping-address']//input[@name='shipping_address.street_name']");
        $this->type("//div[@id='klarna-checkout-shipping-address']//input[@name='shipping_address.postal_code']",$this->getKlarnaDataByName('sKCOFormDelPostCode'));
        $this->delayLoad(2);
        $this->type("//div[@id='klarna-checkout-shipping-address']//input[@name='shipping_address.street_name']",$this->getKlarnaDataByName('sKCOFormDelStreetName'));
        $this->type("//div[@id='klarna-checkout-shipping-address']//input[@name='shipping_address.street_number']",$this->getKlarnaDataByName('sKCOFormDelStreetNumber'));
        $this->type("//div[@id='klarna-checkout-shipping-address']//input[@name='shipping_address.city']",$this->getKlarnaDataByName('sKCOFormDelCity'));
        $this->clickAndWait("css=.fieldset--shipping-address__continue-button");

        $this->clickAndWait("//div[@id='shipping-selector-next']//*[text()='Example Set1: UPS 48 hours']");
        if($this->isElementPresent("terms_consent__box"))
        {
            $this->click("id=terms_consent__box");
        }

        if($this->isElementPresent("//div[@id='additional_merchant_terms_checkbox__box']"))
        {
            $this->click("additional_merchant_terms_checkbox__box");
        }

        if($this->isElementPresent("//div[@id='additional_checkbox_from_merchant__box']"))
        {
            $this->click("id=additional_checkbox_from_merchant__box");
        }

        $this->click("//div[@id='buy-button-next']//button");
        $this->delayLoad();
        $this->waitForItemAppear("thankyouPage", 20);

        /** @var KlarnaUser $klarnaUser */
        $klarnaUser = oxNew(User::class);
        $klarnaUser->loadByEmail($this->getKlarnaDataByName('sKlarnaKCOEmail'));

        $oDb = DatabaseProvider::getDb();
        $sQ  = "SELECT `oxid` FROM `oxuser` WHERE `oxusername` = " . $oDb->quote($this->getKlarnaDataByName('sKlarnaKCOEmail'));
        if (!Registry::getConfig()->getConfigParam('blMallUsers')) {
            $sQ .= " AND `oxshopid` = " . $oDb->quote(Registry::getConfig()->getShopId());
        }
        $sId    = $oDb->getOne($sQ);
        $exists = $klarnaUser->load($sId);

        $this->assertTrue($exists);
        $this->assertTrue(isset($klarnaUser->oxuser__oxpassword->value));

        $this->assertKlarnaData(null, true);
        $this->stopMinkSession();

    }

    /**
     * @dataProvider klarnaKCOMethodsProvider
     * @param $country
     *
     * @throws \Exception
     */
    public function testFrontendKcoOrderLoginAndCountry($country)
    {
        $this->prepareKlarnaDatabase('KCO');

        $this->openNewWindow($this->getTestConfig()->getShopUrl(), false);
        $this->addToBasket('05848170643ab0deb9914566391c0c63');
        $this->addToBasket('058de8224773a1d5fd54d523f0c823e0');
        $this->clickAndWait("link=Go to Checkout");
        $this->assertTextPresent('Please choose your shipping country');
        $this->clickAndWait("//form[@id='select-country-form']//button[@value='DE']");
        if($this->isTextPresent('Your chosen country')) {
            $this->assertTextPresent('Your chosen country');
        }

        //login
        $currency = KlarnaConsts::getCountry2CurrencyArray()[$country];
        $this->switchCurrency($currency?$currency:'EUR');

        $userLogin = "user_".strtolower($country);
        $this->click("klarnaLoginWidget");
        $this->type("//form[@name='login']//input[@name='lgn_usr']", $userLogin."@oxid-esales.com");
        $this->type("//form[@name='login']//input[@name='lgn_pwd']", "12345");
        $this->clickAndWait("//form[@name='login']//button");

        $phone = "30306900";
        $number = "";
        switch ($country)
        {
            case "FI":
                $number = "311280999J";
                break;
            case "DK":
                $number = "1710354509";
                $phone = "41468007";
                break;
            case "NO":
                $number = "01018043587";
                $phone = "48404583";
                break;
            case "NL":
                $phone = "0642227516";
                break;
            case "GB":
                $phone = "07907920647";
                break;
            case "BE":
                $phone = "0488836320";
                break;
            case "SE":
                $number = "8803307019";
                break;
        }

        $this->waitForFrameToLoad("klarna-checkout-iframe");
        $this->selectFrame("klarna-checkout-iframe");

        if($this->isElementPresent("button-primary__loading-wrapper")) {
            $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='phone']",$phone);
            if($this->isElementPresent("//div[@id='klarna-checkout-customer-details']//input[@id='date_of_birth']")){
                $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='date_of_birth']","01011980");
            }
            if($this->isElementPresent("national_identification_number") && $this->isEditable("national_identification_number")){
                $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='national_identification_number']",$number);
            }
            $this->delayLoad();
            $this->clickAndWait("//div[@id='klarna-checkout-customer-details']//button[@id='button-primary']");

            //check if button is still display (continue anyway button) and click
            if($this->isElementPresent("//div[@id='klarna-checkout-customer-details']//button[@id='button-primary']"))
            {
                $this->clickAndWait("//div[@id='klarna-checkout-customer-details']//button[@id='button-primary']");
            }
        }
        $this->delayLoad();
        $this->clickAndWait("//div[@id='shipping-selector-next']//*[text()='Example Set1: UPS 48 hours']");
        $this->delayLoad();

        if(!$this->isElementPresent("payment-selector-pay_later") && $this->isTextPresent("Card"))
        {
            if(!$this->isElementPresent("pgw-iframe")){
                $this->clickAndWait("//div[@id='payment-selector']//*[text()='Card']");
            }

            $this->selectFrame('pgw-iframe');
            $this->type("cardNumber", "4111111111111111");
            $this->type("securityCode", "111");
            $this->type("expire", "01/24");
            $this->selectFrame("relative=top");
            $this->selectFrame("klarna-checkout-iframe");
        }

        if($this->isElementPresent("terms_consent__box"))
        {
            $this->click("id=terms_consent__box");
        }
        if($this->isElementPresent("//div[@id='additional_merchant_terms_checkbox__box']"))
        {
            $this->clickAndWait("//div[@id='additional_merchant_terms_checkbox__box']");
        }

        $this->waitForElement("//div[@id='buy-button-next']//button");
        $this->clickAndWait("//div[@id='buy-button-next']//button");
        $this->waitForFrameToLoad('relative=top');
        $this->selectFrame('relative=top');
        $this->delayLoad();
        $this->waitForText("Thank you", false, 30);
        $this->assertTextPresent("Thank you");
        $this->assertKlarnaData();
        $this->stopMinkSession();//force browser restart to clean previous order address
    }

    /**
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function klarnaKCOMethodsProvider()
    {

        return [
            ['BE'],
            ['GB'],
            ['FI'],
            ['AT'],
            ['SE'],
            ['NO'],
            ['NL'],
            ['DK'],
        ];
    }

    /**
     * Test new order guest user
     * @throws \Exception
     */
    public function testFrontendKcoOrderNachnahme()
    {
        $this->prepareKlarnaDatabase('KCO');

        $this->openNewWindow($this->getTestConfig()->getShopUrl(), false);
        $this->addToBasket('05848170643ab0deb9914566391c0c63');
        $this->addToBasket('058de8224773a1d5fd54d523f0c823e0');
        $this->clickAndWait("link=Go to Checkout");
        $this->assertTextPresent('Please choose your shipping country');
        $this->clickAndWait("//form[@id='select-country-form']//button[@value='DE']");
        if($this->isTextPresent('Your chosen country')) {
            $this->assertTextPresent('Your chosen country');
        }

        //Fill order info
        $this->fillKcoForm();

        $this->clickAndWait("//div[@id='shipping-selector-next']//*[text()='Example Set1: UPS 48 hours']");
        $this->clickAndWait("payment-selector-external_nachnahme__left");
        if($this->isElementPresent("//div[@id='additional_merchant_terms_checkbox__box']"))
        {
            $this->clickAndWait("//div[@id='additional_merchant_terms_checkbox__box']");
        }
        $this->click("//div[@id='buy-button-next']//button");
        $this->selectFrame("relative=top");
        $this->waitForText("Please check all data on this overview before submitting your order!");
        $this->assertTextPresent("Please check all data on this overview before submitting your order!");
        $this->clickAndWait("//form[@id='orderConfirmAgbBottom']//button");
        $this->waitForItemAppear("thankyouPage", 60);
        $this->waitForText("We will inform you immediately if an item is not deliverable.");
        $this->assertTextPresent("We will inform you immediately if an item is not deliverable.");
        $this->stopMinkSession();
    }
}