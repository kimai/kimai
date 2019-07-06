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
 * Creates several indices to improve speed for default queries.
 *
 * @version 1.1
 */
final class Version20190706224219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates several indices to improve speed for default queries.';
    }

    public function up(Schema $schema): void
    {
        $timesheet = $schema->getTable('kimai2_timesheet');
        $timesheet->addIndex(['user', 'start_time'], 'IDX_4F60C6B18D93D649502DF587');
        $timesheet->addIndex(['start_time'], 'IDX_4F60C6B1502DF587');
        $timesheet->addIndex(['start_time', 'end_time', 'user'], 'IDX_4F60C6B1502DF587415614018D93D649');
        $timesheet->addIndex(['start_time', 'end_time'], 'IDX_4F60C6B1502DF58741561401');

        $activity = $schema->getTable('kimai2_activities');
        $activity->addIndex(['visible'], 'IDX_8811FE1C7AB0E859');
        $activity->addIndex(['visible', 'project_id'], 'IDX_8811FE1C7AB0E859166D1F9C');

        $project = $schema->getTable('kimai2_projects');
        $project->addIndex(['visible'], 'IDX_407F12067AB0E859');

        $customer = $schema->getTable('kimai2_customers');
        $customer->addIndex(['visible'], 'IDX_5A9760447AB0E859');
    }

    public function down(Schema $schema): void
    {
        $activity = $schema->getTable('kimai2_activities');
        $activity->dropIndex('IDX_8811FE1C7AB0E859');
        $activity->dropIndex('IDX_8811FE1C7AB0E859166D1F9C');

        $customer = $schema->getTable('kimai2_customers');
        $customer->dropIndex('IDX_5A9760447AB0E859');

        $project = $schema->getTable('kimai2_projects');
        $project->dropIndex('IDX_407F12067AB0E859');

        $timesheet = $schema->getTable('kimai2_timesheet');
        $timesheet->dropIndex('IDX_4F60C6B18D93D649502DF587');
        $timesheet->dropIndex('IDX_4F60C6B1502DF587');
        $timesheet->dropIndex('IDX_4F60C6B1502DF587415614018D93D649');
        $timesheet->dropIndex('idx_4f60c6b1502df58741561401');
    }
}
