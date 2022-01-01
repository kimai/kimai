<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use Doctrine\DBAL\Exception;
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
     * @param string $name
     * @return string
     */
    protected function getTableName($name)
    {
        @trigger_error('AbstractMigration::getTableName() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return 'kimai2_' . $name;
    }

    /**
     * @see https://github.com/doctrine/migrations/issues/1104
     */
    public function isTransactional(): bool
    {
        return false;
    }

    /**
     * @deprecated since 1.14 - will be removed with 2.0
     */
    protected function isSupportingForeignKeys(): bool
    {
        @trigger_error('isSupportingForeignKeys() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return true;
    }

    /**
     * @param Schema $schema
     * @throws Exception
     */
    public function preUp(Schema $schema): void
    {
        $this->abortIfPlatformNotSupported();
    }

    /**
     * @param Schema $schema
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
    protected function abortIfPlatformNotSupported()
    {
        $platform = $this->getPlatform();
        if (!$this->isPlatformMysql()) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }
    }

    /**
     * @deprecated since 1.14 - will be removed with 2.0
     */
    protected function isPlatformSqlite(): bool
    {
        @trigger_error('isPlatformSqlite() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return ($this->getPlatform() === 'sqlite');
    }

    protected function isPlatformMysql(): bool
    {
        return ($this->getPlatform() === 'mysql');
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getPlatform()
    {
        return $this->connection->getDatabasePlatform()->getName();
    }

    /**
     * @deprecated since 1.14 - will be removed with 2.0
     */
    protected function addSqlDropIndex($indexName, $tableName)
    {
        @trigger_error('addSqlDropIndex() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        $this->addSql('DROP INDEX ' . $indexName . ' ON ' . $tableName);
    }

    protected function preventEmptyMigrationWarning(): void
    {
        $this->addSql('#prevent empty warning - no SQL to execute');
    }
}
