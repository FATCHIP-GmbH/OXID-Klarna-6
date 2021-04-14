<?php

namespace TopConcepts\Klarna\Tests\Codeception;

use Codeception\Example;
use Exception;
use OxidEsales\Codeception\Step\Basket;
use TopConcepts\Klarna\Core\KlarnaConsts;
use OxidEsales\Codeception\Module\Translation\Translator;

class ANavigationFrontendKpCest
{

    /**
     * @group KP_frontend
     * @throws Exception
     */
    public function testKpPayNowDebitOrder(AcceptanceTester $I)
    {
        $I->clearShopCache();
        $I->loadKlarnaAdminConfig('KP');

        //Navigate untill step 3
        $this->navigateToPay($I);
        //Wait for Klarna page to load
        $I->wait(6);
        $I->selectOption('form input[name=paymentid]', 'Pay Now');
        $I->switchToIFrame("klarna-pay-now-main");
        $I->click('//*[@id="installments-direct_debit|-1"]');
        $I->wait(2);
        $I->switchToIFrame();
        $I->click(".nextStep");
        $I->wait(2);
        $I->switchToIFrame('klarna-pay-now-fullscreen');
        $this->fillFieldSpecial('//*[@id="purchase-approval-form-date-of-birth"]',$I->getKlarnaDataByName('sKlarnaBDate'), $I);
        $this->fillFieldSpecial('//*[@id="purchase-approval-form-phone-number"]',$I->getKlarnaDataByName('sKlarnaPhoneNumber'), $I);
        $I->click('//*[@id="purchase-approval-form-continue-button"]');
        $I->wait(2);

        try {
            //Check if IBAN needs to be filled
            $I->seeElement('//*[@id="iban"]');
            $I->waitForElementClickable('//*[@id="iban"]');
            $this->fillFieldSpecial('//*[@id="iban"]', $I->getKlarnaDataByName('sKlarnaPayNowIbanDE'), $I);
            $I->click('//*[@id="mandate-signup-sepa-iban__footer-button-wrapper"]');
            $I->waitForElementClickable('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
            $I->click('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
        } catch (Exception $e) {
            $I->click('//*[@id="mandate-review__confirmation-button"]');
        }

        $I->wait(5);
        $I->switchToIFrame();
        $I->wait(2);
        $I->click(".nextStep");
        $I->waitForPageLoad();
        $I->wait(4);
        $I->seeInCurrentUrl('thankyou');
        $I->wait(2);
        $I->assertKlarnaData();
    }

    /**
     * @group KP_frontend
     * @param AcceptanceTester $I
     * @param Example $data
     * @throws Exception
     * @dataProvider klarnaKPMethodsProvider
     */
    public function testFrontendKpOrder(AcceptanceTester $I, Example $data)
    {
        $I->clearShopCache();
        $I->loadKlarnaAdminConfig('KP');

        if(empty($data['country'])) {
            $data['country'] = null;
        }

        //Navigate untill step 3
        $this->navigateToPay($I, $data['country']);

        //step 3
        //Wait for Klarna page to load
        $I->wait(6);
        $I->click("//input[@value='".$data['radio']."']");

        $I->click(".nextStep");
        $I->wait(2);
        if($data['country'] != 'GB') {
            $I->switchToIFrame($data['iframe']);

            if($data['country'] == 'FI' || $data['country'] == 'DK' || $data['country'] == 'NO' || $data['country'] == 'SE'){

                $phone = $I->getKlarnaDataByName('sKlarnaPhoneNumber');
                switch ($data['country'])
                {
                    case "FI":
                        $number = "311280-999J";
                        break;
                    case "DK":
                        $number = "010188-1234";
                        $phone = "41468007";
                        break;
                    case "NO":
                        $number = "01087000571";
                        $phone = "48404583";
                        break;
                    case "SE":
                        $number = "880330-7019";
                        break;
                    default:
                        $number = "";
                }

                $this->fillFieldSpecial('//*[@id="purchase-approval-form-national-identification-number"]',$number, $I);
                $this->fillFieldSpecial('//*[@id="purchase-approval-form-phone-number"]',$phone, $I);
                $I->click('//*[@id="purchase-approval-form-continue-button"]');
            } else {
                $bday = null;
                if($data['country'] == 'AT') {
                    $bday = '14041988';
                }
                $I->wait(2);
                if($data['country'] == 'NL') {
                    $I->wait(2);
                    $I->switchToIFrame();
                    $I->wait(1);
                    $I->switchToIFrame($data['iframe']);
                    $this->fillFieldSpecial('//*[@id="invoice_kp-purchase-approval-form-date-of-birth"]',$I->getKlarnaDataByName('sKlarnaBDate'), $I);
                    $this->fillFieldSpecial('//*[@id="invoice_kp-purchase-approval-form-phone-number"]',$I->getKlarnaDataByName('sKlarnaPhoneNumber'), $I);
                    $I->click('//*[@id="invoice_kp-purchase-approval-form-continue-button"]');
                } else {
                    $this->fillFieldSpecial('//*[@id="purchase-approval-form-date-of-birth"]',$bday != null?$bday:$I->getKlarnaDataByName('sKlarnaBDate'), $I);
                    $this->fillFieldSpecial('//*[@id="purchase-approval-form-phone-number"]',$I->getKlarnaDataByName('sKlarnaPhoneNumber'), $I);
                    $I->click('//*[@id="purchase-approval-form-continue-button"]');
                }
            }
        }

        if($data['country'] == 'GB') {
            $I->wait(1);
            $I->switchToIFrame();
            $I->wait(1);
            $I->switchToIFrame($data['iframe']);
            $I->click('//*[@id="btn-continue"]');
            $I->wait(2);
            $this->fillFieldSpecial('//*[@id="otp_field"]','123456', $I);
            $I->wait(2);
            $I->click('Confirm');
        }

        if($data['iframe'] == 'klarna-pay-now-fullscreen') {
            $I->wait(2);
            try {
                //Check if IBAN needs to be filled
                $I->seeElement('//*[@id="iban"]');
                $I->waitForElementClickable('//*[@id="iban"]');
                $this->fillFieldSpecial('//*[@id="iban"]', $I->getKlarnaDataByName('sKlarnaPayNowIbanDE'), $I);
                $I->click('//*[@id="mandate-signup-sepa-iban__footer-button-wrapper"]');
                $I->waitForElementClickable('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
                $I->click('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
            } catch (Exception $e) {
                $I->click('//*[@id="mandate-review__confirmation-button"]');
            }
        }

        $I->wait(8);
        $I->switchToIFrame();
        $I->click(".nextStep");
        $I->waitForPageLoad();
        $I->wait(2);
        $I->seeInCurrentUrl('thankyou');
        $I->assertKlarnaData();
    }

    /**
     * @return array
     */
    protected function klarnaKPMethodsProvider()
    {

        return [
            ['radio' => 'klarna_pay_later', 'iframe' => 'klarna-pay-later-fullscreen', 'country' => null],
            ['radio' => 'klarna_pay_later', 'iframe' => 'klarna-pay-later-fullscreen','country' => 'AT'],
            ['radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'DK'],
            ['radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'FI'],
            ['radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'NL'],
            ['radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'NO'],
            ['radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'SE'],
            ['radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'GB'],
            ['radio' => 'klarna_slice_it','iframe' => 'klarna-pay-over-time-fullscreen','country' => null],
            ['radio' => 'klarna_pay_now','iframe' => 'klarna-pay-now-fullscreen','country' => null],
        ];
    }

    /**
     * @group KP_frontend
     * @param AcceptanceTester $I
     * @throws Exception
     */
    public function testB2BOrder(AcceptanceTester $I)
    {
        $I->clearShopCache();
        $I->loadKlarnaAdminConfig('KP', 'B2B_B2C');

        //Navigate untill step 3
        $this->navigateToPay($I, 'DE', true);
        //Wait for Klarna page to load
        $I->wait(6);
        $I->selectOption('form input[name=paymentid]', 'Pay Later');
        $I->click(".nextStep");
        $I->waitForElementVisible('//*[@id="klarna-pay-later-fullscreen"]');
        $I->switchToIFrame("klarna-pay-later-fullscreen");
        $I->wait(2);
        $I->click('//*[@id="mismatch-dialog-confirm-button"]');
        $I->wait(3);
        $this->fillFieldSpecial('//*[@id="purchase-approval-form-date-of-birth"]',$I->getKlarnaDataByName('sKlarnaBDate'), $I);
        $this->fillFieldSpecial('//*[@id="purchase-approval-form-phone-number"]',$I->getKlarnaDataByName('sKlarnaPhoneNumber'), $I);
        $I->click('//*[@id="purchase-approval-form-continue-button"]');
        $I->wait(8);
        $I->switchToIFrame();
        $I->wait(2);
        $I->click(".nextStep");
        $I->waitForPageLoad();
        $I->wait(4);
        $I->seeInCurrentUrl('thankyou');
    }

    /**
     * @group KP_frontend
     * @param AcceptanceTester $I
     * @throws Exception
     */
    public function testKpSplitOrder(AcceptanceTester $I)
    {
        $I->clearShopCache();
        $I->loadKlarnaAdminConfig('KPSPLIT');
        //Navigate untill step 3
        $this->navigateToPay($I);
        //Wait for Klarna page to load
        $I->wait(6);
        $I->selectOption('form input[name=paymentid]', 'Direct Debit');
        $I->click(".nextStep");
        $I->wait(2);
        $I->switchToIFrame('klarna-direct-debit-fullscreen');
        $this->fillFieldSpecial('//*[@id="purchase-approval-form-date-of-birth"]',$I->getKlarnaDataByName('sKlarnaBDate'), $I);
        $this->fillFieldSpecial('//*[@id="purchase-approval-form-phone-number"]',$I->getKlarnaDataByName('sKlarnaPhoneNumber'), $I);

        $I->click('//*[@id="purchase-approval-form-continue-button"]');
        $I->wait(2);
        try {
            //Check if IBAN needs to be filled
            $I->seeElement('//*[@id="iban"]');
            $I->waitForElementClickable('//*[@id="iban"]');
            $this->fillFieldSpecial('//*[@id="iban"]', $I->getKlarnaDataByName('sKlarnaPayNowIbanDE'), $I);
            $I->click('//*[@id="mandate-signup-sepa-iban__footer-button-wrapper"]');
            $I->waitForElementClickable('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
            $I->click('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
        } catch (Exception $e) {
            $I->click('//*[@id="mandate-review__confirmation-button"]');
        }
        $I->wait(7);

        $I->switchToIFrame();
        $I->click(".nextStep");
        $I->waitForPageLoad();
        $I->wait(4);
        $I->seeInCurrentUrl('thankyou');
    }

    /**
     * @group KP_frontend
     * @param AcceptanceTester $I
     * @throws Exception
     */
    public function testKpCardOrder(AcceptanceTester $I)
    {
        $I->clearShopCache();
        $I->loadKlarnaAdminConfig('KPSPLIT');
        //Navigate untill step 3
        $this->navigateToPay($I);
        //Wait for Klarna page to load
        $I->wait(6);
        $I->waitForPageLoad();
        $I->click('#payment input[value=klarna_card]');
        $I->wait(3);
        //Fill card info
        $I->switchToIFrame('klarna-card-main');
        $I->wait(2);
        $I->switchToIFrame('payment-gateway-frame');
        $I->wait(2);
        $I->fillField('cardNumber', "4111111111111111");
        $I->wait(2);
        $I->fillField("securityCode", "111");
        $I->wait(2);
        $I->fillField("expire", "01/24");
        $I->switchToIFrame();
        $I->wait(3);
        $I->click(".nextStep");
        $I->wait(2);
        $I->waitForPageLoad();
        $I->click(Translator::translate('SUBMIT_ORDER'));
        $I->waitForElement('#thankyouPage');
        $I->waitForPageLoad();
        $I->wait(4);
        $I->seeInCurrentUrl('thankyou');
    }

    /**
     * @param null $country
     * @param bool $isB2B
     * @param AcceptanceTester $I
     * @throws Exception
     */
    protected function navigateToPay(AcceptanceTester $I, $country = null, $isB2B = false)
    {
        $userLogin = "user";
        $basket = new Basket($I);
        $homePage = $I->openShop();
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $homePage->openMiniBasket()->openCheckout();
        $I->waitForElementClickable(".nextStep");
        if ($country) {
            $I->switchCurrency(KlarnaConsts::getCountry2CurrencyArray()[$country]);
           $userLogin = "user_".strtolower($country);

           if($isB2B){
               $userLogin .= "_b2b";
           }
        }

        $homePage->loginUser($userLogin."@oxid-esales.com", "12345");

        //step 2
        $I->canSee('Billing address');
        $I->click(".nextStep");
    }

    /**
     * Fill the field character by character
     * @param string $input
     * @param string $msg
     * @param AcceptanceTester $I
     */
    protected function fillFieldSpecial(string $input, string $msg, AcceptanceTester $I)
    {
        foreach (str_split($msg) as $key) {
            $I->pressKey($input, $key);
        }
    }
}
