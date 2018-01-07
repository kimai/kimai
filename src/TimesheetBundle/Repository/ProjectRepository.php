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

use AppBundle\Repository\AbstractRepository;
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Model\ProjectStatistic;
use TimesheetBundle\Repository\Query\ProjectQuery;

/**
 * Class ProjectRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectRepository extends AbstractRepository
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
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     * @param ProjectQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function findByQuery(ProjectQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        // if we join activities, the maxperpage limit will limit the list due to the raised amount of rows by projects * activities
        $qb->select('p', 'c')
            ->from('TimesheetBundle:Project', 'p')
            ->join('p.customer', 'c')
            ->orderBy('p.' . $query->getOrderBy(), $query->getOrder());

        if ($query->getVisibility() === ProjectQuery::SHOW_VISIBLE) {
            $qb->andWhere('p.visible = 1');
            // TODO check for visibility of customer
        } elseif ($query->getVisibility() === ProjectQuery::SHOW_HIDDEN) {
            $qb->andWhere('p.visible = 0');
            // TODO check for visibility of customer
        }

        return $this->getPager($qb->getQuery(), $query->getPage(), $query->getPageSize());
    }
}
