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

    public function isTransactional(): bool
    {
        if ($this->isPlatformSqlite()) {
            // does fail if we use transactions, as tables are re-created and foreign keys would fail
            return false;
        }

        return false;

        // @see https://github.com/doctrine/migrations/issues/1104
        // return true;
    }

    /**
     * Whether we should deactivate foreign key support for SQLite.
     * This is required, if columns are changed.
     * SQLite will drop the table and all referenced data, if we don't deactivate this.
     *
     * @return bool
     */
    protected function isSupportingForeignKeys(): bool
    {
        return true;
    }

    protected function deactivateForeignKeysOnSqlite()
    {
        if ($this->isPlatformSqlite() && !$this->isSupportingForeignKeys()) {
            $this->addSql('PRAGMA foreign_keys = OFF;');
        }
    }

    private function activateForeignKeysOnSqlite()
    {
        if ($this->isPlatformSqlite() && !$this->isSupportingForeignKeys()) {
            $this->addSql('PRAGMA foreign_keys = ON;');
        }
    }

    /**
     * @param Schema $schema
     * @throws Exception
     */
    public function preUp(Schema $schema): void
    {
        $this->abortIfPlatformNotSupported();
        $this->deactivateForeignKeysOnSqlite();
    }

    /**
     * @param Schema $schema
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $this->activateForeignKeysOnSqlite();
    }

    /**
     * @param Schema $schema
     * @throws Exception
     */
    public function preDown(Schema $schema): void
    {
        $this->abortIfPlatformNotSupported();
        $this->deactivateForeignKeysOnSqlite();
    }

    /**
     * @param Schema $schema
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        $this->activateForeignKeysOnSqlite();
    }

    /**
     * Abort the migration is the current platform is not supported.
     *
     * @throws Exception
     */
    protected function abortIfPlatformNotSupported()
    {
        $platform = $this->getPlatform();
        if (!$this->isPlatformMysql() && !$this->isPlatformSqlite()) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function isPlatformSqlite()
    {
        return \in_array(strtolower($this->getPlatform()), ['sqlite']);
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function isPlatformMysql()
    {
        return \in_array(strtolower($this->getPlatform()), ['mysql', 'mysqli']);
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
     * We do it via addSql instead of $schema->getTable($users)->dropIndex()
     * otherwise the commands will be executed as last ones.
     *
     * @param string $indexName
     * @param string $tableName
     * @throws Exception
     */
    protected function addSqlDropIndex(string $indexName, string $tableName)
    {
        $dropSql = 'DROP INDEX ' . $indexName;
        if (!$this->isPlatformSqlite()) {
            $dropSql .= ' ON ' . $tableName;
        }
        $this->addSql($dropSql);
    }
}
