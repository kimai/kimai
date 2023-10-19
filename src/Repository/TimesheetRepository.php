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
use App\Model\Revenue;
use App\Model\TimesheetStatistic;
use App\Repository\Loader\TimesheetLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\TimesheetQuery;
use App\Repository\Result\TimesheetResult;
use App\Utils\Pagination;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Exception;
use InvalidArgumentException;

/**
 * @extends \Doctrine\ORM\EntityRepository<Timesheet>
 */
class TimesheetRepository extends EntityRepository
{
    use RepositorySearchTrait;

    /** @deprecated since 2.0.35 */
    public const STATS_QUERY_DURATION = 'duration';
    /** @deprecated since 2.0.35 */
    public const STATS_QUERY_RATE = 'rate';
    /** @deprecated since 2.0.35 */
    public const STATS_QUERY_USER = 'users';
    /** @deprecated since 2.0.35 */
    public const STATS_QUERY_AMOUNT = 'amount';
    /** @deprecated since 2.0.35 */
    public const STATS_QUERY_ACTIVE = 'active';

    /**
     * Fetches the raw data of a timesheet, to allow comparison e.g. of submitted and previously stored data.
     *
     * @param int $id
     * @return array
     */
    public function getRawData(int $id): array
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

    public function delete(Timesheet $timesheet): void
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

    public function begin(): void
    {
        $this->getEntityManager()->beginTransaction();
    }

