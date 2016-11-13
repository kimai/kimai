<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Repository;

use AppBundle\Entity\User;
use TimesheetBundle\Entity\Project;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use TimesheetBundle\Model\ProjectStatistic;

/**
 * Class ProjectRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectRepository extends EntityRepository
{

    /**
     * @param $id
     * @return null|Project
     */
    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * Return statistic data for all user.
     *
     * @return ProjectStatistic
     */
    public function getGlobalStatistics()
    {
        $countAll = $this->getEntityManager()
            ->createQuery('SELECT COUNT(p.id) FROM TimesheetBundle:Project p')
            ->getSingleScalarResult();

        $stats = new ProjectStatistic();
        $stats->setTotalAmount($countAll);
        return $stats;
    }

    /**
     * @param User $user
     * @return Query
     */
    protected function queryLatest(User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p')
            ->from('TimesheetBundle:Project', 'p')
            ->orderBy('p.id', 'DESC');

        return $qb->getQuery();
    }

    /**
     * @param string $orderBy
     * @return Query
     */
    protected function queryAll($orderBy = 'id')
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p')
            ->from('TimesheetBundle:Project', 'p')
            ->orderBy('p.' . $orderBy, 'ASC');

        return $qb->getQuery();
    }

    /**
     * @param User $user
     * @param int $page
     * @return Pagerfanta
     */
    public function findLatest(User $user, $page = 1)
    {
        return $this->getPager($this->queryLatest($user), $page);
    }

    /**
     * @param int $page
     *
     * @return Pagerfanta
     */
    public function findAll($page = 1)
    {
        return $this->getPager($this->queryAll(), $page);
    }

    /**
     * @param Query $query
     * @param int $page
     * @return Pagerfanta
     */
    protected function getPager(Query $query, $page = 1)
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage(25);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
