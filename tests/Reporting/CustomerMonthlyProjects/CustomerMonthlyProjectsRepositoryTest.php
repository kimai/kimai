<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\CustomerMonthlyProjects;

use App\Reporting\CustomerMonthlyProjects\CustomerMonthlyProjectsRepository;
use App\Tests\Repository\AbstractRepositoryTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(CustomerMonthlyProjectsRepository::class)]
#[Group('integration')]
class CustomerMonthlyProjectsRepositoryTest extends AbstractRepositoryTestCase
{
    use ThreeUserTenMinuteEntriesFixtureTrait;

    public function testDurationAndRateDecimalReconcileWithRoundedRowSum(): void
    {
        $em = $this->getEntityManager();
        $fixture = $this->createThreeUserTenMinuteEntriesFixture($em, 'bug-6039');
        $users = $fixture['users'];
        $customer = $fixture['customer'];

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
