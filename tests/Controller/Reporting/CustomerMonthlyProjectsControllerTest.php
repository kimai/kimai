<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\Controller\AbstractControllerBaseTestCase;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

#[Group('integration')]
class CustomerMonthlyProjectsControllerTest extends AbstractControllerBaseTestCase
{
    public function testReportIsSecure(): void
    {
        $this->assertUrlIsSecured('/reporting/customer/monthly_projects/view');
    }

    public function testExportReportIsSecure(): void
    {
        $this->assertUrlIsSecured('/reporting/customer/monthly_projects/export');
    }

    private function prepareReport(): HttpKernelBrowser
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
        $projects->setCallback(function (Project $project): void {
            $project->setIsMonthlyBudget();
        });
        $this->importFixture($projects);

        $activities = new ActivityFixtures();
        $activities->setAmount(5);
        $activities->setIsGlobal(true);
        $activities = $this->importFixture($activities);

        $timesheets = new TimesheetFixtures();
        $timesheets->setAmount(10);
        $timesheets->setActivities($activities);
        $timesheets->setStartDate(new \DateTime('first day of this month'));
        $timesheets->setUser($this->getUserByRole(User::ROLE_TEAMLEAD));
        $this->importFixture($timesheets);
        $timesheets = new TimesheetFixtures();
        $timesheets->setAmount(10);
        $timesheets->setActivities($activities);
        $timesheets->setStartDate(new \DateTime('first day of last month'));
        $timesheets->setUser($this->getUserByRole(User::ROLE_TEAMLEAD));
        $this->importFixture($timesheets);

        return $client;
    }

    public function testReport(): void
    {
        $client = $this->prepareReport();

        $this->assertAccessIsGranted($client, '/reporting/customer/monthly_projects/view');
        self::assertStringContainsString('<form method="get" class="form-reporting" id="report-form">', $client->getResponse()->getContent());
        $rows = $client->getCrawler()->filterXPath("//table[contains(@class, 'dataTable')]/tbody/tr[not(@class='summary')]");
        self::assertGreaterThan(0, $rows->count());
    }

    public function testExport(): void
    {
        $client = $this->prepareReport();

        $this->assertAccessIsGranted($client, '/reporting/customer/monthly_projects/export');

        $response = $client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertInstanceOf(BinaryFileResponse::class, $response);

        // temporary file!
        $file = $response->getFile();
        self::assertFileDoesNotExist($response->getFile());

        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment; filename=kimai-export-users-', $response->headers->get('Content-Disposition'));
    }

    public function testRevenueExcludesNonBillableEntries(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $customers = new CustomerFixtures();
        $customers->setIsVisible(true);
        $customers->setAmount(1);
        $customers = $this->importFixture($customers);

        $projects = new ProjectFixtures();
        $projects->setCustomers($customers);
        $projects->setAmount(1);
        $projects->setIsVisible(true);
        $projects->setCallback(static function (Project $project): void {
            $project->setIsMonthlyBudget();
        });
        $projects = $this->importFixture($projects);

        $activities = new ActivityFixtures();
        $activities->setAmount(1);
        $activities->setProjects($projects);
        $activities->setIsVisible(true);
        $activities = $this->importFixture($activities);

        $counter = 0;
        $timesheets = new TimesheetFixtures();
        $timesheets->setAmount(2);
        $timesheets->setActivities($activities);
        $timesheets->setFixedStartDate(new \DateTime('today 10:00'));
        $timesheets->setUser($this->getUserByRole(User::ROLE_TEAMLEAD));
        $timesheets->setCallback(static function (Timesheet $timesheet) use (&$counter): void {
            $timesheet->setRate($counter === 0 ? 100.0 : 40.0);
            $timesheet->setBillable($counter === 0);
            $counter++;
        });
        $this->importFixture($timesheets);

        $customer = $customers[0];
        self::assertNotNull($customer->getId());

        $this->assertAccessIsGranted($client, \sprintf(
            '/reporting/customer/monthly_projects/view?sumType=rate&customer=%s',
            $customer->getId()
        ));
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('100', $content);
        self::assertStringNotContainsString('140', $content);
    }
}
