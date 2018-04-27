<?php

$serviceCaller = new \OxidEsales\TestingLibrary\ServiceCaller();
$testConfig = new \OxidEsales\TestingLibrary\TestConfig();

$testDirectory = $testConfig->getEditionTestsPath($testConfig->getShopEdition());
$klarnaTestDirectory = $testConfig->getShopPath() . 'modules//tc/tcklarna/Tests/';
$serviceCaller->setParameter('importSql', '@' . $testDirectory . '/Fixtures/testdata.sql');
$serviceCaller->setParameter('importSql', '@' . $klarnaTestDirectory . 'Unit/Testdata/klarna-settings.sql');
$serviceCaller->callService('ShopPreparation', 1);

define('oxADMIN_LOGIN', oxDb::getDb()->getOne("select OXUSERNAME from oxuser where oxid='oxdefaultadmin'"));
define('oxADMIN_PASSWD', getenv('oxADMIN_PASSWD') ? getenv('oxADMIN_PASSWD') : 'admin');


/** Load test helpers on top oxid core classes */
require_once __DIR__ . '/Unit/oxUtilsHelper.php';
oxAddClassModule(oxUtilsHelper::class, "oxutils");
