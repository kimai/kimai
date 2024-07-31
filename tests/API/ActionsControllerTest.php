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
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/actions/timesheet/1/index/en');
    }

    public function test_getTimesheetActions(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $items = $this->importFixture(new TimesheetFixtures($this->getUserByRole(User::ROLE_USER), 1));

        $views = [
            'index' => [
                'repeat',
                'edit',
                'copy',
                'divider0',
                'trash',
            ],
            'calendar' => [
                'repeat',
                'edit',
                'copy',
                'divider0',
                'trash',
            ],
            'custom' => [
                'repeat',
                'edit',
                'copy',
                'divider0',
            ],
        ];

        foreach ($views as $view => $entries) {
            $this->assertAccessIsGranted($client, sprintf('/api/actions/timesheet/%s/%s/en', $items[0]->getId(), $view));
            $result = json_decode($client->getResponse()->getContent(), true);

            $this->assertIsArray($result);
            foreach ($result as $item) {
                self::assertApiResponseTypeStructure('PageActionItem', $item);
            }

            $i = 0;
            foreach ($entries as $id) {
                self::assertEquals($id, $result[$i]['id'], sprintf('Failed action "%s" with name "%s" in view "%s"', $i, $id, $view));
                $i++;
            }
        }
    }

    public function test_getActivityActions(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $customers = $this->importFixture(new CustomerFixtures(1));

        $projectFixture = new ProjectFixtures(1);
        $projectFixture->setCustomers($customers);
        $projects = $this->importFixture($projectFixture);

        $activityFixture = new ActivityFixtures(1);
        $activityFixture->setProjects($projects);
        $activities = $this->importFixture($activityFixture);

        $views = [
            'index' => [
                'details',
                'edit',
                'permissions',
                'divider0',
                'filter',
                'divider1',
                'trash',
            ],
            'custom' => [
                'details',
                'edit',
                'permissions',
                'divider0',
                'filter',
            ],
        ];

        foreach ($views as $view => $entries) {
            $this->assertAccessIsGranted($client, sprintf('/api/actions/activity/%s/%s/en', $activities[0]->getId(), $view));
            $result = json_decode($client->getResponse()->getContent(), true);

            $this->assertIsArray($result);
            foreach ($result as $item) {
                self::assertApiResponseTypeStructure('PageActionItem', $item);
            }

            $i = 0;
            foreach ($entries as $id) {
                self::assertEquals($id, $result[$i]['id'], sprintf('Failed action "%s" with name "%s" in view "%s"', $i, $id, $view));
                $i++;
            }
        }
    }

    public function test_getProjectActions(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $customers = $this->importFixture(new CustomerFixtures(1));

        $projectFixture = new ProjectFixtures(1);
        $projectFixture->setCustomers($customers);
        $projects = $this->importFixture($projectFixture);

        $views = [
            'index' => [
                'details',
                'edit',
                'permissions',
                'divider0',
                'filter',
                'divider1',
                'report_project_details',
                'trash',
            ],
            'custom' => [
                'details',
                'edit',
                'permissions',
                'divider0',
                'filter',
                'divider1',
                'report_project_details',
            ],
        ];

        foreach ($views as $view => $entries) {
            $this->assertAccessIsGranted($client, sprintf('/api/actions/project/%s/%s/en', $projects[0]->getId(), $view));
            $result = json_decode($client->getResponse()->getContent(), true);

            $this->assertIsArray($result);
            foreach ($result as $item) {
                self::assertApiResponseTypeStructure('PageActionItem', $item);
            }

            $i = 0;
            foreach ($entries as $id) {
                self::assertEquals($id, $result[$i]['id'], sprintf('Failed action "%s" with name "%s" in view "%s"', $i, $id, $view));
                $i++;
            }
        }
    }

    public function test_getCustomerActions(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $customers = $this->importFixture(new CustomerFixtures(1));

        $views = [
            'index' => [
                'details',
                'edit',
                'permissions',
                'divider0',
                'filter',
                'divider1',
                'report',
                'trash',
            ],
            'custom' => [
                'details',
                'edit',
                'permissions',
                'divider0',
                'filter',
                'divider1',
                'report',
            ],
        ];

        foreach ($views as $view => $entries) {
            $this->assertAccessIsGranted($client, sprintf('/api/actions/customer/%s/%s/en', $customers[0]->getId(), $view));
            $result = json_decode($client->getResponse()->getContent(), true);

            $this->assertIsArray($result);
            foreach ($result as $item) {
                self::assertApiResponseTypeStructure('PageActionItem', $item);
            }

            $i = 0;
            foreach ($entries as $id) {
                self::assertEquals($id, $result[$i]['id'], sprintf('Failed action "%s" with name "%s" in view "%s"', $i, $id, $view));
                $i++;
            }
        }
    }
}
