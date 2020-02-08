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
 * Adds the rate table, which allows to define user specific rate rules
 *
 * @version 1.8
 */
final class Version20200205115243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the rate table, which allows to define user specific rate rules';
    }

    public function up(Schema $schema): void
    {
        $customerRates = $schema->createTable('kimai2_customers_rates');
        $customerRates->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $customerRates->addColumn('user_id', 'integer', ['length' => 11, 'notnull' => false]);
        $customerRates->addColumn('customer_id', 'integer', ['length' => 11, 'notnull' => false]);
        $customerRates->addColumn('rate', 'float', ['notnull' => true]);
        $customerRates->addColumn('fixed', 'boolean', ['notnull' => true]);
        $customerRates->addForeignKeyConstraint('kimai2_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_82AB0AECA76ED395');
        $customerRates->addForeignKeyConstraint('kimai2_customers', ['customer_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_82AB0AEC9395C3F3');
        $customerRates->addUniqueIndex(['user_id', 'customer_id'], 'UNIQ_82AB0AECA76ED3959395C3F3');
        $customerRates->setPrimaryKey(['id']);

        $projectRates = $schema->createTable('kimai2_projects_rates');
        $projectRates->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $projectRates->addColumn('user_id', 'integer', ['length' => 11, 'notnull' => false]);
        $projectRates->addColumn('project_id', 'integer', ['length' => 11, 'notnull' => false]);
        $projectRates->addColumn('rate', 'float', ['notnull' => true]);
        $projectRates->addColumn('fixed', 'boolean', ['notnull' => true]);
        $projectRates->addForeignKeyConstraint('kimai2_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_41535D55A76ED395');
        $projectRates->addForeignKeyConstraint('kimai2_projects', ['project_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_41535D55166D1F9C');
        $projectRates->addUniqueIndex(['user_id', 'project_id'], 'UNIQ_41535D55A76ED395166D1F9C');
        $projectRates->setPrimaryKey(['id']);

        $activityRates = $schema->createTable('kimai2_activities_rates');
        $activityRates->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $activityRates->addColumn('user_id', 'integer', ['length' => 11, 'notnull' => false]);
        $activityRates->addColumn('activity_id', 'integer', ['length' => 11, 'notnull' => false]);
        $activityRates->addColumn('rate', 'float', ['notnull' => true]);
        $activityRates->addColumn('fixed', 'boolean', ['notnull' => true]);
        $activityRates->addForeignKeyConstraint('kimai2_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_4A7F11BEA76ED395');
        $activityRates->addForeignKeyConstraint('kimai2_activities', ['activity_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_4A7F11BE81C06096');
        $activityRates->addUniqueIndex(['user_id', 'activity_id'], 'UNIQ_4A7F11BEA76ED39581C06096');
        $activityRates->setPrimaryKey(['id']);
    }

    public function postUp(Schema $schema): void
    {
        $migrates = [
            ['kimai2_activities', 'activity_id', 'kimai2_activities_rates'],
            ['kimai2_projects', 'project_id', 'kimai2_projects_rates'],
            ['kimai2_customers', 'customer_id', 'kimai2_customers_rates'],
        ];

        foreach ($migrates as $migrateOpts) {
            $tableName = $migrateOpts[0];
            $fieldName = $migrateOpts[1];
            $targetTable = $migrateOpts[2];

            $rules = $this->connection->prepare(
                'SELECT id, fixed_rate, hourly_rate FROM ' . $tableName . ' WHERE fixed_rate IS NOT NULL OR hourly_rate IS NOT NULL'
            );
            $rules->execute();

            foreach ($rules->fetchAll() as $rateRule) {
                $isFixed = $rateRule['fixed_rate'] !== null;
                $rate = $rateRule['fixed_rate'] ?? $rateRule['hourly_rate'];
                $params = ['user_id' => null, $fieldName => $rateRule['id'], 'rate' => $rate, 'fixed' => $isFixed];

                $this->connection->insert($targetTable, $params, ['fixed' => \PDO::PARAM_BOOL]);
            }
        }

        // FIXME drop hourly_rate, fixed_rate from tables

        parent::postUp($schema);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_activities_rates');
        $schema->dropTable('kimai2_projects_rates');
        $schema->dropTable('kimai2_customers_rates');
    }
}
