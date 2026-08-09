<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\User;
use App\Tests\Controller\AbstractControllerBaseTestCase;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Group('integration')]
class ProjectViewControllerTest extends AbstractControllerBaseTestCase
{
    public function testReportIsSecure(): void
    {
        $this->assertUrlIsSecured('/reporting/project_view');
    }

    public function testReportExportIsSecure(): void
    {
        $this->assertUrlIsSecured('/reporting/project_view/export');
    }

    /**
     * The export route shares its dataset with the report and must therefore be
     * protected by the same permissions, see GHSA-pvc4-crg3-gj44.
     */
    public function testReportExportIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/reporting/project_view/export');
    }

    public function testReportIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/reporting/project_view');
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
        $this->importFixture($projects);

        $activities = new ActivityFixtures();
        $activities->setAmount(5);
        $activities->setIsGlobal(true);
        $activities = $this->importFixture($activities);

        $timesheets = new TimesheetFixtures();
        $timesheets->setAmount(50);
        $timesheets->setActivities($activities);
        $timesheets->setUser($this->getUserByRole(User::ROLE_TEAMLEAD));
        $this->importFixture($timesheets);

        $this->assertAccessIsGranted($client, '/reporting/project_view');
        self::assertStringContainsString('<div class="card-body project_view_reporting-box', $client->getResponse()->getContent());
        $rows = $client->getCrawler()->filterXPath("//table[contains(@class, 'dataTable')]/tbody/tr[not(@class='summary')]");
        self::assertGreaterThan(0, $rows->count());
    }

    public function testReportExport(): void
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
        $this->importFixture($projects);

        $activities = new ActivityFixtures();
        $activities->setAmount(5);
        $activities->setIsGlobal(true);
        $activities = $this->importFixture($activities);

        $timesheets = new TimesheetFixtures();
        $timesheets->setAmount(50);
        $timesheets->setActivities($activities);
        $timesheets->setUser($this->getUserByRole(User::ROLE_TEAMLEAD));
        $this->importFixture($timesheets);

        $this->assertAccessIsGranted($client, '/reporting/project_view/export');
        $response = $client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertNotNull($response->headers->get('Content-Type'));
        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertNotNull($response->headers->get('Content-Disposition'));
        self::assertStringContainsString('attachment; filename=kimai-export-project-overview_', $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }
}
