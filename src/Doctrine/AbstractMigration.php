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
     * @deprecated since 1.14 - will be removed with 2.0
     */
    protected function isSupportingForeignKeys(): bool
    {
        @trigger_error('isSupportingForeignKeys() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return true;
    }

    /**
     * @deprecated since 1.14 - will be removed with 2.0
     */
    protected function deactivateForeignKeysOnSqlite()
    {
        @trigger_error('deactivateForeignKeysOnSqlite() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);
    }

    /**
     * @deprecated since 1.14 - will be removed with 2.0
     */
    private function activateForeignKeysOnSqlite()
    {
        @trigger_error('activateForeignKeysOnSqlite() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     */
    public function preUp(Schema $schema): void
    {
        $this->abortIfPlatformNotSupported();
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     */
    public function preDown(Schema $schema): void
    {
        $this->abortIfPlatformNotSupported();
    }

    /**
     * Abort the migration is the current platform is not supported.
     *
     * @throws DBALException
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
     * @deprecated since 1.14 - will be removed with 2.0
     */
    protected function addSqlDropIndex($indexName, $tableName)
    {
        @trigger_error('addSqlDropIndex() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        $this->addSql('DROP INDEX ' . $indexName . ' ON ' . $tableName);
    }
}
