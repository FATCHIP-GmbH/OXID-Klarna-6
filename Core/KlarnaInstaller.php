<?php
/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration;
use OxidEsales\Eshop\Application\Model\Actions;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use TopConcepts\Klarna\Model\KlarnaPayment;

class KlarnaInstaller extends ShopConfiguration
{
    const KLARNA_MODULE_ID = 'tcklarna';

    static private $instance = null;

    /**
     * @var database object
     */
    protected $db;

    /**
     * Database name
     * @var string $dbName
     */
    protected $dbName;

    protected $moduleRelativePath = 'modules//tc/tcklarna';
    protected $modulePath;

    /**
     * @return KlarnaInstaller|null|object
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new KlarnaInstaller();
            /** @var Database db */
            self::$instance->db         = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
            self::$instance->dbName     = Registry::getConfig()->getConfigParam('dbName');
            self::$instance->modulePath = Registry::getConfig()->getConfigParam('sShopDir') . self::$instance->moduleRelativePath;
        }

        return self::$instance;
    }

    /**
     * Activation sequence
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @throws \Exception
     */
    public static function onActivate()
    {
        $oMetaData = oxNew(DbMetaDataHandler::class);
        $oMetaData->updateViews();

        $instance = self::getInstance();

        $instance->checkAndUpdate();

        $instance->extendDbTables();

        $instance->addConfigVars();

        $instance->addActions();

        $instance->addKlarnaPaymentsMethods();
    }

    protected function checkAndUpdate() {
        // oxconfig.OXMODULE prefix
        $requireUpdate = $this->db->select(
            "SELECT `OXID` FROM `oxconfig` WHERE OXMODULE = ?;",
            array('tcklarna')
        );
        if ($requireUpdate->count()) {
            foreach($requireUpdate->fetchAll() as $row) {
                $this->db->execute("UPDATE `oxconfig` SET OXMODULE = ? WHERE OXID = ?", array('module:tcklarna', $row['OXID']));
            }
        }
    }

    /**
     * Add klarna config vars and set defaults
     */
    protected function addConfigVars()
    {
        $config = Registry::getConfig();
        $shopId = $config->getShopId();

//        $currencies    = Registry::getConfig()->getCurrencyArray();
//        $currenciesVar = '';
//        foreach ($currencies as $currency) {
//            $currenciesVar .= $currency->name . '=>' . $currency->id;
//            if ($currency !== end($currencies)) {
//                $currenciesVar .= "\n";
//            }
//        }

        $defaultConfVars = array(
            'bool'   => array(

                'blIsKlarnaTestMode'                   => 1,
                'blKlarnaLoggingEnabled'               => 0,
                'blKlarnaAllowSeparateDeliveryAddress' => 1,
                'blKlarnaEnableAnonymization'          => 0,
                'blKlarnaSendProductUrls'              => 1,
                'blKlarnaSendImageUrls'                => 1,
                'blKlarnaMandatoryPhone'               => 1,
                'blKlarnaMandatoryBirthDate'           => 1,
                //                'tcklarna_blKlarnaSalutationMandatory'          => 1,
                //                'tcklarna_blKlarnaShowSubtotalDetail'           => 0,
                'blKlarnaEmdCustomerAccountInfo'       => 0,
                'blKlarnaEmdPaymentHistoryFull'        => 0,
                'blKlarnaEmdPassThrough'               => 0,
                'blKlarnaEnableAutofocus'              => 1,
                //                'tcklarna_blKlarnaEnableDHLPackstations'        => 1,
                'blKlarnaEnablePreFilling'             => 1,
                'blKlarnaDisplayBanner'                => 1,
                'blKlarnaPreFillNotification'          => 1,
            ),
            'str'    => array(
                'sKlarnaActiveMode'                => KlarnaConsts::MODULE_MODE_KCO,
                'sKlarnaMerchantId'                => '',
                'sKlarnaPassword'                  => '',
                'sKlarnaDefaultCountry'            => 'DE',
                'iKlarnaActiveCheckbox'            => KlarnaConsts::EXTRA_CHECKBOX_NONE,
                'iKlarnaValidation'                => KlarnaConsts::NO_VALIDATION,
                'sKlarnaAnonymizedProductTitle'    => 'anonymized product',
//                'tcklarna_sKlarnaDefaultEURCountry'         => 'DE',
                'sKlarnaFooterDisplay'             => 0,


                // Multilang Data
                'sKlarnaAnonymizedProductTitle_EN' => 'Product name',
                'sKlarnaAnonymizedProductTitle_DE' => 'Produktname',
                'sKlarnaB2Option' => 'B2C',
            ),
            'arr'    => array(),
//            'aarr'   => array(
//                'tcklarna_aKlarnaCurrencies' => $currenciesVar,
//            ),
            'select' => array(),
        );

        $savedConf     = $this->loadConfVars($shopId, 'module:'. self::KLARNA_MODULE_ID);
        $savedConfVars = $savedConf['vars'];

        foreach ($defaultConfVars as $type => $values) {
            foreach ($values as $name => $data) {
                if (key_exists($name, $savedConfVars[$type])) {
                    continue;
                }
                if ($type === 'aarr') {
                    $data = html_entity_decode($data);
                }

                $config->saveShopConfVar(
                    $type,
                    $name,
                    $this->_serializeConfVar($type, $name, $data),
                    $shopId,
                    "module:" . self::KLARNA_MODULE_ID
                );
            }
        }
    }

    /**
     * Add Klarna payment options
     * @throws \Exception
     */
    protected function addKlarnaPaymentsMethods()
    {
        $oPayment = oxNew(Payment::class);

        $oPayment->load('oxidinvoice');
        $de_prefix = $oPayment->getFieldData('oxdesc') === 'Rechnung' ? 0 : 1;
        $en_prefix = $de_prefix === 1 ? 0 : 1;

        $newPayments = array(KlarnaPayment::KLARNA_PAYMENT_CHECKOUT_ID  =>
                                 array($de_prefix => 'Klarna Checkout', $en_prefix => 'Klarna Checkout'),
                             KlarnaPayment::KLARNA_PAYMENT_PAY_LATER_ID =>
                                 array($de_prefix => 'Klarna Rechnung', $en_prefix => 'Klarna Pay Later'),
                             KlarnaPayment::KLARNA_PAYMENT_SLICE_IT_ID  =>
                                 array($de_prefix => 'Klarna Ratenkauf', $en_prefix => 'Klarna Slice It'),
                             KlarnaPayment::KLARNA_PAYMENT_PAY_NOW =>
                                 array($de_prefix => 'Sofort bezahlen', $en_prefix => 'Klarna Pay Now'),
                             KlarnaPayment::KLARNA_DIRECTDEBIT =>
                                 array($de_prefix => 'Klarna Pay Now Direct Debit', $en_prefix => 'Klarna Pay Now Direct Debit'),
                             KlarnaPayment::KLARNA_SOFORT =>
                                 array($de_prefix => 'Klarna Sofortüberweisung', $en_prefix => 'Klarna Pay Now Instant'),
        );

        $sort   = -350;
        $aLangs = Registry::getLang()->getLanguageArray();

        if ($aLangs) {
            foreach ($newPayments as $oxid => $aTitle) {
                /** @var Payment $oPayment */
                $oPayment = oxNew(Payment::class);
                $oPayment->load($oxid);
                if ($oPayment->isLoaded()) {
                    $oPayment->oxpayments__oxactive = new Field(1, Field::T_RAW);
                    $oPayment->save();
                    continue;
                }
                $oPayment->setEnableMultilang(false);
                $oPayment->setId($oxid);
                $oPayment->oxpayments__oxactive      = new Field(1, Field::T_RAW);
                $oPayment->oxpayments__oxaddsum      = new Field(0, Field::T_RAW);
                $oPayment->oxpayments__oxaddsumtype  = new Field('abs', Field::T_RAW);
                $oPayment->oxpayments__oxaddsumrules = new Field('31', Field::T_RAW);
                $oPayment->oxpayments__oxfromboni    = new Field('0', Field::T_RAW);
                $oPayment->oxpayments__oxfromamount  = new Field('0', Field::T_RAW);
                $oPayment->oxpayments__oxtoamount    = new Field('1000000', Field::T_RAW);
                $oPayment->oxpayments__oxchecked     = new Field(0, Field::T_RAW);
                $oPayment->oxpayments__oxsort        = new Field(strval($sort), Field::T_RAW);
                $oPayment->oxpayments__oxtspaymentid = new Field('', Field::T_RAW);

                // set multi language fields
                foreach ($aLangs as $oLang) {
                    $sTag                                     = Registry::getLang()->getLanguageTag($oLang->id);
                    $oPayment->{'oxpayments__oxdesc' . $sTag} = new Field($aTitle[$oLang->id], Field::T_RAW);
                }

                $oPayment->save();
                $sort += 1;
            }
        }

        $updateOxPayments =
            array(
                "UPDATE `oxpayments` SET `TCKLARNA_PAYMENTOPTION`='card' WHERE `oxid`='oxidcreditcard';",
                "UPDATE `oxpayments` SET `TCKLARNA_PAYMENTOPTION`='direct banking' WHERE `oxid`='oxiddebitnote';",
            );
        foreach ($updateOxPayments as $sQuery) {
            $this->db->execute($sQuery);
        }
    }

    /**
     * Extend klarna tables
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function extendDbTables()
    {

        $sql = "
            CREATE TABLE IF NOT EXISTS `tcklarna_logs` (
                  `OXID`          CHAR(32)
                                  CHARACTER SET latin1 COLLATE latin1_general_ci
                               NOT NULL DEFAULT '',
                
                  `OXSHOPID`      CHAR(32)
                                  CHARACTER SET latin1 COLLATE latin1_general_ci
                               NOT NULL DEFAULT '',
                  `TCKLARNA_METHOD`      VARCHAR(128)
                                  CHARACTER SET utf8
                               NOT NULL DEFAULT '',
                  `TCKLARNA_REQUESTRAW`  TEXT CHARACTER SET utf8
                               NOT NULL,
                  `TCKLARNA_RESPONSERAW` TEXT CHARACTER SET utf8
                               NOT NULL,
                  `TCKLARNA_DATE`        DATETIME
                               NOT NULL DEFAULT '0000-00-00 00:00:00',
                  PRIMARY KEY (`OXID`),
                  KEY `TCKLARNA_DATE` (`TCKLARNA_DATE`)
                )
                  ENGINE = InnoDB
                  DEFAULT CHARSET = utf8;
                  

            CREATE TABLE IF NOT EXISTS `tcklarna_ack` (
                  `OXID`       VARCHAR(32)
                               CHARACTER SET latin1 COLLATE latin1_general_ci
                                        NOT NULL,
                  `KLRECEIVED` DATETIME NOT NULL,
                  PRIMARY KEY (`OXID`)
                )
                  ENGINE = InnoDB
                  COMMENT ='List of all Klarna acknowledge requests'
                  DEFAULT CHARSET = utf8;
                  
                
            CREATE TABLE IF NOT EXISTS `tcklarna_anon_lookup` (
                  `TCKLARNA_ARTNUM` VARCHAR(32) NOT NULL,
                  `OXARTID`  VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
                  PRIMARY KEY (`TCKLARNA_ARTNUM`)
                )
                  ENGINE = InnoDB
                  COMMENT ='Mapping of annonymous article numbers to their oxids'
                  DEFAULT CHARSET = utf8;";

        $this->db->execute($sql);

        $this->addAlterTables();

        $this->updateViews();
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function addAlterTables()
    {
        $aStructure = array(
            'oxorder'         => array(
                'TCKLARNA_MERCHANTID' => 'ADD COLUMN `TCKLARNA_MERCHANTID` VARCHAR(128)  DEFAULT \'\' NOT NULL',
                'TCKLARNA_SERVERMODE' => 'ADD COLUMN `TCKLARNA_SERVERMODE` VARCHAR(16) NOT NULL DEFAULT \'\'',
                'TCKLARNA_ORDERID'    => 'ADD COLUMN `TCKLARNA_ORDERID` VARCHAR(128)  DEFAULT \'\' NOT NULL',
                'TCKLARNA_SYNC'       => 'ADD COLUMN `TCKLARNA_SYNC` TINYINT UNSIGNED NOT NULL DEFAULT \'1\'',
            ),
            'oxorderarticles' => array(
                'TCKLARNA_TITLE'  => 'ADD COLUMN  `TCKLARNA_TITLE` VARCHAR(255) NOT NULL DEFAULT \'\'',
                'TCKLARNA_ARTNUM' => 'ADD COLUMN  `TCKLARNA_ARTNUM` VARCHAR(255) NOT NULL DEFAULT \'\'',
            ),
            'oxpayments'      => array(
                'TCKLARNA_PAYMENTTYPES'           => 'ADD COLUMN `TCKLARNA_PAYMENTTYPES` SET(\'payment\',\'checkout\') NULL DEFAULT \'\'',
                'TCKLARNA_EXTERNALNAME'           => 'ADD COLUMN `TCKLARNA_EXTERNALNAME` VARCHAR(255) NULL DEFAULT \'\'',
                'TCKLARNA_EXTERNALPAYMENT'        => 'ADD COLUMN `TCKLARNA_EXTERNALPAYMENT` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',
                'TCKLARNA_EXTERNALCHECKOUT'       => 'ADD COLUMN `TCKLARNA_EXTERNALCHECKOUT` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',
                'TCKLARNA_PAYMENTIMAGEURL'        => 'ADD COLUMN `TCKLARNA_PAYMENTIMAGEURL` VARCHAR(255) NULL DEFAULT \'\'',
                'TCKLARNA_PAYMENTIMAGEURL_1'      => 'ADD COLUMN `TCKLARNA_PAYMENTIMAGEURL_1` VARCHAR(255) NULL DEFAULT \'\'',
                'TCKLARNA_PAYMENTIMAGEURL_2'      => 'ADD COLUMN `TCKLARNA_PAYMENTIMAGEURL_2` VARCHAR(255) NULL DEFAULT \'\'',
                'TCKLARNA_PAYMENTIMAGEURL_3'      => 'ADD COLUMN `TCKLARNA_PAYMENTIMAGEURL_3` VARCHAR(255) NULL DEFAULT \'\'',
                'TCKLARNA_CHECKOUTIMAGEURL'       => 'ADD COLUMN `TCKLARNA_CHECKOUTIMAGEURL` VARCHAR(255) NULL DEFAULT \'\'',
                'TCKLARNA_CHECKOUTIMAGEURL_1'     => 'ADD COLUMN `TCKLARNA_CHECKOUTIMAGEURL_1` VARCHAR(255) NULL DEFAULT \'\'',
                'TCKLARNA_CHECKOUTIMAGEURL_2'     => 'ADD COLUMN `TCKLARNA_CHECKOUTIMAGEURL_2` VARCHAR(255) NULL DEFAULT \'\'',
                'TCKLARNA_CHECKOUTIMAGEURL_3'     => 'ADD COLUMN `TCKLARNA_CHECKOUTIMAGEURL_3` VARCHAR(255) NULL DEFAULT \'\'',
                'TCKLARNA_PAYMENTOPTION'          => 'ADD COLUMN `TCKLARNA_PAYMENTOPTION` SET(\'card\',\'direct banking\',\'other\') NOT NULL DEFAULT \'other\'',
                'TCKLARNA_EMDPURCHASEHISTORYFULL' => 'ADD COLUMN `TCKLARNA_EMDPURCHASEHISTORYFULL` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',
            ),
            'oxaddress'       => array(
                'TCKLARNA_TEMPORARY' => 'ADD COLUMN `TCKLARNA_TEMPORARY` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',
            ),
            'tcklarna_logs'   => array(
                'TCKLARNA_ORDERID'    => 'ADD COLUMN `TCKLARNA_ORDERID` VARCHAR(128) CHARACTER SET utf8 DEFAULT \'\' NOT NULL AFTER `OXID`',
                'TCKLARNA_MID'        => 'ADD COLUMN `TCKLARNA_MID` VARCHAR(50) CHARACTER SET utf8 NOT NULL AFTER `OXSHOPID`',
                'TCKLARNA_STATUSCODE' => 'ADD COLUMN `TCKLARNA_STATUSCODE` VARCHAR(16) CHARACTER SET utf8 NOT NULL AFTER `TCKLARNA_MID`',
                'TCKLARNA_URL'        => 'ADD COLUMN `TCKLARNA_URL` VARCHAR(256) CHARACTER SET utf8 AFTER `TCKLARNA_METHOD`',
            ),
            'tcklarna_ack'    => array(
                'TCKLARNA_ORDERID' => 'ADD COLUMN `TCKLARNA_ORDERID` VARCHAR(128) CHARACTER SET utf8 DEFAULT \'\' NOT NULL AFTER `OXID`, ADD KEY `TCKLARNA_ORDERID` (`TCKLARNA_ORDERID`)',
            ),
        );

        // ADD COLUMNS
        foreach ($aStructure as $sTableName => $aColumns) {

            $query = "ALTER TABLE `$sTableName` ";
            $first = true;

            foreach ($aColumns as $sColumnName => $queryPart) {
                if (!$this->dbColumnExist($sTableName, $sColumnName)) {
                    if (!$first) {
                        $query .= ', ';
                    }
                    $query .= $queryPart;
                    $first = false;
                }
            }

            $this->db->execute($query);
        }
    }


    /**
     * Checks if specific column exists in the table
     * @param $sTableName
     * @param $sColumnName
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @return bool
     */
    protected function dbColumnExist($sTableName, $sColumnName)
    {
        $query = "SELECT * FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = '" . $this->dbName . "' 
                  AND TABLE_NAME = '$sTableName'
                  AND COLUMN_NAME = '$sColumnName'
                  ";

        return count($this->db->select($query)->fetchAll()) > 0;
    }


    /**
     * Performs full view update
     */
    protected function updateViews()
    {
        //preventing edit for anyone except malladmin
        //if (Registry::getSession()->getVariable("malladmin")) {
        $oMetaData = oxNew(DbMetaDataHandler::class);
        $oMetaData->updateViews();
        //}
    }

    /**
     * Adds Teaser Action
     */
    protected function addActions()
    {
        $shopId = $this->getConfig()->getShopId();
        // Klarna Teaser
        $oxId             = 'klarna_teaser_' . $shopId;
        $sTitle           = 'Klarna Teaser';
        $sLink            = '';
        $sFileName        = 'klarna-banner.png';
        $actionsMediaPath = Registry::getConfig()->getConfigParam('sShopDir') . '/out/pictures/promo/';

        $oActionKlarnaTeaser = oxNew(Actions::class);
        $oActionKlarnaTeaser->setShopId($shopId);
        $oActionKlarnaTeaser->load($oxId);
        $oActionKlarnaTeaser->setId($oxId);
        $active                                   = $oActionKlarnaTeaser->oxactions__oxactive->value ?: 0;                                                // assign old value
        $oActionKlarnaTeaser->oxactions__oxtype   = new Field(3, Field::T_RAW);
        $oActionKlarnaTeaser->oxactions__oxactive = new Field($active, Field::T_RAW);

        // set multi language fields
        $oActionKlarnaTeaser->setEnableMultilang(false);
        $aLangs = Registry::getLang()->getLanguageArray();
        foreach ($aLangs as $oLang) {
            $langFileName                                        = $oLang->oxid . '_' . $sFileName;
            $sTag                                                = Registry::getLang()->getLanguageTag($oLang->id);
            $oActionKlarnaTeaser->{'oxactions__oxtitle' . $sTag} = new Field($sTitle, Field::T_RAW);
            $oActionKlarnaTeaser->{'oxactions__oxlink' . $sTag}  = new Field($sLink, Field::T_RAW);
            $oActionKlarnaTeaser->{'oxactions__oxpic' . $sTag}   = new Field($langFileName, Field::T_RAW);

            $filePath = self::$instance->modulePath . '/out/src/img/' . $langFileName;
            if (file_exists($filePath)) {
                copy($filePath, $actionsMediaPath . $langFileName);
            }
        }
        $oActionKlarnaTeaser->save();
    }
}