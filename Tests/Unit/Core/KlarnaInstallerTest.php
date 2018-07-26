<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;


use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\DisplayError;
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

        $database->execute("DROP TABLE IF EXISTS `tcklarna_ack`");
        $database->execute("DROP TABLE IF EXISTS `tcklarna_logs`");
        $database->execute("DROP TABLE IF EXISTS `tcklarna_anon_lookup`");

        $dbMetaDataHandler->executeSql([
            "ALTER TABLE oxorder DROP `TCKLARNA_MERCHANTID`",
            "ALTER TABLE oxorder DROP `TCKLARNA_SERVERMODE`",
            "ALTER TABLE oxorder DROP `TCKLARNA_ORDERID`",
            "ALTER TABLE oxorder DROP `TCKLARNA_SYNC`",

            "ALTER TABLE oxorderarticles DROP `TCKLARNA_TITLE`",
            "ALTER TABLE oxorderarticles DROP `TCKLARNA_ARTNUM`",

            "ALTER TABLE oxpayments DROP `TCKLARNA_PAYMENTTYPES`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_EXTERNALNAME`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_EXTERNALPAYMENT`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_EXTERNALCHECKOUT`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_PAYMENTIMAGEURL`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_PAYMENTIMAGEURL_1`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_PAYMENTIMAGEURL_2`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_PAYMENTIMAGEURL_3`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_CHECKOUTIMAGEURL`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_CHECKOUTIMAGEURL_1`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_CHECKOUTIMAGEURL_2`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_CHECKOUTIMAGEURL_3`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_PAYMENTOPTION`",
            "ALTER TABLE oxpayments DROP `TCKLARNA_EMDPURCHASEHISTORYFULL`",

            "ALTER TABLE oxaddress DROP `TCKLARNA_TEMPORARY`",

            "DROP TABLE IF EXISTS `tcklarna_ack`",
            "DROP TABLE IF EXISTS `tcklarna_logs`",
            "DROP TABLE IF EXISTS `tcklarna_anon_lookup`",
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
        $this->assertEquals(Registry::getConfig()->getConfigParam('sShopDir') . 'modules//tc/tcklarna', $modulePath);
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
        $this->assertTrue($dbMetaDataHandler->tableExists('tcklarna_ack'));
        $this->assertTrue($dbMetaDataHandler->tableExists('tcklarna_anon_lookup'));
        $this->assertTrue($dbMetaDataHandler->tableExists('tcklarna_logs'));
    }

    /**
     * @param $dbMetaDataHandler
     */
    public function assertColumns($dbMetaDataHandler)
    {
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_MERCHANTID', 'oxorder'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_SERVERMODE', 'oxorder'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_ORDERID', 'oxorder'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_SYNC', 'oxorder'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_TITLE', 'oxorderarticles'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_ARTNUM', 'oxorderarticles'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_PAYMENTTYPES', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_EXTERNALNAME', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_EXTERNALPAYMENT', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_EXTERNALCHECKOUT', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_PAYMENTIMAGEURL', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_PAYMENTIMAGEURL_1', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_PAYMENTIMAGEURL_2', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_PAYMENTIMAGEURL_3', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_CHECKOUTIMAGEURL', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_CHECKOUTIMAGEURL_1', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_CHECKOUTIMAGEURL_2', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_CHECKOUTIMAGEURL_3', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_PAYMENTOPTION', 'oxpayments'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_EMDPURCHASEHISTORYFULL', 'oxpayments'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_TEMPORARY', 'oxaddress'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_ORDERID', 'tcklarna_logs'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_MID', 'tcklarna_logs'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_STATUSCODE', 'tcklarna_logs'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_URL', 'tcklarna_logs'));

        $this->assertTrue($dbMetaDataHandler->fieldExists('TCKLARNA_ORDERID', 'tcklarna_ack'));
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

}
