<?php

namespace TopConcepts\Klarna\Tests\Acceptance;

use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\TestingLibrary\AcceptanceTestCase;
use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaUtils;

class AcceptanceKlarnaTest extends AcceptanceTestCase
{

    /**
     * @param $oxid
     * @param string $expectedStatus
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \oxSystemComponentException
     */
    public function assertKlarnaData($oxid = null, $expectedStatus = "AUTHORIZED")
    {
        $sql = "SELECT OXID, TCKLARNA_ORDERID from `oxorder` ORDER BY `oxorderdate` DESC LIMIT 1";

        if($oxid) {
            $sql = "SELECT OXID, TCKLARNA_ORDERID from `oxorder` WHERE `OXID`='$oxid'";
            $klarnaId = DatabaseProvider::getDb()->getOne($sql);
        } else{
            $result = DatabaseProvider::getDb()->getRow($sql);
            $oxid = $result[0];
            $klarnaId = $result[1];
        }


        /** @var KlarnaOrderManagementClient|KlarnaClientBase $klarnaClient */
        $klarnaClient = KlarnaOrderManagementClient::getInstance();
        $orderData = $klarnaClient->getOrder($klarnaId);

        $order = oxNew(Order::class);
        $order->load($oxid);

        $aFieldMapper = [
            'oxbillfname' => 'given_name',
            'oxbilllname' => 'family_name',
            'joinedAddress' => 'street_address',
            'oxbillzip' => 'postal_code',
            'oxbillcity' => 'city',
            'oxbillcountryid' => 'country',
        ];
        $this->validateAddress($order, $orderData['billing_address'], $aFieldMapper);

        if($order->getFieldData('oxbillstreet') != $order->getFieldData('oxdelstreet')) {
            $aFieldMapper = [
                'oxdelfname' => 'given_name',
                'oxdellname' => 'family_name',
                'joinedAddress' => 'street_address',
                'oxdelzip' => 'postal_code',
                'oxdelcity' => 'city',
                'oxdelcountryid' => 'country',
            ];

            $this->validateAddress($order, $orderData['shipping_address'], $aFieldMapper, 'del');
        }

        $this->assertEquals($order->getFieldData('oxbillemail'), $orderData['billing_address']['email']);
        $this->assertEquals($expectedStatus, $orderData['status']);
        $this->assertEquals('ACCEPTED', $orderData['fraud_status']);

        $orderAmount = KlarnaUtils::parseFloatAsInt($order->getTotalOrderSum() * 100);
        if($expectedStatus == 'CAPTURED'){
            $this->assertEquals($orderAmount, $orderData['captured_amount']);
        }
        $this->assertEquals($orderAmount, $orderData['order_amount']);
    }

    protected function validateAddress($order,$orderData, $aFieldMapper, $type = 'bill')
    {
        foreach ($aFieldMapper as $col => $item) {
            if(strpos($col,'countryid') != false)
            {
                $oCountry   = oxNew(Country::class);
                $sCountryId = $oCountry->getIdByCode($orderData[$item]);

                $this->assertEquals($sCountryId, $order->getFieldData($col));
                continue;
            }
            if ($col == 'joinedAddress') {
                $streetAddress = $order->getFieldData('ox'.$type.'street').' '.$order->getFieldData('ox'.$type.'streetnr');
                $this->assertEquals($streetAddress, $orderData[$item]);
                continue;
            }
            $this->assertEquals(html_entity_decode($order->getFieldData($col), ENT_QUOTES), $orderData[$item]);
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
    public function getKlarnaDataByName($varName)
    {
        if (!$varValue = getenv($varName)) {
            $varValue = $this->getArrayValueFromFile($varName, __DIR__ .'/klarnaData.php');
        }

        if (!$varValue) {
            throw new \Exception('Undefined variable: ' . $varName);
        }

        return $varValue;
    }

    public function getArrayValueFromFile($sVarName, $sFilePath)
    {
        $aData = null;
        if (file_exists($sFilePath)) {
            $aData = include $sFilePath;
        }

        return $aData[$sVarName];
    }

    /**
     * @param $type
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @throws \Exception
     */
    public function prepareKlarnaDatabase($type)
    {
        $klarnaKey = $this->getKlarnaDataByName('sKlarnaEncodeKey');

        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaActiveMode'";
        DatabaseProvider::getDb()->execute($sql);

        $encode = "ENCODE('{$type}', '{$klarnaKey}')";

        $sql = "INSERT INTO `oxconfig` VALUES ('4060f0f9f705d470282a2ce5ed936e48', 1, 'tcklarna', 'sKlarnaActiveMode', 'str', {$encode}, 'now()')";
        DatabaseProvider::getDb()->execute($sql);

        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaMerchantId'";
        DatabaseProvider::getDb()->execute($sql);

        $klarnaMerchantId = $this->getKlarnaDataByName('sKlarna'.$type.'MerchantId');
        $encode = "ENCODE('{$klarnaMerchantId}', '{$klarnaKey}')";

        $sql = "INSERT INTO `oxconfig` VALUES ('f3b48ef3f7c17c916ef6018768377988', 1, 'tcklarna', 'sKlarnaMerchantId', 'str', {$encode}, 'now()')";
        DatabaseProvider::getDb()->execute($sql);

        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaPassword'";
        DatabaseProvider::getDb()->execute($sql);

        $klarnaPassword = $this->getKlarnaDataByName('sKlarna'.$type.'Password');
        $encode = "ENCODE('{$klarnaPassword}', '{$klarnaKey}')";

        $sql = "INSERT INTO `oxconfig` VALUES ('efbd96702f6cead0967cd37ad2cdf49d', 1, 'tcklarna', 'sKlarnaPassword', 'str', {$encode}, 'now()')";
        DatabaseProvider::getDb()->execute($sql);

        if($type == 'KCO') {
            $sql = "UPDATE oxuser SET oxbirthdate='1980-01-01', oxfon='02079460125' WHERE oxusername = 'user_gb@oxid-esales.com'";
            DatabaseProvider::getDb()->execute($sql);
        }

    }

    public function delayLoad($sec = 5)
    {
        sleep($sec);
    }

    public function switchCurrency($currency)
    {
        $this->click("css=.currencies-menu");
        $this->clickAndWait("//ul//*[text()='$currency']");
    }

    /**
     * Adds tests sql data to database.
     *
     * @param string $sTestSuitePath
     */
    public function addTestData($sTestSuitePath)
    {
        parent::addTestData($sTestSuitePath);

        $sTestSuitePath = realpath(__DIR__ .'/testSql/');
        $sFileName = $sTestSuitePath . '/demodata.sql';
        if (file_exists($sFileName)) {
            $this->importSql($sFileName);
        }

        $this->activateTheme('flow');
    }

    /**
     * @throws \Exception
     */
    public function fillKcoForm()
    {
        $this->waitForFrameToLoad("klarna-checkout-iframe", 2000);
        $this->selectFrame('klarna-checkout-iframe');
        $this->type("//div[@id='customer-details-next']//input[@id='email']",$this->getKlarnaDataByName('sKlarnaKCOEmail'));
        $this->type("//div[@id='customer-details-next']//input[@id='postal_code']",'21079');
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
    }
}