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
 * Creates the budget columns on: customer, project, activity.
 *
 * @version 1.0
 */
final class Version20190605171157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the budget columns on: customer, project, activity';
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
        $customers = $schema->getTable('kimai2_customers');
        $customers->addColumn('time_budget', 'integer', ['notnull' => true, 'default' => 0]);
        $customers->addColumn('budget', 'float', ['notnull' => true, 'default' => 0]);

        $projects = $schema->getTable('kimai2_projects');
        $projects->addColumn('time_budget', 'integer', ['notnull' => true, 'default' => 0]);
        $projects->getColumn('budget')->setDefault(0);

        $activities = $schema->getTable('kimai2_activities');
        $activities->addColumn('time_budget', 'integer', ['notnull' => true, 'default' => 0]);
        $activities->addColumn('budget', 'float', ['notnull' => true, 'default' => 0]);
    }

    public function down(Schema $schema): void
    {
        $customers = $schema->getTable('kimai2_customers');
        $customers->dropColumn('time_budget');
        $customers->dropColumn('budget');

        $projects = $schema->getTable('kimai2_projects');
        $projects->dropColumn('time_budget');

        $activities = $schema->getTable('kimai2_activities');
        $activities->dropColumn('time_budget');
        $activities->dropColumn('budget');
    }
}
