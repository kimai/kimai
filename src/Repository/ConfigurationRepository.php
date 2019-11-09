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
use Doctrine\ORM\ORMException;

class ConfigurationRepository extends EntityRepository implements ConfigLoaderInterface
{
    private static $cache = null;

    private function clearCache()
    {
        static::$cache = null;
    }

    private function prefillCache()
    {
        if (null !== static::$cache) {
            return;
        }

        /** @var Configuration[] $configs */
        $configs = $this->findAll();
        static::$cache = [];
        foreach ($configs as $config) {
            $key = substr($config->getName(), 0, strpos($config->getName(), '.'));
            if (!array_key_exists($key, static::$cache)) {
                static::$cache[$key] = [];
            }
            static::$cache[$key][] = $config;
        }
    }

    /**
     * @param string $prefix
     * @return Configuration[]
     */
    public function getConfiguration(?string $prefix = null): array
    {
        $this->prefillCache();

        if (null === $prefix) {
            return array_values(static::$cache);
        }

        if (!array_key_exists($prefix, static::$cache)) {
            return [];
        }

        return static::$cache[$prefix];
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
