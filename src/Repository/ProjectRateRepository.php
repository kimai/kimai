<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectRate;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<ProjectRate>
 */
class ProjectRateRepository extends EntityRepository
{
    public function saveRate(ProjectRate $rate): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($rate);
        $entityManager->flush();
    }

    public function deleteRate(ProjectRate $rate): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($rate);
        $entityManager->flush();
    }

    /**
     * @param Project $project
     * @return ProjectRate[]
     */
    public function getRatesForProject(Project $project): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('r, u, p')
            ->from(ProjectRate::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.project', 'p')
            ->andWhere(
                $qb->expr()->eq('r.project', ':project')
            )
            ->addOrderBy('u.alias')
            ->setParameter('project', $project)
        ;

        return $qb->getQuery()->getResult();
    }
}
