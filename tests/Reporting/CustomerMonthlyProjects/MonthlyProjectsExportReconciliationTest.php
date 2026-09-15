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
use PHPUnit\Framework\Attributes\Group;
use Twig\Environment;

/**
 * Reproduces BUG-6039 for the customer monthly projects export: the export template used
 * to override the project_activity/project_total blocks of monthly_projects_data.html.twig
 * with stale, unreconciled logic, silently undoing the fix made in the shared template.
 */
#[Group('integration')]
class MonthlyProjectsExportReconciliationTest extends AbstractRepositoryTestCase
{
    use ThreeUserTenMinuteEntriesFixtureTrait;

    private function renderExportTable(string $dataType): string
    {
        $em = $this->getEntityManager();
        $fixture = $this->createThreeUserTenMinuteEntriesFixture($em, 'bug-6039-export');
        $users = $fixture['users'];
        $customer = $fixture['customer'];

        /** @var CustomerMonthlyProjectsRepository $repository */
        $repository = self::getContainer()->get(CustomerMonthlyProjectsRepository::class);

        $stats = $repository->getGroupedByCustomerProjectActivityUser(
            new \DateTime('2020-01-01 00:00:00'),
            new \DateTime('2020-01-01 23:59:59'),
            $users,
            $customer
        );

        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        return $twig->render('reporting/customer/monthly_projects_export.html.twig', [
            'dataType' => $dataType,
            'stats' => $stats,
        ]);
    }

    public function testDurationTotalReconcilesWithRoundedRowSum(): void
    {
        $content = $this->renderExportTable('duration');

        // the raw sum is 1800 seconds -> round(1800/3600, 2) == 0.50, but the reconciled
        // total from three already-rounded 0.17 rows must be 0.51
        self::assertStringContainsString('0.51', $content);
        self::assertStringNotContainsString('0.50', $content);
    }

    public function testRateTotalReconcilesWithRoundedRowSum(): void
    {
        $content = $this->renderExportTable('rate');

        // three rows of 1.6666667 print as 1.67 each; the reconciled total is 5.01,
        // not round(3 * 1.6666667, 2) == 5.00
        self::assertStringContainsString('5.01', $content);
        self::assertStringNotContainsString('5.00', $content);
    }
}
