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
 * Fixes foreign keys on tag table.
 *
 * @version 1.2
 */
final class Version20190730123324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates user team and permission tables';
    }

    public function up(Schema $schema): void
    {
        $teams = $schema->createTable('kimai2_teams');
        $teams->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $teams->addColumn('name', 'string', ['notnull' => true, 'length' => 100]);
        $teams->setPrimaryKey(['id']);
        $teams->addUniqueIndex(['name'], 'UNIQ_3BEDDC7F5E237E06');

        $userTeams = $schema->createTable('kimai2_users_teams');
        $userTeams->addColumn('user_id', 'integer', ['length' => 11, 'notnull' => true]);
        $userTeams->addColumn('team_id', 'integer', ['length' => 11, 'notnull' => true]);
        $userTeams->addForeignKeyConstraint('kimai2_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_B5E92CF8A76ED395');
        $userTeams->addForeignKeyConstraint('kimai2_teams', ['team_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_B5E92CF8296CD8AE');
        $userTeams->setPrimaryKey(['user_id', 'team_id']);

        $customerTeams = $schema->createTable('kimai2_customers_teams');
        $customerTeams->addColumn('customer_id', 'integer', ['length' => 11, 'notnull' => true]);
        $customerTeams->addColumn('team_id', 'integer', ['length' => 11, 'notnull' => true]);
        $customerTeams->addForeignKeyConstraint('kimai2_customers', ['customer_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_50BD83889395C3F3');
        $customerTeams->addForeignKeyConstraint('kimai2_teams', ['team_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_50BD8388296CD8AE');
        $customerTeams->setPrimaryKey(['customer_id', 'team_id']);

        $projectTeams = $schema->createTable('kimai2_projects_teams');
        $projectTeams->addColumn('project_id', 'integer', ['length' => 11, 'notnull' => true]);
        $projectTeams->addColumn('team_id', 'integer', ['length' => 11, 'notnull' => true]);
        $projectTeams->addForeignKeyConstraint('kimai2_projects', ['project_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_9345D431166D1F9C');
        $projectTeams->addForeignKeyConstraint('kimai2_teams', ['team_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_9345D431296CD8AE');
        $projectTeams->setPrimaryKey(['project_id', 'team_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_projects_teams');
        $schema->dropTable('kimai2_customers_teams');
        $schema->dropTable('kimai2_users_teams');
        $schema->dropTable('kimai2_teams');
    }
}
