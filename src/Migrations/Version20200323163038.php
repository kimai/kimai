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
 * @version 1.9
 */
final class Version20200323163038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the internal_rate column to all rate tables';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('kimai2_activities_rates')->addColumn('internal_rate', 'float', ['notnull' => false]);
        $schema->getTable('kimai2_projects_rates')->addColumn('internal_rate', 'float', ['notnull' => false]);
        $schema->getTable('kimai2_customers_rates')->addColumn('internal_rate', 'float', ['notnull' => false]);
        $schema->getTable('kimai2_timesheet')->addColumn('internal_rate', 'float', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('kimai2_timesheet')->dropColumn('internal_rate');
        $schema->getTable('kimai2_activities_rates')->dropColumn('internal_rate');
        $schema->getTable('kimai2_projects_rates')->dropColumn('internal_rate');
        $schema->getTable('kimai2_customers_rates')->dropColumn('internal_rate');
    }
}
