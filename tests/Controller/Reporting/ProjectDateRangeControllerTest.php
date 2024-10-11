<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\Project;
use App\Entity\User;
use App\Tests\Controller\ControllerBaseTest;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Timesheet\DateTimeFactory;

/**
 * @group integration
 */
class ProjectDateRangeControllerTest extends ControllerBaseTest
{
    public function testReportIsSecure(): void
    {
        $this->assertUrlIsSecured('/reporting/project_daterange');
    }

    public function testReport(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $customers = new CustomerFixtures();
        $customers->setIsVisible(true);
        $customers->setAmount(1);
        $customers = $this->importFixture($customers);

        $projects = new ProjectFixtures();
        $projects->setCustomers($customers);
        $projects->setAmount(2);
        $projects->setIsVisible(true);
        $projects->setCallback(function (Project $project) {
            $project->setIsMonthlyBudget();
        });
        $this->importFixture($projects);

        $activities = new ActivityFixtures();
        $activities->setAmount(5);
        $activities->setIsGlobal(true);
        $activities = $this->importFixture($activities);

        $user = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $dateTimeFactory = DateTimeFactory::createByUser($user);
        $startMonth = $dateTimeFactory->getStartOfMonth();
        $startDate = $startMonth->add(new \DateInterval('P10D'));

        $timesheets = new TimesheetFixtures();
        $timesheets->setStartDate($startDate);
        $timesheets->setAmount(50);
        $timesheets->setActivities($activities);
        $timesheets->setUser($user);
        $this->importFixture($timesheets);

        $this->assertAccessIsGranted($client, '/reporting/project_daterange');
        self::assertStringContainsString('<div class="card-body project_daterange_reporting-box', $client->getResponse()->getContent());
        $rows = $client->getCrawler()->filterXPath("//table[contains(@class, 'dataTable')]/tbody/tr[not(@class='summary')]");
        self::assertGreaterThan(0, $rows->count());
    }
}
