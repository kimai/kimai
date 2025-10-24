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
final class Version20240917151425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate existing entries from old table name';
    }

    public function up(Schema $schema): void
    {
        $sawTable = false;
        if ($schema->hasTable('kimai2_shared_project_timesheets')) {
            $schema->dropTable('kimai2_shared_project_timesheets');
            $sawTable = true;
        }
        if ($schema->hasTable('bundle_migration_shared_project_timesheets')) {
            $schema->dropTable('bundle_migration_shared_project_timesheets');
            $sawTable = true;
        }
        if (!$sawTable) {
            $this->preventEmptyMigrationWarning();
        }
    }

    public function down(Schema $schema): void
    {
        $this->preventEmptyMigrationWarning();
    }
}
