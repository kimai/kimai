<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Service;

use App\Entity\Project;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use DateInterval;
use DateTime;
use KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\CustomerPortalBundle\Model\ChartStat;
use KimaiPlugin\CustomerPortalBundle\Model\RecordMergeMode;
use KimaiPlugin\CustomerPortalBundle\Model\TimeRecord;
use KimaiPlugin\CustomerPortalBundle\Repository\SharedProjectTimesheetRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class ViewService
{
    public function __construct(
        private readonly TimesheetRepository $timesheetRepository,
        private readonly RequestStack $request,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly SharedProjectTimesheetRepository $sharedTimesheetRepository,
        private readonly RateLimiterFactory $customerPortalLimiter
    )
    {
    }

    /**
     * Check if the user has access to the given shared project timesheet.
     */
    public function hasAccess(SharedProjectTimesheet $sharedProject, ?string $givenPassword, Request $request): bool
    {
        $hashedPassword = $sharedProject->getPassword();

        if ($hashedPassword !== null) {
            // Check session
            $shareKey = $sharedProject->getShareKey();
            // the password is in the session key, so changing it will revoke previous access
            $sessionPasswordKey = \sprintf('spt-authed-%d-%s-%s', $sharedProject->getId(), $shareKey, md5($hashedPassword));

            if (!$this->request->getSession()->has($sessionPasswordKey)) {
                $limiter = $this->customerPortalLimiter->create($request->getClientIp());
                $limit = $limiter->consume();

                if (!$limit->isAccepted()) {
                    return false;
                }

                // Check given password
                if ($givenPassword === null || $givenPassword === '' || !$this->passwordHasherFactory->getPasswordHasher('customer_portal')->verify($hashedPassword, $givenPassword)) {
                    return false;
                }
                $limiter->reset();
                $this->request->getSession()->set($sessionPasswordKey, true);
            }
        }

        return true;
    }

    /**
     * Delivers time records for the given shared project timesheet and time span.
     *
     * @return TimeRecord[]
     */
    public function getTimeRecords(SharedProjectTimesheet $sharedProject, int $year, int $month, ?Project $limitProject = null): array
    {
        $month = max(min($month, 12), 1);

        $begin = new DateTime($year . '-' . $month . '-01 00:00:00');
        $end = clone $begin;
        $end->add(new DateInterval('P1M'));
        $end->sub(new DateInterval('PT1S'));

        $query = new TimesheetQuery();
        $query->setBegin($begin);
        $query->setEnd($end);

        if (isset($limitProject)) {
            $query->addProject($limitProject);
        } else {
            $projects = $this->sharedTimesheetRepository->getProjects($sharedProject);

            // this alone fails if it is a customer portal and the customer has no visible projects
            foreach ($projects as $project) {
                $query->addProject($project);
            }

            // so we make sure to include the customer at all times
            if ($sharedProject->getCustomer() !== null && \count($projects) === 0) {
                return [];
                //$query->addCustomer($sharedProject->getCustomer());
            }
        }

        $query->setOrderBy('begin');
        $query->setOrder(BaseQuery::ORDER_ASC);

        $timesheets = $this->timesheetRepository->getTimesheetResult($query)->getResults();

        // Filter time records by merge mode
        $timeRecords = [];
        $mergeMode = $sharedProject->getRecordMergeMode();
        foreach ($timesheets as $timesheet) {
            if ($timesheet->getBegin() === null || $timesheet->getUser() === null) {
                continue;
            }
            $dateKey = $timesheet->getBegin()->format('Y-m-d');
            if (!\array_key_exists($dateKey, $timeRecords)) {
                $timeRecords[$dateKey] = [];
            }

            $userKey = preg_replace('/[^a-z0-9]/', '', strtolower($timesheet->getUser()->getDisplayName()));
            if ($userKey === null) {
                continue;
            }
            if ($mergeMode !== RecordMergeMode::MODE_NONE) {
                // Assume that records from one user will be merged into one
                if (!\array_key_exists($userKey, $timeRecords[$dateKey])) {
                    $timeRecords[$dateKey][$userKey] = [TimeRecord::fromTimesheet($timesheet, $mergeMode)];
                } else {
                    $timeRecords[$dateKey][$userKey][0]->addTimesheet($timesheet);
                }
            } else {
                // One user can be assigned to multiple records per day
                $time = $timesheet->getBegin()->format('H-i-s') . $timesheet->getId();
                $timeRecords[$dateKey][$userKey][$time] = TimeRecord::fromTimesheet($timesheet);
            }
        }

        // Sort records and create a flat, sorted list of records
        $flattenedTimeRecords = [];

        ksort($timeRecords);
        foreach($timeRecords as $recordsOfDate) {
            ksort($recordsOfDate);
            foreach ($recordsOfDate as $recordsOfUser) {
                ksort($recordsOfUser);
                foreach($recordsOfUser as $record) {
                    $flattenedTimeRecords[] = $record;
                }
            }
        }

        return $flattenedTimeRecords;
    }

    /**
     * Delivers stats for the given year (e.g. duration per month).
     *
     * @return ChartStat[] stats per month, one-based index (1 - 12)
     */
    public function getAnnualStats(SharedProjectTimesheet $sharedProject, int $year, ?Project $limitProject = null): array
    {
        $queryBuilder = $this->timesheetRepository->createQueryBuilder('t')
            ->select([
                'YEAR(t.date) as year',
                'MONTH(t.date) as month',
                'SUM(t.duration) as duration',
                'SUM(t.rate) as rate',
            ])
            ->where('YEAR(t.date) = :year')
            ->groupBy('year')
            ->addGroupBy('month');

        if (isset($limitProject)) {
            $queryBuilder = $queryBuilder
                ->andWhere('t.project = :project')
                ->setParameters([
                    'project' => $limitProject,
                    'year' => $year,
                ]);
        } elseif ($sharedProject->getProject() !== null) {
            $queryBuilder = $queryBuilder
                ->andWhere('t.project = :project')
                ->setParameters([
                    'project' => $sharedProject->getProject(),
                    'year' => $year,
                ]);
        } else {
            $queryBuilder = $queryBuilder
                ->innerJoin('t.project', 'p')
                ->andWhere('p.customer = :customer')
                ->setParameters([
                    'customer' => $sharedProject->getCustomer(),
                    'year' => $year,
                ]);
        }

        $result = $queryBuilder
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[(int) $row['month']] = new ChartStat($row);
        }

        for ($i = 1; $i <= 12; $i++) {
            if (!isset($stats[$i])) {
                $stats[$i] = new ChartStat();
            }
        }

        ksort($stats);

        return $stats;
    }

    /**
     * Delivers stats for the given month (e.g. duration per day).
     *
     * @return ChartStat[] stats per day
     */
    public function getMonthlyStats(SharedProjectTimesheet $sharedProject, int $year, int $month, ?Project $limitProject = null): array
    {
        $queryBuilder = $this->timesheetRepository->createQueryBuilder('t')
            ->select([
                'YEAR(t.date) as year',
                'MONTH(t.date) as month',
                'DAY(t.date) as day',
                'SUM(t.duration) as duration',
                'SUM(t.rate) as rate',
            ])
            ->where('YEAR(t.date) = :year')
            ->andWhere('MONTH(t.date) = :month')
            ->groupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('day');

        if (isset($limitProject)) {
            $queryBuilder = $queryBuilder
                ->andWhere('t.project = :project')
                ->setParameters([
                    'project' => $limitProject,
                    'year' => $year,
                    'month' => $month,
                ]);
        } elseif ($sharedProject->getProject() !== null) {
            $queryBuilder = $queryBuilder
                ->andWhere('t.project = :project')
                ->setParameters([
                    'project' => $sharedProject->getProject(),
                    'year' => $year,
                    'month' => $month,
                ]);
        } else {
            $queryBuilder = $queryBuilder
                ->innerJoin('t.project', 'p')
                ->andWhere('p.customer = :customer')
                ->setParameters([
                    'customer' => $sharedProject->getCustomer(),
                    'year' => $year,
                    'month' => $month,
                ]);
        }

        $result = $queryBuilder
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[(int) $row['day']] = new ChartStat($row);
        }

        $numberOfDays = date('t', (new DateTime("$year-$month-01"))->getTimestamp());
        for ($i = 1; $i <= $numberOfDays; $i++) {
            if (!isset($stats[$i])) {
                $stats[$i] = new ChartStat();
            }
        }

        ksort($stats);

        return $stats;
    }
}
