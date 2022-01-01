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
final class Version20210717211144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the account column to the user table';
    }

    public function up(Schema $schema): void
    {
        $users = $schema->getTable('kimai2_users');
        $users->addColumn('account', 'string', ['length' => 30, 'notnull' => false, 'default' => null]);
    }

    public function down(Schema $schema): void
    {
        $users = $schema->getTable('kimai2_users');
        $users->dropColumn('account');
    }
}
