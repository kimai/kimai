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
 * Adds a column to the user table to identify authenticator
 *
 * @version 1.8
 */
final class Version20200125123942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a column to the user table to identify authenticator';
    }

    protected function isSupportingForeignKeys(): bool
    {
        return false;
    }

    public function isTransactional(): bool
    {
        if ($this->isPlatformSqlite()) {
            // does fail if we use transactions, as tables are re-created and foreign keys would fail
            return false;
        }

        return true;
    }

    public function up(Schema $schema): void
    {
        $users = $schema->getTable('kimai2_users');
        $users->addColumn('auth', 'string', ['notnull' => false, 'length' => 20]);
    }

    public function down(Schema $schema): void
    {
        $users = $schema->getTable('kimai2_users');
        $users->dropColumn('auth');
    }
}
