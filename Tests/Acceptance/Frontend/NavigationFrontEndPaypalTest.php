<?php


namespace TopConcepts\Klarna\Tests\Acceptance\Frontend;


use TopConcepts\Klarna\Tests\Acceptance\AcceptanceKlarnaTest;

class NavigationFrontEndPaypalTest extends AcceptanceKlarnaTest
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
        $this->assertTextPresent('Your chosen country');

        //Fill order info
        $this->fillKcoForm();

        $this->clickAndWait("//div[@id='shipping-selector-next']//*[text()='Example Set1: UPS 48 hours']");
        $this->clickAndWait("//div[@id='payment-selector-next']//*[text()='PayPal']");
        $this->click("//div[@id='buy-button-next']//button");

        // go to PayPal page
        $this->selectFrame('relative=top');
        $this->checkForFailedToOpenPayPalPageError();
        $this->payWithPaypal();

        $this->waitForText("Please check all data on this overview before submitting your order!");
        $this->assertTextPresent("Please check all data on this overview before submitting your order!");
        $this->clickAndWait("//form[@id='orderConfirmAgbBottom']//button");

        $this->waitForPageToLoad();
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
        $this->waitForItemAppear("id=login_email", 60);
        $this->delayLoad(2);
        $this->type("login_email", $this->getKlarnaDataByName('sPaypalClientLogin'));
        $this->type("login_password", $this->getKlarnaDataByName('sPaypalClientPsw'));
        $this->delayLoad(3);
        $this->clickAndWait("id=submitLogin");

        $this->waitForItemAppear("id=continue_abovefold");
        $this->clickPayPalContinuePage();
        //we should be redirected back to shop at this point
        $this->_waitForAppear('isElementPresent', "id=breadCrumb", 10, true);
    }

    /**
     * Continue button is visible before PayPal does callback.
     * Then it becomes invisible while PayPal does callback.
     * Button appears when PayPal gets callback result.
     */
    private function clickPayPalContinuePage()
    {
        $this->waitForItemAppear("//input[@id='continue_abovefold']");
        $this->delayLoad();
        $this->waitForEditable("id=continue_abovefold");
        $this->clickAndWait("id=continue_abovefold");
    }

}