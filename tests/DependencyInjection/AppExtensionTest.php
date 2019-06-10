<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\AppExtension;
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

    public function setUp()
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

        return $container;
    }

    protected function getMinConfig()
    {
        return [
            'kimai' => [
                'data_dir' => '/tmp/',
                'plugin_dir' => '/tmp/',
                'timesheet' => [],
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
            'kimai.languages' => [],
            'kimai.calendar' => [
                'week_numbers' => true,
                'day_limit' => 4,
                'businessHours' => [
                    'days' => [1, 2, 3, 4, 5],
                    'begin' => '08:00',
                    'end' => '20:00',
                ],
                'visibleHours' => [
                    'begin' => '00:00',
                    'end' => '24:00',
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
                    'timezone' => 'Europe/Berlin',
                    'country' => 'DE',
                    'currency' => 'EUR',
                ]
            ],

            'kimai.theme' => [
                'active_warning' => 3,
                'box_color' => 'green',
                'select_type' => null,
                'show_about' => true,
            ],
            'kimai.theme.select_type' => null,
            'kimai.theme.show_about' => true,

            'kimai.fosuser' => [
                'registration' => true,
                'password_reset' => true,
            ],

            'kimai.timesheet' => [
                'mode' => 'default',
                'markdown_content' => false,
                'rounding' => [],
                'rates' => [],
                'active_entries' => [
                    'soft_limit' => 1,
                    'hard_limit' => 1,
                ],
                'rules' => [
                    'allow_future_times' => true,
                ],
            ],
            'kimai.timesheet.rates' => [],
            'kimai.timesheet.rounding' => [],
            'kimai.ldap' => [
                'user' => [
                    'baseDn' => null,
                    'filter' => '',
                    'usernameAttribute' => 'uid',
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
     * @expectedException \PHPUnit\Framework\Error\Notice
     * @expectedExceptionMessage Found ambiguous configuration. Please remove "kimai.timesheet.duration_only" and set "kimai.timesheet.mode" instead.
     * @expectedDeprecation Configuration "kimai.timesheet.duration_only" is deprecated, please remove it
     * @group legacy
     */
    public function testDurationOnlyDeprecationIsTriggered()
    {
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

    /**
     * @expectedException \PHPUnit\Framework\Error\Notice
     * @expectedExceptionMessage Found invalid "kimai" configuration: The child node "data_dir" at path "kimai" must be configured.
     */
    public function testInvalidConfiguration()
    {
        $this->extension->load([], $container = $this->getContainer());
    }

    // TODO test permissions
}
