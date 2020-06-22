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
 * Creates meta tables to store custom fields for entities
 *
 * @version 1.0
 */
final class Version20190617100845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates meta tables to store custom fields for entities';
    }

    public function up(Schema $schema): void
    {
        $timesheetMeta = $schema->createTable('kimai2_timesheet_meta');
        $timesheetMeta->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $timesheetMeta->addColumn('timesheet_id', 'integer', ['notnull' => true]);
        $timesheetMeta->addColumn('name', 'string', ['notnull' => true, 'length' => 50]);
        $timesheetMeta->addColumn('value', 'string', ['notnull' => false, 'length' => 255]);
        $timesheetMeta->addColumn('visible', 'boolean', ['notnull' => false, 'default' => false]);
        $timesheetMeta->setPrimaryKey(['id']);
        $timesheetMeta->addIndex(['timesheet_id'], 'IDX_CB606CBAABDD46BE');
        $timesheetMeta->addUniqueIndex(['timesheet_id', 'name'], 'UNIQ_CB606CBAABDD46BE5E237E06');
        $timesheetMeta->addForeignKeyConstraint('kimai2_timesheet', ['timesheet_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_CB606CBAABDD46BE');

        $customerMeta = $schema->createTable('kimai2_customers_meta');
        $customerMeta->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $customerMeta->addColumn('customer_id', 'integer', ['notnull' => true]);
        $customerMeta->addColumn('name', 'string', ['notnull' => true, 'length' => 50]);
        $customerMeta->addColumn('value', 'string', ['notnull' => false, 'length' => 255]);
        $customerMeta->addColumn('visible', 'boolean', ['notnull' => false, 'default' => false]);
        $customerMeta->setPrimaryKey(['id']);
        $customerMeta->addIndex(['customer_id'], 'IDX_A48A760F9395C3F3');
        $customerMeta->addUniqueIndex(['customer_id', 'name'], 'UNIQ_A48A760F9395C3F35E237E06');
        $customerMeta->addForeignKeyConstraint('kimai2_customers', ['customer_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_A48A760F9395C3F3');

        $projectMeta = $schema->createTable('kimai2_projects_meta');
        $projectMeta->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $projectMeta->addColumn('project_id', 'integer', ['notnull' => true]);
        $projectMeta->addColumn('name', 'string', ['notnull' => true, 'length' => 50]);
        $projectMeta->addColumn('value', 'string', ['notnull' => false, 'length' => 255]);
        $projectMeta->addColumn('visible', 'boolean', ['notnull' => false, 'default' => false]);
        $projectMeta->setPrimaryKey(['id']);
        $projectMeta->addIndex(['project_id'], 'IDX_50536EF2166D1F9C');
        $projectMeta->addUniqueIndex(['project_id', 'name'], 'UNIQ_50536EF2166D1F9C5E237E06');
        $projectMeta->addForeignKeyConstraint('kimai2_projects', ['project_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_50536EF2166D1F9C');

        $activityMeta = $schema->createTable('kimai2_activities_meta');
        $activityMeta->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $activityMeta->addColumn('activity_id', 'integer', ['notnull' => true]);
        $activityMeta->addColumn('name', 'string', ['notnull' => true, 'length' => 50]);
        $activityMeta->addColumn('value', 'string', ['notnull' => false, 'length' => 255]);
        $activityMeta->addColumn('visible', 'boolean', ['notnull' => false, 'default' => false]);
        $activityMeta->setPrimaryKey(['id']);
        $activityMeta->addIndex(['activity_id'], 'IDX_A7C0A43D81C06096');
        $activityMeta->addUniqueIndex(['activity_id', 'name'], 'UNIQ_A7C0A43D81C060965E237E06');
        $activityMeta->addForeignKeyConstraint('kimai2_activities', ['activity_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_A7C0A43D81C06096');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_timesheet_meta');
        $schema->dropTable('kimai2_customers_meta');
        $schema->dropTable('kimai2_projects_meta');
        $schema->dropTable('kimai2_activities_meta');
    }
}
