<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;


use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\KlarnaInstaller;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaInstallerTest extends ModuleUnitTestCase
{
    const KLARNA_PAYMENT_IDS = [
        'klarna_checkout',
        'klarna_pay_later',
        'klarna_pay_now',
        'klarna_slice_it',
    ];

    /**
     * Completely revert the module set-up done at the beginning of tests
     */
    protected function revertTestSuiteSetup()
    {
        $database = DatabaseProvider::getDB();

        $paymentIds = self::KLARNA_PAYMENT_IDS;
        unset($paymentIds[3]);

        foreach ($paymentIds as $id) {
            $database->execute("DELETE FROM `oxpayments` WHERE oxid = ?", [$id]);
            $database->execute("DELETE FROM `oxconfig` WHERE oxvarname = ?", ['blKlarnaAllowSeparateDeliveryAddress']);
        }

        $dbMetaDataHandler = oxNew(DbMetaDataHandler::class);

        $database->execute("DROP TABLE IF EXISTS `kl_ack`");
        $database->execute("DROP TABLE IF EXISTS `kl_logs`");
        $database->execute("DROP TABLE IF EXISTS `kl_anon_lookup`");

        $dbMetaDataHandler->executeSql([
            "ALTER TABLE oxorder DROP `KLMERCHANTID`",
            "ALTER TABLE oxorder DROP `KLSERVERMODE`",
            "ALTER TABLE oxorder DROP `KLORDERID`",
            "ALTER TABLE oxorder DROP `KLSYNC`",

            "ALTER TABLE oxorderarticles DROP `KLTITLE`",
            "ALTER TABLE oxorderarticles DROP `KLARTNUM`",

            "ALTER TABLE oxpayments DROP `KLPAYMENTTYPES`",
            "ALTER TABLE oxpayments DROP `KLEXTERNALNAME`",
            "ALTER TABLE oxpayments DROP `KLEXTERNALPAYMENT`",
            "ALTER TABLE oxpayments DROP `KLEXTERNALCHECKOUT`",
            "ALTER TABLE oxpayments DROP `KLPAYMENTIMAGEURL`",
            "ALTER TABLE oxpayments DROP `KLPAYMENTIMAGEURL_1`",
            "ALTER TABLE oxpayments DROP `KLCHECKOUTIMAGEURL`",
            "ALTER TABLE oxpayments DROP `KLCHECKOUTIMAGEURL_1`",
            "ALTER TABLE oxpayments DROP `KLPAYMENTOPTION`",
            "ALTER TABLE oxpayments DROP `KLEMDPURCHASEHISTORYFULL`",

            "ALTER TABLE oxaddress DROP `KLTEMPORARY`",

            "DROP TABLE IF EXISTS `kl_ack`",
            "DROP TABLE IF EXISTS `kl_logs`",
            "DROP TABLE IF EXISTS `kl_anon_lookup`",
        ]);


    }

    /**
     * Trigger onActivate to bring the environment back to the module being fully active
     * @afterClass
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public static function redoTestSuiteSetup()
    {
        KlarnaInstaller::onActivate();
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $db->execute('update oxconfig set oxvarvalue=ENCODE(?, ?) where oxvarname=? and oxshopid=?',
            [1, 'fq45QS09_fqyx09239QQ', 'blKlarnaAllowSeparateDeliveryAddress', 1]);
    }

    /**
     *
     */
    public function testGetInstance()
    {
        $this->setProtectedClassProperty(KlarnaInstaller::getInstance(), 'instance', null);
        $result = KlarnaInstaller::getInstance();

        $dbName     = $this->getProtectedClassProperty($result, 'dbName');
        $modulePath = $this->getProtectedClassProperty($result, 'modulePath');
        $db         = $this->getProtectedClassProperty($result, 'db');

        $this->assertTrue($result instanceof KlarnaInstaller);
        $this->assertTrue($db instanceof DatabaseInterface);
        $this->assertEquals(Registry::getConfig()->getConfigParam('dbName'), $dbName);
        $this->assertEquals(Registry::getConfig()->getConfigParam('sShopDir') . 'modules/tc/klarna', $modulePath);
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function testOnActivate()
    {
        $this->revertTestSuiteSetup();

        $db                = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $dbMetaDataHandler = oxNew(DbMetaDataHandler::class);
        $db->execute('DELETE FROM oxmigrations_ce WHERE version = ?', ['Klarna400']);
        KlarnaInstaller::onActivate();

        //test payment methods
        $db->setFetchMode(DatabaseProvider::FETCH_MODE_ASSOC);
        $sql      = 'SELECT oxid, oxactive FROM oxpayments WHERE oxid IN (?, ?, ?, ?)';
        $payments = $db->select($sql, self::KLARNA_PAYMENT_IDS);
        $this->assertEquals(4, $payments->count());
        while (!$payments->EOF) {
            $row = $payments->getFields();
            $this->assertEquals('1', $row['oxactive']);
            $payments->fetchRow();
        }

        $this->assertTables($dbMetaDataHandler);
        $this->assertColumns($dbMetaDataHandler);

        $result = $db->getOne("SELECT count(*) FROM oxconfig WHERE oxvarname = ?", ['blKlarnaAllowSeparateDeliveryAddress']);
        $this->assertEquals('1', $result);
    }

    /**
     * @param $dbMetaDataHandler
     */
    public function assertTables($dbMetaDataHandler)
    {
        $this->assertTrue($dbMetaDataHandler->tableExists('kl_ack'));
        $this->assertTrue($dbMetaDataHandler->tableExists('kl_anon_lookup'));
        $this->assertTrue($dbMetaDataHandler->tableExists('kl_logs'));
    }

    /**
     * @param $dbMetaDataHandler
     */
    public function assertColumns($dbMetaDataHandler)
    {
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLMERCHANTID', 'oxorder'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLSERVERMODE', 'oxorder'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLORDERID', 'oxorder'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLSYNC', 'oxorder'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('KLTITLE', 'oxorderarticles'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLARTNUM', 'oxorderarticles'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('KLPAYMENTTYPES', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLEXTERNALNAME', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLEXTERNALPAYMENT', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLEXTERNALCHECKOUT', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLPAYMENTIMAGEURL', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLPAYMENTIMAGEURL_1', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLCHECKOUTIMAGEURL', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLCHECKOUTIMAGEURL_1', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLPAYMENTOPTION', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLEMDPURCHASEHISTORYFULL', 'oxpayments'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('KLTEMPORARY', 'oxaddress'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('KLORDERID', 'kl_logs'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLMID', 'kl_logs'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLSTATUSCODE', 'kl_logs'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('KLURL', 'kl_logs'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('KLORDERID', 'kl_ack'));
    }

    /**
     *
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function testOnDeactivate()
    {
        KlarnaInstaller::onDeactivate();
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $sql      = 'SELECT oxid, oxactive FROM oxpayments WHERE oxid IN (?, ?, ?, ?)';
        $payments = $db->select($sql, self::KLARNA_PAYMENT_IDS);

        $this->assertEquals(4, $payments->count());
        while (!$payments->EOF) {
            $row = $payments->getFields();
            $this->assertEquals('0', $row['oxactive']);
            $payments->fetchRow();
        }
    }

//$sqlPath = $testConfig->getShopPath() . 'modules/tc/klarna/Tests/' . 'Unit/Testdata/klarna-settings.sql';
}
