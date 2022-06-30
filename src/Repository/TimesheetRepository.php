<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\ActivityRate;
use App\Entity\CustomerRate;
use App\Entity\Project;
use App\Entity\ProjectRate;
use App\Entity\RateInterface;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Model\Statistic\Day;
use App\Model\Statistic\Month;
use App\Model\Statistic\Year;
use App\Model\TimesheetStatistic;
use App\Repository\Loader\TimesheetLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\TimesheetQuery;
use App\Repository\Result\TimesheetResult;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Exception;
use InvalidArgumentException;
use Pagerfanta\Pagerfanta;

/**
 * @extends \Doctrine\ORM\EntityRepository<Timesheet>
 */
class TimesheetRepository extends EntityRepository
{
    use RepositorySearchTrait;

    public const STATS_QUERY_DURATION = 'duration';
    public const STATS_QUERY_RATE = 'rate';
    public const STATS_QUERY_USER = 'users';
    public const STATS_QUERY_AMOUNT = 'amount';
    public const STATS_QUERY_ACTIVE = 'active';
    /**
     * @deprecated since 1.15 - use TimesheetStatisticService::getMonthlyStats() instead - will be removed with 2.0
     */
    public const STATS_QUERY_MONTHLY = 'monthly';

    /**
     * Fetches the raw data of a timesheet, to allow comparison e.g. of submitted and previously stored data.
     *
     * @param Timesheet $id
     * @return array
     */
    public function getRawData(Timesheet $id): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb
            ->select([
                't.rate',
                't.begin',
                't.end',
                't.duration',
                't.hourlyRate',
                't.billable',
                'IDENTITY(p.customer) as customer',
                'IDENTITY(t.project) as project',
                'IDENTITY(t.activity) as activity',
                'IDENTITY(t.user) as user'
            ])
            ->leftJoin(Project::class, 'p', Join::WITH, 'p.id = t.project')
            ->andWhere($qb->expr()->eq('t.id', ':id'))
            ->setParameter('id', $id)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param mixed $id
     * @param null $lockMode
     * @param null $lockVersion
     * @return Timesheet|null
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        /** @var Timesheet|null $timesheet */
        $timesheet = parent::find($id, $lockMode, $lockVersion);
        if (null === $timesheet) {
            return null;
        }

        $loader = new TimesheetLoader($this->getEntityManager());
        $loader->loadResults([$timesheet]);

