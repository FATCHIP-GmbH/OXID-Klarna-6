<?php

namespace OxidEsales\EshopCommunity\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
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
            CREATE TABLE IF NOT EXISTS `kl_logs` (
                  `OXID`          CHAR(32)
                                  CHARACTER SET utf8
                               NOT NULL DEFAULT '',
                
                  `OXSHOPID`      CHAR(32)
                                  CHARACTER SET utf8
                               NOT NULL DEFAULT '',
                  `KLMETHOD`      VARCHAR(128)
                                  CHARACTER SET utf8
                               NOT NULL DEFAULT '',
                  `KLREQUESTRAW`  TEXT CHARACTER SET utf8
                               NOT NULL,
                  `KLRESPONSERAW` TEXT CHARACTER SET utf8
                               NOT NULL,
                  `KLDATE`        DATETIME
                               NOT NULL DEFAULT '0000-00-00 00:00:00',
                  PRIMARY KEY (`OXID`),
                  KEY `KLDATE` (`KLDATE`)
                )
                  ENGINE = MyISAM
                  DEFAULT CHARSET = utf8;
                  

            CREATE TABLE IF NOT EXISTS `kl_ack` (
                  `OXID`       VARCHAR(32)
                               CHARACTER SET utf8
                                        NOT NULL,
                  `KLRECEIVED` DATETIME NOT NULL,
                  PRIMARY KEY (`OXID`)
                )
                  ENGINE = MyISAM
                  COMMENT ='List of all Klarna acknowledge requests'
                  DEFAULT CHARSET = utf8;
                  
                
            CREATE TABLE IF NOT EXISTS `kl_anon_lookup` (
                  `KLARTNUM` VARCHAR(32) NOT NULL UNIQUE,
                  `OXARTID`  VARCHAR(32) NOT NULL UNIQUE,
                  PRIMARY KEY (`KLARTNUM`)
                )
                  ENGINE = MyISAM
                  COMMENT ='Mapping of annonymous article numbers to their oxids'
                  DEFAULT CHARSET = utf8;"
        );

        $this->addAlterTables();
//
//        $this->addSql("ALTER TABLE `oxorder`
//            ADD COLUMN `KLMERCHANTID` VARCHAR(128)  DEFAULT '' NOT NULL,
//            ADD COLUMN `KLSERVERMODE` VARCHAR(16) NOT NULL DEFAULT '',
//            ADD COLUMN `KLORDERID` VARCHAR(128)  DEFAULT '' NOT NULL,
//            ADD COLUMN `KLSYNC` TINYINT UNSIGNED NOT NULL DEFAULT '1';
//        ");
//
//        $this->addSql("ALTER TABLE `oxorderarticles`
//            ADD COLUMN  `KLTITLE` VARCHAR(255) NOT NULL DEFAULT '',
//            ADD COLUMN  `KLARTNUM` VARCHAR(255) NOT NULL DEFAULT '';
//        ");
//
//        $this->addSql("ALTER TABLE `oxpayments`
//            ADD COLUMN `KLPAYMENTTYPES` SET('payment', 'checkout') NULL DEFAULT '',
//            ADD COLUMN `KLEXTERNALNAME` VARCHAR(255) NULL DEFAULT '',
//            ADD COLUMN `KLEXTERNALPAYMENT` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
//            ADD COLUMN `KLEXTERNALCHECKOUT` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
//            ADD COLUMN `KLPAYMENTIMAGEURL` VARCHAR(255) NULL DEFAULT '',
//            ADD COLUMN `KLPAYMENTIMAGEURL_1` VARCHAR(255) NULL DEFAULT '',
//            ADD COLUMN `KLCHECKOUTIMAGEURL` VARCHAR(255) NULL DEFAULT '',
//            ADD COLUMN `KLCHECKOUTIMAGEURL_1` VARCHAR(255) NULL DEFAULT '',
//            ADD COLUMN `KLPAYMENTOPTION` SET('card', 'direct banking', 'other') NOT NULL DEFAULT 'other',
//            ADD COLUMN `KLEMDPURCHASEHISTORYFULL` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
//        ");
//
//        $this->addSql("ALTER TABLE `kl_logs`
//            ADD COLUMN `KLORDERID` VARCHAR(128) CHARACTER SET utf8 DEFAULT '' NOT NULL AFTER `OXID`,
//            ADD COLUMN `KLMID` VARCHAR(50) CHARACTER SET utf8 NOT NULL AFTER `OXSHOPID`,
//            ADD COLUMN `KLSTATUSCODE` VARCHAR(16) CHARACTER SET utf8 NOT NULL AFTER `KLMID`,
//            ADD COLUMN `KLURL` VARCHAR(256) CHARACTER SET utf8 AFTER `KLMETHOD`;
//        ");
//        $this->addSql("ALTER TABLE `kl_ack`
//             ADD COLUMN `KLORDERID` VARCHAR(128) CHARACTER SET utf8 DEFAULT '' NOT NULL AFTER `OXID`, ADD KEY `KLORDERID` (`KLORDERID`);
//        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }

    /**
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

    protected function addAlterTables(){
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
