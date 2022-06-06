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
    private ?AppExtension $extension = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new AppExtension();
    }

    private function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('app_locales', 'de|en|he|tr|zh_CN');
        $container->setParameter('kernel.project_dir', realpath(__DIR__ . '/../../'));

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

        // these value list represents the default values with unmerged kimai.yaml
        $expected = [
            'kimai.data_dir' => '/tmp/',
            'kimai.languages' => [
                'en' => [
                    'date_type' => 'dd. MM. yyyy',
                    'date' => 'A-m-d',
                    'date_time' => 'd.m. H:i',
                    'duration' => '%%h:%%m h',
                    'time' => 'H:i',
                    'rtl' => false,
                ],
                'de' => [
                    'date_type' => 'dd. MM. yyyy',
                    'date' => 'A-m-d',
                    'date_time' => 'd.m. H:i',
                    'duration' => '%%h:%%m h',
                    'time' => 'H:i',
                    'rtl' => false,
                ],
                'he' => [
                    'date_type' => 'dd. MM. yyyy',
                    'date' => 'A-m-d',
                    'date_time' => 'd.m. H:i',
                    'duration' => '%%h:%%m h',
                    'time' => 'H:i',
                    'rtl' => false,
                ],
                'tr' => [
                    // this value if pre-filled by the Configuration object, as "tr" is defined in the min config
                    // and the other languages (not defined in min config) are "only" copied during runtime from "en"
                    'date_type' => 'dd.MM.yyyy',
                    'date' => 'X-m-d',
                    'date_time' => 'd.m. H:i',
                    'duration' => '%%h:%%m h',
                    'time' => 'H:i',
                    'rtl' => false,
                ],
                'zh_CN' => [
                    'date_type' => 'dd. MM. yyyy',
                    'date' => 'A-m-d',
                    'date_time' => 'd.m. H:i',
                    'duration' => '%%h:%%m h',
                    'time' => 'H:i',
                    'rtl' => false,
                ],
            ],
            'kimai.dashboard' => [
                'PaginatedWorkingTimeChart',
                'UserTeams',
                'UserTeamProjects',
                'UserAmountToday',
                'UserAmountWeek',
                'UserAmountMonth',
                'UserAmountYear',
                'DurationToday',
                'DurationWeek',
                'DurationMonth',
                'DurationYear',
                'activeUsersToday',
                'activeUsersWeek',
                'activeUsersMonth',
                'activeUsersYear',
                'AmountToday',
                'AmountWeek',
                'AmountMonth',
                'AmountYear',
                'TotalsUser',
                'TotalsCustomer',
                'TotalsProject',
                'TotalsActivity',
            ],
            'kimai.invoice.documents' => [
                'var/invoices/',
                'templates/invoice/renderer/',
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
            'kimai.permissions' => [
                'ROLE_USER' => [],
                'ROLE_TEAMLEAD' => [],
                'ROLE_ADMIN' => [],
                'ROLE_SUPER_ADMIN' => [],
            ],
        ];

        $kimaiLdap = [
            'activate' => false,
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
        ];

        $this->assertTrue($container->hasParameter('kimai.config'));

        $config = $container->getParameter('kimai.config');
        $this->assertArrayHasKey('ldap', $config);
        $this->assertEquals($kimaiLdap, $config['ldap']);

        foreach ($expected as $key => $value) {
            $this->assertTrue($container->hasParameter($key), 'Could not find config: ' . $key);
            $this->assertEquals($value, $container->getParameter($key), 'Invalid config: ' . $key);
        }
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

        $config = $container->getParameter('kimai.config');
        $ldapConfig = $config['ldap'];

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

        $config = $container->getParameter('kimai.config');
        $ldapConfig = $config['ldap'];

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

        $config = $container->getParameter('kimai.config');
        $ldapConfig = $config['ldap'];

        $this->assertEquals('123123123', $ldapConfig['user']['baseDn']);
        $this->assertEquals('zzzz', $ldapConfig['user']['usernameAttribute']);
        $this->assertEquals('7658765', $ldapConfig['connection']['baseDn']);
        $this->assertEquals('(&(&(objectClass=inetOrgPerson))(zzzz=%s))', $ldapConfig['connection']['accountFilterFormat']);
        $this->assertEquals('(&(objectClass=inetOrgPerson))', $ldapConfig['user']['filter']);
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
        $this->expectNotice();
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
        $this->expectNotice();
        $this->expectExceptionMessage('Invalid bundle configuration found, skipping all bundle configuration');

        $container = $this->getContainer();
        $container->setParameter('kimai.bundles.config', 'asdasd');

        $this->extension->load($this->getMinConfig(), $container);
    }

    // TODO test permissions
}
