<?php


use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\Codeception\Step\ProductNavigation;
use TopConcepts\Klarna\Tests\Acceptance\AcceptanceKlarnaTest;
use TopConcepts\Klarna\Tests\Codeception\AcceptanceTester;
use TopConcepts\Klarna\Tests\Codeception\Page\Kco;

class OrderManagementCest
{
    const NEW_ORDER_DISCOUNT        = "10";
    const NEW_ORDER_TRACK_CODE      = "12345";
    const NEW_ORDER_VOUCHER_NR      = "percent_10";

    /**
     * @group Admin
     * @param AcceptanceTester $I
     */
    public function capturePayment(AcceptanceTester $I)
    {
        $I->loadKlarnaAdminConfig('KCO');
        $klarnaId = $this->_prepareNewOrder($I);
        $admin = $I->openShopAdminPanel();
        $admin->login();
        $admin->selectShop();
        $admin->navigateMenu(["Administer Orders", "Orders"]);
        $name = $I->getKlarnaDataByName('sKCOFormGivenName');
        $admin->selectListItem($name);

        $admin->selectDetailsTab("Klarna");
        $I->wait(3);
        $I->waitForFrame("basefrm");
        $I->waitForFrame('edit');
        $I->click("//form[@id='capture']//input[@type='submit']");
        $I->wait(3);
        $I->seeInKlarnaAPI($klarnaId,  'CAPTURED', false);
    }

    /**
     * @group Admin
     * @param AcceptanceTester $I
     */
    public function cancelPayment(AcceptanceTester $I)
    {
        $I->loadKlarnaAdminConfig('KCO');
        $klarnaId = $this->_prepareNewOrder($I);
        $admin = $I->openShopAdminPanel();
        $admin->login();
        $admin->selectShop();
        $admin->navigateMenu(["Administer Orders", "Orders"]);
        $name = $I->getKlarnaDataByName('sKCOFormGivenName');
        $admin->selectListItem($name);

        $admin->selectDetailsTab("Klarna");
        $I->wait(3);
        $I->waitForFrame("basefrm");
        $I->waitForFrame('edit');
        $I->click("//form[@id='cancel']//input[@type='submit']");
        $I->wait(3);
        $I->seeInKlarnaAPI($klarnaId,  'CANCELLED', false);
    }

    /**
     * @group Admin
     * @param AcceptanceTester $I
     */
    public function refundPayment(AcceptanceTester $I)
    {
        $I->loadKlarnaAdminConfig('KCO');
        $klarnaId = $this->_prepareNewOrder($I);
        $admin = $I->openShopAdminPanel();
        $admin->login();
        $admin->selectShop();
        $admin->navigateMenu(["Administer Orders", "Orders"]);
        $name = $I->getKlarnaDataByName('sKCOFormGivenName');
        $admin->selectListItem($name);

        $admin->selectDetailsTab("Klarna");
        $I->wait(3);
        $I->waitForFrame("basefrm");
        $I->waitForFrame('edit');
        $I->cantSee('Refunds');
        $I->click("//form[@id='capture']//input[@type='submit']");
        $I->wait(3);
        $I->seeInKlarnaAPI($klarnaId,  'CAPTURED', false);
        $I->wait(3);
        $I->click("//form[@id='refund']//input[@type='submit']");
        $I->wait(3);
        $I->see('Refunds');
    }

    /**
     * @group Admin
     * @param AcceptanceTester $I
     */
    public function onSiteMessage(AcceptanceTester $I)
    {
        $admin = $I->openShopAdminPanel();
        $admin->login();
        $admin->selectShop();
        $admin->navigateMenu(["Klarna", "On-Site Messaging"]);
        $I->wait(3);
        $I->waitForFrame("basefrm");
        $I->fillField("//*[@id='klscript']","klscript");
        $I->fillField("//*[@id='klproduct']","klproduct");
        $I->fillField("//*[@id='klbasket']","klbasket");
        $I->fillField("//*[@id='klstrip']","klstrip");
        $I->fillField("//*[@id='klbanner']","klbanner");
        $I->click("//*[@id='form-save-button']");
        $I->wait(3);
        $I->openShop();
        $I->wait(3);
        $I->see("klstrip");
        $I->see("klbanner");
        $basket = new Basket($I);
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $I->waitForElement('//div[@class="btn-group minibasket-menu"]/button');
        $I->click('//div[@class="btn-group minibasket-menu"]/button');
        $I->click("//*[@id='header']/div/div/div/div[2]/div/div[4]/ul/li/div/div/div/p[2]/a[2]");
        $I->waitForPageLoad();
        $I->wait(3);
        $I->see("klbasket");
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage('05848170643ab0deb9914566391c0c63');
        $I->see("klproduct");
        $I->wait(3);

    }

    protected function _prepareNewOrder(AcceptanceTester $I) {
        $I->clearShopCache();
        $homePage = $I->openShop();
        $I->waitForPageLoad();
        $basket = new Basket($I);
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $basket->addProductToBasket('058de8224773a1d5fd54d523f0c823e0', 1);
        $homePage->openMiniBasket();
        $I->click(Translator::translate('CHECKOUT'));
        $I->waitForPageLoad();
        $I->selectOption("#other-countries", 'DE');

        //Fill order info
        $kco = new Kco($I);
        $kco->submitVoucher(self::NEW_ORDER_VOUCHER_NR);
        $kco->fillKcoUserForm();
        $I->wait(4);
        $I->executeJS('document.querySelector("[data-cid=\'button.buy_button\']").click()');
        $I->switchToIFrame(); // navigate back to to main document frame
        $I->waitForElement('#thankyouPage');
        $I->waitForPageLoad();

        return $I->grabFromDatabase('oxorder', 'TCKLARNA_ORDERID', ['OXBILLEMAIL' => Fixtures::get('gKCOEmail')]);
    }
}