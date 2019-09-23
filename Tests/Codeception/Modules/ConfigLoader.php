<?php

namespace TopConcepts\Klarna\Tests\Codeception\Modules;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Exception;
use OxidEsales\Eshop\Core\ConfigFile;
use OxidEsales\Facts\Facts;
use OxidEsales\TestingLibrary\Services\Library\DatabaseHandler;

class ConfigLoader extends Module
{
    /**
     * ConfigLoader constructor.
     * @param ModuleContainer $moduleContainer
     * @param null $config
     * @throws Exception
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        parent::__construct($moduleContainer, $config);
        $facts = new Facts();
        $configFile = new ConfigFile($facts->getSourcePath() . '/config.inc.php');
        $this->dbHandler = new DatabaseHandler($configFile);
    }

    /** @var DatabaseHandler */
    protected $dbHandler;

    public function getDBHandler()
    {
        return $this->dbHandler;
    }

    /**
     * Returns klarna data by variable name
     *
     * @param $varName
     *
     * @return mixed|null|string
     * @throws Exception
     */
    public function getKlarnaDataByName($varName)
    {
        if (!$varValue = getenv($varName)) {
            $varValue = $this->_getArrayValueFromFile($varName, __DIR__ . '/../config/klarnaData.php');
        }

        if (!$varValue) {
            throw new Exception('Undefined variable: ' . $varName);
        }

        return $varValue;
    }

    protected function _getArrayValueFromFile($sVarName, $sFilePath)
    {
        $aData = null;

        if (file_exists($sFilePath)) {
            $aData = include $sFilePath;
        }

        return $aData[$sVarName];
    }

    /**
     * @param $type
     * @param null $setB2BOption
     * @throws Exception
     */
    public function loadKlarnaAdminConfig($type, $setB2BOption = null) {

        $klarnaKey = $this->getKlarnaDataByName('sKlarnaEncodeKey');
        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaActiveMode'";
        $this->dbHandler->exec($sql);

        $typeEncode = $type;
        if($type == 'KPSPLIT') {
            $typeEncode = 'KP';
        }

        $encode = "ENCODE('{$typeEncode}', '{$klarnaKey}')";

        $sql = "INSERT INTO `oxconfig` VALUES ('4060f0f9f705d470282a2ce5ed936e48', 1, 'module:tcklarna', 'sKlarnaActiveMode', 'str', {$encode}, now())";
        $this->dbHandler->exec($sql);

        $this->setKlarnaMerchantAndPassword($type, $klarnaKey);

        if($type == 'KCO') {
            $sql = "UPDATE oxuser SET oxbirthdate='1980-01-01', oxfon='02079460125' WHERE oxusername = 'user_gb@oxid-esales.com'";
            $this->dbHandler->exec($sql);
            $sql = "UPDATE oxconfig SET OXVARVALUE = ENCODE('3', '{$klarnaKey}') WHERE OXVARNAME = 'iKlarnaActiveCheckbox'";
            $this->dbHandler->exec($sql);

        }

        if($setB2BOption){
            $encode = "ENCODE('$setB2BOption', '$klarnaKey')";
            $sql = "DELETE FROM `oxconfig` WHERE `oxvarname`='sKlarnaB2Option'";
            $this->dbHandler->exec($sql);
            $sql = "INSERT INTO `oxconfig` VALUES ('f7309beb088c3437462abb18c893c755', 1, 'module:tcklarna', 'sKlarnaB2Option', 'str', {$encode}, now())";
            $this->dbHandler->exec($sql);
        }
    }

    /**
     * @param $type
     * @param string|null $klarnaKey
     * @throws Exception
     */
    public function setKlarnaMerchantAndPassword($type, string $klarnaKey)
    {
        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaMerchantId'";
        $this->dbHandler->exec($sql);

        $klarnaMerchantId = $this->getKlarnaDataByName('sKlarna'.$type.'MerchantId');
        $encode = "ENCODE('{$klarnaMerchantId}', '{$klarnaKey}')";

        $sql = "INSERT INTO `oxconfig` VALUES ('f3b48ef3f7c17c916ef6018768377988', 1, 'module:tcklarna', 'sKlarnaMerchantId', 'str', {$encode}, now())";
        $this->dbHandler->exec($sql);

        $sql = "DELETE FROM `oxconfig` WHERE `OXVARNAME`='sKlarnaPassword'";
        $this->dbHandler->exec($sql);

        $klarnaPassword = $this->getKlarnaDataByName('sKlarna'.$type.'Password');
        $encode = "ENCODE('{$klarnaPassword}', '{$klarnaKey}')";

        $sql = "INSERT INTO `oxconfig` VALUES ('efbd96702f6cead0967cd37ad2cdf49d', 1, 'module:tcklarna', 'sKlarnaPassword', 'str', {$encode}, now())";
        $this->dbHandler->exec($sql);
    }

    /**
     * Updates oxpayment record
     * @param $id
     * @param $values
     */
    public function setExternalPayment($id, $values) {
        if (empty($values)) {
            return; //nothing to update
        }
        $set = [];
        foreach($values as $columnName => $value) {
            $set[] = " $columnName = '$value'";
        }
        $sql = "UPDATE oxpayments SET ".join(',', $set)." WHERE `OXID`='$id'";
        $this->dbHandler->exec($sql);
    }
}