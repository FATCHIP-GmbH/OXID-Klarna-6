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
use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidEsales\Facts\Config\ConfigFile;
use OxidEsales\Facts\Facts;
use TopConcepts\Klarna\Model\KlarnaPayment;

class KlarnaInstaller extends ShopConfiguration
{
    const KLARNA_MODULE_ID = 'tcklarna';

    static private $instance = null;

    /**
     * @var database object
     */
    protected $db;

    /** @var  database name */
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
     */
    public static function onActivate()
    {
        $oMetaData = oxNew(DbMetaDataHandler::class);
        $oMetaData->updateViews();

        $instance = self::getInstance();

        $instance->performMigrations();

        $instance->addConfigVars();

        $instance->addActions();

        $instance->addKlarnaPaymentsMethods();
    }

    /**
     *
     */
    public static function onDeactivate()
    {
        $instance = self::getInstance();
        $instance->deactivateKlarnaPayments();
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
            ),
            'arr'    => array(),
//            'aarr'   => array(
//                'tcklarna_aKlarnaCurrencies' => $currenciesVar,
//            ),
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
     * Migration setup
     * @return \OxidEsales\DoctrineMigrationWrapper\Migrations
     * @throws \Exception
     */
    protected function getModuleMigrations()
    {
        $config = new ConfigFile();
        $config->setVar(ConfigFile::PARAMETER_SOURCE_PATH, $config->sShopDir . '/modules/tc/tcklarna');
        $migrationsBuilder = new MigrationsBuilder();

        return $migrationsBuilder->build(new Facts($config->getVar(ConfigFile::PARAMETER_SOURCE_PATH) . '/migration', $config));
    }

    /**
     * Runs awaiting module migrations
     */
    protected function performMigrations()
    {
        try {
            $migrations = $this->getModuleMigrations();
            $migrations->execute('migrations:migrate');
        } catch (\Exception $e) {
            Registry::getUtilsView()->addErrorToDisplay($e->getMessage() . $e->getCode());
        }

        $this->updateViews();
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

            $filePath = self::$instance->modulePath . 'out/src/img/' . $langFileName;
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