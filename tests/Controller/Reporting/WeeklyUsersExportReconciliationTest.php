<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\User;
use App\Model\DailyStatistic;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * Reproduces BUG-6039 for the weekly users export (route report_weekly_users_export,
 * served by ReportUsersWeekController::export via reporting/report_user_list_export.html.twig):
 * a week of five 29-minute entries must reconcile its period cells against the user and
 * column totals.
 */
#[Group('integration')]
class WeeklyUsersExportReconciliationTest extends KernelTestCase
{
    public function testFiveTwentyNineMinuteDaysReconcileUserAndColumnTotals(): void
    {
        self::bootKernel();

        $user = new User();
        $user->setUserIdentifier('bug-6039-weekly-user');

        $begin = new \DateTime('2020-01-06 00:00:00'); // Monday
        $end = new \DateTime('2020-01-11 00:00:00'); // following Saturday

        $stat = new DailyStatistic($begin, $end, $user);
        foreach ($stat->getDays() as $day) {
            // Monday through Friday each get one 29-minute (1740 second) entry
            if ((int) $day->getDate()->format('N') <= 5) {
                $day->setTotalDuration(1740);
            }
        }

        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $content = $twig->render('reporting/report_user_list_export.html.twig', [
            'stats' => [$stat],
            'dataType' => 'duration',
            'period_attribute' => 'days',
        ]);

        // each of the five working days prints 0.48 (round(1740/3600, 2) per day);
        // the raw-sum total would be round(5 * 1740 / 3600, 2) == 2.42, but the reconciled
        // total of the five printed 0.48 cells is 2.40
        self::assertGreaterThanOrEqual(5, substr_count($content, '0.48'));
        self::assertStringContainsString('2.40', $content);
        self::assertStringNotContainsString('2.42', $content);
    }
}
