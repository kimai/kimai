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
 * Adding hourly_rate and fixed_rate to:
 * - Activities
 * - Projects
 * - Customer
 */
final class Version20180905190737 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_activities ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_activities ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');

        $this->addSql('ALTER TABLE kimai2_projects ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_projects ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');

        $this->addSql('ALTER TABLE kimai2_customers ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_customers ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $customer = $schema->getTable('kimai2_customers');
        $customer->dropColumn('hourly_rate');
        $customer->dropColumn('fixed_rate');

        $project = $schema->getTable('kimai2_projects');
        $project->dropColumn('hourly_rate');
        $project->dropColumn('fixed_rate');

        $activity = $schema->getTable('kimai2_activities');
        $activity->dropColumn('hourly_rate');
        $activity->dropColumn('fixed_rate');
    }
}
