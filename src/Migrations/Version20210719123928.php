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
 * @version 1.15
 */
final class Version20210719123928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the budget_type columns to customer, project and activity';
    }

    public function up(Schema $schema): void
    {
        $activities = $schema->getTable('kimai2_activities');
        $activities->addColumn('budget_type', 'string', ['length' => 10, 'notnull' => false, 'default' => null]);

        $customers = $schema->getTable('kimai2_customers');
        $customers->addColumn('budget_type', 'string', ['length' => 10, 'notnull' => false, 'default' => null]);

        $projects = $schema->getTable('kimai2_projects');
        $projects->addColumn('budget_type', 'string', ['length' => 10, 'notnull' => false, 'default' => null]);
    }

    public function down(Schema $schema): void
    {
        $activities = $schema->getTable('kimai2_activities');
        $activities->dropColumn('budget_type');

        $customers = $schema->getTable('kimai2_customers');
        $customers->dropColumn('budget_type');

        $projects = $schema->getTable('kimai2_projects');
        $projects->dropColumn('budget_type');
    }
}
