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
 * @version 1.10
 */
final class Version20200705152310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updated invoice and added timesheet columns';
    }

    public function up(Schema $schema): void
    {
        $invoices = $schema->getTable('kimai2_invoices');
        $invoices->getColumn('invoice_filename')->setLength(150);

        $timesheet = $schema->getTable('kimai2_timesheet');
        $timesheet->addColumn('billable', 'boolean', ['notnull' => false, 'default' => true]);
        $timesheet->addColumn('category', 'string', ['length' => 10, 'notnull' => true, 'default' => 'work']);
        $timesheet->addColumn('modified_at', 'datetime', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $invoices = $schema->getTable('kimai2_invoices');
        $invoices->getColumn('invoice_filename')->setLength(100);

        $timesheet = $schema->getTable('kimai2_timesheet');
        $timesheet->dropColumn('billable');
        $timesheet->dropColumn('category');
        $timesheet->dropColumn('modified_at');
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
}
