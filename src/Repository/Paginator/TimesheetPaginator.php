<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Paginator;

use App\Entity\Project;
use App\Entity\Timesheet;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\AdapterInterface;

class TimesheetPaginator implements AdapterInterface
{
    /**
     * @var QueryBuilder
     */
    private $query;
    /**
     * @var int
     */
    private $results = 0;

    public function __construct(QueryBuilder $query, int $results)
    {
        $this->query = $query;
        $this->results = $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        return $this->results;
    }

    protected function getResults(Query $query)
    {
        $results = $query->execute();

        $ids = array_map(function (Timesheet $timesheet) {
            return $timesheet->getId();
        }, $results);

        $em = $this->query->getEntityManager();

        $qb = $em->createQueryBuilder();
        $projects = $qb->select('PARTIAL t.{id}', 'project')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.project', 'project')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();

        $projectIds = array_map(function (Timesheet $timesheet) {
            return $timesheet->getProject()->getId();
        }, $projects);

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL p.{id}', 'customer')
            ->from(Project::class, 'p')
            ->leftJoin('p.customer', 'customer')
            ->andWhere($qb->expr()->in('p.id', $projectIds))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL t.{id}', 'activity')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.activity', 'activity')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL t.{id}', 'user')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.user', 'user')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL t.{id}', 'tags')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.tags', 'tags')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();
/*
        // pre-fill the meta fields without left join
        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL t.{id}', 'meta')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.meta', 'meta')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();
*/
        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $query = $this->query
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->getResults($query);
    }

    public function getAll()
    {
        return $this->getResults($this->query->getQuery());
    }
}
