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
 * Adds the user roles and role permissions table
 *
 * @version 1.6
 */
final class Version20191108151534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the user roles and role permissions table';
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
        $roles = $schema->createTable('kimai2_roles');
        $roles->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $roles->addColumn('name', 'string', ['notnull' => true, 'length' => 50]);
        $roles->setPrimaryKey(['id']);
        $roles->addUniqueIndex(['name'], 'roles_name');

        $rolePermissions = $schema->createTable('kimai2_roles_permissions');
        $rolePermissions->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $rolePermissions->addColumn('role_id', 'integer', ['length' => 11, 'notnull' => true]);
        $rolePermissions->addColumn('permission', 'string', ['notnull' => true, 'length' => 50]);
        $rolePermissions->addColumn('allowed', 'boolean', ['notnull' => true, 'default' => false]);
        $rolePermissions->setPrimaryKey(['id']);
        $rolePermissions->addUniqueIndex(['role_id', 'permission'], 'role_permission');
        $rolePermissions->addForeignKeyConstraint('kimai2_roles', ['role_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_D263A3B8D60322AC');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_roles_permissions');
        $schema->dropTable('kimai2_roles');
    }
}
