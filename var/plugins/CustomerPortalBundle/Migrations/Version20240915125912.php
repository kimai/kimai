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
use Doctrine\DBAL\Types\Types;

final class Version20240915125912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial table structure for the Customer Portal';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('kimai2_customer_portals');

        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('project_id', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('customer_id', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('share_key', Types::STRING, ['length' => 20, 'notnull' => true]);
        $table->addColumn('password', Types::STRING, ['length' => 255, 'default' => null, 'notnull' => false]);
        $table->addColumn('record_merge_mode', Types::STRING, ['length' => 50, 'notnull' => true]);
        $table->addColumn('entry_user_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
        $table->addColumn('entry_rate_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
        $table->addColumn('annual_chart_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
        $table->addColumn('monthly_chart_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
        $table->addColumn('budget_stats_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
        $table->addColumn('time_budget_stats_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);

        $table->addForeignKeyConstraint('kimai2_projects', ['project_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_7747AE6B166D1F9C');
        $table->addForeignKeyConstraint('kimai2_customers', ['customer_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_7747AE6B9395C3F3');

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['share_key']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_customer_portals');
    }
}
