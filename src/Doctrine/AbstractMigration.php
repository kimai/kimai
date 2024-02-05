<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration as BaseAbstractMigration;

/**
 * Base class for all Doctrine migrations.
 *
 * @codeCoverageIgnore
 */
abstract class AbstractMigration extends BaseAbstractMigration
{
    /**
     * @see https://github.com/doctrine/migrations/issues/1104
     */
    public function isTransactional(): bool
    {
        return false;
    }

    /**
     * @throws Exception
     */
    public function preUp(Schema $schema): void
    {
        $this->abortIfPlatformNotSupported();
    }

    /**
     * @throws Exception
     */
    public function preDown(Schema $schema): void
    {
        $this->abortIfPlatformNotSupported();
    }

    /**
     * Abort the migration is the current platform is not supported.
     *
     * @throws Exception
     */
    private function abortIfPlatformNotSupported(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if (!($platform instanceof MySQLPlatform)) {
            $this->abortIf(true, 'Unsupported database platform: ' . \get_class($platform));
        }
    }

    protected function preventEmptyMigrationWarning(): void
    {
        $this->addSql('#prevent empty warning - no SQL to execute');
    }

    /**
     * I don't know how often I accidentally dropped database tables,
     * because a generated "left-over" migration was executed.
     *
     * @param mixed[] $params
     * @param mixed[] $types
     */
    protected function addSql(string $sql, array $params = [], array $types = []): void
    {
        if (str_starts_with($sql, 'DROP TABLE ')) {
            throw new \InvalidArgumentException('Cannot use addSql() with DROP TABLE');
        }

        parent::addSql($sql, $params, $types);
    }
}
