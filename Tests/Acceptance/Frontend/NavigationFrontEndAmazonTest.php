<?php


namespace TopConcepts\Klarna\Tests\Acceptance\Frontend;


use TopConcepts\Klarna\Tests\Acceptance\AcceptanceKlarnaTest;

class NavigationFrontEndAmazonTest extends AcceptanceKlarnaTest
{
    /**
     * Test new order guest user
     * @throws \Exception
     */
    public function testFrontendAmazon()
    {
        $this->clearCache();
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
        $this->click("klarnaLoginWidget");
        $this->type("//form[@name='login']//input[@name='lgn_usr']", "user_de@oxid-esales.com");
        $this->type("//form[@name='login']//input[@name='lgn_pwd']", "12345");
        $this->clickAndWait("//form[@name='login']//button");

        $this->waitForFrameToLoad("klarna-checkout-iframe");
        $this->selectFrame("klarna-checkout-iframe");
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='phone']","30306900");
        if($this->isElementPresent("//div[@id='klarna-checkout-customer-details']//input[@id='date_of_birth']")){
            $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='date_of_birth']","01011980");
        }
        $this->delayLoad(2);
        $this->clickAndWait("//div[@id='klarna-checkout-customer-details']//button[@id='button-primary']");
        $this->delayLoad();
        $this->clickAndWait("//div[@id='payment-selector-next']//*[text()='Amazon Payments']");
        if($this->isElementPresent("//div[@id='additional_merchant_terms_checkbox__box']"))
        {
            $this->clickAndWait("//div[@id='additional_merchant_terms_checkbox__box']");
        }
        $this->click("//div[@id='buy-button-next']//button");

        // pay with Amazon
        $this->selectFrame('relative=top');
        $this->waitForElement("//div[@id='_amazonLoginButton']");
        $this->clickAndWait("//div[@id='_amazonLoginButton']//img");
        if(in_array('amazonloginpopup', $this->getAllWindowNames())){
            $this->selectWindow("amazonloginpopup");
            $this->type("ap_email", $this->getKlarnaDataByName('sAmazonClientLogin'));
            $this->type("ap_password", $this->getKlarnaDataByName('sAmazonClientPsw'));
            $this->click("//form[@id='ap_signin_form']//button");
            $this->selectWindow(null);
        }
        $this->clickAndWait("//div[@id='amazonNextStep']//a[@id='userNextStepBottom']");
        $this->clickAndWait("//form[@id='payment']//button[@id='paymentNextStepBottom']");
        $this->waitForText("Please check all data on this overview before submitting your order!", false, 60);
        $this->assertTextPresent("Please check all data on this overview before submitting your order!");
        $this->clickAndWait("//form[@id='orderConfirmAgbBottom']//button");

        $this->waitForText("Thank you", false, 120);
        $this->assertTextPresent("Thank you");
    }

}