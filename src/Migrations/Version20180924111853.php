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
        $this->addSql('UPDATE kimai2_invoice_templates SET name=SUBSTRING(name, 1, 60)');
        $this->addSql('ALTER TABLE kimai2_invoice_templates CHANGE name name VARCHAR(60) NOT NULL, CHANGE vat vat DOUBLE PRECISION DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_invoice_templates CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE vat vat INT DEFAULT NULL');
    }
}
