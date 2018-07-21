<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

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
     */
    protected function getTableName($name)
    {
        return getenv('DATABASE_PREFIX') . $name;
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\DBALException
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
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
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
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function addSqlDropIndex($indexName, $tableName)
    {
        $dropSql = 'DROP INDEX ' . $indexName;
        if ($this->getPlatform() === 'mysql') {
            $dropSql .= ' ON ' . $tableName;
        }
        $this->addSql($dropSql);
    }
}
