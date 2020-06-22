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
 * Adds language and decimal_duration column to invoice template table
 *
 * @version 1.8
 */
final class Version20200204124425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds language and decimal_duration column to invoice template table';
    }

    protected function isSupportingForeignKeys(): bool
    {
        return false;
    }

    public function isTransactional(): bool
    {
        if ($this->isPlatformSqlite()) {
            // does fail if we use transactions, as tables are re-created and foreign keys would fail
            return false;
        }

        return true;
    }

    public function up(Schema $schema): void
    {
        $invoiceTemplates = $schema->getTable('kimai2_invoice_templates');
        $invoiceTemplates->addColumn('decimal_duration', 'boolean', ['notnull' => true, 'default' => false]);
        $invoiceTemplates->addColumn('language', 'string', ['notnull' => false, 'length' => 6]);
    }

    public function down(Schema $schema): void
    {
        $invoiceTemplates = $schema->getTable('kimai2_invoice_templates');
        $invoiceTemplates->dropColumn('language');
        $invoiceTemplates->dropColumn('decimal_duration');
    }
}
