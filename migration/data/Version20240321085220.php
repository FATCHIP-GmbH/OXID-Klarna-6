<?php

declare(strict_types=1);

namespace TopConcepts\Klarna\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240321085220 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        if (!$schema->hasTable('tcklarna_authtokens')) {
            $this->addSql("
                CREATE TABLE `tcklarna_authtokens` (
                    `OXID`        
                        CHAR(32)
                        CHARACTER SET latin1 COLLATE latin1_general_ci
                        NOT NULL DEFAULT '',
                    `TCKLARNA_AUTHTOKEN` 
                        CHAR(32) 
                        CHARACTER SET latin1 COLLATE latin1_general_ci 
                        NOT NULL,
                    `TCKLARNA_SESSIONID`      
                        CHAR(32)
                        CHARACTER SET latin1 COLLATE latin1_general_ci
                        NOT NULL,
                    `TIMESTAMP` timestamp 
                        NOT NULL 
                        DEFAULT CURRENT_TIMESTAMP 
                        ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp',
                    PRIMARY KEY (`OXID`),
                    UNIQUE (TCKLARNA_AUTHTOKEN),
                    UNIQUE (TCKLARNA_SESSIONID)
                )
                ENGINE = InnoDB
                DEFAULT CHARSET = utf8;
            ");
        }
    }

    public function down(Schema $schema) : void
    {
    }
}
