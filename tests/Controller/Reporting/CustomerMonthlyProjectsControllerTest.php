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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class CustomerMonthlyProjectsControllerTest extends ControllerBaseTest
{
    public function testReportIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/customer/monthly_projects/view');
    }

    public function testExportReportIsSecure()
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
        $projects->setCallback(function (Project $project) {
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

    public function testReport()
    {
        $client = $this->prepareReport();

        $this->assertAccessIsGranted($client, '/reporting/customer/monthly_projects/view');
        self::assertStringContainsString('<form method="get" class="form-reporting" id="report-form">', $client->getResponse()->getContent());
        $rows = $client->getCrawler()->filterXPath("//table[contains(@class, 'dataTable')]/tbody/tr[not(@class='summary')]");
        self::assertGreaterThan(0, $rows->count());
    }

    public function testExport()
    {
        $client = $this->prepareReport();

        $this->assertAccessIsGranted($client, '/reporting/customer/monthly_projects/export');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        self::assertInstanceOf(BinaryFileResponse::class, $response);

        // temporary file!
        $file = $response->getFile();
        self::assertFileDoesNotExist($response->getFile());

        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=kimai-export-users-', $response->headers->get('Content-Disposition'));
    }
}
