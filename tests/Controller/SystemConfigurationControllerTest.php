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
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/admin/system-config/');
    }

    public function testIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/system-config/');
    }

    private function getSystemConfiguration(): SystemConfiguration
    {
        return static::getContainer()->get(SystemConfiguration::class); // @phpstan-ignore return.type
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $expectedForms = $this->getTestDataForms();
        $expectedCount = \count($expectedForms) + 1; // the menu is another card

        $result = $client->getCrawler()->filter('section.content div.card');
        $this->assertEquals($expectedCount, \count($result));

        $result = $client->getCrawler()->filter('section.content div.card form');
        $this->assertEquals(\count($expectedForms), \count($result));

        foreach ($expectedForms as $formConfig) {
            $result = $client->getCrawler()->filter($formConfig[0]);
            $this->assertEquals(1, \count($result));
            $form = $result->form();
            $this->assertStringEndsWith($formConfig[1], $form->getUri());
            $this->assertEquals('POST', $form->getMethod());
        }
    }

    public function testSectionAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/edit/timesheet');

        $result = $client->getCrawler()->filter('section.content div.card');
        $this->assertEquals(1, \count($result));

        $result = $client->getCrawler()->filter('section.content div.card form');
        $this->assertEquals(1, \count($result));

        $result = $client->getCrawler()->filter('form[name=system_configuration_form_timesheet]');
        $this->assertEquals(1, \count($result));
        $form = $result->form();
        $this->assertEquals('POST', $form->getMethod());
    }

    /**
     * @return array<array<string>>
     */
    public function getTestDataForms(): array
    {
        return [
            ['form[name=system_configuration_form_timesheet]', $this->createUrl('/admin/system-config/update/timesheet')],
            ['form[name=system_configuration_form_quick_entry]', $this->createUrl('/admin/system-config/update/quick_entry')],
            ['form[name=system_configuration_form_lockdown_period]', $this->createUrl('/admin/system-config/update/lockdown_period')],
            ['form[name=system_configuration_form_invoice]', $this->createUrl('/admin/system-config/update/invoice')],
            ['form[name=system_configuration_form_authentication]', $this->createUrl('/admin/system-config/update/authentication')],
            ['form[name=system_configuration_form_rounding]', $this->createUrl('/admin/system-config/update/rounding')],
            ['form[name=system_configuration_form_customer]', $this->createUrl('/admin/system-config/update/customer')],
            ['form[name=system_configuration_form_project]', $this->createUrl('/admin/system-config/update/project')],
            ['form[name=system_configuration_form_activity]', $this->createUrl('/admin/system-config/update/activity')],
            ['form[name=system_configuration_form_user]', $this->createUrl('/admin/system-config/update/user')],
            ['form[name=system_configuration_form_theme]', $this->createUrl('/admin/system-config/update/theme')],
            ['form[name=system_configuration_form_calendar]', $this->createUrl('/admin/system-config/update/calendar')],
            ['form[name=system_configuration_form_branding]', $this->createUrl('/admin/system-config/update/branding')],
        ];
    }

    public function testUpdateTimesheetConfig(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = $this->getSystemConfiguration();
        $this->assertEquals('default', $configService->find('timesheet.mode'));
        $this->assertTrue($configService->find('timesheet.rules.allow_future_times'));
        $this->assertTrue($configService->find('timesheet.rules.allow_zero_duration'));
        $this->assertEquals(1, $configService->find('timesheet.active_entries.hard_limit'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_timesheet]')->form();
        $client->submit($form, [
            'system_configuration_form_timesheet' => [
                'configuration' => [
                    ['name' => 'timesheet.mode', 'value' => 'punch'],
                    ['name' => 'timesheet.default_begin', 'value' => '23:59'],
                    ['name' => 'timesheet.rules.allow_future_times', 'value' => false],
                    ['name' => 'timesheet.rules.allow_zero_duration', 'value' => true],
                    ['name' => 'timesheet.rules.allow_overlapping_records', 'value' => false],
                    ['name' => 'timesheet.rules.allow_overbooking_budget', 'value' => false],
                    ['name' => 'timesheet.active_entries.hard_limit', 'value' => 99],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $configService = $this->getSystemConfiguration();
        $this->assertEquals('punch', $configService->find('timesheet.mode'));
        $this->assertFalse($configService->find('timesheet.rules.allow_future_times'));
        $this->assertFalse($configService->find('timesheet.rules.allow_overlapping_records'));
        $this->assertEquals(99, $configService->find('timesheet.active_entries.hard_limit'));
    }

    public function testUpdateLockdownPeriodConfig(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = $this->getSystemConfiguration();
        $this->assertNull($configService->find('timesheet.rules.lockdown_period_start'));
        $this->assertNull($configService->find('timesheet.rules.lockdown_period_end'));
        $this->assertNull($configService->find('timesheet.rules.lockdown_period_timezone'));
        $this->assertNull($configService->find('timesheet.rules.lockdown_grace_period'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_lockdown_period]')->form();
        $client->submit($form, [
            'system_configuration_form_lockdown_period' => [
                'configuration' => [
                    ['name' => 'timesheet.rules.lockdown_period_start', 'value' => 'first day of last month 01:23:45'],
                    ['name' => 'timesheet.rules.lockdown_period_end', 'value' => 'last day of last month 23:01:45'],
                    ['name' => 'timesheet.rules.lockdown_period_timezone', 'value' => 'Africa/Bangui'],
                    ['name' => 'timesheet.rules.lockdown_grace_period', 'value' => '+ 12 hours'],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $configService = $this->getSystemConfiguration();
        $this->assertEquals('first day of last month 01:23:45', $configService->find('timesheet.rules.lockdown_period_start'));
        $this->assertEquals('last day of last month 23:01:45', $configService->find('timesheet.rules.lockdown_period_end'));
        $this->assertEquals('Africa/Bangui', $configService->find('timesheet.rules.lockdown_period_timezone'));
        $this->assertEquals('+ 12 hours', $configService->find('timesheet.rules.lockdown_grace_period'));
    }

    public function testUpdateTimesheetConfigValidation(): void
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/system-config/',
            'form[name=system_configuration_form_timesheet]',
            [
                'system_configuration_form_timesheet' => [
                    'configuration' => [
                        ['name' => 'timesheet.mode', 'value' => 'foo'],
                        ['name' => 'timesheet.default_begin', 'value' => '23:59'],
                        ['name' => 'timesheet.rules.allow_future_times', 'value' => 1],
                        ['name' => 'timesheet.rules.allow_zero_duration', 'value' => 1],
                        ['name' => 'timesheet.rules.allow_overlapping_records', 'value' => 1],
                        ['name' => 'timesheet.rules.allow_overbooking_budget', 'value' => 1],
                        ['name' => 'timesheet.active_entries.hard_limit', 'value' => -1],
                    ]
                ]
            ],
            [
                '#system_configuration_form_timesheet_configuration_0_value', // mode
                '#system_configuration_form_timesheet_configuration_6_value', // hard_limit
            ]
        );
    }

    public function testUpdateCustomerConfig(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = $this->getSystemConfiguration();
        $this->assertNull($configService->find('defaults.customer.timezone'));
        $this->assertEquals('DE', $configService->find('defaults.customer.country'));
        $this->assertEquals('EUR', $configService->find('defaults.customer.currency'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_customer]')->form();
        $client->submit($form, [
            'system_configuration_form_customer' => [
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

        $configService = $this->getSystemConfiguration();
        $this->assertEquals('Atlantic/Canary', $configService->find('defaults.customer.timezone'));
        $this->assertEquals('BB', $configService->find('defaults.customer.country'));
        $this->assertEquals('GBP', $configService->find('defaults.customer.currency'));
    }

    public function testUpdateCustomerConfigWithSingleParam(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/edit/customer');

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_customer]')->form();
        self::assertStringEndsWith('/admin/system-config/update/customer/1', $form->getUri());
        $client->submit($form, [
            'system_configuration_form_customer' => [
                'configuration' => [
                    ['name' => 'defaults.customer.timezone', 'value' => 'Atlantic/Canary'],
                    ['name' => 'defaults.customer.country', 'value' => 'BB'],
                    ['name' => 'defaults.customer.currency', 'value' => 'GBP'],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/edit/customer'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);
    }

    public function testUpdateUserConfig(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/edit/user');

        $configService = $this->getSystemConfiguration();
        $this->assertNull($configService->find('defaults.user.timezone'));
        $this->assertEquals('default', $configService->find('defaults.user.theme'));
        $this->assertEquals('en', $configService->find('defaults.user.language'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_user]')->form();
        $client->submit($form, [
            'system_configuration_form_user' => [
                'configuration' => [
                    ['name' => 'defaults.user.timezone', 'value' => 'Pacific/Tahiti'],
                    ['name' => 'defaults.user.language', 'value' => 'ru'],
                    ['name' => 'defaults.user.theme', 'value' => 'dark'],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/edit/user'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $configService = $this->getSystemConfiguration();
        $this->assertEquals('Pacific/Tahiti', $configService->find('defaults.user.timezone'));
        $this->assertEquals('dark', $configService->find('defaults.user.theme'));
        $this->assertEquals('ru', $configService->find('defaults.user.language'));
    }

    public function testUpdateCustomerConfigValidation(): void
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/system-config/',
            'form[name=system_configuration_form_customer]',
            [
                'system_configuration_form_customer' => [
                    'configuration' => [
                        ['name' => 'defaults.customer.timezone', 'value' => 'XX'],
                        ['name' => 'defaults.customer.country', 'value' => 1],
                        ['name' => 'defaults.customer.currency', 'value' => 'XXX'],
                    ]
                ]
            ],
            [
                '#system_configuration_form_customer_configuration_0_value',
                '#system_configuration_form_customer_configuration_1_value',
                '#system_configuration_form_customer_configuration_2_value',
            ]
        );
    }

    public function testUpdateThemeConfig(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = $this->getSystemConfiguration();
        $this->assertFalse($configService->find('timesheet.markdown_content'));

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_theme]')->form();
        $client->submit($form, [
            'system_configuration_form_theme' => [
                'configuration' => [
                    ['name' => 'timesheet.markdown_content', 'value' => 1],
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/system-config/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $configService = $this->getSystemConfiguration();
        $this->assertTrue($configService->find('timesheet.markdown_content'));
    }

    public function testUpdateThemeConfigValidation(): void
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/system-config/',
            'form[name=system_configuration_form_theme]',
            [
                'system_configuration_form_theme' => [
                    'configuration' => [
                        ['name' => 'timesheet.markdown_content', 'value' => 1],
                        ['name' => 'theme.color_choices', 'value' => '112324567865=)(/&%$§Silver|#c0c0c0'],
                    ]
                ]
            ],
            [
                '#system_configuration_form_theme_configuration_1_value',
            ]
        );
    }

    public function testUpdateCalendarConfig(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = $this->getSystemConfiguration();
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

        $configService = $this->getSystemConfiguration();
        $this->assertFalse($configService->find('calendar.week_numbers'));
        $this->assertFalse($configService->find('calendar.weekends'));
        $this->assertEquals('10:00', $configService->find('calendar.businessHours.begin'));
        $this->assertEquals('16:00', $configService->find('calendar.businessHours.end'));
        $this->assertEquals('05:17', $configService->find('calendar.visibleHours.begin'));
        $this->assertEquals('21:43', $configService->find('calendar.visibleHours.end'));
    }

    public function testUpdateCalendarConfigValidation(): void
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
            ]
        );
    }
}
