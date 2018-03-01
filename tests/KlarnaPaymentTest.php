<?php
use SeleniumTests\KlarnaSeleniumBaseTestCase;

class KlarnaPaymentTest extends KlarnaSeleniumBaseTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->inputData['lang'] = array(
            'userDataMismatch' => array(
                'de' => 'Um mit Klarna Zahlungsarten bezahlen',
                'en' => 'When using Klarna payments'
            )
        );

        $this->inputData['countries'] = array(
            'DE' => array(
                'oxid'          => 'a7c40f631fc920687.20179984',
                'KPMethodCount' => 4,
                'KPMList' => array('pay_later')
            ),
            'AT' => array(
                'oxid'          => 'a7c40f6320aeb2ec2.72885259',
                'KPMethodCount' => 2,
                'KPMList' => array('pay_later')
            ),
            'NO' => array(
                'oxid'          => '8f241f11096176795.61257067',
                'KPMethodCount' => 0,
            ),
        );

        $randId                    = rand(0, 3000);
        $this->inputData['oxuser'] = array(
            "lgn_usr"                            => "kostrzeba+$randId@topconcepts.de",
            "blnewssubscribed"                   => 0,
            "invadr[oxuser__oxfname]"            => "Arek",
            "invadr[oxuser__oxlname]"            => "Kostrzeba",
            "invadr[oxuser__oxcompany]"          => "",
            "invadr[oxuser__oxaddinfo]"          => "",
            "invadr[oxuser__oxstreet]"           => "Street Name",
            "invadr[oxuser__oxstreetnr]"         => "21",
            "invadr[oxuser__oxzip]"              => "10557",
            "invadr[oxuser__oxcity]"             => "Berlin",
            "invadr[oxuser__oxcountryid]"        => "a7c40f631fc920687.20179984", // DE
            "invadr[oxuser__oxustid]"            => "",
            "invadr[oxuser__oxfon]"              => "6543216546",
            "invadr[oxuser__oxfax]"              => "",
            "invadr[oxuser__oxmobfon]"           => "",
            "invadr[oxuser__oxprivfon]"          => "",
            "invadr[oxuser__oxbirthdate][day]"   => "14",
            "invadr[oxuser__oxbirthdate][month]" => "6",
            "invadr[oxuser__oxbirthdate][year]"  => "1988",
            "deladr[oxaddress__oxfname]"         => "Arek",
            "deladr[oxaddress__oxlname]"         => "Kostrzeba",
            "deladr[oxaddress__oxcompany]"       => "",
            "deladr[oxaddress__oxaddinfo]"       => "",
            "deladr[oxaddress__oxstreet]"        => "Street Other Name",
            "deladr[oxaddress__oxstreetnr]"      => "21",
            "deladr[oxaddress__oxzip]"           => "10553",
            "deladr[oxaddress__oxcity]"          => "Berlin",
            "deladr[oxaddress__oxcountryid]"     => "a7c40f631fc920687.20179984", // DE
            "deladr[oxaddress__oxstateid]"       => "",
            "deladr[oxaddress__oxfon]"           => "6543216546",
            "deladr[oxaddress__oxfax]"           => "",
            "order_remark"                       => "",
        );

    }

    public function configDataProvider()
    {
        return array(
            array(
                array(
                    'str' => array(
                        'sKlarnaActiveMode' => 'KP'
                    ),
                )
            )
        );
    }

    public function userLoginDataProvider()
    {
        return array(
            array(
                'info@topconcepts.de',
                'muhipo2015'
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

    /**
     * @throws Exception
     */
    public function testDifferentShippingAddress()
    {
        $this->billCountryISO = 'DE';
        $this->delCountryISO  = $this->billCountryISO;
        $this->url('/');
        $this->timeouts()->implicitWait(10000);
        // home page
        $this->initPurchase();
        $goToAddressSequence = array(
            '.nextStep',
            '.nextStep'
        );
        $this->runClickSequence($goToAddressSequence);

        $this->waitForJQuery();
        $this->populateOxAddressForm('DE', 'diffNames');
        $this->waitForElement('.nextStep')->click();


        $lang         = $this->byCssSelector('.languages-menu li.active a')->attribute('hreflang');
        $errorMessage = $this->inputData['lang']['userDataMismatch'][$lang];
        // assert alert is present when names mismatch
        $this->assertContains($errorMessage,
            $this->waitForElement('.alert.alert-danger')->text()
        );

        // assert alert is present when countries mismatch
        $this->url('/?cl=user');
        $this->waitForJQuery();
        $this->populateOxAddressForm('DE', 'diffCountries');
        $this->waitForElement('.nextStep')->click();
        $this->assertContains($errorMessage,
            $this->waitForElement('.alert.alert-danger')->text()
        );

        //TODO: company name provided case

        // assert that there are 3 options for DE with correct data
        $this->url('/?cl=user');
        $this->populateOxAddressForm('DE', 'diffDelAddressPass');
        $this->waitForElement('.nextStep')->click();
        $kpMethods = $this->elements($this->using('css selector')->value('.kp-outer'));
        $this->assertEquals(
            $this->inputData['countries'][$this->billCountryISO]['KPMethodCount'],
            count($kpMethods)
        );

        // change currency assert there is no KP options
        $this->byCssSelector('.currencies-menu button')->click();
        $this->byCssSelector('.currencies-menu li a[title="GBP"]')->click();
        $kpMethods = $this->elements($this->using('css selector')->value('.kp-outer'));
        $this->assertEquals(0, count($kpMethods));

    }

    public function testNotLoggedInCase()
    {
        $testCountries = array('DE', 'AT');

        foreach($testCountries as $countryISO){
            foreach($this->inputData['countries'][$countryISO]['KPMList'] as $kpMethod){
                $this->notLoggedInCase($countryISO, $kpMethod);
            }
        }
    }

    /**
     * @param $users
     * @dataProvider loginWidgetsDataProvider
     */
    public function testLoggedInCase($users)
    {
        if(strpos($this->baseUrl, 'ngrok.io'))
            $userData = $users[0];
        else
            $userData = $users[1];

        $this->loggedInCase('DE', 'pay_later', $userData[0], $userData[1]);
    }


    /**
     * @param $countryISO
     * @param $kpMethod
     * @throws Exception
     */
    protected function notLoggedInCase($countryISO, $kpMethod)
    {
        $this->url('/');
        $this->timeouts()->implicitWait(10000);
        // home page
        $this->initPurchase();
        $goToAddressSequence = array(
            '.nextStep',
            '.nextStep'
        );
        $this->runClickSequence($goToAddressSequence);

        // user address page
        $this->populateOxAddressForm($countryISO);
        $this->waitForElement('.nextStep')->click();

        // payment page
        $kpMethods = $this->elements($this->using('css selector')->value('.kp-outer'));
        $this->assertEquals(
            $this->inputData['countries'][$countryISO]['KPMethodCount'],
            count($kpMethods),
            $this->getAssertionInfo("Number of available KP methods for $countryISO", __FUNCTION__)
        );

        $this->pickKPMethodAndContinue($kpMethod);

        // order over overview page
        $this->clickWhenReady($this->waitForElement('.nextStep'));

        // thankyou page
        $oxOrderNr = $this->findOrderNr();

        // assertion
        $aOrderData = $this->getFinalOrderData($oxOrderNr);
        $this->assertOrders($aOrderData);

    }

    protected function loggedInCase($countryISO, $kpMethod, $username, $password)
    {
        $this->url('/');
        $this->timeouts()->implicitWait(10000);
        // home page
        $this->initPurchase();
        $this->waitForElement('.nextStep')->click();

        // login routine
        $this->byXPath('//*[@id="optionLogin"]/div[2]/div[1]/input')->value($username);
        $this->byXPath('//*[@id="optionLogin"]/div[2]/div[2]/div[1]/input')->value($password);
        $this->byXPath('//*[@id="optionLogin"]/div[3]/button')->click();

        // user page
        $this->setUserCountry($countryISO);
        $this->byCssSelector('.nextStep')->click();

        // payment page
        $kpMethods = $this->elements($this->using('css selector')->value('.kp-outer'));
        $this->assertEquals(
            $this->inputData['countries'][$countryISO]['KPMethodCount'], count($kpMethods),
            "Number of available KP methods for $countryISO"
        );
        $this->pickKPMethodAndContinue($kpMethod);

        // order overview page
        $this->byCssSelector('.nextStep')->click();

        // thankyou page
        $oxOrderNr = $this->findOrderNr();

        // assertion
        $aOrderData = $this->getFinalOrderData($oxOrderNr);
        $this->assertOrders($aOrderData);

    }



    protected function initPurchase()
    {
        $getItemAndGoToCartSequence = array(
            'button.hasTooltip',
            '.modal-footer a',
        );
        $this->runClickSequence($getItemAndGoToCartSequence);
    }

    protected function pickKPMethodAndContinue($kpMethodName)
    {
        $kpRadio = $this->byCssSelector("[data-payment_id=$kpMethodName]");
        $klarnaPayId = str_replace('_', '-', $kpMethodName);
        $kpRadio->click();
        $this->clickWhenReady($this->waitForElement('#paymentNextStepBottom'));
        // switch to iframe
        $this->frame($this->byId('klarna-' . $klarnaPayId . '-fullscreen'));
        $e = $this->waitForElement('#purchase-approval-accept-terms-input__bullet__checkmark');
        $this->clickWhenReady($e);

//        $phoneInput = $this->byId('purchase-approval-phone-number__input');
//        if(!$phoneInput->value())
//            $phoneInput->value($this->inputData['oxuser']['invadr[oxuser__oxfon]']);




        $this->byId('purchase-approval-continue-button')->click();

        $e = $this->waitForElement('#confirmation-button');
        $this->clickWhenReady($e);
        $this->frame(null);
    }

    /**
     * Sets country for logged in user from user page
     * @param $countryISO
     * @return string
     */
    protected function setUserCountry($countryISO)
    {
        $populateAddressDataScript = '
        var form = $("form input[value=createuser], form input[value=changeuser]").closest("form")[0];        
        var i = 0;
        while(form[i]){
            var value = arguments[0][form[i].name];
            console.log(form[i].name, typeof value);
            if(typeof value !== "undefined"){
                if(form[i].type === "checkbox"){
                    if(form[i].name === "blshowshipaddress" && !value &&  form[i].checked
                        ||
                        form[i].name === "blshowshipaddress" && value &&  !form[i].checked
                    ){
                        form[i].click();
                    } else {
                        form[i].checked = value;
                    }
                    
                } else {
                    form[i].value = value;
                }
            }

            i++;
        }
        
        ';
        $data = array("invadr[oxuser__oxcountryid]" => $this->inputData['countries'][$countryISO]['oxid']);
        return $this->execute(array(
            'script' => $populateAddressDataScript,
            'args' => array($data)
        ));
    }
}
