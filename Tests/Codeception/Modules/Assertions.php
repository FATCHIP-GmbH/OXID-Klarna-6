<?php


namespace TopConcepts\Klarna\Tests\Codeception\Modules;


use Codeception\Module;
use TopConcepts\Klarna\Core\KlarnaOrderManagementClient;
use TopConcepts\Klarna\Core\KlarnaUtils;

class Assertions extends Module
{
    /**
     * @return \OxidEsales\TestingLibrary\Services\Library\DatabaseHandler
     * @throws \Codeception\Exception\ModuleException
     */
    protected function _getDbHandler() {
        /** @var ConfigLoader $configLoader */
        $configLoader = $this->getModule('\TopConcepts\Klarna\Tests\Codeception\Modules\ConfigLoader');
        return $configLoader->getDBHandler();
    }

    /**
     * @param $key
     * @return mixed|string|null
     * @throws \Codeception\Exception\ModuleException
     */
    protected function _getInputParam($key)
    {
        /** @var ConfigLoader $configLoader */
        $configLoader = $this->getModule('\TopConcepts\Klarna\Tests\Codeception\Modules\ConfigLoader');
        return $configLoader->getKlarnaDataByName($key);
    }

    /**
     * @param $orderId string - klarna order id
     * @return mixed
     */
    public function grabFromKlarnaAPI($orderId) {
        /** @var KlarnaOrderManagementClient|TopConcepts\Klarna\Core\KlarnaClientBase $klarnaClient */
        $klarnaClient = KlarnaOrderManagementClient::getInstance();
        $orderData = $klarnaClient->getOrder($orderId);

        return $orderData;
    }

    public function seeOrderInDb($klarnaId) {
        $actualArray = $this->_getDBHandler()
            ->query("SELECT * FROM oxorder WHERE TCKLARNA_ORDERID = '$klarnaId'")
            ->fetch(\PDO::FETCH_ASSOC);
        $inputDataMapper = [
            'sKCOFormPostCode' => 'OXBILLZIP',
            'sKCOFormGivenName' => 'OXBILLFNAME',
            'sKCOFormFamilyName' => 'OXBILLLNAME',
            'sKCOFormStreetName' => 'OXBILLSTREET',
            'sKCOFormStreetNumber' => 'OXBILLSTREETNR',
            'sKCOFormCity' => 'OXBILLCITY',
            'sKCOFormDelPostCode' => 'OXDELZIP',
            'sKCOFormDelStreetName' => 'OXDELSTREET',
            'sKCOFormDelStreetNumber' => 'OXDELSTREETNR',
            'sKCOFormDelCity' => 'OXDELCITY',
        ];
        $this->assertInputStored($actualArray, $inputDataMapper);
    }

    /**
     * @param $orderId string - klarna order id
     * @throws \Codeception\Exception\ModuleException
     */
    public function seeInKlarnaAPI($klarnaId, $expectedStatus = "AUTHORIZED")
    {
        $klarnaOrderData = $this->grabFromKlarnaAPI($klarnaId);
        $oxidOrder = $this->_getDBHandler()
            ->query("SELECT * FROM oxorder WHERE TCKLARNA_ORDERID = '$klarnaId'")
            ->fetch(\PDO::FETCH_ASSOC);

        $oxidOrderData = $this->prepareOxidData($oxidOrder, $expectedStatus);

        $billingDataMapper = [
            'OXBILLEMAIL' => 'email',
            'OXBILLFNAME' => 'given_name',
            'OXBILLLNAME' => 'family_name',
            'OXBILLZIP' => 'postal_code',
            'OXBILLCITY' => 'city',
            'OXBILLSTREET' => 'street_address',
            'OXBILLCOUNTRYID' => 'country',
        ];
        $this->assertDataEquals(
            $oxidOrderData,
            $klarnaOrderData['billing_address'],
            $billingDataMapper
        );

        $shippingDataMapper = [
            'OXDELFNAME' => 'given_name',
            'OXDELLNAME' => 'family_name',
            'OXDELZIP' => 'postal_code',
            'OXDELCITY' => 'city',
            'OXDELCOUNTRYID' => 'country',
            'OXDELSTREET' => 'street_address',
        ];
        $this->assertDataEquals(
            $oxidOrderData,
            $klarnaOrderData['shipping_address'],
            $shippingDataMapper
        );

        $orderDataMapper = [
            'OXTOTALORDERSUM' => 'order_amount',
            'FRAUD_STATUS' => 'fraud_status',
            'STATUS' => 'status'
        ];
        $this->assertDataEquals(
            $oxidOrderData,
            $klarnaOrderData,
            $orderDataMapper
        );
    }

    protected function prepareOxidData($oxidRow, $expectedStatus) {
        foreach($oxidRow as $colName => $val) {
            // replace COUNTRYID with OXISOALPHA2
            if (strpos($colName, 'COUNTRYID') !== false) {
                $country = $this->_getDbHandler()
                    ->execSql("SELECT OXISOALPHA2 FROM oxcountry WHERE OXID = '$val'")
                    ->fetch();
                $oxidRow[$colName] = $country['OXISOALPHA2'];
            }
            // concat OXBILLSTREET, OXDELSTREET or OXSTREET with corresponding street number
            if ($colName == 'OXBILLSTREETNR' || $colName == 'OXDELSTREETNR' || $colName == 'OXSTREETNR') {
                $streetColName = substr($colName, 0 , -2);
                $oxidRow[$streetColName] .= ' ' . $val;
            }
            if ($colName == 'OXTOTALORDERSUM') {
                $oxidRow[$colName] = KlarnaUtils::parseFloatAsInt($val * 100);
            }
        }
        $oxidRow['STATUS'] = $expectedStatus;
        $oxidRow['FRAUD_STATUS'] = 'ACCEPTED';

        return $oxidRow;
    }

    /**
     * @param $expectedArray
     * @param $actualArray
     * @param $dataMapper
     */
    public function assertDataEquals($expectedArray, $actualArray, $dataMapper)
    {
        foreach ($dataMapper as $fieldName => $anotherFieldName) {
            print_r("Compering $fieldName = $expectedArray[$fieldName] to $anotherFieldName = $actualArray[$anotherFieldName]\n");
            $this->assertEquals($expectedArray[$fieldName], $actualArray[$anotherFieldName]);
        }
    }

    public function assertInputStored($actualArray, $dataMapper)
    {
        foreach ($dataMapper as $fieldName => $anotherFieldName) {
            $expectedArray[$fieldName] = $this->_getInputParam($fieldName);
            print_r("Compering $fieldName = $expectedArray[$fieldName] to $anotherFieldName = $actualArray[$anotherFieldName]\n");
            $this->assertEquals($expectedArray[$fieldName], $actualArray[$anotherFieldName]);
        }
    }
}