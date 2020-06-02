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

/**
 * @group integration
 */
class SystemConfigurationControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/system-config/');
    }

    public function testIsSecureForRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/system-config/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $expectedForms = $this->getTestDataForms();

        $result = $client->getCrawler()->filter('section.content div.box.box-primary');
        $this->assertEquals(\count($expectedForms), \count($result));

        $result = $client->getCrawler()->filter('section.content div.box.box-primary form');
        $this->assertEquals(\count($expectedForms), \count($result));

        foreach ($expectedForms as $formConfig) {
            $result = $client->getCrawler()->filter($formConfig[0]);
            $this->assertEquals(1, \count($result));
            $form = $result->form();
            $this->assertStringEndsWith($formConfig[1], $form->getUri());
            $this->assertEquals('POST', $form->getMethod());
        }
    }

    public function testSectionAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/edit/timesheet');

        $expectedForms = $this->getTestDataForms();

        $result = $client->getCrawler()->filter('section.content div.box.box-primary');
        $this->assertEquals(1, \count($result));

        $result = $client->getCrawler()->filter('section.content div.box.box-primary form');
        $this->assertEquals(1, \count($result));

        $result = $client->getCrawler()->filter('form[name=system_configuration_form_timesheet]');
        $this->assertEquals(1, \count($result));
        $form = $result->form();
        $this->assertEquals('POST', $form->getMethod());
    }

    public function getTestDataForms()
    {
        return [
            ['form[name=system_configuration_form_timesheet]', $this->createUrl('/admin/system-config/update/timesheet')],
            ['form[name=system_configuration_form_invoice]', $this->createUrl('/admin/system-config/update/invoice')],
            ['form[name=system_configuration_form_rounding]', $this->createUrl('/admin/system-config/update/rounding')],
            ['form[name=system_configuration_form_form_customer]', $this->createUrl('/admin/system-config/update/form_customer')],
            ['form[name=system_configuration_form_form_user]', $this->createUrl('/admin/system-config/update/form_user')],
            ['form[name=system_configuration_form_theme]', $this->createUrl('/admin/system-config/update/theme')],
            ['form[name=system_configuration_form_calendar]', $this->createUrl('/admin/system-config/update/calendar')],
            ['form[name=system_configuration_form_branding]', $this->createUrl('/admin/system-config/update/branding')],
        ];
    }

    public function testUpdateTimesheetConfig()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals('default', $configService->find('timesheet.mode'));
        $this->assertEquals(true, $configService->find('timesheet.rules.allow_future_times'));
        $this->assertEquals(1, $configService->find('timesheet.active_entries.hard_limit'));
        $this->assertEquals(1, $configService->find('timesheet.active_entries.soft_limit'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_timesheet]')->form();
        $client->submit($form, [
            'system_configuration_form_timesheet' => [
                'configuration' => [
                    ['name' => 'timesheet.mode', 'value' => 'duration_only'],
                    ['name' => 'timesheet.active_entries.default_begin', 'value' => '23:59'],
                    ['name' => 'timesheet.rules.allow_future_times', 'value' => false],
                    ['name' => 'timesheet.active_entries.hard_limit', 'value' => 99],
                    ['name' => 'timesheet.active_entries.soft_limit', 'value' => 77],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals('duration_only', $configService->find('timesheet.mode'));
        $this->assertEquals(false, $configService->find('timesheet.rules.allow_future_times'));
        $this->assertEquals(99, $configService->find('timesheet.active_entries.hard_limit'));
        $this->assertEquals(77, $configService->find('timesheet.active_entries.soft_limit'));
    }

    public function testUpdateTimesheetConfigValidation()
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/system-config/',
            'form[name=system_configuration_form_timesheet]',
            [
                'system_configuration_form_timesheet' => [
                    'configuration' => [
                        ['name' => 'timesheet.mode', 'value' => 'foo'],
                        ['name' => 'timesheet.active_entries.default_begin', 'value' => '23:59'],
                        ['name' => 'timesheet.rules.allow_future_times', 'value' => 1],
                        ['name' => 'timesheet.active_entries.hard_limit', 'value' => -1],
                        ['name' => 'timesheet.active_entries.soft_limit', 'value' => -1],
                    ]
                ]
            ],
            [
                '#system_configuration_form_timesheet_configuration_0_value', // mode
                '#system_configuration_form_timesheet_configuration_3_value', // hard_limit
                '#system_configuration_form_timesheet_configuration_4_value', // soft_limit
            ],
            true
        );
    }

    public function testUpdateCustomerConfig()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertNull($configService->find('defaults.customer.timezone'));
        $this->assertEquals('DE', $configService->find('defaults.customer.country'));
        $this->assertEquals('EUR', $configService->find('defaults.customer.currency'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_form_customer]')->form();
        $client->submit($form, [
            'system_configuration_form_form_customer' => [
                'configuration' => [
                    ['name' => 'defaults.customer.timezone', 'value' => 'Atlantic/Canary'],
                    ['name' => 'defaults.customer.country', 'value' => 'BB'],
                    ['name' => 'defaults.customer.currency', 'value' => 'GBP'],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals('Atlantic/Canary', $configService->find('defaults.customer.timezone'));
        $this->assertEquals('BB', $configService->find('defaults.customer.country'));
        $this->assertEquals('GBP', $configService->find('defaults.customer.currency'));
    }

    public function testUpdateUserConfig()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertNull($configService->find('defaults.user.timezone'));
        $this->assertNull($configService->find('defaults.user.theme'));
        $this->assertEquals('en', $configService->find('defaults.user.language'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_form_user]')->form();
        $client->submit($form, [
            'system_configuration_form_form_user' => [
                'configuration' => [
                    ['name' => 'defaults.user.timezone', 'value' => 'Pacific/Tahiti'],
                    ['name' => 'defaults.user.language', 'value' => 'ru'],
                    ['name' => 'defaults.user.theme', 'value' => 'purple'],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals('Pacific/Tahiti', $configService->find('defaults.user.timezone'));
        $this->assertEquals('purple', $configService->find('defaults.user.theme'));
        $this->assertEquals('ru', $configService->find('defaults.user.language'));
    }

    public function testUpdateCustomerConfigValidation()
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/system-config/',
            'form[name=system_configuration_form_form_customer]',
            [
                'system_configuration_form_form_customer' => [
                    'configuration' => [
                        ['name' => 'defaults.customer.timezone', 'value' => 'XX'],
                        ['name' => 'defaults.customer.country', 'value' => 1],
                        ['name' => 'defaults.customer.currency', 'value' => 'XXX'],
                    ]
                ]
            ],
            [
                '#system_configuration_form_form_customer_configuration_0_value',
                '#system_configuration_form_form_customer_configuration_1_value',
                '#system_configuration_form_form_customer_configuration_2_value',
            ],
            true
        );
    }

    public function testUpdateThemeConfig()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals(false, $configService->find('timesheet.markdown_content'));
        $this->assertEquals('selectpicker', $configService->find('theme.select_type'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_theme]')->form();
        $client->submit($form, [
            'system_configuration_form_theme' => [
                'configuration' => [
                    ['name' => 'theme.autocomplete_chars', 'value' => 5],
                    ['name' => 'timesheet.markdown_content', 'value' => 1],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals('selectpicker', $configService->find('theme.select_type'));
        $this->assertEquals(true, $configService->find('timesheet.markdown_content'));
    }

    public function testUpdateThemeConfigValidation()
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/system-config/',
            'form[name=system_configuration_form_theme]',
            [
                'system_configuration_form_theme' => [
                    'configuration' => [
                        ['name' => 'theme.select_type', 'value' => 'foo'],
                        ['name' => 'timesheet.markdown_content', 'value' => 1],
                    ]
                ]
            ],
            [
                '#system_configuration_form_theme_configuration_0_value',
            ],
            true
        );
    }

    public function testUpdateCalendarConfig()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertTrue($configService->find('calendar.week_numbers'));
        $this->assertTrue($configService->find('calendar.weekends'));
        $this->assertEquals('08:00', $configService->find('calendar.businessHours.begin'));
        $this->assertEquals('20:00', $configService->find('calendar.businessHours.end'));
        $this->assertEquals('00:00', $configService->find('calendar.visibleHours.begin'));
        $this->assertEquals('23:59', $configService->find('calendar.visibleHours.end'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_calendar]')->form();
        $client->submit($form, [
            'system_configuration_form_calendar' => [
                'configuration' => [
                    ['name' => 'calendar.week_numbers', 'value' => false],
                    ['name' => 'calendar.weekends', 'value' => false],
                    ['name' => 'calendar.businessHours.begin', 'value' => '10:00'],
                    ['name' => 'calendar.businessHours.end', 'value' => '16:00'],
                    ['name' => 'calendar.visibleHours.begin', 'value' => '05:17'],
                    ['name' => 'calendar.visibleHours.end', 'value' => '21:43'],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $configService = static::$kernel->getContainer()->get(SystemConfiguration::class);
        $this->assertFalse($configService->find('calendar.week_numbers'));
        $this->assertFalse($configService->find('calendar.weekends'));
        $this->assertEquals('10:00', $configService->find('calendar.businessHours.begin'));
        $this->assertEquals('16:00', $configService->find('calendar.businessHours.end'));
        $this->assertEquals('05:17', $configService->find('calendar.visibleHours.begin'));
        $this->assertEquals('21:43', $configService->find('calendar.visibleHours.end'));
    }

    public function testUpdateCalendarConfigValidation()
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/system-config/',
            'form[name=system_configuration_form_calendar]',
            [
                'system_configuration_form_calendar' => [
                    'configuration' => [
                        ['name' => 'calendar.week_numbers', 'value' => 'foo'],
                        ['name' => 'calendar.weekends', 'value' => 'bar'],
                        ['name' => 'calendar.businessHours.begin', 'value' => '25:13'],
                        ['name' => 'calendar.businessHours.end', 'value' => null],
                        ['name' => 'calendar.visibleHours.begin', 'value' => 'aa:bb'],
                        ['name' => 'calendar.visibleHours.end', 'value' => ''],
                    ]
                ]
            ],
            [
                '#system_configuration_form_calendar_configuration_2_value',
                '#system_configuration_form_calendar_configuration_3_value',
                '#system_configuration_form_calendar_configuration_4_value',
                '#system_configuration_form_calendar_configuration_5_value',
            ],
            true
        );
    }
}
