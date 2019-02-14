<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\Statistic\Month;
use App\Model\Statistic\Year;
use App\Model\TimesheetStatistic;
use App\Repository\Query\TimesheetQuery;
use DateTime;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class TimesheetRepository extends AbstractRepository
{
    public const STATS_QUERY_DURATION = 'duration';
    public const STATS_QUERY_RATE = 'rate';
    public const STATS_QUERY_USER = 'users';
    public const STATS_QUERY_AMOUNT = 'amount';
    public const STATS_QUERY_ACTIVE = 'active';
    public const STATS_QUERY_MONTHLY = 'monthly';

    /**
     * @param Timesheet $timesheet
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Timesheet $timesheet)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($timesheet);
        $entityManager->flush();
    }

    /**
     * @param Timesheet $entry
     * @return bool
     * @throws RepositoryException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function stopRecording(Timesheet $entry)
    {
        if (null !== $entry->getEnd()) {
            throw new RepositoryException('Timesheet entry already stopped');
        }

        // seems to be necessary so Doctrine will recognize a changed timestamp
        $begin = clone $entry->getBegin();
        $end = new \DateTime('now', $begin->getTimezone());

        $entry->setBegin($begin);
        $entry->setEnd($end);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($entry);
        $entityManager->flush();

        return true;
    }

    /**
     * @param string $type
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @param User|null $user
     * @return int|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getStatistic(string $type, ?DateTime $begin, ?DateTime $end, ?User $user)
    {
        switch ($type) {
            case self::STATS_QUERY_ACTIVE:
                return count($this->getActiveEntries($user));
                break;
            case self::STATS_QUERY_MONTHLY:
                return $this->getMonthlyStats($user, $begin, $end);
                break;
            case self::STATS_QUERY_DURATION:
                $what = 'SUM(t.duration)';
                break;
            case self::STATS_QUERY_RATE:
                $what = 'SUM(t.rate)';
                break;
            case self::STATS_QUERY_USER:
                $what = 'COUNT(DISTINCT(t.user))';
                break;
            case self::STATS_QUERY_AMOUNT:
                $what = 'COUNT(t.id)';
                break;
            default:
                throw new \InvalidArgumentException('Invalid query type: ' . $type);
        }

        return $this->queryTimeRange($what, $begin, $end, $user);
    }

    /**
     * @param $select
     * @param User $user
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function queryThisMonth($select, User $user)
    {
        $begin = new DateTime('first day of this month 00:00:00');
        $end = new DateTime('last day of this month 23:59:59');

        return $this->queryTimeRange($select, $begin, $end, $user);
    }

    /**
     * @param string $select
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @param User|null $user
     * @return int|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function queryTimeRange(string $select, ?DateTime $begin, ?DateTime $end, ?User $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($select)
            ->from(Timesheet::class, 't');

        if (!empty($begin)) {
            $qb
                ->andWhere($qb->expr()->gt('t.begin', ':from'))
                ->setParameter('from', $begin, Type::DATETIME);
        }

        if (!empty($end)) {
            $qb
                ->andWhere($qb->expr()->lt('t.end', ':to'))
                ->setParameter('to', $end, Type::DATETIME);
        }

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return empty($result) ? 0 : $result;
    }

    /**
     * Fetch statistic data for one user.
     *
     * @param User $user
     * @return TimesheetStatistic
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUserStatistics(User $user)
    {
        $durationTotal = $this->getStatistic(self::STATS_QUERY_DURATION, null, null, $user);
        $recordsTotal = $this->getStatistic(self::STATS_QUERY_AMOUNT, null, null, $user);
        $rateTotal = $this->getStatistic(self::STATS_QUERY_RATE, null, null, $user);
        $amountMonth = $this->queryThisMonth('SUM(t.rate)', $user);
        $durationMonth = $this->queryThisMonth('SUM(t.duration)', $user);
        $firstEntry = $this->getEntityManager()
            ->createQuery('SELECT MIN(t.begin) FROM ' . Timesheet::class . ' t WHERE t.user = :user')
            ->setParameter('user', $user)
            ->getSingleScalarResult();

        $stats = new TimesheetStatistic();
        $stats->setAmountTotal($rateTotal);
        $stats->setDurationTotal($durationTotal);
        $stats->setAmountThisMonth($amountMonth);
        $stats->setDurationThisMonth($durationMonth);
        $stats->setFirstEntry(new DateTime($firstEntry));
        $stats->setRecordsTotal($recordsTotal);

        return $stats;
    }

    /**
     * Returns an array of Year statistics.
     *
     * @param User|null $user
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @return Year[]
     */
    public function getMonthlyStats(User $user = null, ?DateTime $begin = null, ?DateTime $end = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('SUM(t.rate) as rate, SUM(t.duration) as duration, MONTH(t.begin) as month, YEAR(t.begin) as year')
            ->from(Timesheet::class, 't')
            ->where($qb->expr()->gt('t.begin', ':from'))
        ;

        if (!empty($begin)) {
            $qb->setParameter('from', $begin, Type::DATETIME);
        } else {
            $qb->setParameter('from', 0);
        }

        if (!empty($end)) {
            $qb->andWhere($qb->expr()->lt('t.end', ':to'))
                ->setParameter('to', $end, Type::DATETIME);
        } else {
            $qb->andWhere($qb->expr()->isNotNull('t.end'));
        }

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        $qb
            ->orderBy('year', 'DESC')
            ->addOrderBy('month', 'ASC')
            ->groupBy('year')
            ->addGroupBy('month');

        $years = [];
        foreach ($qb->getQuery()->execute() as $statRow) {
            $curYear = $statRow['year'];

            if (!isset($years[$curYear])) {
                $year = new Year($curYear);
                for ($i = 1; $i < 13; $i++) {
                    $month = $i < 10 ? '0' . $i : (string) $i;
                    $year->setMonth(new Month($month));
                }
                $years[$curYear] = $year;
            }

            $month = new Month($statRow['month']);
            $month->setTotalDuration($statRow['duration'])
                ->setTotalRate($statRow['rate']);
            $years[$curYear]->setMonth($month);
        }

        return $years;
    }

    /**
     * @param User $user
     * @return Timesheet[]|null
     */
    public function getActiveEntries(User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t', 'a', 'p', 'c')
            ->from(Timesheet::class, 't')
            ->join('t.activity', 'a')
            ->join('t.project', 'p')
            ->join('p.customer', 'c')
            ->where($qb->expr()->gt('t.begin', '0'))
            ->andWhere($qb->expr()->isNull('t.end'))
            ->orderBy('t.begin', 'DESC');

        $params = [];

        if (null !== $user) {
            $qb->andWhere('t.user = :user');
            $params['user'] = $user;
        }

        return $qb->getQuery()->execute($params);
    }

    /**
     * @param User $user
     * @param int $limit
     * @return int
     * @throws RepositoryException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function stopActiveEntries(User $user, int $hardLimit)
    {
        $counter = 0;
        $activeEntries = $this->getActiveEntries($user);

        // reduce limit by one:
        // this method is only called when a new entry is started
        // -> all entries, including the new one must not exceed the $limit
        $limit = $hardLimit - 1;

        if (count($activeEntries) > $limit) {
            $i = 1;
            foreach ($activeEntries as $activeEntry) {
                if ($i > $limit) {
                    if ($hardLimit > 1) {
                        throw new \Exception('timesheet.start.exceeded_limit');
                    }

                    $this->stopRecording($activeEntry);
                    $counter++;
                }
                $i++;
            }
        }

        return $counter;
    }

    /**
     * @param TimesheetQuery $query
     * @return QueryBuilder|Pagerfanta|array
     */
    public function findByQuery(TimesheetQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t', 'a', 'p', 'c', 'u')
            ->from(Timesheet::class, 't')
            ->leftJoin('t.activity', 'a')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.customer', 'c')
            ->orderBy('t.' . $query->getOrderBy(), $query->getOrder());

        if (null !== $query->getUser()) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $query->getUser());
        }

        if (null !== $query->getBegin()) {
            $qb->andWhere('t.begin >= :begin')
                ->setParameter('begin', $query->getBegin());
        }

        if (TimesheetQuery::STATE_RUNNING == $query->getState()) {
            $qb->andWhere($qb->expr()->isNull('t.end'));
        }

        if (TimesheetQuery::STATE_STOPPED == $query->getState()) {
            $qb->andWhere($qb->expr()->isNotNull('t.end'));

            if (null !== $query->getEnd()) {
                $qb->andWhere('t.end <= :end')
                    ->setParameter('end', $query->getEnd());
            }
        } elseif (null !== $query->getEnd()) {
            $qb->andWhere('t.begin <= :end')
                ->setParameter('end', $query->getEnd());
        }

        if ($query->getExported() === TimesheetQuery::STATE_EXPORTED) {
            $qb->andWhere('t.exported = :exported')->setParameter('exported', true);
        } elseif ($query->getExported() === TimesheetQuery::STATE_NOT_EXPORTED) {
            $qb->andWhere('t.exported = :exported')->setParameter('exported', false);
        }

        if (null !== $query->getActivity()) {
            $qb->andWhere('t.activity = :activity')
                ->setParameter('activity', $query->getActivity());
        }

        if (null === $query->getActivity() || ($query->getActivity() instanceof Activity && null === $query->getActivity()->getProject())) {
            if (null !== $query->getProject()) {
                $qb->andWhere('t.project = :project')
                    ->setParameter('project', $query->getProject());
            } elseif (null !== $query->getCustomer()) {
                $qb->andWhere('p.customer = :customer')
                    ->setParameter('customer', $query->getCustomer());
            }
        }

        return $this->getBaseQueryResult($qb, $query);
    }
}
