<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * @version 2.41
 */
final class Version20251031142000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dedicated address fields for customer and customer-field for invoice template';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_customers ADD address_line1 VARCHAR(150) DEFAULT NULL, ADD address_line2 VARCHAR(150) DEFAULT NULL, ADD address_line3 VARCHAR(150) DEFAULT NULL, ADD postcode VARCHAR(20) DEFAULT NULL, ADD city VARCHAR(50) DEFAULT NULL, ADD buyer_reference VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_invoice_templates ADD customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_invoice_templates MODIFY company VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE kimai2_invoice_templates ADD CONSTRAINT FK_1626CFE99395C3F3 FOREIGN KEY (customer_id) REFERENCES kimai2_customers (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1626CFE99395C3F3 ON kimai2_invoice_templates (customer_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_invoice_templates DROP FOREIGN KEY FK_1626CFE99395C3F3');
        $this->addSql('DROP INDEX IDX_1626CFE99395C3F3 ON kimai2_invoice_templates');
        $this->addSql('ALTER TABLE kimai2_invoice_templates DROP customer_id');
        $this->addSql('ALTER TABLE kimai2_customers DROP address_line1, DROP address_line2, DROP address_line3, DROP postcode, DROP city, DROP buyer_reference');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
