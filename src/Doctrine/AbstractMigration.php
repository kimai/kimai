<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use Doctrine\Migrations\AbstractMigration as BaseAbstractMigration;
use Doctrine\ORM\Tools\SchemaTool;
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
     * Call me like this:
     * $schema = $this->getClassMetaData(User::class);
     *
     * @param string $entityName
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    protected function getClassMetaData($entityName)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);

        return $em->getClassMetadata($entityName);
    }
}
