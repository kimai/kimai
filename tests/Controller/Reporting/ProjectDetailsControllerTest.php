<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\User;
use App\Tests\Controller\ControllerBaseTest;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @group integration
 */
class ProjectDetailsControllerTest extends ControllerBaseTest
{
    public function testReportIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/project_details');
    }

    public function testReport()
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
        $projects = $this->importFixture($projects);

        $activities = new ActivityFixtures();
        $activities->setAmount(1);
        $activities->setIsGlobal(true);
        $activities = $this->importFixture($activities);

        $timesheets = new TimesheetFixtures();
        $timesheets->setAmount(50);
        $timesheets->setActivities($activities);
        $this->importFixture($timesheets);

        $this->assertAccessIsGranted($client, '/reporting/project_details');
        $this->assertHasNoEntriesWithFilter($client);

        $this->assertAccessIsGranted($client, '/reporting/project_details?project=' . $projects[0]->getId());
        $rows = $client->getCrawler()->filterXPath("//form[@id='report-form']");
        self::assertEquals(1, $rows->count());

        $rows = $client->getCrawler()->filterXPath("//div[@id='reporting-content']//ul[contains(@class, 'nav-pills')]");
        self::assertGreaterThan(1, $rows->count());
    }
}
