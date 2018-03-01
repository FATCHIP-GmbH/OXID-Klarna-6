<?php
namespace SeleniumTests;

class KlarnaSeleniumBaseTestCase extends \PHPUnit_Extensions_Selenium2TestCase
{
    /**
     * @var array of config values
     */
    static $config;

    /**
     * @var bool
     */
    static $shopConfigured;

    /**
     * @var string app base url
     */
    protected $baseUrl;

    /**
     * @var array of input data
     */
    protected $inputData = array();


    protected $delCountryISO;
    protected $billCountryISO;

    public static function setUpBeforeClass()
    {
        self::$config = self::getConfigKeys(
            array('sSSLShopURL')
        );
    }

    public function setUp()
    {

        $this->baseUrl = self::$config['sSSLShopURL'];
        $this->baseUrl = 'https://demoshop-oxid6.topconcepts.com/klarna/4_0_0/ce_4106';

//        $this->setHost('164.132.54.20');
//        $this->setPort(80);

        if(strpos($this->baseUrl, '.ngrok.io')){
            $this->setHost('localhost');
            $this->setPort(4444);
        }

        $this->setBrowser('chrome');
        $this->setBrowserUrl($this->baseUrl);

    }

    public function setUpShopConfig(array $configData)
    {
        if(self::$shopConfigured !== NULL)
            return;

        $this->url('/');
        $this->waitForJQuery();
        $configScript = '
        $.post(
            "'.$this->baseUrl.'/?cl=klarna_selenium_controller&fnc=setShopConfig",
            JSON.stringify(arguments[0]),
            arguments[1],
            "json"
        );
        ';

        // arguments[1] returns response as result of executeAsync
        $response =  $this->executeAsync(array(
            'script' => $configScript,
            'args' => array($configData),
        ));

        if($response === 200){
            $this->shopConfigured = true;
            print_r("\nShop test config loaded successfully: $response\n");
        } else {
            $this->shopConfigured = false;
            print_r("\nCouldn't load test config\n");
            print_r("Oxid response:\n");
            print_r("$response\n");
        }
        $this->refresh();
    }

    /**
     * @param string $selector css selector
     * @param int $wait - maximum (in seconds)
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element - false on time-out
     * @throws \Exception
     */
    protected function waitForElement($selector, $wait=10) {
        for ($i=0; $i <= $wait; $i++) {
            try{
                $element = $this->byCssSelector($selector);
                return $element;
            }
            catch (\Exception $e) {
                sleep(1);
            }
        }

        throw new \Exception("$selector - element not found!");
    }

    /**
     * Waiting for element to be clickable and then clicks it
     * @param PHPUnit_Extensions_Selenium2TestCase_Element $element
     * @param int $wait
     * @return bool
     */
    protected function clickWhenReady($element, $wait=10) {
        for ($i=0; $i <= $wait; $i++) {
            try{
                $element->click();
                return true;
            }
            catch (\Exception $e) {
                sleep(1);
            }
        }
        return false;
    }

    /**
     * @param PHPUnit_Extensions_Selenium2TestCase_Element $element
     * @param int $wait
     * @return PHPUnit_Extensions_Selenium2TestCase_Element|false - false on time-out
     */
    protected function waitForElementVisibility($element, $wait=10) {
        for ($i=0; $i <= $wait; $i++) {
            try{
                $element->displayed();
                return $element;
            }
            catch (\Exception $e) {
                sleep(1);
            }
        }
        return false;
    }


    protected function waitForAjaxComplete()
    {
        $this->waitForJsExpression('$.active == 0');
    }

    protected function waitForJQuery()
    {
        $this->waitForJsExpression('typeof $ !== "undefined"');
    }

    /**
     * Tests javascript expression until return true or timeout
     *
     * waitUntil(callback, timeout)
     * callback - will be called in a loop until return non null value or timeout
     *
     * executeAsync(array(string script, array arguments))
     * executeAsync returns prams of default arguments[0] function when arguments[0] will be called
     * A bit strange, maybe there is another solution to pass the value from browser's js environment.
     * @param string $condition javascript expression or function
     * @param int $timeout
     */
    protected function waitForJsExpression($condition, $timeout = 10000)
    {
        $driver = $this;
        $this->waitUntil(function() use($driver, $condition) {
            $script = sprintf('arguments[0].call(null, %s)', $condition);
            if($driver->executeAsync(array(
                'script' => $script,
                'args' => array()
            ))
            )
                return true;
        }, $timeout);
    }

    /**
     * @param $selectorsList array of css selectors/strings
     * @throws \Exception
     */
    protected function runClickSequence($selectorsList)
    {
        foreach($selectorsList as $selector){
            if($selector === '.modal-footer a')
                sleep(1);

            $element = $this->waitForElement($selector);
            $this->clickWhenReady($element);
        }
    }

