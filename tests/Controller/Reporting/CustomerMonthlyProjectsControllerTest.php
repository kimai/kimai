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
use App\Tests\Controller\AbstractControllerBaseTestCase;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\TeamRestrictedTimesheetTrait;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

#[Group('integration')]
class CustomerMonthlyProjectsControllerTest extends AbstractControllerBaseTestCase
{
    use TeamRestrictedTimesheetTrait;

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

    /**
     * A teamlead sees the records of his team members, but not the ones for projects
     * and activities which are limited to teams he is not part of.
     */
    public function testReportHidesRecordsOfForeignTeams(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->createTeamRestrictedTimesheets();

        $this->assertAccessIsGranted($client, '/reporting/customer/monthly_projects/view?date=2020-05-01');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('Own project', $content);
        self::assertStringContainsString('Own activity', $content);
        // users always see all of their own records
        self::assertStringContainsString('Lost project', $content);
        self::assertStringNotContainsString('Foreign project', $content);
        self::assertStringNotContainsString('Foreign activity', $content);
        self::assertStringNotContainsString('Open project', $content);
        self::assertStringNotContainsString('Restricted activity', $content);

        $this->assertAccessIsGranted($client, '/reporting/customer/monthly_projects/export?date=2020-05-01');
        $content = $this->getExcelExportContent($client);
        self::assertStringContainsString('Own project', $content);
        self::assertStringContainsString('Lost project', $content);
        self::assertStringNotContainsString('Foreign project', $content);
        self::assertStringNotContainsString('Restricted activity', $content);
    }

    /**
     * Only the customer is limited to a team the teamlead is not part of,
     * project and activity have no team.
     */
    public function testReportHidesRecordsOfForeignTeamCustomer(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->createCustomerRestrictedTimesheets();

        $this->assertAccessIsGranted($client, '/reporting/customer/monthly_projects/view?date=2020-05-01');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('Free project', $content);
        self::assertStringContainsString('Free activity', $content);
        // users always see all of their own records
        self::assertStringContainsString('Kept project', $content);
        self::assertStringNotContainsString('Shielded project', $content);
        self::assertStringNotContainsString('Shielded activity', $content);
        self::assertStringNotContainsString('Shielded customer', $content);

        $this->assertAccessIsGranted($client, '/reporting/customer/monthly_projects/export?date=2020-05-01');
        $content = $this->getExcelExportContent($client);
        self::assertStringContainsString('Free project', $content);
        self::assertStringContainsString('Kept project', $content);
        self::assertStringNotContainsString('Shielded project', $content);
        self::assertStringNotContainsString('Shielded activity', $content);
    }

    /**
     * Control for testReportHidesRecordsOfForeignTeams(): users with the permission
     * to see all data are not limited.
     */
    public function testReportShowsRecordsOfForeignTeamsToAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->createTeamRestrictedTimesheets();

        $this->assertAccessIsGranted($client, '/reporting/customer/monthly_projects/view?date=2020-05-01');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('Own project', $content);
        self::assertStringContainsString('Foreign project', $content);
        self::assertStringContainsString('Restricted activity', $content);
        self::assertStringContainsString('Lost project', $content);
    }
}
