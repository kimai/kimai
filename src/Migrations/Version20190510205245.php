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
 * New feature: tagging of timesheet records
 *
 * @version 1.0
 */
class Version20190510205245 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $timesheetTags = $schema->createTable('kimai2_timesheet_tags');
        $timesheetTags->addColumn('timesheet_id', 'integer', ['length' => 11, 'notnull' => true]);
        $timesheetTags->addColumn('tag_id', 'integer', ['length' => 11, 'notnull' => true]);
        $timesheetTags->addIndex(['timesheet_id'], 'IDX_E3284EFEABDD46BE');
        $timesheetTags->addIndex(['tag_id'], 'IDX_E3284EFEBAD26311');
        $timesheetTags->setPrimaryKey(['timesheet_id', 'tag_id']);

        $tags = $schema->createTable('kimai2_tags');
        $tags->addColumn('id', 'integer', ['length' => 11, 'autoincrement' => true, 'notnull' => true]);
        $tags->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $tags->addUniqueIndex(['name'], 'UNIQ_27CAF54C5E237E06');
        $tags->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $tags = $schema->getTable('kimai2_tags');
        $tags->dropIndex('UNIQ_27CAF54C5E237E06');

        $timesheetTags = $schema->getTable('kimai2_timesheet_tags');
        $timesheetTags->dropIndex('IDX_E3284EFEABDD46BE');
        $timesheetTags->dropIndex('IDX_E3284EFEBAD26311');

        $schema->dropTable('kimai2_timesheet_tags');
        $schema->dropTable('kimai2_tags');
    }
}
