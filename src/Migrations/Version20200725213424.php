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
 * Creates the activity teams table.
 *
 * @version 1.10
 */
final class Version20200725213424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the activity teams table';
    }

    public function up(Schema $schema): void
    {
        $activityTeams = $schema->createTable('kimai2_activities_teams');
        $activityTeams->addColumn('activity_id', 'integer', ['length' => 11, 'notnull' => true]);
        $activityTeams->addColumn('team_id', 'integer', ['length' => 11, 'notnull' => true]);
        $activityTeams->addForeignKeyConstraint('kimai2_activities', ['activity_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_986998DA81C06096');
        $activityTeams->addForeignKeyConstraint('kimai2_teams', ['team_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_986998DA296CD8AE');
        $activityTeams->setPrimaryKey(['activity_id', 'team_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_activities_teams');
    }
}
