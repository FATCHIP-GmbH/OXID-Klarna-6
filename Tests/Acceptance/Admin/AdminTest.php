<?php

namespace TopConcepts\Klarna\Tests\Acceptance\Admin;

use TopConcepts\Klarna\Tests\Acceptance\AcceptanceKlarnaTest;

class AdminTest extends AcceptanceKlarnaTest
{

    /**
     * @throws \Exception
     */
    public function testOrderManagementCaputre()
    {
        $this->clearTemp();
        $this->activateTheme('flow');
        $this->prepareKlarnaDatabase('KCO');
        $this->createNewOrder();

        $this->loginAdmin("Administer Orders", "Orders", false, 'admin', 'admin');
        $this->openListItem('ÅåÆæØø');
        $this->openTab('Main');

        $this->type("//input[@name='editval[oxorder__oxdiscount]']", "10");
        $this->type("//input[@name='editval[oxorder__oxtrackcode]']", "12345");
        $this->clickAndWait("saveFormButton");
        $this->clickAndWait("shippNowButton");
        sleep(10);//wait for klarna capture

        $oxid = $this->getValue("//form[@id='myedit']//input[@name='oxid']");

        $this->assertKlarnaData($oxid, 'CAPTURED');

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

        $this->loginAdmin("Administer Orders", "Orders", false, 'admin', 'admin');
        $this->openListItem('ÅåÆæØø');
        sleep(3);//wait for klarna tab
        $this->openTab('Klarna');

        $this->clickAndWait("//form[@id='cancel']//input[@type='submit']");

        $this->assertTextPresent('Order is cancelled. See this order in the Klarna Portal.');
    }

    /**
     * Adds tests sql data to database.
     *
     * @param string $sTestSuitePath
     */
    public function addTestData($sTestSuitePath)
    {
        parent::addTestData($sTestSuitePath);

        $sTestSuitePath = realpath(__DIR__.'/../testSql/');
        $sFileName = $sTestSuitePath.'/demodata.sql';
        if (file_exists($sFileName)) {
            $this->importSql($sFileName);
        }
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
        $this->type("input_voucherNr", "percent_10");
        $this->clickAndWait("submitVoucher");

        $this->waitForFrameToLoad("klarna-checkout-iframe", 2000);
        $this->selectFrame('klarna-checkout-iframe');
        $this->type(
            "//div[@id='customer-details-next']//input[@id='email']",
            $this->getKlarnaDataByName('sKlarnaKCOEmail')
        );
        $this->type("//div[@id='customer-details-next']//input[@id='postal_code']", '21079');
        $this->click("//select[@id='title']");
        $this->click("//option[@id='title__option__herr']");
        $this->type("//div[@id='customer-details-next']//input[@id='given_name']", "ÅåÆæØø");
        $this->type("//div[@id='customer-details-next']//input[@id='family_name']", "St.Jäöüm'es");
        $this->type("//div[@id='customer-details-next']//input[@id='street_name']", "Karnapp");
        $this->type("//div[@id='customer-details-next']//input[@id='street_number']", "25");
        $this->type("//div[@id='customer-details-next']//input[@id='city']", "Hamburg");
        $this->type("//div[@id='customer-details-next']//input[@id='phone']", "30306900");
        $this->type("//div[@id='customer-details-next']//input[@id='date_of_birth']", "01011980");

        $this->clickAndWait("//div[@id='customer-details-next']//button[@id='button-primary']");
        $this->clickAndWait("//div[@id='terms-consent-next']//input[@type='checkbox']");
        $this->clickAndWait("//div[@id='buy-button-next']//button");
        $this->waitForFrameToLoad('relative=top');
        $this->selectFrame('relative=top');
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