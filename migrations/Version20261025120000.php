<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 * The "array" type uses unserialize() without restricting allowed classes,
 * which allows PHP object injection through manipulated database values.
 *
 * @version 2.72
 */
final class Version20261025120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert user roles from serialized PHP array to JSON';
    }

    public function up(Schema $schema): void
    {
        $users = $this->connection->fetchAllKeyValue('SELECT `id`, `roles` FROM kimai2_users');

        $converted = [];
        foreach ($users as $id => $roles) {
            $json = \is_string($roles) ? self::convertToJson($roles) : null;
            if ($json === null) {
                $this->warnIf(true, \sprintf('Removed invalid roles from user with ID %s', $id));
                $json = '[]';
            }
            $converted[$json][] = (int) $id;
        }

        foreach ($converted as $json => $ids) {
            $this->addSql('UPDATE kimai2_users SET `roles` = :roles WHERE `id` IN (:ids)', ['roles' => $json, 'ids' => $ids], ['ids' => ArrayParameterType::INTEGER]);
        }

        // SQL from schema changes is executed after addSql(), so the data is already valid JSON (required for MySQL JSON columns)
        $schema->getTable('kimai2_users')->getColumn('roles')->setType(Type::getType(Types::JSON));
    }

    public function down(Schema $schema): void
    {
        // raw SQL, because the column type has to change before the data is converted (MySQL JSON columns reject non JSON values)
        $this->addSql("ALTER TABLE kimai2_users CHANGE `roles` `roles` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:array)'");

        $users = $this->connection->fetchAllKeyValue('SELECT `id`, `roles` FROM kimai2_users');

        $converted = [];
        foreach ($users as $id => $roles) {
            $decoded = \is_string($roles) ? json_decode($roles, true) : null;
            $converted[serialize(self::filterRoles($decoded) ?? [])][] = (int) $id;
        }

        foreach ($converted as $serialized => $ids) {
            $this->addSql('UPDATE kimai2_users SET `roles` = :roles WHERE `id` IN (:ids)', ['roles' => $serialized, 'ids' => $ids], ['ids' => ArrayParameterType::INTEGER]);
        }
    }

    /**
     * Returns the JSON list of roles or null, if the value is not a list of strings.
     */
    private static function convertToJson(string $roles): ?string
    {
        // already converted, e.g. if a previous run was aborted
        $decoded = json_decode($roles, true);

        if (!\is_array($decoded)) {
            // the @ suppresses the notice for invalid strings, objects are never instantiated
            $decoded = @unserialize($roles, ['allowed_classes' => false]);
        }

        $filtered = self::filterRoles($decoded);
        if ($filtered === null) {
            return null;
        }

        return json_encode($filtered, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string>|null
     */
    private static function filterRoles(mixed $roles): ?array
    {
        if (!\is_array($roles)) {
            return null;
        }

        $result = [];
        foreach ($roles as $role) {
            // anything else than a string can only be the result of a manipulated value
            if (!\is_string($role)) {
                return null;
            }
            $result[] = $role;
        }

        return array_values(array_unique($result));
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
