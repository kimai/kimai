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
 * Fixes foreign keys on tag table.
 *
 * @version 1.6
 */
final class Version20191113132640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fixes foreign keys on tag table';
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
        $timesheetTags = $schema->getTable('kimai2_timesheet_tags');

        $timesheetTags->removeForeignKey('FK_732EECA9ABDD46BE');
        $timesheetTags->addForeignKeyConstraint('kimai2_timesheet', ['timesheet_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_732EECA9ABDD46BE');

        $timesheetTags->removeForeignKey('FK_732EECA9BAD26311');
        $timesheetTags->addForeignKeyConstraint('kimai2_tags', ['tag_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_732EECA9BAD26311');
    }

    public function down(Schema $schema): void
    {
    }
}
