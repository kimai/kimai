<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectDetails;

use App\Entity\Project;
use App\Entity\User;
use App\Model\ActivityStatistic;
use App\Model\BudgetStatisticModel;
use App\Model\Statistic\UserYear;
use App\Model\Statistic\Year;
use App\Model\UserStatistic;

final class ProjectDetailsModel
{
    /**
     * @var Year[]
     */
    private array $years = [];
    /**
     * @var array<string, array<ActivityStatistic>>
     */
    private array $yearlyActivities = [];
    /**
     * @var array<string, array<int, UserYear>>
     */
    private array $usersMonthly = [];
    /**
     * @var ActivityStatistic[]
     */
    private array $activities = [];
    /**
     * @var BudgetStatisticModel
     */
    private ?BudgetStatisticModel $budgetStatisticModel = null;

    public function __construct(private Project $project)
    {
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function addActivity(ActivityStatistic $activityStatistic): void
    {
        $this->activities[$activityStatistic->getActivity()->getId()] = $activityStatistic;
    }

    /**
     * @return ActivityStatistic[]
     */
    public function getActivities(): array
    {
        return array_values($this->activities);
    }

    public function addYearActivity(string $year, ActivityStatistic $activityStatistic): void
    {
        $this->yearlyActivities[$year][] = $activityStatistic;
    }

    /**
     * @param string $year
     * @return ActivityStatistic[]
     */
    public function getYearActivities(string $year): array
    {
        if (!\array_key_exists($year, $this->yearlyActivities)) {
            return [];
        }

        return $this->yearlyActivities[$year];
    }

    /**
     * @return UserStatistic[]
     */
    public function getUserStats(): array
    {
        $users = [];
        foreach ($this->usersMonthly as $year) {
            foreach ($year as $id => $userYear) {
                if (\array_key_exists($id, $users)) {
                    $userStat = $users[$id];
                } else {
                    $userStat = new UserStatistic($userYear->getUser());
                    $users[$id] = $userStat;
                }
                $userStat->setDuration($userStat->getDuration() + $userYear->getDuration());
                $userStat->setRate($userStat->getRate() + $userYear->getRate());
            }
        }

        return $users;
    }

    public function setUserYear(Year $year, User $user): void
    {
        $this->usersMonthly[$year->getYear()][$user->getId()] = new UserYear($user, $year);
    }

    public function getUserYear(string $year, User $user): ?Year
    {
        if (!\array_key_exists($year, $this->usersMonthly) || !\array_key_exists($user->getId(), $this->usersMonthly[$year])) {
            return null;
        }

        return $this->usersMonthly[$year][$user->getId()]->getYear();
    }

    /**
     * @param string $year
     * @return UserYear[]
     */
    public function getUserYears(string $year): array
    {
        if (!\array_key_exists($year, $this->usersMonthly)) {
            return [];
        }

        return $this->usersMonthly[$year];
    }

    /**
     * @return Year[]
     */
    public function getYears(): array
    {
        return $this->years;
    }

    public function getYear(string $year): ?Year
    {
        foreach ($this->years as $tmp) {
            if ($tmp->getYear() === $year) {
                return $tmp;
            }
        }

        return null;
    }

    /**
     * @param Year[] $years
     */
    public function setYears(array $years): void
    {
        $all = [];
        foreach ($years as $year) {
            $all[$year->getYear()] = $year;
        }
        ksort($all);
        $this->years = array_values($all);
    }

    public function getBudgetStatisticModel(): ?BudgetStatisticModel
    {
        return $this->budgetStatisticModel;
    }

    public function setBudgetStatisticModel(BudgetStatisticModel $budgetStatisticModel): void
    {
        $this->budgetStatisticModel = $budgetStatisticModel;
    }
}
