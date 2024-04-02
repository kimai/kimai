<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\CustomerMonthlyProjects;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

final class CustomerMonthlyProjectsRepository
{
    public function __construct(
        private readonly TimesheetRepository $repository,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * @param User[] $users
     * @return array
     * @internal
     */
    public function getGroupedByCustomerProjectActivityUser(DateTime $begin, DateTime $end, array $users, ?Customer $customer): array
    {
        $stats = [];

        $qb = $this->repository->createQueryBuilder('t');
        $qb
            ->select('COALESCE(SUM(t.rate), 0) as rate')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('IDENTITY(t.user) as user')
            ->addSelect('IDENTITY(t.activity) as activity')
            ->addSelect('IDENTITY(t.project) as project')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.begin', ':begin', ':end'))
            ->andWhere($qb->expr()->in('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $users)
            ->groupBy('project')
            ->addGroupBy('activity')
            ->addGroupBy('user')
        ;

        if ($customer !== null) {
            $qb2 = $this->entityManager->createQueryBuilder();

            $qb2->select('p.id')
                ->from(Project::class, 'p')
                ->andWhere($qb2->expr()->eq('p.customer', ':customer'))
                ->setParameter('customer', $customer->getId())
            ;
            $projectIds = $qb2->getQuery()->getSingleColumnResult();

            $qb
                ->andWhere($qb->expr()->in('t.project', ':project'))
                ->setParameter('project', array_values($projectIds))
            ;
        }

        $results = $qb->getQuery()->getResult();

        $projectIds = [];
        $activityIds = [];
        $userIds = [];

        foreach ($results as $row) {
            $projectId = $row['project'];
            $activityId = $row['activity'];
            $userId = $row['user'];

            $projectIds[$projectId] = $projectId;
            $activityIds[$activityId] = $activityId;
            $userIds[$userId] = $userId;

            if (!isset($stats[$projectId])) {
                $stats[$projectId] = [
                    'id' => $projectId,
                    'customer' => '',
                    'customer_id' => null,
                    'name' => null,
                    'activities' => [],
                    'duration' => 0,
                    'rate' => 0,
                    'internalRate' => 0,
                    'max_users' => 0,
                ];
            }

            $stats[$projectId]['duration'] += (int) $row['duration'];
            $stats[$projectId]['rate'] += (int) $row['rate'];
            $stats[$projectId]['internalRate'] += (int) $row['internalRate'];

            if (!isset($stats[$projectId]['activities'][$activityId])) {
                $stats[$projectId]['activities'][$activityId] = [
                    'id' => $activityId,
                    'name' => null,
                    'users' => [],
                    'duration' => 0,
                    'rate' => 0,
                    'internalRate' => 0,
                ];
            }

            $stats[$projectId]['activities'][$activityId]['duration'] += (int) $row['duration'];
            $stats[$projectId]['activities'][$activityId]['rate'] += (int) $row['rate'];
            $stats[$projectId]['activities'][$activityId]['internalRate'] += (int) $row['internalRate'];

            if (!isset($stats[$projectId]['activities'][$activityId]['users'][$userId])) {
                $stats[$projectId]['activities'][$activityId]['users'][$userId] = [
                    'id' => $userId,
                    'name' => null,
                    'duration' => 0,
                    'rate' => 0,
                    'internalRate' => 0,
                ];
            }

            $stats[$projectId]['activities'][$activityId]['users'][$userId]['duration'] += (int) $row['duration'];
            $stats[$projectId]['activities'][$activityId]['users'][$userId]['rate'] += (int) $row['rate'];
            $stats[$projectId]['activities'][$activityId]['users'][$userId]['internalRate'] += (int) $row['internalRate'];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('a.id, a.name')
            ->from(Activity::class, 'a', 'a.id')
            ->where($qb->expr()->in('a.id', ':id'))
            ->setParameter('id', array_values($activityIds))
        ;
        $activities = $qb->getQuery()->getResult();

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('p.id, p.name, c.id as customer_id, c.name as customer, c.currency')
            ->from(Project::class, 'p', 'p.id')
            ->leftJoin(Customer::class, 'c', Join::WITH, 'c.id = p.customer')
            ->where($qb->expr()->in('p.id', ':id'))
            ->setParameter('id', array_values($projectIds))
        ;
        $projects = $qb->getQuery()->getResult();

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('u')
            ->from(User::class, 'u', 'u.id')
            ->where($qb->expr()->in('u.id', ':id'))
            ->setParameter('id', array_values($userIds))
        ;
        $users = $qb->getQuery()->getResult();

        foreach (array_keys($stats) as $pid) {
            $stats[$pid]['name'] = $projects[$pid]['name'];
            $stats[$pid]['customer'] = $projects[$pid]['customer'];
            $stats[$pid]['customer_id'] = $projects[$pid]['customer_id'];
            foreach (array_keys($stats[$pid]['activities']) as $aid) {
                $stats[$pid]['activities'][$aid]['name'] = $activities[$aid]['name'];
                foreach (array_keys($stats[$pid]['activities'][$aid]['users']) as $uid) {
                    $stats[$pid]['activities'][$aid]['users'][$uid]['name'] = $users[$uid]->getDisplayName();
                }
                $stats[$pid]['max_users'] = max($stats[$pid]['max_users'], \count($stats[$pid]['activities'][$aid]['users']));
            }
        }

        return [
            'stats' => $stats,
            'projects' => $projects,
            'activities' => $activities,
            'users' => $users,
        ];
    }
}
