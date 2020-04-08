<?php

declare(strict_types=1);

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
 * Changes the invoice templates table for:
 * - shorter template name and proper index name
 * - VAT supporting percentages
 */
final class Version20180924111853 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $invoiceTemplates = 'kimai2_invoice_templates';

        if ($this->isPlatformSqlite()) {
            $this->addSql('UPDATE ' . $invoiceTemplates . ' SET name=substr(name, 1, 60)');
            $this->addSql('DROP INDEX UNIQ_1626CFE95E237E06');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $invoiceTemplates . ' AS SELECT id, name, title, company, address, due_days, vat, calculator, number_generator, renderer, payment_terms FROM ' . $invoiceTemplates);
            $this->addSql('DROP TABLE ' . $invoiceTemplates);
            $this->addSql('CREATE TABLE ' . $invoiceTemplates . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL COLLATE BINARY, company VARCHAR(255) NOT NULL COLLATE BINARY, address CLOB DEFAULT NULL COLLATE BINARY, due_days INTEGER NOT NULL, calculator VARCHAR(20) NOT NULL COLLATE BINARY, number_generator VARCHAR(20) NOT NULL COLLATE BINARY, renderer VARCHAR(20) NOT NULL COLLATE BINARY, payment_terms CLOB DEFAULT NULL COLLATE BINARY, name VARCHAR(60) NOT NULL, vat DOUBLE PRECISION DEFAULT 0)');
            $this->addSql('INSERT INTO ' . $invoiceTemplates . ' (id, name, title, company, address, due_days, vat, calculator, number_generator, renderer, payment_terms) SELECT id, name, title, company, address, due_days, vat, calculator, number_generator, renderer, payment_terms FROM __temp__' . $invoiceTemplates);
            $this->addSql('DROP TABLE __temp__' . $invoiceTemplates);
            $this->addSql('CREATE UNIQUE INDEX UNIQ_1626CFE95E237E06 ON ' . $invoiceTemplates . ' (name)');
        } else {
            $this->addSql('UPDATE ' . $invoiceTemplates . ' SET name=SUBSTRING(name, 1, 60)');
            $this->addSql('ALTER TABLE ' . $invoiceTemplates . ' CHANGE name name VARCHAR(60) NOT NULL, CHANGE vat vat DOUBLE PRECISION DEFAULT 0');
        }
    }

    public function down(Schema $schema): void
    {
        $invoiceTemplates = 'kimai2_invoice_templates';

        if ($this->isPlatformSqlite()) {
            $this->addSql('DROP INDEX UNIQ_1626CFE95E237E06');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $invoiceTemplates . ' AS SELECT id, name, title, company, address, due_days, vat, calculator, number_generator, renderer, payment_terms FROM ' . $invoiceTemplates);
            $this->addSql('DROP TABLE ' . $invoiceTemplates);
            $this->addSql('CREATE TABLE ' . $invoiceTemplates . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, address CLOB DEFAULT NULL, due_days INTEGER NOT NULL, calculator VARCHAR(20) NOT NULL, number_generator VARCHAR(20) NOT NULL, renderer VARCHAR(20) NOT NULL, payment_terms CLOB DEFAULT NULL, name VARCHAR(255) NOT NULL COLLATE BINARY, vat INTEGER DEFAULT NULL)');
            $this->addSql('INSERT INTO ' . $invoiceTemplates . ' (id, name, title, company, address, due_days, vat, calculator, number_generator, renderer, payment_terms) SELECT id, name, title, company, address, due_days, vat, calculator, number_generator, renderer, payment_terms FROM __temp__' . $invoiceTemplates);
            $this->addSql('DROP TABLE __temp__' . $invoiceTemplates);
            $this->addSql('CREATE UNIQUE INDEX UNIQ_1626CFE95E237E06 ON ' . $invoiceTemplates . ' (name)');
        } else {
            $this->addSql('ALTER TABLE ' . $invoiceTemplates . ' CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE vat vat INT DEFAULT NULL');
        }
    }
}
