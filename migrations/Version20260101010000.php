<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 * @version 3.0
 */
final class Version20260101010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Kimai 3.0 update';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->connection;

        $rows = $connection->fetchAllAssociative('SELECT id, roles FROM kimai2_users');

        foreach ($rows as $row) {
            $id = $row['id'];
            $value = $row['roles'];
            $data = [];

            if (\is_string($value) && str_starts_with($value, 'a:')) {
                $data = unserialize($value);
            }

            if (!\is_array($data)) {
                $data = [];
            }

            $json = json_encode($data);

            $connection->executeStatement('UPDATE kimai2_users SET roles = :data WHERE id = :id', [
                'data' => $json,
                'id' => $id,
            ]);
        }

        $table = $schema->getTable('kimai2_users');
        $table->dropColumn('api_token');

        $column = $table->getColumn('roles');
        $column->setType(Type::getType(Types::JSON));
        $column->setComment('(DC2Type:json)');
        $column->setNotnull(true);

        $table = $schema->getTable('kimai2_timesheet');
        $table->dropColumn('category');
    }

    public function down(Schema $schema): void
    {
        $connection = $this->connection;

        // this will leave behind a json_valid() check in mariadb
        $connection->executeStatement('ALTER TABLE kimai2_users CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');

        // Fetch the existing rows from the table
        $rows = $connection->fetchAllAssociative('SELECT id, roles FROM kimai2_users');

        // Iterate over each row
        foreach ($rows as $row) {
            $id = $row['id'];
            $roles = $row['roles'];
            $data = [];

            if (json_validate($roles)) {
                $data = json_decode($roles, true);
            }

            if (!\is_array($data)) {
                $data = [];
            }

            $connection->executeStatement('UPDATE kimai2_users SET roles = :roles WHERE id = :id', [
                'roles' => serialize($data),
                'id' => $id,
            ]);
        }

        $timesheetTable = $schema->getTable('kimai2_timesheet');
        $timesheetTable->addColumn('category', 'string', ['length' => 10, 'notnull' => true, 'default' => 'work']);

        $usersTable = $schema->getTable('kimai2_users');
        $usersTable->addColumn('api_token', 'string', ['length' => 255, 'notnull' => false, 'default' => null]);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
