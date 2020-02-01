<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\AppExtension;
use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \App\DependencyInjection\AppExtension
 */
class AppExtensionTest extends TestCase
{
    /**
     * @var AppExtension
     */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new AppExtension();
    }

    /**
     * @return ContainerBuilder
     */
    private function getContainer()
    {
        $container = new ContainerBuilder();
        $container->setParameter('app_locales', 'de|en|tr|zh_CN');

        return $container;
    }

    protected function getMinConfig()
    {
        return [
            'kimai' => [
                'languages' => [
                    'en' => [
                        'date_type' => 'dd. MM. yyyy',
                        'date' => 'A-m-d'
                    ],
                    'tr' => [
                        'date' => 'X-m-d'
                    ],
                ],
                'data_dir' => '/tmp/',
                'plugin_dir' => '/tmp/',
                'timesheet' => [],
                'saml' => [
                    'connection' => []
                ]
            ]
        ];
    }

    public function testDefaultValues()
    {
        $minConfig = $this->getMinConfig();

        $this->extension->load($minConfig, $container = $this->getContainer());

        $expected = [
            'kimai.data_dir' => '/tmp/',
            'kimai.plugin_dir' => '/tmp/',
            'kimai.languages' => [
                'en' => [
                    'date_time_type' => 'yyyy-MM-dd HH:mm',
                    'date_type' => 'dd. MM. yyyy',
                    'date' => 'A-m-d',
                    'date_time' => 'm-d H:i',
                    'duration' => '%%h:%%m h',
                    'time' => 'H:i',
                    '24_hours' => true,
                ],
                'de' => [
                    'date_time_type' => 'yyyy-MM-dd HH:mm',
                    'date_type' => 'dd. MM. yyyy',
                    'date' => 'A-m-d',
                    'date_time' => 'm-d H:i',
                    'duration' => '%%h:%%m h',
                    'time' => 'H:i',
                    '24_hours' => true,
                ],
                'tr' => [
                    'date_time_type' => 'yyyy-MM-dd HH:mm',
                    // this value if pre-filled by the Configuration object, as "tr" is defined in the min config
                    // and the other languages (not defined in min config) are "only" copied during runtime from "en"
                    'date_type' => 'yyyy-MM-dd',
                    'date' => 'X-m-d',
                    'date_time' => 'm-d H:i',
                    'duration' => '%%h:%%m h',
                    'time' => 'H:i',
                    '24_hours' => true,
                ],
                'zh_CN' => [
                    'date_time_type' => 'yyyy-MM-dd HH:mm',
                    'date_type' => 'dd. MM. yyyy',
                    'date' => 'A-m-d',
                    'date_time' => 'm-d H:i',
                    'duration' => '%%h:%%m h',
                    'time' => 'H:i',
                    '24_hours' => true,
                ],
            ],
            'kimai.calendar' => [
                'week_numbers' => true,
                'day_limit' => 4,
                'slot_duration' => '00:30:00',
                'businessHours' => [
                    'days' => [1, 2, 3, 4, 5],
                    'begin' => '08:00',
                    'end' => '20:00',
                ],
                'visibleHours' => [
                    'begin' => '00:00',
                    'end' => '23:59',
                ],
                'google' => [
                    'api_key' => null,
                    'sources' => [],
                ],
                'weekends' => true
            ],
            'kimai.dashboard' => [],
            'kimai.widgets' => [],
            'kimai.invoice.documents' => [
                'var/invoices/',
                'templates/invoice/renderer/',
            ],
            'kimai.defaults' => [
                'customer' => [
                    'timezone' => null,
                    'country' => 'DE',
                    'currency' => 'EUR',
                ],
                'user' => [
                    'timezone' => null,
                    'language' => 'en',
                    'theme' => null,
                    'currency' => 'EUR',
                ]
            ],
            'kimai.theme' => [
                'active_warning' => 3,
                'box_color' => 'green',
                'select_type' => 'selectpicker',
                'show_about' => true,
                'chart' => [
                    'background_color' => 'rgba(0,115,183,0.7)',
                    'border_color' => '#3b8bba',
                    'grid_color' => 'rgba(0,0,0,.05)',
                    'height' => '200'
                ],
                'branding' => [
                    'logo' => null,
                    'mini' => null,
                    'company' => null,
                    'title' => null,
                    'translation' => null,
                ],
                'auto_reload_datatable' => false,
                'autocomplete_chars' => 3,
            ],
            'kimai.theme.select_type' => 'selectpicker',
            'kimai.theme.show_about' => true,
            'kimai.fosuser' => [
                'registration' => true,
                'password_reset' => true,
            ],
            'kimai.timesheet' => [
                'mode' => 'default',
                'markdown_content' => false,
                'rounding' => [
                    'default' => [
                        'begin' => 1,
                        'end' => 1,
                        'duration' => 0,
                        'mode' => 'default',
                        'days' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday'
                    ]
                ],
                'rates' => [],
                'active_entries' => [
                    'soft_limit' => 1,
                    'hard_limit' => 1,
                ],
                'rules' => [
                    'allow_future_times' => true,
                ],
                'default_begin' => 'now',
            ],
            'kimai.timesheet.rates' => [],
            'kimai.timesheet.rounding' => [
                    'default' => [
                        'begin' => 1,
                        'end' => 1,
                        'duration' => 0,
                        'mode' => 'default',
                        'days' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday'
                    ]
            ],
            'kimai.ldap' => [
                'user' => [
                    'baseDn' => null,
                    'filter' => '',
                    'usernameAttribute' => 'uid',
                    'attributesFilter' => '(objectClass=*)',
                    'attributes' => [],
                ],
                'role' => [
                    'baseDn' => null,
                    'nameAttribute' => 'cn',
                    'userDnAttribute' => 'member',
                    'groups' => [],
                    'usernameAttribute' => 'dn',
                ],
                'connection' => [
                    'baseDn' => null,
                    'host' => null,
                    'port' => 389,
                    'useStartTls' => false,
                    'useSsl' => false,
                    'bindRequiresDn' => true,
                    'accountFilterFormat' => '(&(uid=%s))',
                ],
            ],
            'kimai.permissions' => [
                'ROLE_USER' => [],
                'ROLE_TEAMLEAD' => [],
                'ROLE_ADMIN' => [],
                'ROLE_SUPER_ADMIN' => [],
            ],
            'kimai.i18n_domains' => []
        ];

        // nasty parameter, should be removed!!!
        $this->assertTrue($container->hasParameter('kimai.config'));

        foreach ($expected as $key => $value) {
            $this->assertTrue($container->hasParameter($key), 'Could not find config: ' . $key);
            $this->assertEquals($value, $container->getParameter($key), 'Invalid config: ' . $key);
        }
    }

    public function testAdditionalAuthenticationRoutes()
    {
        $minConfig = $this->getMinConfig();
        $adminLte = [
            'adminlte_registration' => 'foo',
            'adminlte_password_reset' => 'bar',
        ];

        $container = $this->getContainer();
        $container->setParameter('admin_lte_theme.routes', $adminLte);

        $this->extension->load($minConfig, $container);

        $this->assertEquals(
            [
                'adminlte_registration' => 'foo',
                'adminlte_password_reset' => 'bar',
            ],
            $container->getParameter('admin_lte_theme.routes')
        );
    }

    public function testDeactivateAdditionalAuthenticationRoutes()
    {
        $minConfig = $this->getMinConfig();
        $minConfig['kimai']['user'] = [
            'registration' => false,
            'password_reset' => false,
        ];
        $adminLte = [
            'adminlte_registration' => 'foo',
            'adminlte_password_reset' => 'bar',
        ];

        $container = $this->getContainer();
        $container->setParameter('admin_lte_theme.routes', $adminLte);

        $this->extension->load($minConfig, $container);

        $this->assertEquals(
            [
                'adminlte_registration' => null,
                'adminlte_password_reset' => null,
            ],
            $container->getParameter('admin_lte_theme.routes')
        );
    }

    /**
     * @expectedDeprecation Configuration "kimai.timesheet.duration_only" is deprecated, please remove it
     * @group legacy
     */
    public function testDurationOnlyDeprecationIsTriggered()
    {
        $this->expectException(Notice::class);
        $this->expectExceptionMessage('Found ambiguous configuration: remove "kimai.timesheet.duration_only" and set "kimai.timesheet.mode" instead.');

        $minConfig = $this->getMinConfig();
        $minConfig['kimai']['timesheet']['duration_only'] = true;
        $minConfig['kimai']['timesheet']['mode'] = 'punch';

        $this->extension->load($minConfig, $container = $this->getContainer());
    }

    public function testLdapDefaultValues()
    {
        $minConfig = $this->getMinConfig();
        $minConfig['kimai']['ldap'] = [
            'connection' => [
                'host' => '9.9.9.9',
                'baseDn' => 'lkhiuzhkj',
                'accountFilterFormat' => '(uid=%s)'
            ],
            'user' => [
                'baseDn' => '123123123',
                'usernameAttribute' => 'xxx',
                'filter' => '(..........)'
            ],
        ];

        $this->extension->load($minConfig, $container = $this->getContainer());

        $ldapConfig = $container->getParameter('kimai.ldap');
        $this->assertEquals('123123123', $ldapConfig['user']['baseDn']);
        $this->assertEquals('(..........)', $ldapConfig['user']['filter']);
        $this->assertEquals('xxx', $ldapConfig['user']['usernameAttribute']);
        $this->assertEquals('lkhiuzhkj', $ldapConfig['connection']['baseDn']);
        $this->assertEquals('(uid=%s)', $ldapConfig['connection']['accountFilterFormat']);
    }

    public function testLdapFallbackValue()
    {
        $minConfig = $this->getMinConfig();
        $minConfig['kimai']['ldap'] = [
            'connection' => [
                'host' => '9.9.9.9',
            ],
            'user' => [
                'baseDn' => '123123123',
                'usernameAttribute' => 'xxx',
            ],
        ];

        $this->extension->load($minConfig, $container = $this->getContainer());

        $ldapConfig = $container->getParameter('kimai.ldap');
        $this->assertEquals('123123123', $ldapConfig['user']['baseDn']);
        $this->assertEquals('xxx', $ldapConfig['user']['usernameAttribute']);
        $this->assertEquals('123123123', $ldapConfig['connection']['baseDn']);
        $this->assertEquals('(&(xxx=%s))', $ldapConfig['connection']['accountFilterFormat']);
        $this->assertEquals('', $ldapConfig['user']['filter']);
    }

    public function testLdapMoreFallbackValue()
    {
        $minConfig = $this->getMinConfig();
        $minConfig['kimai']['ldap'] = [
            'connection' => [
                'host' => '9.9.9.9',
                'baseDn' => '7658765',
            ],
            'user' => [
                'baseDn' => '123123123',
                'usernameAttribute' => 'zzzz',
                'filter' => '(&(objectClass=inetOrgPerson))',
            ],
        ];

        $this->extension->load($minConfig, $container = $this->getContainer());

        $ldapConfig = $container->getParameter('kimai.ldap');
        $this->assertEquals('123123123', $ldapConfig['user']['baseDn']);
        $this->assertEquals('zzzz', $ldapConfig['user']['usernameAttribute']);
        $this->assertEquals('7658765', $ldapConfig['connection']['baseDn']);
        $this->assertEquals('(&(&(objectClass=inetOrgPerson))(zzzz=%s))', $ldapConfig['connection']['accountFilterFormat']);
        $this->assertEquals('(&(objectClass=inetOrgPerson))', $ldapConfig['user']['filter']);
    }

    public function testTranslationOverwritesEmpty()
    {
        $minConfig = $this->getMinConfig();
        $this->extension->load($minConfig, $container = $this->getContainer());

        $config = $container->getParameter('kimai.i18n_domains');
        $this->assertEquals([], $config);
    }

    public function testTranslationOverwrites()
    {
        $minConfig = $this->getMinConfig();
        $minConfig['kimai']['industry'] = [
            'translation' => 'xxxx',
        ];
        $minConfig['kimai']['theme'] = [
            'branding' => [
                'translation' => 'yyyy',
            ]
        ];

        $this->extension->load($minConfig, $container = $this->getContainer());

        $config = $container->getParameter('kimai.i18n_domains');
        // oder is important, theme/installation specific translations win
        $this->assertEquals(['yyyy', 'xxxx'], $config);
    }

    public function testInvalidConfiguration()
    {
        $this->expectException(Notice::class);
        $this->expectExceptionMessage('Found invalid "kimai" configuration: The child node "data_dir" at path "kimai" must be configured.');

        $this->extension->load([], $container = $this->getContainer());
    }

    public function testWithBundleConfiguration()
    {
        $bundleConfig = [
            'foo-bundle' => ['test'],
        ];
        $container = $this->getContainer();
        $container->setParameter('kimai.bundles.config', $bundleConfig);

        $this->extension->load($this->getMinConfig(), $container);
        $config = $container->getParameter('kimai.config');
        self::assertEquals(['test'], $config['foo-bundle']);
    }

    public function testWithBundleConfigurationFailsOnDuplicatedKey()
    {
        $this->expectException(Notice::class);
        $this->expectExceptionMessage('Invalid bundle configuration "timesheet" found, skipping');

        $bundleConfig = [
            'timesheet' => ['test'],
        ];
        $container = $this->getContainer();
        $container->setParameter('kimai.bundles.config', $bundleConfig);

        $this->extension->load($this->getMinConfig(), $container);
    }

    public function testWithBundleConfigurationFailsOnNonArray()
    {
        $this->expectException(Notice::class);
        $this->expectExceptionMessage('Invalid bundle configuration found, skipping all bundle configuration');

        $container = $this->getContainer();
        $container->setParameter('kimai.bundles.config', 'asdasd');

        $this->extension->load($this->getMinConfig(), $container);
    }

    // TODO test permissions
}
