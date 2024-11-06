<?php

declare(strict_types=1);

namespace TopConcepts\Klarna\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use OxidEsales\Eshop\Application\Model\Payment;
use TopConcepts\Klarna\Core\KlarnaPaymentTypes;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241008125304 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->removeOneKlarna();
    }
    
    protected function removeOneKlarna()
    {
        $id = KlarnaPaymentTypes::KLARNA_PAYMENT_ID;
        $oPayment = oxNew(Payment::class);

        if ($oPayment->load($id)) {
            $oPayment->delete();
        }
    }

    public function down(Schema $schema) : void
    {
    }
}