        return $timesheet;
    }

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
     * @param Timesheet[] $timesheets
     * @throws Exception
     */
    public function deleteMultiple(iterable $timesheets): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            foreach ($timesheets as $timesheet) {
                $em->remove($timesheet);
            }
            $em->flush();
            $em->commit();
        } catch (Exception $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    /**
     * @deprecated since 1.11 use TimesheetService::stopTimesheet() instead
     * @codeCoverageIgnore
     */
    public function add(Timesheet $timesheet, int $maxRunningEntries)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if (null === $timesheet->getEnd()) {
                $this->stopActiveEntries($timesheet->getUser(), $maxRunningEntries, false);
            }

            $em->persist($timesheet);
            $em->flush();
            $em->commit();
        } catch (Exception $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    public function begin()
    {
        $this->getEntityManager()->beginTransaction();
    }

    public function commit()
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->commit();
    }

    public function rollback()
    {
        $this->getEntityManager()->rollback();
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
     * @param Timesheet[] $timesheets
     * @throws Exception
     */
    public function saveMultiple(array $timesheets): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            foreach ($timesheets as $timesheet) {
                $em->persist($timesheet);
            }
            $em->flush();
            $em->commit();
        } catch (Exception $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    /**
     * @deprecated since 1.11 use TimesheetService::stopTimesheet() instead
     * @codeCoverageIgnore
     *
     * @param Timesheet $entry
     * @param bool $flush
     * @return bool
     * @throws RepositoryException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function stopRecording(Timesheet $entry, bool $flush = true)
    {
        if (null !== $entry->getEnd()) {
            throw new RepositoryException('Timesheet entry already stopped');
        }

        // seems to be necessary so Doctrine will recognize a changed timestamp
        $begin = clone $entry->getBegin();
        $end = new DateTime('now', $begin->getTimezone());

        $entry->setBegin($begin);
        $entry->setEnd($end);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($entry);
        if ($flush) {
            $entityManager->flush();
        }

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
    public function getStatistic(string $type, ?DateTime $begin, ?DateTime $end, ?User $user, ?bool $billable = null)
    {
        switch ($type) {
            case self::STATS_QUERY_ACTIVE:
                return \count($this->getActiveEntries($user));

            case self::STATS_QUERY_MONTHLY:
                return $this->getMonthlyStats($begin, $end, $user);

            case 'daily':
                return $this->getDailyStats($user, $begin, $end);

            case self::STATS_QUERY_DURATION:
                $what = 'COALESCE(SUM(t.duration), 0)';
                break;
            case self::STATS_QUERY_RATE:
                $what = 'COALESCE(SUM(t.rate), 0)';
                $billable = true;
                break;
            case self::STATS_QUERY_USER:
                $what = 'COUNT(DISTINCT(t.user))';
                break;
            case self::STATS_QUERY_AMOUNT:
                $what = 'COUNT(t.id)';
                break;
            default:
                throw new InvalidArgumentException('Invalid query type: ' . $type);
        }

        return $this->queryTimeRange($what, $begin, $end, $user, $billable);
    }

    /**
     * @param string|string[] $select
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @param User|null $user
     * @param bool|null $billable
     * @return int|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function queryTimeRange($select, ?DateTime $begin, ?DateTime $end, ?User $user, ?bool $billable = null)
    {
        $selects = $select;
        if (!\is_array($select)) {
            $selects = [$select];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->from(Timesheet::class, 't');
        foreach ($selects as $s) {
            $qb->addSelect($s);
        }

        if (!empty($begin)) {
            $qb
                ->andWhere($qb->expr()->gte('t.begin', ':from'))
                ->setParameter('from', $begin);
        }

        if (!empty($end)) {
            $qb
                ->andWhere($qb->expr()->lte('t.end', ':to'))
                ->setParameter('to', $end);
        }

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        if (null !== $billable) {
            $qb->andWhere('t.billable = :billable')
                ->setParameter('billable', $billable);
        }

        if (\is_array($select)) {
            /* @phpstan-ignore-next-line  */
            return $qb->getQuery()->getOneOrNullResult();
        }

        /** @phpstan-ignore-next-line  */
        $result = $qb->getQuery()->getSingleScalarResult();

        return empty($result) ? 0 : $result;
    }

    /**
     * @param User $user
     * @param bool $bcSafe will be removed with 2.0
     * @return TimesheetStatistic
     */
    public function getUserStatistics(User $user, bool $bcSafe = true): TimesheetStatistic
    {
        $stats = new TimesheetStatistic();

        $allTimeData = $this->queryTimeRange([
            'COALESCE(SUM(t.duration), 0) as duration',
            'COALESCE(SUM(t.rate), 0) as rate',
            'COUNT(t.id) as amount'
        ], null, null, $user);

        $stats->setAmountTotal($allTimeData['rate']);
        $stats->setDurationTotal($allTimeData['duration']);
        $stats->setRecordsTotal($allTimeData['amount']);

        $billableAllTime = $this->getStatistic(self::STATS_QUERY_RATE, null, null, $user, true);
        $stats->setRateTotalBillable($billableAllTime);

        $timezone = new \DateTimeZone($user->getTimezone());
        $begin = new DateTime('first day of this month 00:00:00', $timezone);
        $end = new DateTime('last day of this month 23:59:59', $timezone);

        $monthData = $this->queryTimeRange(
            [
                'COALESCE(SUM(t.rate), 0) as rate',
                'COALESCE(SUM(t.duration), 0) as duration'
            ],
            $begin,
            $end,
            $user
        );

        $stats->setAmountThisMonth($monthData['rate']);
        $stats->setDurationThisMonth($monthData['duration']);

        $billableMonth = $this->getStatistic(self::STATS_QUERY_RATE, $begin, $end, $user, true);
        $stats->setRateThisMonthBillable($billableMonth);

        if ($bcSafe) {
            $firstEntry = $this->getEntityManager()
                ->createQuery('SELECT MIN(t.begin) FROM ' . Timesheet::class . ' t WHERE t.user = :user')
                ->setParameter('user', $user)
                ->getSingleScalarResult();

            $timezone = new \DateTimeZone($user->getTimezone());

            if ($firstEntry !== null) {
                $stats->setFirstEntry(new DateTime($firstEntry, $timezone));
            } else {
                @trigger_error(
                    'TimesheetStatistic::getFirstEntry() returns a wrong result for users without record and will be removed with 2.0',
                    E_USER_DEPRECATED
                );
                $stats->setFirstEntry(new DateTime('now', $timezone));
            }
        }

        return $stats;
    }

    /**
     * @deprecated since 1.15 - use TimesheetStatisticService::getMonthlyStats() instead - will be removed with 2.0
     * @codeCoverageIgnore
     *
     * @param DateTime $begin
     * @param DateTime $end
     * @param User|null $user
     * @return Year[]
     */
    public function getMonthlyStats(DateTime $begin, DateTime $end, ?User $user = null): array
    {
        @trigger_error('TimesheetRepository::getMonthlyStats() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        /** @var Year[] $years */
        $years = [];

        $tmp = clone $begin;
        while ($tmp < $end) {
            $curYear = $tmp->format('Y');
            if (!isset($years[$curYear])) {
                $year = new Year($curYear);
                for ($i = 1; $i < 13; $i++) {
                    $date = clone $begin;
                    $date->setDate((int) $curYear, $i, (int) $begin->format('d'));
                    $date->setTime(0, 0, 0);
                    if ($date < $begin || $date > $end) {
                        continue;
                    }
                    $year->setMonth(new Month((string) $i));
                }
                $years[$curYear] = $year;
            }
            $tmp->modify('+1 month');
        }

        $qb = $this->getMonthlyStatsQuery($user, $begin, $end, null);
        foreach ($qb->getQuery()->execute() as $statRow) {
            if (!isset($years[$statRow['year']])) {
                continue;
            }
            $month = $years[$statRow['year']]->getMonth((int) $statRow['month']);
            if (null === $month) {
                continue;
            }
            $month->setTotalDuration((int) $statRow['duration']);
            $month->setTotalRate((float) $statRow['rate']);
        }

        $qb = $this->getMonthlyStatsQuery($user, $begin, $end, true);
        foreach ($qb->getQuery()->execute() as $statRow) {
            if (!isset($years[$statRow['year']])) {
                continue;
            }
            $month = $years[$statRow['year']]->getMonth((int) $statRow['month']);
            if (null === $month) {
                continue;
            }
            $month->setBillableDuration((int) $statRow['duration']);
            $month->setBillableRate((float) $statRow['rate']);
        }

        return $years;
    }

    /**
     * @deprecated since 1.15 - use TimesheetStatisticService::getMonthlyStats() instead - will be removed with 2.0
     * @codeCoverageIgnore
     *
     * @param User|null $user
     * @param DateTime|null $begin
     * @param DateTime|null $end
     * @param bool|null $billable
     * @return QueryBuilder
     */
    private function getMonthlyStatsQuery(User $user = null, ?DateTime $begin = null, ?DateTime $end = null, ?bool $billable = null): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->from(Timesheet::class, 't');
        $qb->select('COALESCE(SUM(t.rate), 0) as rate');
        $qb->addSelect('COALESCE(SUM(t.duration), 0) as duration');
        $qb->addSelect('MONTH(t.date) as month');
        $qb->addSelect('YEAR(t.date) as year');

        if (!empty($begin)) {
            $qb->andWhere($qb->expr()->gte('t.begin', ':from'));
            $qb->setParameter('from', $begin);
        } else {
            $qb->andWhere($qb->expr()->isNotNull('t.begin'));
        }

        if (!empty($end)) {
            $qb->andWhere($qb->expr()->lte('t.end', ':to'));
            $qb->setParameter('to', $end);
        } else {
            $qb->andWhere($qb->expr()->isNotNull('t.end'));
        }

        if (null !== $user) {
            $qb->andWhere('t.user = :user');
            $qb->setParameter('user', $user);
        }

        if (null !== $billable) {
            $qb->andWhere('t.billable = :billable');
            $qb->setParameter('billable', $billable);
        }

        $qb
            ->orderBy('year', 'DESC')
            ->addOrderBy('month', 'ASC')
            ->groupBy('year')
            ->addGroupBy('month')
        ;

        return $qb;
    }

    /**
     * In case this method is called with one timezone and the results are from another timezone,
     * it might return rows outside the time-range.
     *
     * @param DateTime $begin
     * @param DateTime $end
     * @param User|null $user
     * @return mixed
     */
    protected function getDailyData(DateTime $begin, DateTime $end, ?User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $or = $qb->expr()->orX();
        $or->add($qb->expr()->between(':begin', 't.begin', 't.end'));
        $or->add($qb->expr()->between(':end', 't.begin', 't.end'));
        $or->add($qb->expr()->between('t.begin', ':begin', ':end'));
        $or->add($qb->expr()->between('t.end', ':begin', ':end'));

        $qb->select('t, p, a, c')
            ->from(Timesheet::class, 't')
            ->andWhere($qb->expr()->isNotNull('t.end'))
            ->andWhere($or)
            ->orderBy('t.begin', 'DESC')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->leftJoin('t.activity', 'a')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.customer', 'c')
        ;

        if (null !== $user) {
            $qb
                ->andWhere($qb->expr()->eq('t.user', ':user'))
                ->setParameter('user', $user)
            ;
        }

        $timesheets = $qb->getQuery()->getResult();

        $results = [];
        /** @var Timesheet $result */
        foreach ($timesheets as $result) {
            /** @var DateTime $beginTmp */
            $beginTmp = $result->getBegin();
            /** @var DateTime $endTmp */
            $endTmp = $result->getEnd();
            $dateKeyEnd = $endTmp->format('Ymd');

            do {
                $dateKey = $beginTmp->format('Ymd');

                if ($dateKey !== $dateKeyEnd) {
                    $newDateBegin = clone $beginTmp;
                    $newDateBegin->add(new DateInterval('P1D'));
                    // overlapping records should always start at midnight
                    $newDateBegin->setTime(0, 0, 0);
                } else {
                    $newDateBegin = clone $endTmp;
                }

                // make sure to exclude entries that are outside the requested time-range:
                // these entries can exist if you have long running entries that started before $begin
                // for statistical reasons we have to include everything between $begin and $end while
                // excluding everything that is outside of that range
                // --------------------------------------------------------------------------------------
                // Be aware that this will NOT filter every record, in case there is a timezone mismatch between the
                // begin/end dates and the ones from the database (eg. recorded in UTC) - which might actually be
                // before $begin (which happens thanks to the timezone conversion when querying the database)
                if ($newDateBegin > $begin && $beginTmp < $end) {
                    if (!isset($results[$dateKey])) {
                        $results[$dateKey] = [
                            'rate' => 0,
                            'duration' => 0,
                            'billable' => 0, // duration
                            'month' => $beginTmp->format('n'),
                            'year' => $beginTmp->format('Y'),
                            'day' => $beginTmp->format('j'),
                            'details' => []
                        ];
                    }
                    $duration = $newDateBegin->getTimestamp() - $beginTmp->getTimestamp();
                    $durationPercent = 0;
                    if ($result->getDuration() !== null && $result->getDuration() > 0) {
                        $durationPercent = $duration / $result->getDuration();
                    }
                    $rate = $result->getRate() * $durationPercent;

                    $results[$dateKey]['rate'] += $rate;
                    $results[$dateKey]['duration'] += $duration;
                    if ($result->isBillable()) {
                        $results[$dateKey]['billable'] += $duration;
                    }
                    $detailsId =
                        $result->getProject()->getCustomer()->getId()
                        . '_' . $result->getProject()->getId()
                        . '_' . $result->getActivity()->getId()
                    ;

                    if (!isset($results[$dateKey]['details'][$detailsId])) {
                        $results[$dateKey]['details'][$detailsId] = [
                            'project' => $result->getProject(),
                            'activity' => $result->getActivity(),
                            'duration' => 0,
                            'rate' => 0,
                            'billable' => 0, // duration
                        ];
                    }

                    $results[$dateKey]['details'][$detailsId]['duration'] += $duration;
                    $results[$dateKey]['details'][$detailsId]['rate'] += $rate;
                    if ($result->isBillable()) {
                        $results[$dateKey]['details'][$detailsId]['billable'] += $duration;
                    }
                }

                $beginTmp = $newDateBegin;

                // yes, we only want to compare the day, not the time
                if ((int) $end->format('Ymd') < (int) $newDateBegin->format('Ymd')) {
                    break;
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
     * @deprecated since 1.15 - use TimesheetStatisticService::getDailyStatistics() instead
     * @codeCoverageIgnore
     *
     * @param User|null $user
     * @param DateTime $begin
     * @param DateTime $end
     * @return Day[]
     * @throws Exception
     */
    public function getDailyStats(?User $user, DateTime $begin, DateTime $end): array
    {
        /** @var Day[] $days */
        $days = [];

        // prefill the array
        $tmp = clone $end;
        $until = (int) $begin->format('Ymd');
        while ((int) $tmp->format('Ymd') >= $until) {
            $last = clone $tmp;
            $days[$last->format('Ymd')] = new Day($last, 0, 0.00);
            $tmp->modify('-1 day');
        }

        $results = $this->getDailyData($begin, $end, $user);

        foreach ($results as $statRow) {
            $dateTime = clone $begin;
            $dateTime->setDate($statRow['year'], $statRow['month'], $statRow['day']);
            $dateTime->setTime(0, 0, 0);
            $day = new Day($dateTime, (int) $statRow['duration'], (float) $statRow['rate']);
            $day->setTotalDurationBillable($statRow['billable']);
            $day->setDetails($statRow['details']);
            $dateKey = $dateTime->format('Ymd');
            // make sure entries from other timezones are filtered
            if (!\array_key_exists($dateKey, $days)) {
                continue;
            }
            $days[$dateKey] = $day;
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

        return $this->getHydratedResultsByQuery($qb, false);
    }

    /**
     * @deprecated since 1.11 use TimesheetService::stopTimesheet() instead
     * @codeCoverageIgnore
     *
     * @param User $user
     * @param int $hardLimit
     * @param bool $flush
     * @return int
     * @throws RepositoryException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function stopActiveEntries(User $user, int $hardLimit, bool $flush = true)
    {
        $counter = 0;
        $activeEntries = $this->getActiveEntries($user);

        // reduce limit by one:
        // this method is only called when a new entry is started
        // -> all entries, including the new one must not exceed the $limit
        $limit = $hardLimit - 1;

        if (\count($activeEntries) > $limit) {
            $i = 1;
            foreach ($activeEntries as $activeEntry) {
                if ($i > $limit) {
                    if ($hardLimit > 1) {
                        throw new Exception('timesheet.start.exceeded_limit');
                    }

                    $this->stopRecording($activeEntry, $flush);
                    $counter++;
                }
                $i++;
            }
        }

        return $counter;
    }

    /**
     * This method causes me some headaches ...
     *
     * Activity permissions are currently not checked (which would be easy to add)
     *
     * Especially the following question is still un-answered!
     *
     * Should a teamlead:
     * 1. see all records of his team-members, even if they recorded times for projects invisible to him
     * 2. only see records for projects which can be accessed by hom (current situation)
     */
    private function addPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = []): bool
    {
        // make sure that all queries without a user see all projects
        if (null === $user && empty($teams)) {
            return false;
        }

        // make sure that admins see all timesheet records
        if (null !== $user && $user->canSeeAllData()) {
            return false;
        }

        if (null !== $user) {
            $teams = array_merge($teams, $user->getTeams());
        }

        if (empty($teams)) {
            $qb->andWhere('SIZE(c.teams) = 0');
            $qb->andWhere('SIZE(p.teams) = 0');

            return true;
        }

        $orProject = $qb->expr()->orX(
            'SIZE(p.teams) = 0',
            $qb->expr()->isMemberOf(':teams', 'p.teams')
        );
        $qb->andWhere($orProject);

        $orCustomer = $qb->expr()->orX(
            'SIZE(c.teams) = 0',
            $qb->expr()->isMemberOf(':teams', 'c.teams')
        );
        $qb->andWhere($orCustomer);

        $ids = array_values(array_unique(array_map(function (Team $team) {
            return $team->getId();
        }, $teams)));

        $qb->setParameter('teams', $ids);

        return true;
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
            ->select($qb->expr()->count('t.id'))
        ;
        $counter = (int) $qb->getQuery()->getSingleScalarResult();

        $qb = $this->getQueryBuilderForQuery($query);

        return new LoaderPaginator(new TimesheetLoader($qb->getEntityManager()), $qb, $counter);
    }

    /**
     * When switching $fullyHydrated to true, the call gets even more expensive.
     * You normally don't need this, unless you want to access deeply nested attributes for many entries!
     *
     * @param TimesheetQuery $query
     * @param bool $fullyHydrated
     * @return Timesheet[]
     */
    public function getTimesheetsForQuery(TimesheetQuery $query, bool $fullyHydrated = false): iterable
    {
        $qb = $this->getQueryBuilderForQuery($query);

        return $this->getHydratedResultsByQuery($qb, $fullyHydrated);
    }

    public function getTimesheetResult(TimesheetQuery $query): TimesheetResult
    {
        $qb = $this->getQueryBuilderForQuery($query);

        return new TimesheetResult($qb);
    }

    /**
     * @param QueryBuilder $qb
     * @param bool $fullyHydrated
     * @return Timesheet[]
     */
    protected function getHydratedResultsByQuery(QueryBuilder $qb, bool $fullyHydrated = false): iterable
    {
        $results = $qb->getQuery()->getResult();

        $loader = new TimesheetLoader($qb->getEntityManager(), $fullyHydrated);
        $loader->loadResults($results);

        return $results;
    }

    private function getQueryBuilderForQuery(TimesheetQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $requiresProject = false;
        $requiresCustomer = false;
        $requiresActivity = false;

        $qb
            ->select('t')
            ->from(Timesheet::class, 't')
        ;

        $orderBy = $query->getOrderBy();
        switch ($orderBy) {
            case 'project':
                $orderBy = 'p.name';
                $requiresProject = true;
                break;
            case 'customer':
                $requiresCustomer = true;
                $orderBy = 'c.name';
                break;
            case 'activity':
                $requiresActivity = true;
                $orderBy = 'a.name';
                break;
            default:
                $orderBy = 't.' . $orderBy;
                break;
        }

        $qb->addOrderBy($orderBy, $query->getOrder());

        $user = [];
        if (null !== $query->getUser()) {
            $user[] = $query->getUser();
        }

        $user = array_merge($user, $query->getUsers());

        if (empty($user) && null !== ($currentUser = $query->getCurrentUser()) && !$currentUser->canSeeAllData()) {
            // make sure that the user himself is in the list of users, if he is part of a team
            // if teams are used and the user is not a teamlead, the list of users would be empty and then leading to NOT limit the select by user IDs
            $user[] = $currentUser;

            foreach ($currentUser->getTeams() as $team) {
                if ($currentUser->isTeamleadOf($team)) {
                    $query->addTeam($team);
                }
            }
        }

        if (!empty($query->getTeams())) {
            foreach ($query->getTeams() as $team) {
                foreach ($team->getUsers() as $teamUser) {
                    $user[] = $teamUser;
                }
            }
        }

        $user = array_map(function ($user) {
            if ($user instanceof User) {
                return $user->getId();
            }

            return $user;
        }, $user);
        $user = array_unique($user);

        if (!empty($user)) {
            $qb->andWhere($qb->expr()->in('t.user', $user));
        }

        if (null !== $query->getBegin()) {
            $qb->andWhere($qb->expr()->gte('t.begin', ':begin'))
                ->setParameter('begin', $query->getBegin());
        }

        if ($query->isRunning()) {
            $qb->andWhere($qb->expr()->isNull('t.end'));
        } elseif ($query->isStopped()) {
            $qb->andWhere($qb->expr()->isNotNull('t.end'));
        }

        if (null !== $query->getEnd()) {
            $qb->andWhere($qb->expr()->lte('t.begin', ':end'))
                ->setParameter('end', $query->getEnd());
        }

        if ($query->isExported()) {
            $qb->andWhere('t.exported = :exported')->setParameter('exported', true, Types::BOOLEAN);
        } elseif ($query->isNotExported()) {
            $qb->andWhere('t.exported = :exported')->setParameter('exported', false, Types::BOOLEAN);
        }

        if ($query->isBillable()) {
            $qb->andWhere('t.billable = :billable')->setParameter('billable', true, Types::BOOLEAN);
        } elseif ($query->isNotBillable()) {
            $qb->andWhere('t.billable = :billable')->setParameter('billable', false, Types::BOOLEAN);
        }

        if (null !== $query->getModifiedAfter()) {
            $qb->andWhere($qb->expr()->gte('t.modifiedAt', ':modified_at'))
                ->setParameter('modified_at', $query->getModifiedAfter());
        }

        if ($query->hasActivities()) {
            $qb->andWhere($qb->expr()->in('t.activity', ':activity'))
                ->setParameter('activity', $query->getActivities());
        }

        if ($query->hasProjects()) {
            $qb->andWhere($qb->expr()->in('t.project', ':project'))
                ->setParameter('project', $query->getProjects());
        } elseif ($query->hasCustomers()) {
            $requiresCustomer = true;
            $qb->andWhere($qb->expr()->in('p.customer', ':customer'))
                ->setParameter('customer', $query->getCustomers());
        }

        $tags = $query->getTags();
        if (!empty($tags)) {
            $qb->andWhere($qb->expr()->isMemberOf(':tags', 't.tags'))
                ->setParameter('tags', $query->getTags());
        }

        $requiresTeams = $this->addPermissionCriteria($qb, $query->getCurrentUser(), $query->getTeams());

        $this->addSearchTerm($qb, $query);

        if ($requiresCustomer || $requiresProject || $requiresTeams) {
            $qb->leftJoin('t.project', 'p');
        }

        if ($requiresCustomer || $requiresTeams) {
            $qb->leftJoin('p.customer', 'c');
        }

        if ($requiresActivity) {
            $qb->leftJoin('t.activity', 'a');
        }

        if ($query->getMaxResults() !== null) {
            $qb->setMaxResults($query->getMaxResults());
        }

        return $qb;
    }

    private function getMetaFieldClass(): string
    {
        return TimesheetMeta::class;
    }

    private function getMetaFieldName(): string
    {
        return 'timesheet';
    }

    /**
     * @return array<string>
     */
    private function getSearchableFields(): array
    {
        return ['t.description'];
    }

    /**
     * @param User|null $user
     * @param DateTime|null $startFrom
     * @param int $limit
     * @return Timesheet[]
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getRecentActivities(User $user = null, DateTime $startFrom = null, int $limit = 10)
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
            ->setParameter('visible', true, Types::BOOLEAN)
        ;

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        if (null !== $startFrom) {
            $qb->andWhere($qb->expr()->gte('t.begin', ':begin'))
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

        return $this->getHydratedResultsByQuery($qb, true);
    }

    /**
     * @param Timesheet[]|int[] $timesheets
     */
    public function setExported(array $timesheets)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $qb = $em->createQueryBuilder();
            $qb
                ->update(Timesheet::class, 't')
                ->set('t.exported', ':exported')
                ->where($qb->expr()->in('t.id', ':ids'))
                ->setParameter('exported', true, Types::BOOLEAN)
                ->setParameter('ids', $timesheets)
                ->getQuery()
                ->execute();

            $em->commit();
        } catch (\Exception $ex) {
            $em->rollback();
        }
    }

    /**
     * @param Timesheet $timesheet
     * @return RateInterface[]
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

    public function hasRecordForTime(Timesheet $timesheet): bool
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select($qb->expr()->count('t.id'))
            ->from(Timesheet::class, 't')
        ;

        $or = $qb->expr()->orX(
            $qb->expr()->between(':begin', 't.begin', 't.end')
        );

        if (null !== $timesheet->getEnd()) {
            $or->add($qb->expr()->between(':end', 't.begin', 't.end'));
            $or->add($qb->expr()->between('t.begin', ':begin', ':end'));
            $or->add($qb->expr()->between('t.end', ':begin', ':end'));
            $end = clone $timesheet->getEnd();
            $end->sub(new DateInterval('PT1S'));
            $qb->setParameter('end', $end);
        }

        // one second is added, because people normally either use the calendar / times which are rounded to the full minute
        // for an existing entry like 12:45-13:00 it is impossible to add a new one from 13:00-13:15 as the between() query find the first one
        // by adding one second the between() select will not match any longer

        $begin = clone $timesheet->getBegin();
        $begin->add(new DateInterval('PT1S'));

        $qb
            ->andWhere($qb->expr()->eq('t.user', ':user'))
            ->andWhere($qb->expr()->isNotNull('t.end'))
            ->andWhere($or)
            ->setParameter('begin', $begin)
            ->setParameter('user', $timesheet->getUser()->getId())
        ;

        // if we edit an existing entry, make sure we do not find "the same entry" when only updating eg. the description
        if ($timesheet->getId() !== null) {
            $qb->andWhere($qb->expr()->neq('t.id', $timesheet->getId()));
        }

        try {
            $result = (int) $qb->getQuery()->getSingleScalarResult();
        } catch (Exception $ex) {
            return true;
        }

        return $result > 0;
    }
}
