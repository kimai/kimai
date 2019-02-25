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
 * @coversDefaultClass \App\Controller\CalendarController
 * @group integration
 */
class CalendarControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/calendar/');
    }

    public function testCalendarAction()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/calendar/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $crawler = $client->getCrawler();
        $calendar = $crawler->filter('div#calendar');
    }

    public function testCalendarEntriesAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($this->getUserByRole($em, User::ROLE_USER));
        $fixture->setStartDate('2017-05-01');
        $this->importFixture($em, $fixture);

        $this->request($client, '/calendar/user');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertEmpty($json);
    }

    public function testCalendarEntriesActionWithStartAndEndDate()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($this->getUserByRole($em, User::ROLE_USER));
        $fixture->setStartDate('2017-05-01');
        $this->importFixture($em, $fixture);

        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/calendar/user?start=2017-05-01&end=2017-05-30');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertNotEmpty($json);
        $this->assertEquals(10, count($json));
        foreach ($json as $result) {
            $this->assertIsArray($result);
            $this->assertCalendarStructure($result);
        }
    }

    protected function assertCalendarStructure(array $result)
    {
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('project', $result);
        $this->assertArrayHasKey('activity', $result);
        $this->assertArrayHasKey('borderColor', $result);
        $this->assertArrayHasKey('backgroundColor', $result);

        if (isset($result['end'])) {
            $this->assertNull($result['borderColor']);
            $this->assertNull($result['backgroundColor']);
        }
    }
}
