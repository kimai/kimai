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
final class Version20210802174319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate teamleads to join table';
    }

    public function up(Schema $schema): void
    {
        $fetch = $this->connection->prepare('SELECT id, teamlead_id FROM kimai2_teams');
        $result = $fetch->executeQuery();

        foreach ($result->iterateAssociative() as $row) {
            $this->addSql('UPDATE kimai2_users_teams SET teamlead = 1 WHERE user_id = ? AND team_id = ?', [$row['teamlead_id'], $row['id']]);
        }

        $result->free();

        $this->preventEmptyMigrationWarning();
    }

    public function down(Schema $schema): void
    {
        $fetch = $this->connection->prepare('SELECT user_id, team_id, teamlead FROM kimai2_users_teams WHERE teamlead = 1');
        $result = $fetch->executeQuery();

        foreach ($result->iterateAssociative() as $row) {
            $this->addSql('UPDATE kimai2_teams SET teamlead_id = ? where id = ?', [$row['user_id'], $row['team_id']]);
        }

        $result->free();

        $teams = $schema->getTable('kimai2_teams');
        $teams->getColumn('teamlead_id')->setNotnull(true);
        $teams->addForeignKeyConstraint('kimai2_users', ['teamlead_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_3BEDDC7F8F7DE5D7');

        $this->preventEmptyMigrationWarning();
    }
}
