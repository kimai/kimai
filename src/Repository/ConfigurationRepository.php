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
use Doctrine\ORM\Query;

class ConfigurationRepository extends EntityRepository implements ConfigLoaderInterface
{
    /**
     * @param string $prefix
     * @return Configuration[]
     */
    public function getConfiguration(?string $prefix = null): array
    {
        if (null === $prefix) {
            return $this->findAll();
        }

        $qb = $this->createQueryBuilder('c');
        $qb
            ->select('c')
            ->where($qb->expr()->like('c.name', ':prefix'))
            ->setParameter(':prefix', $prefix . '%');

        return $qb->getQuery()->getResult(Query::HYDRATE_OBJECT);
    }

    public function saveSystemConfiguration(SystemConfiguration $model)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            foreach ($model->getConfiguration() as $configuration) {
                $entity = $this->findOneBy(['name' => $configuration->getName()]);
                $value = $configuration->getValue();

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
    }
}
