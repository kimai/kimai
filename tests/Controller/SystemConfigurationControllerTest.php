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
 * @covers \App\Controller\SystemConfigurationController
 * @covers \App\Controller\AbstractController
 * @group integration
 */
class SystemConfigurationControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/system-config/');
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/system-config/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $expectedForms = $this->getTestDataForms();

        $result = $client->getCrawler()->filter('section.content div.box.box-primary');
        $this->assertEquals(count($expectedForms), count($result));

        $result = $client->getCrawler()->filter('section.content div.box.box-primary form');
        $this->assertEquals(count($expectedForms), count($result));

        foreach ($expectedForms as $formConfig) {
            $result = $client->getCrawler()->filter($formConfig[0]);
            $this->assertEquals(1, count($result));
            $form = $result->form();
            $this->assertStringEndsWith($formConfig[1], $form->getUri());
            $this->assertEquals('POST', $form->getMethod());
        }
    }

    public function getTestDataForms()
    {
        return [
            ['#system_configuration_form_timesheet', $this->createUrl('/admin/system-config/timesheet')],
            ['#system_configuration_form_form_customer', $this->createUrl('/admin/system-config/customer')],
            ['#system_configuration_form_theme', $this->createUrl('/admin/system-config/theme')],
        ];
    }

    public function testUpdateTimesheetConfig()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = $client->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals(false, $configService->find('timesheet.markdown_content'));
        $this->assertEquals('default', $configService->find('timesheet.mode'));
        $this->assertEquals(true, $configService->find('timesheet.rules.allow_future_times'));
        $this->assertEquals(3, $configService->find('timesheet.active_entries.hard_limit'));
        $this->assertEquals(1, $configService->find('timesheet.active_entries.soft_limit'));

        $form = $client->getCrawler()->filter('#system_configuration_form_timesheet')->form();
        $client->submit($form, [
            'system_configuration_form' => [
                'configuration' => [
                    ['name' => 'timesheet.mode', 'value' => 'duration_only'],
                    ['name' => 'timesheet.markdown_content', 'value' => 1],
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

        $configService = $client->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals(true, $configService->find('timesheet.markdown_content'));
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
            '#system_configuration_form_timesheet',
            [
                'system_configuration_form' => [
                    'configuration' => [
                        ['name' => 'timesheet.mode', 'value' => 'foo'],
                        ['name' => 'timesheet.markdown_content', 'value' => 1],
                        ['name' => 'timesheet.rules.allow_future_times', 'value' => 1],
                        ['name' => 'timesheet.active_entries.hard_limit', 'value' => -1],
                        ['name' => 'timesheet.active_entries.soft_limit', 'value' => -1],
                    ]
                ]
            ],
            [
                '#system_configuration_form_configuration_0_value', // mode
                '#system_configuration_form_configuration_3_value', // hard_limit
                '#system_configuration_form_configuration_4_value', // soft_limit
            ],
            true
        );
    }

    public function testUpdateCustomerConfig()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $configService = $client->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals('Europe/Berlin', $configService->find('defaults.customer.timezone'));
        $this->assertEquals('DE', $configService->find('defaults.customer.country'));
        $this->assertEquals('EUR', $configService->find('defaults.customer.currency'));

        $form = $client->getCrawler()->filter('#system_configuration_form_form_customer')->form();
        $client->submit($form, [
            'system_configuration_form' => [
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

        $configService = $client->getContainer()->get(SystemConfiguration::class);
        $this->assertEquals('Atlantic/Canary', $configService->find('defaults.customer.timezone'));
        $this->assertEquals('BB', $configService->find('defaults.customer.country'));
        $this->assertEquals('GBP', $configService->find('defaults.customer.currency'));
    }

    public function testUpdateCustomerConfigValidation()
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/system-config/',
            '#system_configuration_form_form_customer',
            [
                'system_configuration_form' => [
                    'configuration' => [
                        ['name' => 'defaults.customer.timezone', 'value' => 'XX'],
                        ['name' => 'defaults.customer.country', 'value' => 1],
                        ['name' => 'defaults.customer.currency', 'value' => 'XXX'],
                    ]
                ]
            ],
            [
                '#system_configuration_form_configuration_0_value',
                '#system_configuration_form_configuration_1_value',
                '#system_configuration_form_configuration_2_value',
            ],
            true
        );
    }
}
