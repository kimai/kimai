<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\ActivityRate;
use App\Entity\Customer;
use App\Entity\CustomerRate;
use App\Entity\Project;
use App\Entity\ProjectRate;
use App\Entity\Rate;
use App\Entity\Timesheet;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

class RateRepository extends EntityRepository
{
    public function saveRate(Rate $rate)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($rate);
        $entityManager->flush();
    }

    public function deleteRate(Rate $rate)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->remove($rate);
            $em->flush();
            $em->commit();
        } catch (ORMException $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    /**
     * @param Customer $customer
     * @return Rate[]
     */
    public function getRatesForCustomer(Customer $customer): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('r, u, c')
            ->from(CustomerRate::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.customer', 'c')
            ->andWhere(
                $qb->expr()->eq('r.customer', ':customer')
            )
            ->addOrderBy('u.alias')
            ->setParameter('customer', $customer)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Project $project
     * @return Rate[]
     */
    public function getRatesForProject(Project $project): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('r, u, p')
            ->from(ProjectRate::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.project', 'p')
            ->andWhere(
                $qb->expr()->eq('r.project', ':project'),
            )
            ->addOrderBy('u.alias')
            ->setParameter('project', $project)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Activity $activity
     * @return Rate[]
     */
    public function getRatesForActivity(Activity $activity): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('r, u, a')
            ->from(ActivityRate::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.activity', 'a')
            ->andWhere(
                $qb->expr()->eq('r.activity', ':activity')
            )
            ->addOrderBy('u.alias')
            ->setParameter('activity', $activity)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Timesheet $timesheet
     * @return Rate[]
     */
    public function findMatchingRates(Timesheet $timesheet): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('r, u, a')
            ->from(ActivityRate::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.activity', 'a')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('r.user', ':user'),
                    $qb->expr()->isNull('r.user')
                ),
                $qb->expr()->orX(
                    $qb->expr()->eq('r.activity', ':activity'),
                    $qb->expr()->isNull('r.activity')
                )
            )
            ->setParameter('user', $timesheet->getUser())
            ->setParameter('activity', $timesheet->getActivity())
        ;
        $results = $qb->getQuery()->getResult();

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('r, u, p')
            ->from(ProjectRate::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.project', 'p')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('r.user', ':user'),
                    $qb->expr()->isNull('r.user')
                ),
                $qb->expr()->orX(
                    $qb->expr()->eq('r.project', ':project'),
                    $qb->expr()->isNull('r.project')
                )
            )
            ->setParameter('user', $timesheet->getUser())
            ->setParameter('project', $timesheet->getProject())
        ;
        $results = array_merge($results, $qb->getQuery()->getResult());

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('r, u, c')
            ->from(CustomerRate::class, 'r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.customer', 'c')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('r.user', ':user'),
                    $qb->expr()->isNull('r.user')
                ),
                $qb->expr()->orX(
                    $qb->expr()->eq('r.customer', ':customer'),
                    $qb->expr()->isNull('r.customer')
                )
            )
            ->setParameter('user', $timesheet->getUser())
            ->setParameter('customer', $timesheet->getProject()->getCustomer())
        ;
        $results = array_merge($results, $qb->getQuery()->getResult());

        return $results;
    }
}
