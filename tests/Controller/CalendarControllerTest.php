<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Tests\Configuration\TestConfigLoader;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\SystemConfigurationFactory;

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
        $fixtures = new TimesheetFixtures($this->getUserByRole(), 10);
        $fixtures->setStartDate(new \DateTime('-6 month'));
        $this->importFixture($fixtures);

        $this->request($client, '/calendar/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $crawler = $client->getCrawler();
        $calendar = $crawler->filter('div#timesheet_calendar');
        $this->assertEquals(1, $calendar->count());
        $dragAndDropBoxes = $crawler->filter('div.card-body.drag-and-drop-source');
        $this->assertEquals(1, $dragAndDropBoxes->count());
    }

    public function testCalendarActionAsSuperAdmin()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/calendar/');
    }

    public function testCalendarActionWithGoogleSource()
    {
        $loader = new TestConfigLoader([]);
        $config = SystemConfigurationFactory::create($loader, $this->getDefaultSettings());

        $client = $this->getClientForAuthenticatedUser();
        self::getContainer()->set(SystemConfiguration::class, $config);
        $this->request($client, '/calendar/');
        $this->assertSuccessResponse($client);

        $crawler = $client->getCrawler();
        $calendar = $crawler->filter('div#timesheet_calendar');
        $this->assertEquals(1, $calendar->count());

        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString("googleCalendarId: 'de.german#holiday@group.v.calendar.google.com',", $content);
        $this->assertStringContainsString("name: 'holidays'", $content);
        $this->assertStringContainsString("googleCalendarId: 'en.german#holiday@group.v.calendar.google.com',", $content);
        $this->assertStringContainsString("name: 'holidays_en'", $content);
    }

    protected function getDefaultSettings(): array
    {
        return [
            'theme' => [
                'show_about' => true,
                'chart' => [
                    'background_color' => '#3c8dbc',
                    'border_color' => '#3b8bba',
                    'grid_color' => 'rgba(0,0,0,.05)',
                    'height' => '200',
                ],
                'branding' => [
                    'logo' => null,
                    'mini' => null,
                    'company' => null,
                    'title' => null,
                    'translation' => null,
                ],
                'calendar' => [
                    'background_color' => '#d2d6de'
                ],
                'colors_limited' => true,
                'color_choices' => 'Silver|#c0c0c0,Gray|#808080,Black|#000000,Maroon|#800000,Brown|#a52a2a,Red|#ff0000,Orange|#ffa500,Gold|#ffd700,Yellow|#ffff00,Peach|#ffdab9,Khaki|#f0e68c,Olive|#808000,Lime|#00ff00,Jelly|#9acd32,Green|#008000,Teal|#008080,Aqua|#00ffff,LightBlue|#add8e6,DeepSky|#00bfff,Dodger|#1e90ff,Blue|#0000ff,Navy|#000080,Purple|#800080,Fuchsia|#ff00ff,Violet|#ee82ee,Rose|#ffe4e1,Lavender|#E6E6FA'
            ],
            'defaults' => [
                'user' => [
                    'language' => 'en'
                ],
            ],
            'timesheet' => [
                'default_begin' => '08:30:00',
                'mode' => 'default'
            ],
            'calendar' => [
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
                'slot_duration' => '00:15:00',
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
            ],
        ];
    }
}
