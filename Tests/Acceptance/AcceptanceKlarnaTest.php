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
     */
    public function assertKlarnaData($oxid, $expectedStatus = "AUTHORIZED")
    {
        $sql = "SELECT TCKLARNA_ORDERID from `oxorder` WHERE `OXID`='$oxid'";
        $klarnaId = DatabaseProvider::getDb()->getOne($sql);

        /** @var KlarnaOrderManagementClient|KlarnaClientBase $klarnaClient */
        $klarnaClient = KlarnaOrderManagementClient::getInstance();
        $orderData = $klarnaClient->getOrder($klarnaId);

        $order = oxNew(Order::class);
        $order->load($oxid);

        $aFieldMapper = [
            'oxbillemail' => 'email',
            'oxbillfname' => 'given_name',
            'oxbilllname' => 'family_name',
            'joinedAddress' => 'street_address',
            'oxbillzip' => 'postal_code',
            'oxbillcity' => 'city',
            'oxbillcountryid' => 'country',
        ];

        $this->assertEquals($order->getFieldData('oxbillemail'), $orderData['billing_address']['email']);

        foreach ($aFieldMapper as $col => $item) {
            if($col == 'oxbillcountryid')
            {
                $oCountry   = oxNew(Country::class);
                $sCountryId = $oCountry->getIdByCode($orderData['billing_address'][$item]);

                $this->assertEquals($sCountryId, $order->getFieldData('oxbillcountryid'));
                continue;
            }
            if ($col == 'joinedAddress') {
                $streetAddress = $order->getFieldData('oxbillstreet').' '.$order->getFieldData('oxbillstreetnr');
                $this->assertEquals($streetAddress, $orderData['billing_address'][$item]);
                continue;
            }
            $this->assertEquals(html_entity_decode($order->getFieldData($col), ENT_QUOTES), $orderData['billing_address'][$item]);
        }


        $this->assertEquals($expectedStatus, $orderData['status']);
        $this->assertEquals('ACCEPTED', $orderData['fraud_status']);

        $orderAmount = KlarnaUtils::parseFloatAsInt($order->getTotalOrderSum() * 100);
        $this->assertEquals($orderAmount, $orderData['captured_amount']);
        $this->assertEquals($orderAmount, $orderData['order_amount']);
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
    public function prepareKPDatabase($type)
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

    }

}