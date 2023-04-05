<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Model\DailyStatistic;
use App\Model\DateStatisticInterface;
use App\Model\Statistic\StatisticDate;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use App\Timesheet\TimesheetStatisticService;
use DateTime;

abstract class AbstractUserReportController extends AbstractController
{
    public function __construct(protected TimesheetStatisticService $statisticService, private ProjectRepository $projectRepository, private ActivityRepository $activityRepository)
    {
    }

    protected function canSelectUser(): bool
    {
        // also found in App\EventSubscriber\Actions\UserSubscriber
        if (!$this->isGranted('view_other_timesheet') || !$this->isGranted('view_other_reporting')) {
            return false;
        }

        return true;
    }

    protected function getStatisticDataRaw(DateTime $begin, DateTime $end, User $user): array
    {
        return $this->statisticService->getDailyStatisticsGrouped($begin, $end, [$user]);
    }

    protected function createStatisticModel(DateTime $begin, DateTime $end, User $user): DateStatisticInterface
    {
        return new DailyStatistic($begin, $end, $user);
    }

    protected function prepareReport(DateTime $begin, DateTime $end, User $user): array
    {
        $data = $this->getStatisticDataRaw($begin, $end, $user);

        $data = array_pop($data);
        $projectIds = [];
        $activityIds = [];

        foreach ($data as $projectId => $projectValues) {
            $projectIds[$projectId] = $projectId;
            $dailyProjectStatistic = $this->createStatisticModel($begin, $end, $user);
            foreach ($projectValues['activities'] as $activityId => $activityValues) {
                $activityIds[$activityId] = $activityId;
                if (!isset($data[$projectId]['duration'])) {
                    $data[$projectId]['duration'] = 0;
                }
                if (!isset($data[$projectId]['rate'])) {
                    $data[$projectId]['rate'] = 0.0;
                }
                if (!isset($data[$projectId]['internalRate'])) {
                    $data[$projectId]['internalRate'] = 0.0;
                }
                if (!isset($data[$projectId]['activities'][$activityId]['duration'])) {
                    $data[$projectId]['activities'][$activityId]['duration'] = 0;
                }
                if (!isset($data[$projectId]['activities'][$activityId]['rate'])) {
                    $data[$projectId]['activities'][$activityId]['rate'] = 0.0;
                }
                if (!isset($data[$projectId]['activities'][$activityId]['internalRate'])) {
                    $data[$projectId]['activities'][$activityId]['internalRate'] = 0.0;
                }
                /** @var StatisticDate $date */
                foreach ($activityValues['data']->getData() as $date) {
                    $statisticDate = $dailyProjectStatistic->getByDateTime($date->getDate());
                    if ($statisticDate === null) {
                        // this should not happen, but sometimes it does ...
                        continue;
                    }
                    $statisticDate->setTotalDuration($statisticDate->getTotalDuration() + $date->getTotalDuration());
                    $statisticDate->setTotalRate($statisticDate->getTotalRate() + $date->getTotalRate());
                    $statisticDate->setTotalInternalRate($statisticDate->getTotalInternalRate() + $date->getTotalInternalRate());
                    $data[$projectId]['duration'] = $data[$projectId]['duration'] + $date->getTotalDuration();
                    $data[$projectId]['rate'] = $data[$projectId]['rate'] + $date->getTotalRate();
                    $data[$projectId]['internalRate'] = $data[$projectId]['internalRate'] + $date->getTotalInternalRate();
                    $data[$projectId]['activities'][$activityId]['duration'] = $data[$projectId]['activities'][$activityId]['duration'] + $date->getTotalDuration();
                    $data[$projectId]['activities'][$activityId]['rate'] = $data[$projectId]['activities'][$activityId]['rate'] + $date->getTotalRate();
                    $data[$projectId]['activities'][$activityId]['internalRate'] = $data[$projectId]['activities'][$activityId]['internalRate'] + $date->getTotalInternalRate();
                }
            }
            $data[$projectId]['data'] = $dailyProjectStatistic;
        }

        $activities = $this->activityRepository->findByIds($activityIds);
        foreach ($activities as $activity) {
            $activityIds[$activity->getId()] = $activity;
        }

        foreach ($data as $projectId => $projectValues) {
            foreach ($projectValues['activities'] as $activityId => $activityValues) {
                $data[$projectId]['activities'][$activityId]['activity'] = $activityIds[$activityId];
            }
        }

        $projects = $this->projectRepository->findByIds($projectIds);
        foreach ($projects as $project) {
            $data[$project->getId()]['project'] = $project;
        }

        $customers = [];
        foreach ($data as $id => $row) {
            $customerId = (string) $row['project']->getCustomer()->getId();
            if (!\array_key_exists($customerId, $customers)) {
                $customers[$customerId] = [
                    'customer' => $row['project']->getCustomer(),
                    'projects' => [],
                    'duration' => 0,
                    'rate' => 0.0,
                    'internalRate' => 0.0,
                ];
            }
            $customers[$customerId]['projects'][$id] = $row;
            $customers[$customerId]['duration'] += $row['duration'];
            $customers[$customerId]['rate'] += $row['rate'];
            $customers[$customerId]['internalRate'] += $row['internalRate'];
        }

        return $customers;
    }
}
