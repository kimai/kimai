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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all Doctrine migrations.
 *
 * @codeCoverageIgnore
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
        if (!\in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function isPlatformSqlite()
    {
        return ($this->getPlatform() === 'sqlite');
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function isPlatformMysql()
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

    protected function getClassMetaData(string $className): ClassMetadata
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        return $em->getClassMetadata($className);
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
