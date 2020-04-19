<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration as BaseAbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all Doctrine migrations.
 */
abstract class AbstractMigration extends BaseAbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param string $name
     * @return string
     * @deprecated since 0.9 - will be removed with 2.0
     */
    protected function getTableName($name)
    {
        return 'kimai2_' . $name;
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
     * @throws DBALException
     */
    public function preUp(Schema $schema): void
    {
        $this->abortIfPlatformNotSupported();
        $this->deactivateForeignKeysOnSqlite();
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     */
    public function postUp(Schema $schema): void
    {
        $this->activateForeignKeysOnSqlite();
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     */
    public function preDown(Schema $schema): void
    {
        $this->abortIfPlatformNotSupported();
        $this->deactivateForeignKeysOnSqlite();
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     */
    public function postDown(Schema $schema): void
    {
        $this->activateForeignKeysOnSqlite();
    }

    /**
     * Abort the migration is the current platform is not supported.
     *
     * @throws DBALException
     */
    protected function abortIfPlatformNotSupported()
    {
        $platform = $this->getPlatform();
        if (!\in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }
    }

    /**
     * @return bool
     * @throws DBALException
     */
    protected function isPlatformSqlite()
    {
        return ($this->getPlatform() === 'sqlite');
    }

    /**
     * @return bool
     * @throws DBALException
     */
    protected function isPlatformMysql()
    {
        return ($this->getPlatform() === 'mysql');
    }

    /**
     * @return string
     * @throws DBALException
     */
    protected function getPlatform()
    {
        return $this->connection->getDatabasePlatform()->getName();
    }

    /**
     * Call me like this:
     * $schema = $this->getClassMetaData(User::class);
     *
     * @param string $entityName
     * @return ClassMetadata
     */
    protected function getClassMetaData($entityName)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        return $em->getClassMetadata($entityName);
    }

    /**
     * we do it via addSql instead of $schema->getTable($users)->dropIndex()
     * otherwise the commands will be executed as last ones.
     *
     * @param string $indexName
     * @param string $tableName
     * @throws DBALException
     */
    protected function addSqlDropIndex($indexName, $tableName)
    {
        $dropSql = 'DROP INDEX ' . $indexName;
        if (!$this->isPlatformSqlite()) {
            $dropSql .= ' ON ' . $tableName;
        }
        $this->addSql($dropSql);
    }
}
