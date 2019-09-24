<?php


use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;
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

        $admin->selectDetailsTab("Main");
        $I->wait(3);
        $I->waitForFrame("basefrm");
        $I->waitForFrame('edit');
        $I->fillField("//input[@name='editval[oxorder__oxdiscount]']", self::NEW_ORDER_DISCOUNT);
        $I->fillField("//input[@name='editval[oxorder__oxtrackcode]']", self::NEW_ORDER_TRACK_CODE);
        $I->click("#saveFormButton");
        $I->wait(3);
        $I->click("#shippNowButton");
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