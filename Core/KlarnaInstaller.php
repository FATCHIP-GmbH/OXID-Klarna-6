<?php

namespace Klarna\Klarna\Core;


use OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration;
use OxidEsales\Eshop\Application\Model\Actions;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use Klarna\Klarna\Models\KlarnaPayment;

class KlarnaInstaller extends ShopConfiguration
{
    const KLARNA_MODULE_ID = 'klarna';

    static private $instance = null;

    /**
     * @var database object
     */
    protected $db;

    /** @var  database name */
    protected $dbName;

    protected $moduleRelativePath = 'modules/klarna/klarna';
    protected $modulePath;

    /**
     * @return KlarnaInstaller|null|object
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new KlarnaInstaller;
            /** @var Database db */
            self::$instance->db         = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
            self::$instance->dbName     = Registry::getConfig()->getConfigParam('dbName');
            self::$instance->modulePath = Registry::getConfig()->getConfigParam('sShopDir') . self::$instance->moduleRelativePath;
        }

        return self::$instance;
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public static function onActivate()
    {
        $instance = self::getInstance();

        $instance->extendDbTables();

        $instance->updateViews();

        $instance->addKlarnaPaymentsMethods();

        $instance->addConfigVars();

        $instance->addActions();
    }

    /**
     *
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public static function onDeactivate()
    {
        $instance = self::getInstance();
        $instance->deactivateKlarnaPayments();
    }

    /**
     * @param $sTableName
     * @param $sColumnName
     * @return bool
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function dbColumnExist($sTableName, $sColumnName)
    {
        $query = "SELECT * FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = '" . $this->dbName . "' 
                  AND TABLE_NAME = '$sTableName'
                  AND COLUMN_NAME = '$sColumnName'
                  ";

        return count($this->db->select($query)) > 0;

    }

    /**
     * Add klarna config vars and set defaults
     */
    protected function addConfigVars()
    {
        $config = Registry::getConfig();
        $shopId = $config->getShopId();

        $currencies    = Registry::getConfig()->getCurrencyArray();
        $currenciesVar = '';
        foreach ($currencies as $currency) {
            $currenciesVar .= $currency->name . '=>' . $currency->id;
            if ($currency !== end($currencies)) {
                $currenciesVar .= "\n";
            }
        }

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
                //                'blKlarnaSalutationMandatory'          => 1,
                'blKlarnaShowSubtotalDetail'           => 0,
                'blKlarnaEmdCustomerAccountInfo'       => 0,
                'blKlarnaEmdPaymentHistoryFull'        => 0,
                'blKlarnaEmdPassThrough'               => 0,
                'blKlarnaEnableAutofocus'              => 1,
                'blKlarnaEnableDHLPackstations'        => 1,
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
                'sKlarnaDefaultEURCountry'         => 'DE',
                'sKlarnaFooterDisplay'             => 0,

                // Multilang Data
                'sKlarnaAnonymizedProductTitle_EN' => 'Product name',
                'sKlarnaAnonymizedProductTitle_DE' => 'Produktname',
            ),
            'arr'    => array(),
            'aarr'   => array(
                'aKlarnaCurrencies' => $currenciesVar,
            ),
            'select' => array(),
        );

        $savedConf     = $this->loadConfVars($shopId, self::KLARNA_MODULE_ID);
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
                    self::KLARNA_MODULE_ID
                );
            }
        }
    }

    /**
     * Add Klarna Checkout to payments
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
                             KlarnaPayment::KLARNA_PAYMENT_PAY_NOW      =>
                                 array($de_prefix => 'Sofort bezahlen', $en_prefix => 'Klarna Pay Now'),
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
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function extendDbTables()
    {
        /*
        $sql      = file_get_contents(__DIR__ . '/../install/install.sql');
        $sqlArray = explode(';', trim($sql));
        foreach ($sqlArray as $sql) {
            if ($sql === '') {
                break;
            }
            $this->db->execute($sql);
        }

        $aStructure = array(
            'oxorder'         => array(
                'KLMERCHANTID' => 'ADD COLUMN `KLMERCHANTID` VARCHAR(128)  DEFAULT \'\' NOT NULL',
                'KLSERVERMODE' => 'ADD COLUMN `KLSERVERMODE` VARCHAR(16) NOT NULL DEFAULT \'\'',
                'KLORDERID'    => 'ADD COLUMN `KLORDERID` VARCHAR(128)  DEFAULT \'\' NOT NULL',
                'KLSYNC'       => 'ADD COLUMN `KLSYNC` TINYINT UNSIGNED NOT NULL DEFAULT \'1\'',
            ),
            'oxorderarticles' => array(
                'KLTITLE'  => 'ADD COLUMN  `KLTITLE` VARCHAR(255) NOT NULL DEFAULT \'\'',
                'KLARTNUM' => 'ADD COLUMN  `KLARTNUM` VARCHAR(255) NOT NULL DEFAULT \'\'',
            ),
            'oxpayments'      => array(
                'KLPAYMENTTYPES'           => 'ADD COLUMN `KLPAYMENTTYPES` SET(\'payment\',\'checkout\') NULL DEFAULT \'\'',
                'KLEXTERNALNAME'           => 'ADD COLUMN `KLEXTERNALNAME` VARCHAR(255) NULL DEFAULT \'\'',
                'KLEXTERNALPAYMENT'        => 'ADD COLUMN `KLEXTERNALPAYMENT` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',
                'KLEXTERNALCHECKOUT'       => 'ADD COLUMN `KLEXTERNALCHECKOUT` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',
                'KLPAYMENTIMAGEURL'        => 'ADD COLUMN `KLPAYMENTIMAGEURL` VARCHAR(255) NULL DEFAULT \'\'',
                'KLPAYMENTIMAGEURL_1'      => 'ADD COLUMN `KLPAYMENTIMAGEURL_1` VARCHAR(255) NULL DEFAULT \'\'',
                'KLCHECKOUTIMAGEURL'       => 'ADD COLUMN `KLCHECKOUTIMAGEURL` VARCHAR(255) NULL DEFAULT \'\'',
                'KLCHECKOUTIMAGEURL_1'     => 'ADD COLUMN `KLCHECKOUTIMAGEURL_1` VARCHAR(255) NULL DEFAULT \'\'',
                'KLPAYMENTOPTION'          => 'ADD COLUMN `KLPAYMENTOPTION` SET(\'card\',\'direct banking\',\'other\') NOT NULL DEFAULT \'other\'',
                'KLEMDPURCHASEHISTORYFULL' => 'ADD COLUMN `KLEMDPURCHASEHISTORYFULL` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',

            ),
            'kl_logs'         => array(
                'KLORDERID'    => 'ADD COLUMN `KLORDERID` VARCHAR(128) CHARACTER SET utf8 DEFAULT \'\' NOT NULL AFTER `OXID`',
                'KLMID'        => 'ADD COLUMN `KLMID` VARCHAR(50) CHARACTER SET utf8 NOT NULL AFTER `OXSHOPID`',
                'KLSTATUSCODE' => 'ADD COLUMN `KLSTATUSCODE` VARCHAR(16) CHARACTER SET utf8 NOT NULL AFTER `KLMID`',
                'KLURL'        => 'ADD COLUMN `KLURL` VARCHAR(256) CHARACTER SET utf8 AFTER `KLMETHOD`',
            ),
            'kl_ack'          => array(
                'KLORDERID' => 'ADD COLUMN `KLORDERID` VARCHAR(128) CHARACTER SET utf8 DEFAULT \'\' NOT NULL AFTER `OXID`, ADD KEY `KLORDERID` (`KLORDERID`)',
            ),
        );

        foreach ($aStructure as $sTableName => $aColumns) {
            $query = "ALTER IGNORE TABLE `$sTableName` ";
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
        */






        $this->updateViews();

        $updateOxPayments =
            array(
                "UPDATE `oxpayments` SET `KLPAYMENTOPTION`='card' WHERE `oxid`='oxidcreditcard';",
                "UPDATE `oxpayments` SET `KLPAYMENTOPTION`='direct banking' WHERE `oxid`='oxiddebitnote';",
            );
        foreach ($updateOxPayments as $sQuery) {
            $this->db->execute($sQuery);
        }


    }


    /**
     * Performs full view update
     */
    protected function updateViews()
    {
        //preventing edit for anyone except malladmin
        if (Registry::getSession()->getVariable("malladmin")) {
            $oMetaData = oxNew(DbMetaDataHandler::class);
            $oMetaData->updateViews();
        }
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
        $actionsMediaPath = Registry::getConfig()->getConfigParam('sShopDir') . 'out/pictures/promo/';

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

            $filePath = self::$instance->modulePath . '/out/img/' . $langFileName;
            if (file_exists($filePath)) {
                copy($filePath, $actionsMediaPath . $langFileName);
            }
        }
        $oActionKlarnaTeaser->save();
    }

    /**
     *
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function deactivateKlarnaPayments()
    {
        $sql = 'UPDATE oxpayments
                SET oxactive = 0
                WHERE oxid LIKE "klarna%"';
        $this->db->execute($sql);
    }
}
