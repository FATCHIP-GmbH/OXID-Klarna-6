<?php
use SeleniumTests\KlarnaSeleniumBaseTestCase;

class KCOTest extends KlarnaSeleniumBaseTestCase
{
    public function configDataProvider()
    {
        return array(
            array(
                array(
                    'str' => array(
                        'sKlarnaActiveMode' => 'KCO'
                    ),
                )
            )
        );
    }

    /**
     *
     * This must be a first test in the class
     * Purpose of this method is to setUp shop configuration for all test cases in this class. Placing this method on
     * top of all tests guaranties that will run as first.
     * @dataProvider configDataProvider
     * @param $aConfig
     */
    public function testConfiguration($aConfig)
    {
        $response = $this->setUpShopConfig($aConfig);
        if($response === 200){
            print_r("\nShop test config loaded successfully: $response\n");
        } else {
            print_r("\nCouldn't load test config\n");
            print_r("Oxid response:\n");
            print_r("$response\n");
        }
    }

    public function testSimpleFull()
    {
        $this->goToKCOIframe();

        $this->fillAddressForm();

        $this->markTestIncomplete('In development.');


        // switchToFrame
        $this->frame($this->byId('pgw-iframe'));
        // setElementText
        $element = $this->byXPath("//*[@id=\"text-card_number\"]");
        $element->click();
        $element->clear();
        $element->value("4111 1111 1111 1111");
        // setElementText
        $element = $this->byXPath("//div/div[1]/form/div[1]/div[2]/div/div[1]/input");
        $element->click();
        $element->clear();
        $element->value("10 / 21");
        // setElementText
        $element = $this->byXPath("//div/div[1]/form/div[1]/div[2]/div/div[2]/input");
        $element->click();
        $element->clear();
        $element->value("123");
        // switchToDefaultContent
        $this->frame();
        // switchToFrame
        $this->frame("klarna-checkout-iframe");
        // clickElement
        $this->byXPath("//div[2]/div/div[3]/div[5]/button")->click();
    }

    protected function fillAddressForm()
    {
        // switchToFrame
        $this->frame("klarna-checkout-iframe");
        // setElementText
        $element = $this->byXPath("//div[2]/div/div[1]/span/span/div/div/form/div[2]/div[1]/div[2]/div[1]/input");
        $element->click();
        $element->clear();
        $element->value("kostrzeba+21111@topconcepts.de");
        // setElementText
        $element = $this->byXPath("//div[2]/div/div[1]/span/span/div/div/form/div[2]/div[1]/div[2]/div[2]/input");
        $element->click();
        $element->clear();
        $element->value("10557");
        // clickElement
        $this->byXPath("//div[2]/div/div[1]/span/span/div/div/form/div[2]/div[2]/button")->click();
        // pause
        sleep(2);
        // setElementText
        $element = $this->byXPath("//div[2]/div/div[2]/form/fieldset[1]/div[2]/div[2]/input");
        $element->click();
        $element->clear();
        $element->value("Arkadiusz");
        // setElementText
        $element = $this->byXPath("//div[2]/div/div[2]/form/fieldset[1]/div[3]/div[2]/input");
        $element->click();
        $element->clear();
        $element->value("Kostrzeba");
        // setElementText
        $element = $this->byXPath("//div[2]/div/div[2]/form/fieldset[1]/div[4]/div[3]/span/input");
        $element->click();
        $element->clear();
        $element->value("Trrrrrrs 14");
        // setElementText
        $element = $this->byXPath("//div[2]/div/div[2]/form/fieldset[1]/div[5]/div[3]/input");
        $element->click();
        $element->clear();
        $element->value("2");
        // setElementText
        $element = $this->byXPath("//div[2]/div/div[2]/form/fieldset[1]/div[5]/div[3]/input");
        $element->click();
        $element->clear();
        $element->value("2");
        // setElementText
        $element = $this->byXPath("//div[2]/div/div[2]/form/fieldset[1]/div[10]/div[2]/input");
        $element->click();
        $element->clear();
        $element->value("656565656");
        // clickElement
        $this->byXPath("//div[2]/div/div[2]/form/fieldset[1]/button")->click();
    }
}
