<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Configuration\CalendarConfiguration;
use App\Tests\Configuration\TestConfigLoader;

/**
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
        $calendar = $crawler->filter('div#timesheet_calendar');
        $this->assertEquals(1, $calendar->count());
    }

    public function testCalendarActionWithGoogleSource()
    {
        $loader = new TestConfigLoader([]);
        $config = new CalendarConfiguration($loader, $this->getDefaultSettings());

        $client = $this->getClientForAuthenticatedUser();
        $client->getContainer()->set(CalendarConfiguration::class, $config);
        $this->request($client, '/calendar/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $crawler = $client->getCrawler();
        $calendar = $crawler->filter('div#timesheet_calendar');
        $this->assertEquals(1, $calendar->count());

        $content = $client->getResponse()->getContent();
        $this->assertContains("googleCalendarId: 'de.german#holiday@group.v.calendar.google.com',", $content);
        $this->assertContains("name: 'holidays'", $content);
        $this->assertContains("googleCalendarId: 'en.german#holiday@group.v.calendar.google.com',", $content);
        $this->assertContains("name: 'holidays_en'", $content);
    }

    protected function getDefaultSettings()
    {
        return [
            'businessHours' => [
                'days' => [2, 4, 6],
                'begin' => '07:49',
                'end' => '19:27'
            ],
            'visibleHours' => [
                'begin' => '07:49',
                'end' => '19:27'
            ],
            'day_limit' => 20,
            'week_numbers' => false,
            'google' => [
                'api_key' => 'wertwertwegsdfbdf243w567fg8ihuon',
                'sources' => [
                    'holidays' => [
                        'id' => 'de.german#holiday@group.v.calendar.google.com',
                        'color' => '#ccc',
                    ],
                    'holidays_en' => [
                        'id' => 'en.german#holiday@group.v.calendar.google.com',
                        'color' => '#fff',
                    ],
                ]
            ],
            'weekends' => true,
        ];
    }
}
