<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @coversDefaultClass \App\Controller\ActivityController
 * @group integration
 */
class ActivityControllerTest extends ControllerBaseTest
{
    public function testRecentActivitiesAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture->setUser($user);
        $fixture->setAmount(1);
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($em, $fixture);

        $this->request($client, '/activities/recent');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();

        $this->assertContains('<li class="dropdown notifications-menu">', $content);
        $this->assertContains('<span class="label label-success">1</span>', $content);
        $this->assertContains('<a href="/en/timesheet/start/1">', $content);
    }
}
