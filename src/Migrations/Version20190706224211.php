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
 * Fix meta-table definitions
 *
 * @version 1.1
 */
final class Version20190706224211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix meta-table definitions';
    }

    public function up(Schema $schema): void
    {
        $timesheetMeta = $schema->getTable('kimai2_timesheet_meta');
        $timesheetMeta->changeColumn('visible', ['notnull' => true, 'default' => false]);
        $timesheetMeta->changeColumn('timesheet_id', ['notnull' => true]);

        $projectMeta = $schema->getTable('kimai2_projects_meta');
        $projectMeta->changeColumn('visible', ['notnull' => true, 'default' => false]);
        $projectMeta->changeColumn('project_id', ['notnull' => true]);

        $customerMeta = $schema->getTable('kimai2_customers_meta');
        $customerMeta->changeColumn('visible', ['notnull' => true, 'default' => false]);
        $customerMeta->changeColumn('customer_id', ['notnull' => true]);

        $activityMeta = $schema->getTable('kimai2_activities_meta');
        $activityMeta->changeColumn('visible', ['notnull' => true, 'default' => false]);
        $activityMeta->changeColumn('activity_id', ['notnull' => true]);
    }

    public function down(Schema $schema): void
    {
        // the columns above were created incorrect in migration Version20190617100845 for upgraded systems
        // that's why there are no equivalent changes in down()
    }
}
