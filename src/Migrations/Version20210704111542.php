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
final class Version20210704111542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the color columns on: user, team';
    }

    public function up(Schema $schema): void
    {
        $users = $schema->getTable('kimai2_users');
        $users->addColumn('color', 'string', ['length' => 7, 'notnull' => false, 'default' => null]);

        $teams = $schema->getTable('kimai2_teams');
        $teams->addColumn('color', 'string', ['length' => 7, 'notnull' => false, 'default' => null]);
    }

    public function down(Schema $schema): void
    {
        $users = $schema->getTable('kimai2_users');
        $users->dropColumn('color');

        $teams = $schema->getTable('kimai2_teams');
        $teams->dropColumn('color');
    }
}
