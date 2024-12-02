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
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use Symfony\Component\Console\Output\BufferedOutput;

final class KlarnaInstaller
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
        $instance = self::getInstance();

        $instance->checkAndUpdate();
        $instance->addConfigVars();

        $instance->executeModuleMigrations();

        $oMetaData = oxNew(DbMetaDataHandler::class);
        $oMetaData->updateViews();
    }

    /**
     * Execute necessary module migrations on activate event
     */
    private static function executeModuleMigrations(): void
    {
        $migrations = (new MigrationsBuilder())->build();

        $output = new BufferedOutput();
        $migrations->setOutput($output);
        $neeedsUpdate = $migrations->execute('migrations:up-to-date', 'tcklarna');

        if ($neeedsUpdate) {
            $migrations->execute('migrations:migrate', 'tcklarna');
        }
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
                'blKlarnaPreFillNotification'          => 1,
            ),
            'str'    => array(
                'sKlarnaActiveMode'                => KlarnaConsts::MODULE_MODE_KP,
                'sKlarnaMerchantId'                => '',
                'sKlarnaPassword'                  => '',
                'sKlarnaDefaultCountry'            => 'DE',
                'iKlarnaActiveCheckbox'            => KlarnaConsts::EXTRA_CHECKBOX_NONE,
                'iKlarnaValidation'                => KlarnaConsts::NO_VALIDATION,
                'sKlarnaAnonymizedProductTitle'    => 'anonymized product',
                'sKlarnaFooterDisplay'             => 0,


                // Multilang Data
                'sKlarnaAnonymizedProductTitle_EN' => 'Product name',
                'sKlarnaAnonymizedProductTitle_DE' => 'Produktname',
                'sKlarnaB2Option' => 'B2C',
            ),
            'arr'    => array(),
            'aarr'   => array(
                'aarrKlarnaISButtonStyle' => 'variation => klarna
                    tagline => light
                    type => pay',
                'aarrKlarnaISButtonSettings' => 'allow_separate_shipping_address => 0
                    date_of_birth_mandatory => 0
                    national_identification_number_mandatory => 0
                    phone_mandatory => 0'
            ),
            'select' => array(),
        );

        $oShopConf     = oxNew(ShopConfiguration::class);
        $savedConf     = $oShopConf->loadConfVars($shopId, 'module:'. self::KLARNA_MODULE_ID);
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
                    $data,
                    $shopId,
                    "module:" . self::KLARNA_MODULE_ID
                );
            }
        }
    }

    public static function onDeactivate()
    {
        $tempDirectory = Registry::getConfig()->getConfigParam("sCompileDir");
        $mask = $tempDirectory . '/smarty/*';
        $files = glob($mask);
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
}