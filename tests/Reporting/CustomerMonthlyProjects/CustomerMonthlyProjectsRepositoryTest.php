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
use App\Reporting\CustomerMonthlyProjects\CustomerMonthlyProjectsRepository;
use App\Tests\Repository\AbstractRepositoryTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(CustomerMonthlyProjectsRepository::class)]
#[Group('integration')]
class CustomerMonthlyProjectsRepositoryTest extends AbstractRepositoryTestCase
{
    public function testDurationAndRateDecimalReconcileWithRoundedRowSum(): void
    {
        $em = $this->getEntityManager();

        $users = [];
        for ($u = 0; $u < 3; $u++) {
            $user = new User();
            $user->setUserIdentifier("bug-6039-user-{$u}");
            $user->setEmail("bug-6039-user-{$u}@example.com");
            $user->setPassword('foo');
            $em->persist($user);
            $users[] = $user;
        }

        $customer = new Customer('bug-6039-customer');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);

        $project = new Project();
        $project->setName('bug-6039-project');
        $project->setCustomer($customer);
        $em->persist($project);

        $activity = new Activity();
        $activity->setName('bug-6039-activity');
        $activity->setProject($project);
        $em->persist($activity);

        $em->flush();

        $begin = new \DateTime('2020-01-01 08:00:00');

        // three different users, each with one 10-minute entry at an hourly rate of 10.00:
        // the SQL groups by (project, activity, user), so each user's total is its own row -
        // the finest granularity this table ever prints. Each row is 0.17h / EUR 1.67.
        foreach ($users as $i => $user) {
            $entry = new Timesheet();
            $entry->setUser($user);
            $entry->setProject($project);
            $entry->setActivity($activity);
            $entry->setBegin((clone $begin)->modify("+{$i} hours"));
            $entry->setEnd((clone $begin)->modify("+{$i} hours +10 minutes"));
            $entry->setDuration(600);
            $entry->setFixedRate(1.6666667);
            $em->persist($entry);
        }

        $em->flush();

        /** @var CustomerMonthlyProjectsRepository $sut */
        $sut = self::getContainer()->get(CustomerMonthlyProjectsRepository::class);

        $result = $sut->getGroupedByCustomerProjectActivityUser(
            new \DateTime('2020-01-01 00:00:00'),
            new \DateTime('2020-01-01 23:59:59'),
            $users,
            $customer
        );

        $projectStats = array_values($result['stats'])[0];
        $activityStats = array_values($projectStats['activities'])[0];

        // the raw sum is 1800 seconds -> round(1800/3600, 2) == 0.50, but the sum of the
        // three already-rounded 0.17 rows is 0.51 - durationDecimal must hold that
        self::assertSame(1800, $projectStats['duration']);
        self::assertEqualsWithDelta(0.51, $projectStats['durationDecimal'], 0.00001);
        self::assertEqualsWithDelta(0.51, $activityStats['durationDecimal'], 0.00001);

        // three rows of 1.6666667 print as 1.67 each; the reconciled sum is 5.01, not
        // round(3 * 1.6666667, 2) == 5.00
        self::assertEqualsWithDelta(5.01, $projectStats['rateDecimal'], 0.00001);
        self::assertEqualsWithDelta(5.01, $activityStats['rateDecimal'], 0.00001);
    }
}
