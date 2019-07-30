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
 * Creates user team tables.
 *
 * @version 1.2
 */
final class Version20190729162655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fixes foreign keys on tag table';
    }

    public function up(Schema $schema): void
    {
        $timesheetTags = $schema->getTable('kimai2_timesheet_tags');

        if ($timesheetTags->hasIndex('IDX_E3284EFEABDD46BE')) {
            $timesheetTags->dropIndex('IDX_E3284EFEABDD46BE');
        }
        if ($timesheetTags->hasIndex('IDX_E3284EFEBAD26311')) {
            $timesheetTags->dropIndex('IDX_E3284EFEBAD26311');
        }
        if ($timesheetTags->hasForeignKey('FK_732EECA9ABDD46BE')) {
            $timesheetTags->removeForeignKey('FK_732EECA9ABDD46BE');
        }
        if ($timesheetTags->hasForeignKey('FK_732EECA9BAD26311')) {
            $timesheetTags->removeForeignKey('FK_732EECA9BAD26311');
        }
        if ($timesheetTags->hasIndex('IDX_732EECA9ABDD46BE')) {
            $timesheetTags->dropIndex('IDX_732EECA9ABDD46BE');
        }
        if ($timesheetTags->hasIndex('IDX_732EECA9BAD26311')) {
            $timesheetTags->dropIndex('IDX_732EECA9BAD26311');
        }

        $timesheetTags->addForeignKeyConstraint('kimai2_timesheet', ['timesheet_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_732EECA9ABDD46BE');
        $timesheetTags->addForeignKeyConstraint('kimai2_tags', ['tag_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_732EECA9BAD26311');
    }

    public function down(Schema $schema): void
    {
    }
}
