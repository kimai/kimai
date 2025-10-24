<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Migrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * @version 4.1.0
 */
final class Version20240917151424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate existing entries from old table name';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('kimai2_shared_project_timesheets')) {
            // likely an older installation without the latest features, only import the columns available since 2020
            $this->addSql('INSERT INTO kimai2_customer_portals (`share_key`, `project_id`, `customer_id`, `password`, `entry_user_visible`, `entry_rate_visible`, `record_merge_mode`, `annual_chart_visible`, `monthly_chart_visible`) 
                                                         SELECT `share_key`, `project_id`, null,          `password`, `entry_user_visible`, `entry_rate_visible`, `record_merge_mode`, `annual_chart_visible`, `monthly_chart_visible` FROM kimai2_shared_project_timesheets');
        } else {
            $this->preventEmptyMigrationWarning();
        }
    }

    public function down(Schema $schema): void
    {
        $this->preventEmptyMigrationWarning();
    }
}
