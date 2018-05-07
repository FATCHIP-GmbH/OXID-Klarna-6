<?php

namespace TopConcepts\Klarna\Tests\Acceptance\Frontend;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\TestingLibrary\AcceptanceTestCase;


class NavigationFrontendTest extends AcceptanceTestCase
{

    /**
     * Test new order guest user
     * @throws \Exception
     */
    public function testFrontendKcoOrder()
    {
        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaActiveMode'";
        DatabaseProvider::getDb()->execute($sql);

        $sql = "INSERT INTO `oxconfig` VALUES ('4060f0f9f705d470282a2ce5ed936e48', 1, 'tcklarna', 'sKlarnaActiveMode', 'str', 0xA7E7BC, 'now()')";
        DatabaseProvider::getDb()->execute($sql);

        $this->openNewWindow($this->getTestConfig()->getShopUrl(), false);
        $this->addToBasket('05848170643ab0deb9914566391c0c63');
        $this->clickAndWait("link=Go to Checkout");
        $this->assertTextPresent('Please choose your shipping country');
        $this->clickAndWait("//form[@id='select-country-form']//button[@value='DE']");
        $this->assertTextPresent('Your chosen country');

        $this->waitForFrameToLoad("klarna-checkout-iframe", 2000);
        $this->selectFrame('klarna-checkout-iframe');
        $this->type("//div[@id='customer-details-next']//input[@id='email']","ferreira@topconcepts.com");
        $this->type("//div[@id='customer-details-next']//input[@id='postal_code']","21079");
        $this->click("//select[@id='title']");
        $this->click("//option[@id='title__option__herr']");
        $this->type("//div[@id='customer-details-next']//input[@id='given_name']","concepts");
        $this->type("//div[@id='customer-details-next']//input[@id='family_name']","test");
        $this->type("//div[@id='customer-details-next']//input[@id='street_name']","Karnapp");
        $this->type("//div[@id='customer-details-next']//input[@id='street_number']","25");
        $this->type("//div[@id='customer-details-next']//input[@id='city']","Hamburg");
        $this->type("//div[@id='customer-details-next']//input[@id='phone']","30306900");
        $this->type("//div[@id='customer-details-next']//input[@id='date_of_birth']","01011980");

        $this->clickAndWait("//div[@id='customer-details-next']//button[@id='button-primary']");
        $this->clickAndWait("//div[@id='terms-consent-next']//input[@type='checkbox']");
        $this->clickAndWait("//div[@id='buy-button-next']//button");
        $this->waitForFrameToLoad('relative=top');
        $this->selectFrame('relative=top');
        $this->assertTextPresent("Thank you");
    }

    /**
     * @param $title
     * @param $radio
     * @param $desc
     * @param $iframe
     * @throws \Exception
     * @dataProvider klarnaKPMethodsProvider
     */
    public function testFrontendKpOrder($title, $radio,$desc, $iframe)
    {
        //Navigate untill step 3
        $this->navigateToPay();

        //step 3
        $this->assertTextPresent($title);
        $this->clickAndWait("//input[@value='".$radio."']", 2);
        $this->assertTextPresent($desc);
        $this->clickAndWait("css=.nextStep");
        $this->waitForFrameToLoad($iframe, 2000);
        $this->selectFrame($iframe);

        //finalize order
        $this->type("//div[@id='purchase-approval-date-of-birth__root']//input[@id='purchase-approval-date-of-birth']","01011980");
        $this->type("//div[@id='purchase-approval-phone-number__root']//input[@id='purchase-approval-phone-number']","111111111");
        $this->clickAndWait("//div[@id='purchase-approval-accept-terms']//input[@type='checkbox']");
        $this->clickAndWait("//div[@id='purchase-approval-continue__loading-wrapper-wrapper']", 7);
        $this->selectFrame('relative=top');
        $this->assertTextPresent("Please check all data on this overview before submitting your order!");
        $this->clickAndWait("//form[@id='orderConfirmAgbBottom']//button");
        $this->waitForText(["Thank you for ordering", "We will inform you immediately if an item is not deliverable."]);
        $this->assertTextPresent("Thank you for ordering");
        $this->assertTextPresent("We will inform you immediately if an item is not deliverable.");
    }

