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
        $this->assertTextPresent('Your chosen country');

        //Fill order info
        $this->fillKcoForm();

        $this->clickAndWait("//div[@id='shipping-selector-next']//*[text()='Example Set1: UPS 48 hours']");
        $this->click("id=terms_consent__box");
        $this->click("id=additional_checkbox__box");
        $this->clickAndWait("//div[@id='buy-button-next']//button");
        $this->waitForFrameToLoad('relative=top');
        $this->selectFrame('relative=top');
        $this->assertTextPresent("Thank you");

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

        $this->assertKlarnaData();

    }

    /**
     * @dataProvider klarnaKCOMethodsProvider
     * @param $country
     *
     * @throws \Exception
     */
    public function testFrontendKcoOrderLoginAndCountry($country)
    {
        $this->clearTemp();
        $this->openNewWindow($this->getTestConfig()->getShopUrl(), false);
        $this->addToBasket('05848170643ab0deb9914566391c0c63');
        $this->addToBasket('058de8224773a1d5fd54d523f0c823e0');
        $this->clickAndWait("link=Go to Checkout");
        $this->assertTextPresent('Please choose your shipping country');
        $this->clickAndWait("//form[@id='select-country-form']//button[@value='DE']");
        $this->assertTextPresent('Your chosen country');

        //login
        $this->switchCurrency(KlarnaConsts::getCountry2CurrencyArray()[$country]);
        $userLogin = "user_".strtolower($country);
        $this->click("klarnaLoginWidget");
        $this->type("//form[@name='login']//input[@name='lgn_usr']", $userLogin."@oxid-esales.com");
        $this->type("//form[@name='login']//input[@name='lgn_pwd']", "12345");
        $this->clickAndWait("//form[@name='login']//button");

        switch ($country)
        {
            case "DK":
                $phone = "41468007";
                break;
            case "NO":
                $phone = "48404583";
                break;
            case "NL":
                $phone = "0642227516";
                break;
            default:
                $phone = "30306900";
        }

        $this->waitForFrameToLoad("klarna-checkout-iframe");
        $this->selectFrame("klarna-checkout-iframe");

        if($this->isElementPresent("button-primary__loading-wrapper")) {
            $this->type("//div[@id='customer-details-next']//input[@id='phone']",$phone);
            $this->type("//div[@id='customer-details-next']//input[@id='date_of_birth']","01011980");
            $this->clickAndWait("button-primary__loading-wrapper");
        }
        $this->delayLoad();
        $this->clickAndWait("//div[@id='shipping-selector-next']//*[text()='Example Set1: UPS 48 hours']");
        $this->delayLoad();

        if($this->isElementPresent("pgw-iframe"))
        {
            $this->selectFrame('pgw-iframe');
            $this->type("text-card_number", "4111111111111111");
            $this->type("text-expiry_date", "0124");
            $this->type("text-security_code", "111");
            $this->selectFrame("relative=top");
            $this->selectFrame("klarna-checkout-iframe");
        }

        $this->waitForElement("//div[@id='buy-button-next']//button");
        $this->clickAndWait("//div[@id='buy-button-next']//button");
        $this->waitForFrameToLoad('relative=top');
        $this->selectFrame('relative=top');
        $this->delayLoad();
        $this->waitForText("Thank you");
        $this->assertTextPresent("Thank you");
        $this->stopMinkSession();//force browser restart to clean previous order address
    }

    /**
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function klarnaKCOMethodsProvider()
    {
        $this->prepareKlarnaDatabase('KCO');

        return [
            ['GB'],
            ['FI'],
            ['AT'],
            ['SE'],
            ['NO'],
//            ['NL'],
            ['DK'],
        ];
    }
}