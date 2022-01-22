<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Configuration\ConfigLoaderInterface;
use App\Entity\Configuration;
use App\Form\Model\SystemConfiguration;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;

/**
 * @extends \Doctrine\ORM\EntityRepository<Configuration>
 * @final
 */
class ConfigurationRepository extends EntityRepository implements ConfigLoaderInterface
{
    private static $cacheByPrefix = [];
    private static $cacheAll = [];
    private static $initialized = false;

    public function clearCache()
    {
        self::$cacheByPrefix = [];
        self::$cacheAll = [];
        self::$initialized = false;
    }

    private function prefillCache()
    {
        if (self::$initialized === true) {
            return;
        }

        /** @var Configuration[] $configs */
        $configs = $this->findAll();
        foreach ($configs as $config) {
            $key = substr($config->getName(), 0, strpos($config->getName(), '.'));
            if (!\array_key_exists($key, self::$cacheByPrefix)) {
                self::$cacheByPrefix[$key] = [];
            }
            self::$cacheByPrefix[$key][] = $config;
            self::$cacheAll[] = $config;
        }
        self::$initialized = true;
    }

    public function getConfigurationByName(string $name): ?Configuration
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function saveConfiguration(Configuration $configuration)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($configuration);
        $entityManager->flush();
        $this->clearCache();
    }

    public function deleteConfiguration(Configuration $configuration)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($configuration);
        $entityManager->flush();
        $this->clearCache();
    }

    /**
     * @param string $prefix
     * @return Configuration[]
     */
    public function getConfiguration(?string $prefix = null): array
    {
        $this->prefillCache();

        if (null === $prefix) {
            return self::$cacheAll;
        }

        if (!\array_key_exists($prefix, self::$cacheByPrefix)) {
            return [];
        }

        return self::$cacheByPrefix[$prefix];
    }

    public function saveSystemConfiguration(SystemConfiguration $model)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            foreach ($model->getConfiguration() as $configuration) {
                $entity = $this->findOneBy(['name' => $configuration->getName()]);
                $value = $configuration->getValue();

                if (null === $value && null !== $entity) {
                    $em->remove($entity);
                    continue;
                }

                if (null === $entity) {
                    $entity = new Configuration();
                    $entity->setName($configuration->getName());
                }

                // allow to use entity types
                if (\is_object($value) && method_exists($value, 'getId')) {
                    $value = $value->getId();
                }

                $entity->setValue($value);

                $em->persist($entity);
            }

            $em->flush();
            $em->commit();
        } catch (ORMException $ex) {
            $em->rollback();
            throw $ex;
        }

        $this->clearCache();
    }
}
