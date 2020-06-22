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
 * Migrates the data from entity tables to user specific rate tables
 *
 * @version 1.8
 */
final class Version20200205115244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrates the data from entity tables to user specific rate tables';
    }

    public function up(Schema $schema): void
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

        $schema->getTable('kimai2_customers')->dropColumn('fixed_rate')->dropColumn('hourly_rate');
        $schema->getTable('kimai2_projects')->dropColumn('fixed_rate')->dropColumn('hourly_rate');
        $schema->getTable('kimai2_activities')->dropColumn('fixed_rate')->dropColumn('hourly_rate');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_customers ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_customers ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');

        $this->addSql('ALTER TABLE kimai2_projects ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_projects ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');

        $this->addSql('ALTER TABLE kimai2_activities ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_activities ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');
    }
}
