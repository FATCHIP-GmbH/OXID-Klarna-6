<?php

namespace OxidEsales\EshopCommunity\Migrations;


use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Updates DB structure for Klarna Module 4.0.0
 * @codeCoverageIgnore
 */
class VersionKlarna400 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            "
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
                  ENGINE = MyISAM
                  DEFAULT CHARSET = utf8;
                  

            CREATE TABLE IF NOT EXISTS `tcklarna_ack` (
                  `OXID`       VARCHAR(32)
                               CHARACTER SET latin1 COLLATE latin1_general_ci
                                        NOT NULL,
                  `KLRECEIVED` DATETIME NOT NULL,
                  PRIMARY KEY (`OXID`)
                )
                  ENGINE = MyISAM
                  COMMENT ='List of all Klarna acknowledge requests'
                  DEFAULT CHARSET = utf8;
                  
                
            CREATE TABLE IF NOT EXISTS `tcklarna_anon_lookup` (
                  `TCKLARNA_ARTNUM` VARCHAR(32) NOT NULL,
                  `OXARTID`  VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
                  PRIMARY KEY (`TCKLARNA_ARTNUM`)
                )
                  ENGINE = MyISAM
                  COMMENT ='Mapping of annonymous article numbers to their oxids'
                  DEFAULT CHARSET = utf8;"
        );

        $this->addAlterTables();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    /**
     * Checks if specific column exists in the table
     * @param $sTableName
     * @param $sColumnName
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function dbColumnExist($sTableName, $sColumnName)
    {
        $query = "SELECT * FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = '" . $this->connection->getDatabase() . "' 
                  AND TABLE_NAME = '$sTableName'
                  AND COLUMN_NAME = '$sColumnName'
                  ";

        return count($this->connection->query($query)->fetchAll()) > 0;
    }

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

            $this->addSql($query);
        }
    }
}