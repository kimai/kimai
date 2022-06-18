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
final class Version20210802174318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a new column to the team join table to support multiple teamleads';
    }

    public function up(Schema $schema): void
    {
        $teamMember = $schema->getTable('kimai2_users_teams');
        $teamMember->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $teamMember->addColumn('teamlead', 'boolean', ['notnull' => true, 'default' => false]);
        $teamMember->dropPrimaryKey();
        $teamMember->setPrimaryKey(['id']);
        $teamMember->addUniqueIndex(['user_id', 'team_id'], 'UNIQ_B5E92CF8A76ED395296CD8AE');
    }

    public function down(Schema $schema): void
    {
        $teamMember = $schema->getTable('kimai2_users_teams');
        $teamMember->dropIndex('UNIQ_B5E92CF8A76ED395296CD8AE');
        $teamMember->dropPrimaryKey();
        $teamMember->dropColumn('teamlead');
        $teamMember->dropColumn('id');
        $teamMember->setPrimaryKey(['user_id', 'team_id']);
    }
}