    public function commit(): void
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->commit();
    }

    public function rollback(): void
    {
        $this->getEntityManager()->rollback();
    }

    public function save(Timesheet $timesheet): void
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
     * @param self::STATS_QUERY_* $type
     * @return int|mixed
     * @deprecated since 2.0.35
     */
    public function getStatistic(string $type, ?\DateTimeInterface $begin, ?\DateTimeInterface $end, ?User $user, ?bool $billable = null): mixed
    {
        @trigger_error('Repository method getStatistic() is deprecated, use explicit methods instead', E_USER_DEPRECATED);

        switch ($type) {
            case 'active':
                return $this->countActiveEntries($user);
            case 'duration':
                return $this->getDurationForTimeRange($begin, $end, $user, $billable);
            case 'rate':
                return $this->getRevenue($begin, $end, $user);
            case 'users':
                return $this->countActiveUsers($begin, $end, $billable);
            case 'amount':
                return $this->queryTimeRange('COUNT(t.id)', $begin, $end, $user, $billable);
        }

        throw new InvalidArgumentException('Invalid query type: ' . $type); // @phpstan-ignore-line
    }

    public function getDurationForTimeRange(?\DateTimeInterface $begin, ?\DateTimeInterface $end, ?User $user, ?bool $billable = null): int
    {
        $tmp = $this->queryTimeRange('COALESCE(SUM(t.duration), 0)', $begin, $end, $user, $billable);

        if (!is_numeric($tmp)) {
            return 0;
        }

        return (int) $tmp;
    }

    /**
     * @return array<Revenue>
     */
    public function getRevenue(?\DateTimeInterface $begin, ?\DateTimeInterface $end, ?User $user): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->from(Timesheet::class, 't')
            ->addSelect('COALESCE(SUM(t.rate), 0) as revenue')
            ->addSelect('c.currency as currency')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.customer', 'c')
            ->groupBy('c.currency')
            ->andWhere($qb->expr()->eq('t.billable', ':billable'))
            ->setParameter('billable', true);

        if ($begin !== null) {
            $qb->andWhere($qb->expr()->between('t.begin', ':from', ':to'))
                ->setParameter('from', $begin)
                ->setParameter('to', $end);
        }

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        $all = [];
        foreach ($qb->getQuery()->getArrayResult() as $item) {
            $all[] = new Revenue($item['currency'], $item['revenue']);
        }

        return $all;
    }

    /**
     * @param string|string[] $select
     * @return int|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function queryTimeRange(string|array $select, ?\DateTimeInterface $begin, ?\DateTimeInterface $end, ?User $user, ?bool $billable = null): mixed
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
            /* @phpstan-ignore-next-line */
            return $qb->getQuery()->getOneOrNullResult();
        }

        /* @phpstan-ignore-next-line */
        $result = $qb->getQuery()->getSingleScalarResult();

        return empty($result) ? 0 : $result;
    }

    public function getUserStatistics(User $user): TimesheetStatistic
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

        $data = $this->getRevenue(null, null, $user);
        foreach ($data as $row) {
            $stats->setRateTotalBillable($stats->getRateTotalBillable() + $row->getAmount());
        }

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

        $data = $this->getRevenue($begin, $end, $user);
        foreach ($data as $row) {
            $stats->setRateThisMonthBillable($stats->getRateThisMonthBillable() + $row->getAmount());
        }

        return $stats;
    }

    /**
     * @param User|null $user
     * @param bool $ticktac
     * @return Timesheet[]
     */
    public function getActiveEntries(User $user = null, bool $ticktac = false): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t')
            ->from(Timesheet::class, 't')
            ->andWhere($qb->expr()->isNull('t.end'))
            ->orderBy('t.begin', 'DESC');

        if (null !== $user) {
            $qb->andWhere('t.user = :user');
            $qb->setParameter('user', $user);
        }

        if ($ticktac) {
            $qb->setMaxResults(1);

            return $qb->getQuery()->getResult();
        }

        return $this->getHydratedResultsByQuery($qb, false);
    }

    public function countActiveEntries(?User $user = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('t'))
            ->from(Timesheet::class, 't')
            ->andWhere($qb->expr()->isNull('t.end'))
        ;

        if (null !== $user) {
            $qb
                ->andWhere('t.user = :user')
                ->groupBy('t.user')
                ->setParameter('user', $user)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countActiveUsers(?\DateTimeInterface $begin, ?\DateTimeInterface $end, ?bool $billable = null): int
    {
        $tmp = $this->queryTimeRange('COUNT(DISTINCT(t.user))', $begin, $end, null, $billable);

        if (!is_numeric($tmp)) {
            return 0;
        }

        return (int) $tmp;
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
     * 2. only see records for projects which can be accessed by him (current situation)
     *
     * @param array<Team> $teams
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

    public function getPagerfantaForQuery(TimesheetQuery $query): Pagination
    {
        return new Pagination($this->getPaginatorForQuery($query), $query);
    }

    private function getPaginatorForQuery(TimesheetQuery $query): PaginatorInterface
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
     * You normally don't need this, unless you want to access deeply nested attributes for many entries.
     *
     * @param TimesheetQuery $query
     * @param bool $fullyHydrated
     * @param bool $basicHydrated
     * @return Timesheet[]
     */
    public function getTimesheetsForQuery(TimesheetQuery $query, bool $fullyHydrated = false, bool $basicHydrated = true): iterable
    {
        $qb = $this->getQueryBuilderForQuery($query);

        return $this->getHydratedResultsByQuery($qb, $fullyHydrated, $basicHydrated);
    }

    public function getTimesheetResult(TimesheetQuery $query): TimesheetResult
    {
        $qb = $this->getQueryBuilderForQuery($query);

        return new TimesheetResult($query, $qb);
    }

    /**
     * @param QueryBuilder $qb
     * @param bool $fullyHydrated
     * @param bool $basicHydrated
     * @return Timesheet[]
     */
    private function getHydratedResultsByQuery(QueryBuilder $qb, bool $fullyHydrated = false, bool $basicHydrated = true): iterable
    {
        $results = $qb->getQuery()->getResult();

        $loader = new TimesheetLoader($qb->getEntityManager(), $fullyHydrated, $basicHydrated);
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

        if (\count($user) === 0 && null !== ($currentUser = $query->getCurrentUser()) && !$currentUser->canSeeAllData()) {
            // make sure that the user himself is in the list of users, if he is part of a team
            // if teams are used and the user is not a teamlead, the list of users would be empty and then leading to NOT limit the select by user IDs
            $user[] = $currentUser;

            if (!$query->hasTeams()) {
                foreach ($currentUser->getTeams() as $team) {
                    if ($currentUser->isTeamleadOf($team)) {
                        $query->addTeam($team);
                    }
                }
            }
        }

        foreach ($query->getTeams() as $team) {
            foreach ($team->getUsers() as $teamUser) {
                $user[] = $teamUser;
            }
        }

        $userIds = array_unique(array_map(function (User $user) {
            return $user->getId();
        }, $user));

        if (\count($userIds) > 0) {
            $qb->andWhere($qb->expr()->in('t.user', $userIds));
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
                ->setParameter('activity', $query->getActivityIds());
        }

        if ($query->hasProjects()) {
            $qb->andWhere($qb->expr()->in('t.project', ':project'))
                ->setParameter('project', $query->getProjectIds());
        } elseif ($query->hasCustomers()) {
            $requiresCustomer = true;
            $qb->andWhere($qb->expr()->in('p.customer', ':customer'))
                ->setParameter('customer', $query->getCustomerIds());
        }

        $tags = $query->getTags();
        if (\count($tags) > 0) {
            $qb->andWhere($qb->expr()->isMemberOf(':tags', 't.tags'))
                ->setParameter('tags', $tags);
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
     * @param User $user
     * @param DateTime|null $startFrom
     * @param int $limit
     * @return Timesheet[]
     */
    public function getRecentActivities(User $user, DateTime $startFrom = null, int $limit = 10): array
    {
        return $this->findTimesheetsById(
            $user,
            $this->getRecentActivityIds($user, $startFrom, $limit)
        );
    }

    /**
     * @param User $user
     * @param DateTime|null $startFrom
     * @param int $limit
     * @return array<int>
     */
    public function getRecentActivityIds(User $user, DateTime $startFrom = null, int $limit = 10): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        // do NOT join the customer and do NOT check the customer visibility, as this
        // will dramatically increase the speed of this (otherwise slow) query
        // ->andWhere($qb->expr()->eq('c.visible', ':visible'))

        // you might want to join activity and project to check their visibility
        // but for now this is way slower than simply fetching more items
        //
        // ->andWhere($qb->expr()->eq('p.visible', ':visible'))
        // ->join('t.activity', 'a')
        // ->andWhere($qb->expr()->eq('a.visible', ':visible'))
        // ->setParameter('visible', true, Types::BOOLEAN)

        $qb->select($qb->expr()->max('t.id') . ' AS maxid')
            ->from(Timesheet::class, 't')
            ->indexBy('t', 't.id')
            ->andWhere($qb->expr()->eq('t.user', ':user'))
            ->groupBy('t.project', 't.activity')
            ->orderBy('maxid', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('user', $user)
        ;

        if (null !== $startFrom) {
            $qb->andWhere($qb->expr()->gte('t.begin', ':begin'))
                ->setParameter('begin', $startFrom);
        }

        $qb->join('t.project', 'p');
        $qb->join('p.customer', 'c');

        $this->addPermissionCriteria($qb, $user);

        $results = $qb->getQuery()->getScalarResult();

        if (empty($results)) {
            return [];
        }

        return array_column($results, 'maxid');
    }

    /**
     * @param User $user
     * @param array<int> $ids
     * @param bool $fullyHydrated
     * @param bool $basicHydrated
     * @return array<Timesheet>
     */
    public function findTimesheetsById(User $user, array $ids, bool $fullyHydrated = false, bool $basicHydrated = true): array
    {
        if (\count($ids) === 0) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('t')
            ->from(Timesheet::class, 't')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->orderBy('t.end', 'DESC')
        ;

        $qb->join('t.project', 'p');
        $qb->join('p.customer', 'c');

        $this->addPermissionCriteria($qb, $user);

        return $this->getHydratedResultsByQuery($qb, $fullyHydrated, $basicHydrated);
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
