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

final class Version20220315224645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds billable fields to Customer, Project and Activity';
    }

    public function up(Schema $schema): void
    {
        $customers = $schema->getTable('kimai2_customers');
        $customers->addColumn('billable', 'boolean', ['notnull' => true, 'default' => true]);

        $projects = $schema->getTable('kimai2_projects');
        $projects->addColumn('billable', 'boolean', ['notnull' => true, 'default' => true]);

        $activities = $schema->getTable('kimai2_activities');
        $activities->addColumn('billable', 'boolean', ['notnull' => true, 'default' => true]);

        $this->addSql('DELETE from kimai2_configuration WHERE `name` = "defaults.timesheet.billable"');
    }

    public function down(Schema $schema): void
    {
        $customers = $schema->getTable('kimai2_customers');
        $customers->dropColumn('billable');

        $projects = $schema->getTable('kimai2_projects');
        $projects->dropColumn('billable');

        $activities = $schema->getTable('kimai2_activities');
        $activities->dropColumn('billable');
    }
}
