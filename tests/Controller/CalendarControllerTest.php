<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

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

    /**
     * TODO load test fixtures to make sure a proper results is returned
     */
    public function testCalendarEntriesAction()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/calendar/user');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        $this->assertNotNull($json);
        $this->assertNotFalse($json);
        $this->assertInternalType('array', $json);
    }

    public function testCalendarEntriesActionWithStartAndEndDate()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/calendar/user?start=2017-01-01&end=2017-12-31');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        $this->assertNotNull($json);
        $this->assertNotFalse($json);
        $this->assertInternalType('array', $json);
    }
}
