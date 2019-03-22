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
use Doctrine\ORM\ORMException;

class ConfigurationRepository extends AbstractRepository
{
    public function getAllConfigurations(): array
    {
        $configs = $this->findAll();
        $all = [];
        /** @var Configuration $config */
        foreach ($configs as $config) {
            $all[$config->getName()] = $config->getValue();
        }

        return $all;
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
