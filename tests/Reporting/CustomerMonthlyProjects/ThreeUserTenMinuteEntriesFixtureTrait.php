<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\CustomerMonthlyProjects;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

trait ThreeUserTenMinuteEntriesFixtureTrait
{
    /**
     * Persists three users, each with one 10-minute entry on the same project/activity:
     * the finest granularity the customer-monthly-projects table prints, one row per
     * (project, activity, user). With the default $rates, each row is 0.17h / EUR 1.67.
     *
     * @param float[]|null $rates one rate per user, defaults to 1.6666667 for all three
     * @return array{users: User[], customer: Customer, project: Project, activity: Activity}
     */
    private function createThreeUserTenMinuteEntriesFixture(EntityManagerInterface $em, string $idPrefix, ?string $currency = null, ?array $rates = null): array
    {
        $rates ??= [1.6666667, 1.6666667, 1.6666667];

        $users = [];
        for ($u = 0; $u < 3; $u++) {
            $user = new User();
            $user->setUserIdentifier("{$idPrefix}-user-{$u}");
            $user->setEmail("{$idPrefix}-user-{$u}@example.com");
            $user->setPassword('foo');
            $em->persist($user);
            $users[] = $user;
        }

        $customer = new Customer("{$idPrefix}-customer");
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        if ($currency !== null) {
            $customer->setCurrency($currency);
        }
        $em->persist($customer);

        $project = new Project();
        $project->setName("{$idPrefix}-project");
        $project->setCustomer($customer);
        $em->persist($project);

        $activity = new Activity();
        $activity->setName("{$idPrefix}-activity");
        $activity->setProject($project);
        $em->persist($activity);

        $em->flush();

        $begin = new \DateTime('2020-01-01 08:00:00');

        foreach ($users as $i => $user) {
            $entry = new Timesheet();
            $entry->setUser($user);
            $entry->setProject($project);
            $entry->setActivity($activity);
            $entry->setBegin((clone $begin)->modify("+{$i} hours"));
            $entry->setEnd((clone $begin)->modify("+{$i} hours +10 minutes"));
            $entry->setDuration(600);
            $entry->setFixedRate($rates[$i]);
            $em->persist($entry);
        }

        $em->flush();

        return ['users' => $users, 'customer' => $customer, 'project' => $project, 'activity' => $activity];
    }
}
