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

final class Version20220101204502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the timesheet-to-invoice mapping table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('kimai2_timesheets_invoices');
        $table->addColumn('timesheet_id', 'integer', ['length' => 11, 'notnull' => true]);
        $table->addColumn('invoice_id', 'integer', ['length' => 11, 'notnull' => true]);
        $table->addForeignKeyConstraint('kimai2_timesheet', ['timesheet_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_94531758ABDD46BE');
        $table->addForeignKeyConstraint('kimai2_invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_945317582989F1FD');
        $table->setPrimaryKey(['timesheet_id', 'invoice_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_timesheets_invoices');
    }
}
