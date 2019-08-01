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
use App\Model\Statistic\Day;
use App\Model\Statistic\Month;
use App\Model\Statistic\Year;
use App\Model\TimesheetStatistic;
use App\Repository\Loader\TimesheetLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\TimesheetQuery;
use DateTime;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class TimesheetRepository extends EntityRepository
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
    public function delete(Timesheet $timesheet)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($timesheet);
        $entityManager->flush();
    }

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
        $entityManager->flush($entry);

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

            case self::STATS_QUERY_MONTHLY:
                return $this->getMonthlyStats($user, $begin, $end);

            case 'daily':
                return $this->getDailyStats($user, $begin, $end);

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
     * @param string $select
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
        ;

        if (!empty($begin)) {
            $qb->where($qb->expr()->gt('t.begin', ':from'));
            $qb->setParameter('from', $begin, Type::DATETIME);
        } else {
            $qb->where($qb->expr()->isNotNull('t.begin'));
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
            $month->setTotalDuration((int) $statRow['duration'])
                ->setTotalRate((float) $statRow['rate']);
            $years[$curYear]->setMonth($month);
        }

        return $years;
    }

    /**
     * @param DateTime $begin
     * @param DateTime $end
     * @param User|null $user
     * @return mixed
     */
    protected function getDailyData(DateTime $begin, DateTime $end, ?User $user = null)
    {
        $query = new TimesheetQuery();
        $query
            ->setBegin($begin)
            ->setEnd($end)
            ->setUser($user)
            ->setState(TimesheetQuery::STATE_STOPPED)
        ;
        $timesheets = $this->getTimesheetsForQuery($query);

        $results = [];
        /** @var Timesheet $result */
        foreach ($timesheets as $result) {
            $timezone = new \DateTimeZone($result->getTimezone());
            /** @var \DateTime $beginTmp */
            $beginTmp = $result->getBegin();
            $beginTmp->setTimezone($timezone);
            /** @var DateTime $endTmp */
            $endTmp = $result->getEnd();
            $endTmp->setTimezone($timezone);
            $dateKeyEnd = $endTmp->format('Ymd');

            do {
                $dateKey = $beginTmp->format('Ymd');

                if (!isset($results[$dateKey])) {
                    $results[$dateKey] = [
                        'rate' => 0,
                        'duration' => 0,
                        'month' => $beginTmp->format('n'),
                        'year' => $beginTmp->format('Y'),
                        'day' => $beginTmp->format('j'),
                        'details' => []
                    ];
                }

                if ($dateKey !== $dateKeyEnd) {
                    $newDateBegin = clone $beginTmp;
                    $newDateBegin->add(new \DateInterval('P1D'));
                    $newDateBegin->setTime(0, 0, 0);
                } else {
                    $newDateBegin = clone $endTmp;
                }

                $duration = $newDateBegin->getTimestamp() - $beginTmp->getTimestamp();
                $durationPercent = $duration / $result->getDuration();
                $rate = $result->getRate() * $durationPercent;

                $results[$dateKey]['rate'] += $rate;
                $results[$dateKey]['duration'] += $duration;
                $detailsId = $result->getProject()->getCustomer()->getId() . '_' . $result->getProject()->getId();
                if (!isset($results[$dateKey]['details'][$detailsId])) {
                    $results[$dateKey]['details'][$detailsId] = [
                        'project' => $result->getProject(),
                        'activity' => $result->getActivity(),
                        'duration' => 0,
                        'rate' => 0,
                    ];

                    $results[$dateKey]['details'][$detailsId]['duration'] += $duration;
                    $results[$dateKey]['details'][$detailsId]['rate'] += $rate;
                }

                $beginTmp = $newDateBegin;

                if ((int) $end->format('Ymd') < (int) $newDateBegin->format('Ymd')) {
                    break 1;
                }
            } while ($dateKey !== $dateKeyEnd);
        }

        ksort($results);

        foreach ($results as $key => $value) {
            $results[$key]['details'] = array_values($results[$key]['details']);
        }
        $results = array_values($results);

        return $results;
    }

    /**
     * @param User $user
     * @param DateTime $begin
     * @param DateTime $end
     * @return Day[]
     * @throws \Exception
     */
    public function getDailyStats(User $user, DateTime $begin, DateTime $end): array
    {
        $results = $this->getDailyData($begin, $end, $user);

        /** @var Day[] $days */
        $days = [];

        // prefill the array
        $tmp = clone $end;
        $until = (int) $begin->format('Ymd');
        while ((int) $tmp->format('Ymd') > $until) {
            $tmp->modify('-1 day');
            $last = clone $tmp;
            $days[$last->format('Ymd')] = new Day($last, 0, 0.00);
        }

        foreach ($results as $statRow) {
            $dateTime = new DateTime();
            $dateTime->setDate($statRow['year'], $statRow['month'], $statRow['day']);
            $dateTime->setTime(0, 0, 0);
            $day = new Day($dateTime, (int) $statRow['duration'], (float) $statRow['rate']);
            $days[$dateTime->format('Ymd')] = $day;
        }

        ksort($days);

        return array_values($days);
    }

    /**
     * @param User $user
     * @return Timesheet[]|null
     */
    public function getActiveEntries(User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t')
            ->from(Timesheet::class, 't')
            ->andWhere($qb->expr()->isNotNull('t.begin'))
            ->andWhere($qb->expr()->isNull('t.end'))
            ->orderBy('t.begin', 'DESC');

        if (null !== $user) {
            $qb->andWhere('t.user = :user');
            $qb->setParameter('user', $user);
        }

        $results = $qb->getQuery()->getResult();

        $loader = new TimesheetLoader($qb->getEntityManager());
        $loader->loadResults($results);

        return $results;
    }

    /**
     * @param User $user
     * @param int $hardLimit
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

    public function getPagerfantaForQuery(TimesheetQuery $query): Pagerfanta
    {
        $paginator = new Pagerfanta($this->getPaginatorForQuery($query));
        $paginator->setMaxPerPage($query->getPageSize());
        $paginator->setCurrentPage($query->getPage());

        return $paginator;
    }

    protected function getPaginatorForQuery(TimesheetQuery $query): PaginatorInterface
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->countDistinct('t.id'))
        ;
        $counter = (int) $qb->getQuery()->getSingleScalarResult();

        $qb = $this->getQueryBuilderForQuery($query);

        return new LoaderPaginator(new TimesheetLoader($qb->getEntityManager()), $qb, $counter);
    }

    /**
     * @param TimesheetQuery $query
     * @return Timesheet[]
     */
    public function getTimesheetsForQuery(TimesheetQuery $query): iterable
    {
        // this is using the paginator internally, as it will load all joined entities into the working unit
        // do not "optimize" to use the query directly, as it would results in hundreds of additional lazy queries
        $paginator = $this->getPaginatorForQuery($query);

        return $paginator->getAll();
    }

    private function getQueryBuilderForQuery(TimesheetQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('t')
            ->from(Timesheet::class, 't')
        ;

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
        } elseif (TimesheetQuery::STATE_STOPPED == $query->getState()) {
            $qb->andWhere($qb->expr()->isNotNull('t.end'));
        }

        if (null !== $query->getEnd()) {
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
                $qb->join('t.project', 'p');
                $qb->andWhere('p.customer = :customer')
                    ->setParameter('customer', $query->getCustomer());
            }
        }

        $tags = $query->getTags();
        if (!empty($tags)) {
            $qb->andWhere($qb->expr()->isMemberOf(':tags', 't.tags'))
                ->setParameter('tags', $query->getTags());
        }

        $qb->orderBy('t.' . $query->getOrderBy(), $query->getOrder());

        return $qb;
    }

    /**
     * @param User|null $user
     * @param DateTime|null $startFrom
     * @param int $limit
     * @return array|mixed
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getRecentActivities(User $user = null, \DateTime $startFrom = null, $limit = 10)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->max('t.id') . ' AS maxid')
            ->from(Timesheet::class, 't')
            ->indexBy('t', 't.id')
            ->join('t.activity', 'a')
            ->join('t.project', 'p')
            ->join('p.customer', 'c')
            ->andWhere($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->eq('a.visible', ':visible'))
            ->andWhere($qb->expr()->eq('p.visible', ':visible'))
            ->andWhere($qb->expr()->eq('c.visible', ':visible'))
            ->groupBy('a.id', 'p.id')
            ->orderBy('maxid', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('visible', true, \PDO::PARAM_BOOL)
        ;

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        if (null !== $startFrom) {
            $qb->andWhere($qb->expr()->gt('t.begin', ':begin'))
                ->setParameter('begin', $startFrom);
        }

        $results = $qb->getQuery()->getScalarResult();

        if (empty($results)) {
            return [];
        }

        $ids = array_column($results, 'maxid');

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('t')
            ->from(Timesheet::class, 't')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->orderBy('t.end', 'DESC')
        ;

        $results = $qb->getQuery()->getResult();

        $loader = new TimesheetLoader($qb->getEntityManager());
        $loader->loadResults($results);

        return $results;
    }
}
