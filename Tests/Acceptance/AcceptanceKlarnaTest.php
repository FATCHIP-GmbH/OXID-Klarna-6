<?php

namespace TopConcepts\Klarna\Tests\Acceptance;

use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\TestingLibrary\AcceptanceTestCase;
use TopConcepts\Klarna\Core\Exception\KlarnaOrderNotFoundException;
use TopConcepts\Klarna\Core\KlarnaClientBase;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaUtils;

abstract class AcceptanceKlarnaTest extends AcceptanceTestCase
{
    const NEW_ORDER_GIVEN_NAME      = "ÅåÆæØø";
    const NEW_ORDER_FAMILY_NAME     = "St.Jäöüm'es";
    const NEW_ORDER_STREET_ADDRESS  = "Karnapp 25";
    const NEW_ORDER_CITY            = "Hamburg";
    const NEW_ORDER_PHONE           = "30306900";
    const NEW_ORDER_DATE_OF_BIRTH   = "01011980";
    const NEW_ORDER_DISCOUNT        = "10";
    const NEW_ORDER_TRACK_CODE      = "12345";
    const NEW_ORDER_VOUCHER_NR      = "percent_10";
    const NEW_ORDER_ZIP_CODE        = "21079";

    /**
     * @param $oxid
     * @param bool $validateInput
     * @param string $expectedStatus
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \oxSystemComponentException
     * @throws \Exception
     */
    public function assertKlarnaData($oxid = null, $validateInput = false, $expectedStatus = "AUTHORIZED")
    {
        $sql = "SELECT OXID, TCKLARNA_ORDERID from `oxorder` ORDER BY `oxorderdate` DESC LIMIT 1";

        if($oxid) {
            $sql = "SELECT TCKLARNA_ORDERID from `oxorder` WHERE `OXID`='$oxid'";
            $klarnaId = DatabaseProvider::getDb()->getOne($sql);
        } else{
            $result = DatabaseProvider::getDb()->getRow($sql);
            $oxid = $result[0];
            $klarnaId = $result[1];
        }


        /** @var KlarnaOrderManagementClient|KlarnaClientBase $klarnaClient */
        $klarnaClient = KlarnaOrderManagementClient::getInstance();
        try {
            $orderData = $klarnaClient->getOrder($klarnaId);
        } catch (KlarnaOrderNotFoundException $e){
            //try a second time if fails to find order on klarna system
            $this->delayLoad(2);
        }


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

        if(!empty($order->getFieldData('oxdelstreet')) && $order->getFieldData('oxbillstreet') != $order->getFieldData('oxdelstreet')) {
            $aFieldMapper = [
                'oxdelfname' => 'given_name',
                'oxdellname' => 'family_name',
                'joinedAddress' => 'street_address',
                'oxdelzip' => 'postal_code',
                'oxdelcity' => 'city',
                'oxdelcountryid' => 'country',
            ];

            $this->validateAddress($order, $orderData['shipping_address'], $aFieldMapper, 'del');
            if($validateInput) {
                $this->validateInputData($order,'del');
            }
        }

        $this->assertEquals($order->getFieldData('oxbillemail'), $orderData['billing_address']['email']);
        $this->assertEquals($expectedStatus, $orderData['status']);
        $this->assertEquals('ACCEPTED', $orderData['fraud_status']);

        $orderAmount = KlarnaUtils::parseFloatAsInt($order->getTotalOrderSum() * 100);
        if($expectedStatus == 'CAPTURED'){
            $this->assertEquals($orderAmount, $orderData['captured_amount']);
        }
        $this->assertEquals($orderAmount, $orderData['order_amount']);

        //validate input date with db data
        if($validateInput) {
            $this->validateInputData($order);
        }
    }

    /**
     * @param Order $order
     * @param string $type
     * @throws \Exception
     */
    protected function validateInputData(Order $order, $type = 'bill')
    {
        //validate input date with db data
        $this->assertEquals($this->getKlarnaDataByName('sKlarnaKCOEmail'), $order->getFieldData('oxbillemail'));
        $this->assertEquals($this->getKlarnaDataByName('sKCOFormPostCode'), $order->getFieldData('oxbillzip'));
        $this->assertEquals($this->getKlarnaDataByName('sKCOFormGivenName'), $order->getFieldData('ox'.$type.'fname'));
        $this->assertEquals($this->getKlarnaDataByName('sKCOFormFamilyName'), $order->getFieldData('ox'.$type.'lname'));
        $this->assertEquals($this->getKlarnaDataByName('sKCOFormStreetName'), $order->getFieldData('oxbillstreet'));
        $this->assertEquals($this->getKlarnaDataByName('sKCOFormStreetNumber'), $order->getFieldData('oxbillstreetnr'));
        $this->assertEquals($this->getKlarnaDataByName('sKCOFormCity'), $order->getFieldData('oxbillcity'));

        if($type == 'del')
        {
            $this->assertEquals($this->getKlarnaDataByName('sKCOFormDelPostCode'), $order->getFieldData('oxdelzip'));
            $this->assertEquals($this->getKlarnaDataByName('sKCOFormDelStreetName'), $order->getFieldData('oxdelstreet'));
            $this->assertEquals($this->getKlarnaDataByName('sKCOFormDelStreetNumber'), $order->getFieldData('oxdelstreetnr'));
            $this->assertEquals($this->getKlarnaDataByName('sKCOFormDelCity'), $order->getFieldData('oxdelcity'));
        }
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
                $streetAddress = trim($order->getFieldData('ox'.$type.'street').' '.$order->getFieldData('ox'.$type.'streetnr'));
                $this->assertEquals($streetAddress, trim($orderData[$item]));
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

        /** Add object to shop mapping for EE */
        if ($this->getTestConfig()->getShopEdition() === 'EE') {
            $db = DatabaseProvider::getDb();
            $shopId = 1;
            $mapIds = [
                'oxarticles' => [1118, 1119],
                'oxdelivery' => range(1, 5),
                'oxdeliveryset' => range(1, 3),
                'oxvoucherseries' => [1],
            ];

            foreach($mapIds as $tableName => $mapIds){
                $sql = "REPLACE INTO `{$tableName}2shop` SET `oxmapobjectid` = ?, `oxshopid` = ?";
                foreach($mapIds as $mapId){
                    $db->execute($sql, array($mapId, $shopId));
                }
            }
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
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='email']",$this->getKlarnaDataByName('sKlarnaKCOEmail'));
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='postal_code']",$this->getKlarnaDataByName('sKCOFormPostCode'));
        $this->click("//select[@id='title']");
        $this->click("//option[@id='title__option__herr']");
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='given_name']",$this->getKlarnaDataByName('sKCOFormGivenName'));
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='family_name']",$this->getKlarnaDataByName('sKCOFormFamilyName'));
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='street_address']",$this->getKlarnaDataByName('sKCOFormStreetName').' '.$this->getKlarnaDataByName('sKCOFormStreetNumber'));
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='city']",$this->getKlarnaDataByName('sKCOFormCity'));
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='phone']",$this->getKlarnaDataByName('sKCOFormPhone'));
        $this->type("//div[@id='klarna-checkout-customer-details']//input[@id='date_of_birth']",$this->getKlarnaDataByName('sKCOFormDob'));
        $this->delayLoad();
        if($this->isElementPresent("//div[@id='klarna-checkout-customer-details']//*[text()='Submit']")){
            $this->clickAndWait("//div[@id='klarna-checkout-customer-details']//*[text()='Submit']");
        }
    }
}