    /**
     * Injects js script to populate oxid user form
     * @param string $countryISO
     * @param string $case
     * @return string
     */
    protected function populateOxAddressForm($countryISO = null, $case = '')
    {
        $data = $this->inputData['oxuser']; // always get default data
        if($countryISO){
            $data['invadr[oxuser__oxcountryid]'] = $this->inputData['countries'][$countryISO]['oxid'];
            $data['deladr[oxaddress__oxcountryid]'] = $this->inputData['countries'][$countryISO]['oxid'];
        }

        switch ($case) {

            case 'diffNames':
                $data['showDeliveryForm'] = 1;
                $data['invadr[oxuser__oxfname]'] = 'Bonifacy';
                break;

            case 'diffCountries':
                $data['showDeliveryForm'] = 1;
                $data['deladr[oxaddress__oxcountryid]'] = $this->inputData['countries']['AT']['oxid'];
                break;

            case 'diffDelAddressPass':
                $data['showDeliveryForm'] = 1;
                break;
        }

        $populateAddressDataScript = '
        var form = $("form input[value=createuser], form input[value=changeuser]").closest("form")[0];
        
        if(arguments[0][\'showDeliveryForm\'] && $("#showShipAddress")[0].checked)
            $("#showShipAddress").click();
                    
        var i = 0;
        while(form[i]){
            var value = arguments[0][form[i].name];
            console.log(form[i].name, typeof value);
            if(typeof value !== "undefined"){
                if(form[i].type === "checkbox"){
                    form[i].checked = value;            
                } else {
                    form[i].value = value;
                }
            }

            i++;
        }
        ';

       return $this->execute(array(
            'script' => $populateAddressDataScript,
            'args' => array($data),
        ));
    }

    /**
     * @param $searchedKeys array Keys we want to extract from config.inc.php file
     * @return array
     */
    public static function getConfigKeys($searchedKeys){

        $configIterator = function($handle){
            while(!feof($handle)) {
                $line = trim(fgets($handle));
                if(strpos($line, '$this->') === 0){
                    preg_match('/\$this->(.*)\s?=\s?(.*);/', $line, $o);
                    if(count($o) >= 3)
                        yield trim($o[1], ' ') => trim($o[2], '\'"');
                }
            }
        };

        $result = array();
        $confgFile = fopen(getcwd() . '/../../config.inc.php', 'r');
        foreach($configIterator($confgFile) as $key => $value){
            if(in_array($key, $searchedKeys))
                $result[$key] = $value;
        }
        fclose($confgFile);
        return $result;
    }

    /**
     * Parses than you page content to fetch oxorder ID
     * @return string
     * @throws \Exception
     */
    protected function findOrderNr(){
        $aContent = explode("\n", $this->waitForElement('#thankyouPage')->text());
        preg_match('/[0-9]+/', $aContent[2], $matches);

        return $matches[0];
    }

    protected function getCurrentOrderData()
    {
        $this->url('/?cl=klarna_selenium_controller&fnc=getCurrentOrderData');
        $oOrderData = json_decode($this->byTag('body')->text(), false);
        return $oOrderData;
    }

    protected function getFinalOrderData($oxOrderNr)
    {
        $this->url('/?cl=klarna_selenium_controller&fnc=getFinalOrderData&oxOrderNr=' . $oxOrderNr);
        $oOrderData = json_decode($this->byTag('body')->text(), false);

        return $oOrderData;
    }

    /**
     * Asserts that oxid and klarna order contains the same data
     * @param stdClass $oOrderData containing klarna and oxid order data
     */
    protected function assertOrders($oOrderData){

        $oxidOrder = $oOrderData->oxidOrder;
        $klarnaOrder = $oOrderData->klarnaOrder;

        $this->assertEquals('ACCEPTED', $klarnaOrder->fraud_status,
            $this->getAssertionInfo("klarna fraud_status",__FUNCTION__)
        );
        $this->assertEquals('AUTHORIZED', $klarnaOrder->status,
            $this->getAssertionInfo("klarna status", __FUNCTION__)
        );

        $this->assertEquals($oxidOrder->oxorder__oxtotalordersum->rawValue * 100,
            $klarnaOrder->order_amount,
            $this->getAssertionInfo("order_amount don't match",__FUNCTION__)
        );
        $this->assertEquals($oxidOrder->oxorder__oxordernr->rawValue,
            $klarnaOrder->merchant_reference1,
            $this->getAssertionInfo("merchant_reference1 don't match", __FUNCTION__));
    }

    /**
     * Injects javascript file
     * @param string $fileName path related to the tests folder
     * @return string|PHPUnit_Extensions_Selenium2TestCase_Element if script returns DOM Element
     */
    protected function loadJsFile($fileName)
    {
        $content = file_get_contents(getcwd() . '/tests/' . $fileName);

        return $this->execute(array(
            'script' => $content,
            'args' => array(),
        ));
    }

    protected function getAssertionInfo($customMessage, $funcName = '')
    {
        return json_encode(array(
                'method' => $funcName,
                'message' => $customMessage
            )
        );
    }

    protected function goToKCOIframe()
    {
        $this->url("/");
        $this->timeouts()->implicitWait(8000);

        $productForm = $this->byCssSelector("#bargainItems .productData form");
        $this->clickWhenReady($productForm->byCssSelector("button[type=submit]"));

        $modalKCOButton = $this->byXPath("//*[@id=\"basketModal\"]/div/div/div[3]/a[2]");
        $this->clickWhenReady($modalKCOButton);

        // clickElement
        $modalGermanyButton = $this->byXPath("//form[@id='select-country-form']/button[1]");
        $this->clickWhenReady($modalGermanyButton);
    }

    protected function useLoginWidget($users)
    {
        if(strpos($this->baseUrl, 'ngrok.io'))
            $userData = $users[0];
        else
            $userData = $users[1];

        $widget = $this->byId('klarnaLoginWidget');
        $widget->byCssSelector('.drop-trigger')->click();

        $widget->byCssSelector('[type=email]')->value($userData[0]);
        $widget->byCssSelector('[type=password]')->value($userData[1]);
        $widget->byCssSelector('[type=submit]')->click();
    }
}