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
 * @version 2.63
 */
final class Version20260803120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add company to invoice';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('kimai2_invoices')->hasColumn('company')) {
            $this->addSql('ALTER TABLE kimai2_invoices ADD `company` VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('kimai2_invoices')->hasColumn('company')) {
            $this->addSql('ALTER TABLE kimai2_invoices DROP COLUMN `company`');
        }
    }
}
