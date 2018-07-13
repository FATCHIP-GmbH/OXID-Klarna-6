<?php


namespace TopConcepts\Klarna\Tests\Acceptance\Frontend;


use TopConcepts\Klarna\Tests\Acceptance\AcceptanceKlarnaTest;

class NavigationFrontEndPaypalTest extends AcceptanceKlarnaTest
{
    /**
     * Test new order guest user
     * @throws \Exception
     */
    public function testFrontendOrderPayPal()
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
        $this->clickAndWait("//div[@id='payment-selector-next']//*[text()='PayPal']");
        if($this->isElementPresent("//div[@id='additional_merchant_terms_checkbox__box']"))
        {
            $this->clickAndWait("//div[@id='additional_merchant_terms_checkbox__box']");
        }
        $this->click("//div[@id='buy-button-next']//button");

        // go to PayPal page
        $this->selectFrame('relative=top');
        $this->checkForFailedToOpenPayPalPageError();
        $this->payWithPaypal();

        $this->waitForText("Please check all data on this overview before submitting your order!", false, 60);
        $this->assertTextPresent("Please check all data on this overview before submitting your order!");
        $this->clickAndWait("//form[@id='orderConfirmAgbBottom']//button");

        $this->waitForText("Thank you", false, 120);
        $this->assertTextPresent("Thank you");
    }


    /**
     * check if paypal loads
     */
    protected function checkForFailedToOpenPayPalPageError()
    {
        $this->assertTextNotPresent("Security header is not valid", "Did not succeed to open PayPal page.");
        $this->assertTextNotPresent("ehlermeldung von PayPal", "Did not succeed to open PayPal page.");
    }

    /**
     * PayPal sandbox.
     *
     * @throws \Exception
     */
    protected function payWithPaypal()
    {
        $this->waitForItemAppear("css=.loginRedirect", 60);
        $this->clickAndWait("//div[@id='loginSection']//*[text()='Log In']");
        $this->waitForItemAppear("id=email", 60);
        $this->delayLoad(2);
        $this->type("email", $this->getKlarnaDataByName('sPaypalClientLogin'));
        $this->type("password", $this->getKlarnaDataByName('sPaypalClientPsw'));
        $this->clickAndWait("id=btnLogin");

        $this->waitForItemAppear("id=confirmButtonTop", 60);
        $this->clickPayPalContinuePage();
    }

    /**
     * Continue button is visible before PayPal does callback.
     * Then it becomes invisible while PayPal does callback.
     * Button appears when PayPal gets callback result.
     */
    private function clickPayPalContinuePage()
    {
        $this->delayLoad();
        $this->waitForEditable("id=confirmButtonTop");
        $this->click("id=confirmButtonTop");
    }

}