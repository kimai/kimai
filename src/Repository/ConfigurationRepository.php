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
 * @extends EntityRepository<Configuration>
 * @final
 */
class ConfigurationRepository extends EntityRepository implements ConfigLoaderInterface
{
    private const CACHE_KEY = 'ConfigurationRepository_All';
    /**
     * @var array<string, Configuration>
     */
    private static array $cacheAll = [];
    private static bool $initialized = false;

    public function clearCache(): void
    {
        self::$cacheAll = [];
        self::$initialized = false;

        $cache = $this->getEntityManager()->getConfiguration()->getResultCache();
        if ($cache !== null && $cache->hasItem(self::CACHE_KEY)) {
            $cache->deleteItem(self::CACHE_KEY);
        }
    }

    private function prefillCache(): void
    {
        if (self::$initialized === true) {
            return;
        }

        $query = $this->createQueryBuilder('s')->getQuery();
        $query->enableResultCache(86400, self::CACHE_KEY);

        $configs = $query->getResult();
        foreach ($configs as $config) {
            self::$cacheAll[$config->getName()] = $config;
        }
        self::$initialized = true;
    }

    public function saveConfiguration(Configuration $configuration): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($configuration);
        $entityManager->flush();
        $this->clearCache();
    }

    /**
     * @return Configuration[]
     */
    public function getConfigurations(): array
    {
        $this->prefillCache();

        return array_values(self::$cacheAll);
    }

    public function getConfiguration(string $name): ?Configuration
    {
        $this->prefillCache();

        if (!\array_key_exists($name, self::$cacheAll)) {
            return null;
        }

        return self::$cacheAll[$name];
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
