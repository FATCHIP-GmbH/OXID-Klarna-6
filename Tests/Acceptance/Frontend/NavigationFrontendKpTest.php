<?php

namespace TopConcepts\Klarna\Tests\Acceptance\Frontend;

use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Tests\Acceptance\AcceptanceKlarnaTest;


class NavigationFrontendKpTest extends AcceptanceKlarnaTest
{

    /**
     * @param $title
     * @param $radio
     * @param $desc
     * @param $iframe
     * @param null $country
     * @throws \Exception
     * @dataProvider klarnaKPMethodsProvider
     */
    public function testFrontendKpOrder($title, $radio,$desc, $iframe, $country = null)
    {
        $this->prepareKlarnaDatabase('KP');
        //Navigate untill step 3
        $this->navigateToPay($country);

        //step 3
        $this->assertTextPresent($title);
        $this->clickAndWait("//input[@value='".$radio."']", 2);

        $this->assertTextPresent($desc);

        $this->click("css=.nextStep");
        if($iframe != 'klarna-pay-now-fullscreen' && $country != 'GB') {
            $this->waitForFrameToLoad($iframe, 2000);
            $this->selectFrame($iframe);

            if($country == 'FI' || $country == 'DK' || $country == 'NO' || $country == 'SE'){

                $phone = $this->getKlarnaDataByName('sKlarnaPhoneNumber');
                switch ($country)
                {
                    case "FI":
                        $number = "311280-999J";
                        break;
                    case "DK":
                        $number = "171035-4509";
                        $phone = "41468007";
                        break;
                    case "NO":
                        $number = "01018043587";
                        $phone = "48404583";
                        break;
                    case "SE":
                        $number = "880330-7019";
                        break;
                    default:
                        $number = "";
                }


                $this->type("//div[@id='purchase-approval-national-identification-number__root']//input[@id='purchase-approval-national-identification-number']",$number);
                $this->type("//div[@id='purchase-approval-phone-number__root']//input[@id='purchase-approval-phone-number']",$phone);
            } else {
                $this->type("//div[@id='purchase-approval-date-of-birth__root']//input[@id='purchase-approval-date-of-birth']",$this->getKlarnaDataByName('sKlarnaBDate'));
                $this->type("//div[@id='purchase-approval-phone-number__root']//input[@id='purchase-approval-phone-number']",$this->getKlarnaDataByName('sKlarnaPhoneNumber'));
                if($this->isElementPresent("purchase-approval-accept-terms")) {
                    $this->click("//div[@id='purchase-approval-accept-terms']//input[@type='checkbox']");
                }
            }

            $this->clickAndWait("//button[@id='purchase-approval-continue']");
        }

        $this->selectFrame('relative=top');
        $this->waitForText("Please check all data on this overview before submitting your order!");
        $this->assertTextPresent("Please check all data on this overview before submitting your order!");
        $this->clickAndWait("//form[@id='orderConfirmAgbBottom']//button");

        if($iframe == 'klarna-pay-now-fullscreen')
        {
            $this->waitForFrameToLoad($iframe, 2000);
            $this->selectFrame($iframe);
            $this->getMinkSession()->executeScript("document.getElementsByTagName('iframe')[0].setAttribute('id', 'inner-frame')");

            $this->selectFrame('inner-frame');

            $this->type("//form[@id='WizardForm']//input[@id='BankCodeSearch']",$this->getKlarnaDataByName('sKlarnaPayNowBank'));
            $this->typeKeys("BankCodeSearch",$this->getKlarnaDataByName('sKlarnaPayNowBank'));
            $this->click("//form[@id='WizardForm']//button");

            $this->type("//form[@id='WizardForm']//input[@id='BackendFormLOGINNAMEUSERID']",$this->getKlarnaDataByName('sKlarnaPayNowLoginPin'));
            $this->typeKeys("BackendFormLOGINNAMEUSERID",$this->getKlarnaDataByName('sKlarnaPayNowLoginPin'));
            $this->type("//form[@id='WizardForm']//input[@id='BackendFormUSERPIN']",$this->getKlarnaDataByName('sKlarnaPayNowLoginPin'));
            $this->typeKeys("BackendFormUSERPIN",$this->getKlarnaDataByName('sKlarnaPayNowLoginPin'));
            $this->click("//form[@id='WizardForm']//button");

            $this->click("//form[@id='WizardForm']//input[@id='account-1']");
            $this->click("//form[@id='WizardForm']//button");

            $this->type("//form[@id='WizardForm']//input[@id='BackendFormTan']",$this->getKlarnaDataByName('sKlarnaPayNowTan'));
            $this->typeKeys("BackendFormTan",$this->getKlarnaDataByName('sKlarnaPayNowTan'));
            $this->click("//form[@id='WizardForm']//button");
            $this->selectFrame('relative=top');
        }

        $this->waitForItemAppear("thankyouPage", 60);
        $this->waitForText("We will inform you immediately if an item is not deliverable.");
        $this->assertTextPresent("We will inform you immediately if an item is not deliverable.");
        $this->assertKlarnaData();
        $this->stopMinkSession();
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @return array
     */
    public function klarnaKPMethodsProvider()
    {

        return [
            ['Pay Later', 'klarna_pay_later', 'Pay X days after delivery', 'klarna-pay-later-fullscreen'],
            ['Pay Later', 'klarna_pay_later', 'Pay X days after delivery', 'klarna-pay-later-fullscreen', 'AT'],
            ['Pay Later', 'klarna_pay_later', 'Pay X days after delivery', 'klarna-pay-later-fullscreen', 'DK'],
            ['Pay Later', 'klarna_pay_later', 'Pay X days after delivery', 'klarna-pay-later-fullscreen', 'FI'],
            ['Pay Later', 'klarna_pay_later', 'Pay X days after delivery', 'klarna-pay-later-fullscreen', 'NL'],
            ['Pay Later', 'klarna_pay_later', 'Pay X days after delivery', 'klarna-pay-later-fullscreen', 'NO'],
            ['Pay Later', 'klarna_pay_later', 'Pay X days after delivery', 'klarna-pay-later-fullscreen', 'SE'],
            ['Pay Later', 'klarna_pay_later', 'Pay X days after delivery', 'klarna-pay-later-fullscreen', 'GB'],
            ['Slice It', 'klarna_slice_it', 'Pay over time', 'klarna-pay-over-time-fullscreen'],
            ['Pay Now', 'klarna_pay_now', 'Easy and direct payment', 'klarna-pay-now-fullscreen'],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testKpPayNowDebitOrder()
    {
        $this->prepareKlarnaDatabase('KP');

        //Navigate untill step 3
        $this->navigateToPay();
        $this->click("//input[@value='klarna_pay_now']");
        $this->assertTextPresent('Easy and direct payment');
        $this->selectFrame("klarna-pay-now-main");
        $this->click("payment-selector-direct_debit");
        $this->selectFrame('relative=top');
        $this->click("css=.nextStep");
        $this->waitForFrameToLoad('klarna-pay-now-fullscreen', 2000);
        $this->selectFrame('klarna-pay-now-fullscreen');
        $this->type("//div[@id='purchase-approval-date-of-birth__root']//input[@id='purchase-approval-date-of-birth']",$this->getKlarnaDataByName('sKlarnaBDate'));
        $this->type("//div[@id='purchase-approval-phone-number__root']//input[@id='purchase-approval-phone-number']",$this->getKlarnaDataByName('sKlarnaPhoneNumber'));
        if($this->isElementPresent("purchase-approval-accept-terms")) {
            $this->click("//div[@id='purchase-approval-accept-terms']//input[@type='checkbox']");
        }
        $this->clickAndWait("//button[@id='purchase-approval-continue']");

        $this->assertTextPresent("Konto bestätigen");
        $this->click("//div[@id='direct-debit-mandate-review__bottom']//button");
        $this->assertTextPresent("Großartig!");
        $this->click("//div[@id='direct-debit-confirmation__bottom']//button");
        $this->selectFrame('relative=top');
        $this->clickAndWait("//form[@id='orderConfirmAgbBottom']//button");
        $this->waitForItemAppear("thankyouPage", 60);
        $this->waitForText("We will inform you immediately if an item is not deliverable.");
        $this->assertTextPresent("We will inform you immediately if an item is not deliverable.");
        $this->assertKlarnaData();
    }

    protected function navigateToPay($country = null)
    {
        $userLogin = "user";

        $this->openNewWindow($this->getTestConfig()->getShopUrl(), false);
        $this->addToBasket('05848170643ab0deb9914566391c0c63');
        $this->clickAndWait("link=Display cart");
        $this->assertTextPresent('Continue to the next step');
        $this->clickAndWait("css=.nextStep");

        if ($country) {
           $this->switchCurrency(KlarnaConsts::getCountry2CurrencyArray()[$country]);
           $userLogin = "user_".strtolower($country);
        }

        //login//step1
        $this->type("//form[@name='login']//input[@name='lgn_usr']", $userLogin."@oxid-esales.com");
        $this->type("//form[@name='login']//input[@name='lgn_pwd']", "12345");
        $this->clickAndWait("//form[@name='login']//button");

        //step 2
        $this->assertTextPresent('Billing address');
        $this->clickAndWait("css=.nextStep");
    }
}
