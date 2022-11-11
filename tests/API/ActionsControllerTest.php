<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @group integration
 */
class ActionsControllerTest extends APIControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/actions/timesheet/1/index/en');
    }

    public function test_getTimesheetActions()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $items = $this->importFixture(new TimesheetFixtures($this->getUserByRole(User::ROLE_USER), 1));
        $this->assertAccessIsGranted($client, sprintf('/api/actions/timesheet/%s/index/en', $items[0]->getId()));
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        foreach ($result as $item) {
            self::assertApiResponseTypeStructure('PageActionItem', $item);
        }

        self::assertEquals('repeat', $result[0]['id']);
        self::assertEquals('edit', $result[1]['id']);
        self::assertEquals('copy', $result[2]['id']);
        self::assertEquals('divider0', $result[3]['id']);
    }

    public function test_getActivityActions()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $customers = $this->importFixture(new CustomerFixtures(1));

        $projectFixture = new ProjectFixtures(1);
        $projectFixture->setCustomers($customers);
        $projects = $this->importFixture($projectFixture);

        $activityFixture = new ActivityFixtures(1);
        $activityFixture->setProjects($projects);
        $activities = $this->importFixture($activityFixture);

        $this->assertAccessIsGranted($client, sprintf('/api/actions/activity/%s/index/en', $activities[0]->getId()));
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        foreach ($result as $item) {
            self::assertApiResponseTypeStructure('PageActionItem', $item);
        }

        self::assertEquals('details', $result[0]['id']);
        self::assertEquals('edit', $result[1]['id']);
        self::assertEquals('permissions', $result[2]['id']);
        self::assertEquals('divider0', $result[3]['id']);
        self::assertEquals('filter', $result[4]['id']);
        self::assertEquals('divider1', $result[5]['id']);
        self::assertEquals('trash', $result[6]['id']);
    }

    public function test_getProjectActions()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $customers = $this->importFixture(new CustomerFixtures(1));

        $projectFixture = new ProjectFixtures(1);
        $projectFixture->setCustomers($customers);
        $projects = $this->importFixture($projectFixture);

        $this->assertAccessIsGranted($client, sprintf('/api/actions/project/%s/index/en', $projects[0]->getId()));
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        foreach ($result as $item) {
            self::assertApiResponseTypeStructure('PageActionItem', $item);
        }

        self::assertEquals('details', $result[0]['id']);
        self::assertEquals('edit', $result[1]['id']);
        self::assertEquals('permissions', $result[2]['id']);
        self::assertEquals('divider0', $result[3]['id']);
        self::assertEquals('filter', $result[4]['id']);
        self::assertEquals('divider1', $result[5]['id']);
        self::assertEquals('report_project_details', $result[6]['id']);
        self::assertEquals('trash', $result[7]['id']);
    }

    public function test_getCustomerActions()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $customers = $this->importFixture(new CustomerFixtures(1));

        $this->assertAccessIsGranted($client, sprintf('/api/actions/customer/%s/index/en', $customers[0]->getId()));
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        foreach ($result as $item) {
            self::assertApiResponseTypeStructure('PageActionItem', $item);
        }

        self::assertEquals('details', $result[0]['id']);
        self::assertEquals('edit', $result[1]['id']);
        self::assertEquals('permissions', $result[2]['id']);
        self::assertEquals('vcard', $result[3]['id']);
        self::assertEquals('divider0', $result[4]['id']);
        self::assertEquals('filter', $result[5]['id']);
        self::assertEquals('divider1', $result[6]['id']);
        self::assertEquals('report', $result[7]['id']);
        self::assertEquals('trash', $result[8]['id']);
    }
}
