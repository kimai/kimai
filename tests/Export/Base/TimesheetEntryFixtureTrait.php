<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;

trait TimesheetEntryFixtureTrait
{
    /**
     * @return Timesheet[]
     */
    private function createThreeTenMinuteEntries(): array
    {
        return $this->createEntries(3, 600, 1.6666667);
    }

    /**
     * @return Timesheet[]
     */
    private function createEntries(int $count, int $durationSeconds, float $rate, ?Project $project = null): array
    {
        if ($project === null) {
            $customer = new Customer('Customer Name');
            $project = new Project();
            $project->setName('project name');
            $project->setCustomer($customer);
        }

        $activity = new Activity();
        $activity->setName('activity');
        $activity->setProject($project);

        $user = new User();
        $user->setUserIdentifier('foo-bar');

        $entries = [];
        for ($i = 0; $i < $count; $i++) {
            $entry = new Timesheet();
            $entry->setDuration($durationSeconds);
            $entry->setRate($rate);
            $entry->setInternalRate($rate);
            $entry->setUser($user);
            $entry->setActivity($activity);
            $entry->setProject($project);
            $entry->setBegin(new \DateTime());
            $entry->setEnd(new \DateTime());
            $entries[] = $entry;
        }

        return $entries;
    }
}
