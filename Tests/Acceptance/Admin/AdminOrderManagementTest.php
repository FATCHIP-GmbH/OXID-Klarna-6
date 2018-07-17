<?php

namespace TopConcepts\Klarna\Tests\Acceptance\Admin;

use TopConcepts\Klarna\Tests\Acceptance\AcceptanceKlarnaTest;

class AdminOrderManagementTest extends AcceptanceKlarnaTest
{

    /**
     * @throws \Exception
     * @throws \oxSystemComponentException
     */
    public function testOrderManagementCaputre()
    {
        $this->clearTemp();
        $this->activateTheme('flow');
        $this->prepareKlarnaDatabase('KCO');
        $this->createNewOrder();
        $this->delayLoad(3);
        $this->loginAdmin("Administer Orders", "Orders", false, 'admin', 'admin');
        $this->delayLoad();
        $this->waitForFrameToLoad('list');
        $this->waitForText(AcceptanceKlarnaTest::NEW_ORDER_GIVEN_NAME);
        $this->openListItem(AcceptanceKlarnaTest::NEW_ORDER_GIVEN_NAME);
        $this->delayLoad(3);
        $this->openTab('Main');
        $this->type("//input[@name='editval[oxorder__oxdiscount]']", AcceptanceKlarnaTest::NEW_ORDER_DISCOUNT);
        $this->type("//input[@name='editval[oxorder__oxtrackcode]']", AcceptanceKlarnaTest::NEW_ORDER_TRACK_CODE);
        $this->clickAndWait("saveFormButton");
        $this->clickAndWait("shippNowButton");
        $this->delayLoad(10);//wait for klarna capture

        $oxid = $this->getValue("//form[@id='myedit']//input[@name='oxid']");

        $this->assertKlarnaData($oxid, false, 'CAPTURED');
        $this->stopMinkSession();

    }

    /**
     * @throws \Exception
     */
    public function testOrderManagementCancel()
    {
        $this->clearTemp();
        $this->activateTheme('flow');
        $this->prepareKlarnaDatabase('KCO');
        $this->createNewOrder();
        $this->delayLoad(3);
        $this->loginAdmin("Administer Orders", "Orders", false, 'admin', 'admin');
        $this->waitForFrameToLoad('list');
        $this->waitForText(AcceptanceKlarnaTest::NEW_ORDER_GIVEN_NAME);
        $this->openListItem(AcceptanceKlarnaTest::NEW_ORDER_GIVEN_NAME);
        $this->delayLoad(3);//wait for klarna tab
        $this->openTab('Klarna');

        $this->clickAndWait("//form[@id='cancel']//input[@type='submit']");

        $this->assertTextPresent('Order is cancelled. See this order in the Klarna Portal.');
        $this->stopMinkSession();
    }

    /**
     * @throws \Exception
     */
    protected function createNewOrder()
    {
        $this->openNewWindow($this->getTestConfig()->getShopUrl(), false);
        $this->addToBasket('05848170643ab0deb9914566391c0c63');
        $this->addToBasket('058de8224773a1d5fd54d523f0c823e0');

        $this->clickAndWait("link=Go to Checkout");
        $this->clickAndWait("//form[@id='select-country-form']//button[@value='DE']");

        $this->click("klarnaVouchersWidget");
        $this->type("input_voucherNr", AcceptanceKlarnaTest::NEW_ORDER_VOUCHER_NR);
        $this->clickAndWait("submitVoucher");

        $this->waitForFrameToLoad("klarna-checkout-iframe", 2000);
        $this->selectFrame('klarna-checkout-iframe');
        $this->type(
            "//div[@id='klarna-checkout-customer-details']//input[@id='email']",
            $this->getKlarnaDataByName('sKlarnaKCOEmail')
        );
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='postal_code']", AcceptanceKlarnaTest::NEW_ORDER_ZIP_CODE);
        $this->click("//select[@id='title']");
        $this->click("//option[@id='title__option__herr']");
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='given_name']", AcceptanceKlarnaTest::NEW_ORDER_GIVEN_NAME);
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='family_name']", AcceptanceKlarnaTest::NEW_ORDER_FAMILY_NAME);
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='street_address']",AcceptanceKlarnaTest::NEW_ORDER_STREET_ADDRESS);
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='city']", AcceptanceKlarnaTest::NEW_ORDER_CITY);
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='phone']", AcceptanceKlarnaTest::NEW_ORDER_PHONE);
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='date_of_birth']", AcceptanceKlarnaTest::NEW_ORDER_DATE_OF_BIRTH);
        $this->delayLoad();
        if($this->isElementPresent("//div[@id='klarna-checkout-customer-details']//button[@id='button-primary']")){
            $this->click("//div[@id='klarna-checkout-customer-details']//button[@id='button-primary']");
        }
        $this->delayLoad();
        if($this->isElementPresent("terms_consent__box"))
        {
            $this->clickAndWait("id=terms_consent__box");
        }
        if($this->isElementPresent("//div[@id='additional_merchant_terms_checkbox__box']"))
        {
            $this->delayLoad(3); // wait for checkbox to be reloaded
            $this->clickAndWait("//div[@id='additional_merchant_terms_checkbox__box']");
        }
        if($this->isElementPresent("//div[@id='additional_checkbox_from_merchant__box']"))
        {
            $this->click("id=additional_checkbox_from_merchant__box");
        }

        $this->delayLoad();
        $this->click("//div[@id='buy-button-next']//*[text()='Place Order']");
        $this->selectFrame('relative=top');
        $this->waitForText("Thank you", false, 60);
        $this->assertTextPresent("Thank you");
    }

    /**
     * login to admin with default admin pass and opens needed menu.
     *
     * @param string $menuLink1 Menu link (e.g. master settings, shop settings).
     * @param string $menuLink2 Sub menu link (e.g. administer products, discounts, vat).
     * @param bool $forceMainShop Force main shop.
     * @param string $user Shop admin username.
     * @param string $pass Shop admin password.
     * @param string $language Shop admin language.
     * @throws \Exception
     */
    public function loginAdmin(
        $menuLink1 = null,
        $menuLink2 = null,
        $forceMainShop = false,
        $user = "",
        $pass = "",
        $language = "English"
    ) {
        $this->openNewWindow(shopURL."admin");
        $this->type("usr", $this->getKlarnaDataByName('sKlarnaAdminUser'));
        $this->type("pwd", $this->getKlarnaDataByName('sKlarnaAdminPsw'));
        $this->clickAndWait("//input[@type='submit']");

        $this->frame("navigation");

        if ($this->getTestConfig()->isSubShop() && !$forceMainShop) {
            $this->selectAndWaitFrame("selectshop", "label=subshop", "basefrm");
        }

        if ($menuLink1 && $menuLink2) {
            $this->selectMenu($menuLink1, $menuLink2);
        } else {
            $this->frame("basefrm");
        }
    }

}