    public function klarnaKPMethodsProvider()
    {
        $this->prepareKPDatabase();
        return [
          ['Pay Later', 'klarna_pay_later', 'Pay X days after delivery', 'klarna-pay-later-fullscreen'],
          ['Slice It', 'klarna_slice_it', 'Pay over time', 'klarna-pay-over-time-fullscreen']
        ];

        //todo Pay Now

    }

    protected function navigateToPay()
    {

        $this->openNewWindow($this->getTestConfig()->getShopUrl(), false);
        $this->addToBasket('05848170643ab0deb9914566391c0c63');
        $this->clickAndWait("link=Display cart");
        $this->assertTextPresent('Continue to the next step');
        $this->clickAndWait("css=.nextStep");

        //login//step1
        $this->type("//form[@name='login']//input[@name='lgn_usr']", "user@oxid-esales.com");
        $this->type("//form[@name='login']//input[@name='lgn_pwd']", "12345");
        $this->clickAndWait("//form[@name='login']//button");

        //step 2
        $this->assertTextPresent('Billing address');
        $this->clickAndWait("css=.nextStep");
    }

    protected function prepareKPDatabase()
    {
        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaActiveMode'";
        DatabaseProvider::getDb()->execute($sql);

        $sql = "INSERT INTO `oxconfig` VALUES ('4060f0f9f705d470282a2ce5ed936e48', 1, 'tcklarna', 'sKlarnaActiveMode', 'str', 0xA75C, 'now()')";
        DatabaseProvider::getDb()->execute($sql);

        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaMerchantId'";
        DatabaseProvider::getDb()->execute($sql);

        $klarnaMerchantId = $this->getKlarnaDataByName('sKlarnaKPMerchantId');
        $klarnaKey = $this->getKlarnaDataByName('sKlarnaEncodeKey');
        $encode = "ENCODE('{$klarnaMerchantId}', '{$klarnaKey}')";

        $sql = "INSERT INTO `oxconfig` VALUES ('f3b48ef3f7c17c916ef6018768377988', 1, 'tcklarna', 'sKlarnaMerchantId', 'str', {$encode}, 'now()')";
        DatabaseProvider::getDb()->execute($sql);

        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaPassword'";
        DatabaseProvider::getDb()->execute($sql);

        $klarnaPassword = $this->getKlarnaDataByName('sKlarnaKPPassword');
        $encode = "ENCODE('{$klarnaPassword}', '{$klarnaKey}')";

        $sql = "INSERT INTO `oxconfig` VALUES ('efbd96702f6cead0967cd37ad2cdf49d', 1, 'tcklarna', 'sKlarnaPassword', 'str', {$encode}, 'now()')";
        DatabaseProvider::getDb()->execute($sql);

    }

    /**
     * Adds tests sql data to database.
     *
     * @param string $sTestSuitePath
     */
    public function addTestData($sTestSuitePath)
    {
        parent::addTestData($sTestSuitePath);

        $sTestSuitePath = realpath(__DIR__ .'/../testSql/');
        $sFileName = $sTestSuitePath . '/demodata.sql';
        if (file_exists($sFileName)) {
            $this->importSql($sFileName);
        }
    }

    /**
     * Returns klarna data by variable name
     *
     * @param $varName
     *
     * @return mixed|null|string
     * @throws \Exception
     */
    protected function getKlarnaDataByName($varName)
    {
        if (!$varValue = getenv($varName)) {
            $varValue = $this->getArrayValueFromFile($varName, __DIR__ .'/../klarnaData.php');
        }

        if (!$varValue) {
            throw new \Exception('Undefined variable: ' . $varName);
        }

        return $varValue;
    }
}
