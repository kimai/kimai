<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Configuration;
use App\Form\Model\SystemConfiguration;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;

/**
 * @extends EntityRepository<Configuration>
 * @internal use App\Configuration\ConfigurationService instead
 * @final
 */
class ConfigurationRepository extends EntityRepository
{
    public function saveConfiguration(Configuration $configuration): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($configuration);
        $entityManager->flush();
    }

    /**
     * @return array<string, string>
     */
    public function getConfigurations(): array
    {
        $query = $this->createQueryBuilder('s')->select('s.name')->addSelect('s.value')->getQuery();
        /** @var array<int, array<'name'|'value', string>> $result */
        $result = $query->getArrayResult();

        $all = [];
        foreach ($result as $row) {
            $all[$row['name']] = $row['value'];
        }

        return $all;
    }

    public function saveSystemConfiguration(SystemConfiguration $model): void
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
    }
}
