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

/**
 * @version 3.0
 */
final class Version20250830182140 extends AbstractMigration
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
            $roles = $row['roles'];

            $roles = unserialize($roles);
            if (!\is_array($roles)) {
                $roles = [];
            }

            $data = json_encode($roles);

            $connection->executeStatement('UPDATE kimai2_users SET roles = :roles WHERE id = :id', [
                'roles' => $data,
                'id' => $id,
            ]);
        }

        $this->addSql('ALTER TABLE kimai2_users CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $connection = $this->connection;

        $connection->executeStatement('ALTER TABLE kimai2_users CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
        $connection->executeStatement('ALTER TABLE kimai2_users DROP CONSTRAINT IF EXISTS roles');

        // Fetch the existing rows from the table
        $rows = $connection->fetchAllAssociative('SELECT id, roles FROM kimai2_users');

        // Iterate over each row
        foreach ($rows as $row) {
            $id = $row['id'];
            $roles = $row['roles'];

            $json = json_decode($roles);
            $data = serialize($json);

            $connection->executeStatement('UPDATE kimai2_users SET roles = :roles WHERE id = :id', [
                'roles' => $data,
                'id' => $id,
            ]);
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
