<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\AppExtension;
use App\Tests\Mocks\SystemConfigurationFactory;
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
        $container->setParameter('kimai_locales', ['de', 'en', 'he', 'tr', 'zh_CN']);
        $container->setParameter('kernel.project_dir', realpath(__DIR__ . '/../../'));
        $container->setParameter('security.role_hierarchy.roles', [
            'ROLE_TEAMLEAD' => ['ROLE_USER'],
            'ROLE_ADMIN' => ['ROLE_TEAMLEAD'],
            'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN'],
        ]);

        return $container;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getMinConfig(): array
    {
        return [
            'kimai' => [
                'data_dir' => '/tmp/',
                'timesheet' => [],
                'saml' => [
                    'connection' => []
                ]
            ]
        ];
    }

    public function testDefaultValues(): void
    {
        $minConfig = $this->getMinConfig();

        $this->extension->load($minConfig, $container = $this->getContainer());

        // these value list represents the default values with unmerged kimai.yaml
        $expected = [
            'kimai.data_dir' => '/tmp/',
            'kimai.languages' => [
                'en' => [
                    'date' => 'M/d/yy',
                    'time' => 'h:mm a',
                    'rtl' => false,
                    'translation' => true,
                ],
                'de' => [
                    'date' => 'dd.MM.yy',
                    'time' => 'HH:mm',
                    'rtl' => false,
                    'translation' => true,
                ],
                'he' => [
                    'date' => 'd.M.y',
                    'time' => 'H:mm',
                    'rtl' => true,
                    'translation' => true,
                ],
                'tr' => [
                    'date' => 'd.MM.y',
                    'time' => 'HH:mm',
                    'rtl' => false,
                    'translation' => true,
                ],
                'zh_CN' => [
                    'date' => 'y/M/d',
                    'time' => 'HH:mm',
                    'rtl' => false,
                    'translation' => true,
                ],
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
            'ldap' => [
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
            ],
        ];

        self::assertTrue($container->hasParameter('kimai.config'));

        /** @var array<string, mixed> $config */
        $config = $container->getParameter('kimai.config');

        foreach (SystemConfigurationFactory::flatten($kimaiLdap) as $key => $value) {
            self::assertArrayHasKey($key, $config);
            self::assertEquals($value, $config[$key]);
        }

        foreach ($expected as $key => $value) {
            self::assertTrue($container->hasParameter($key), 'Could not find config: ' . $key);
            self::assertEquals($value, $container->getParameter($key), 'Invalid config: ' . $key);
        }
    }

    public function testLdapDefaultValues(): void
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

        self::assertIsArray($config);
        self::assertArrayHasKey('ldap.user.baseDn', $config);
        self::assertArrayHasKey('ldap.user.filter', $config);
        self::assertArrayHasKey('ldap.user.usernameAttribute', $config);
        self::assertArrayHasKey('ldap.connection.baseDn', $config);
        self::assertArrayHasKey('ldap.connection.accountFilterFormat', $config);
        self::assertEquals('123123123', $config['ldap.user.baseDn']);
        self::assertEquals('(..........)', $config['ldap.user.filter']);
        self::assertEquals('xxx', $config['ldap.user.usernameAttribute']);
        self::assertEquals('lkhiuzhkj', $config['ldap.connection.baseDn']);
        self::assertEquals('(uid=%s)', $config['ldap.connection.accountFilterFormat']);
    }

    public function testLdapFallbackValue(): void
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

        self::assertIsArray($config);
        self::assertEquals('123123123', $config['ldap.user.baseDn']);
        self::assertEquals('xxx', $config['ldap.user.usernameAttribute']);
        self::assertEquals('123123123', $config['ldap.connection.baseDn']);
        self::assertEquals('(&(xxx=%s))', $config['ldap.connection.accountFilterFormat']);
        self::assertEquals('', $config['ldap.user.filter']);
    }

    public function testLdapMoreFallbackValue(): void
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
        self::assertIsArray($config);

        self::assertEquals('123123123', $config['ldap.user.baseDn']);
        self::assertEquals('zzzz', $config['ldap.user.usernameAttribute']);
        self::assertEquals('7658765', $config['ldap.connection.baseDn']);
        self::assertEquals('(&(&(objectClass=inetOrgPerson))(zzzz=%s))', $config['ldap.connection.accountFilterFormat']);
        self::assertEquals('(&(objectClass=inetOrgPerson))', $config['ldap.user.filter']);
    }

    public function testWithBundleConfiguration(): void
    {
        $bundleConfig = [
            'foo-bundle' => [
                'bar' => 'test'
            ],
        ];
        $container = $this->getContainer();
        $container->setParameter('kimai.bundles.config', $bundleConfig);

        $this->extension->load($this->getMinConfig(), $container);
        $config = $container->getParameter('kimai.config');
        self::assertEquals('test', $config['foo-bundle.bar']);
    }

    public function testWithBundleConfigurationFailsOnDuplicatedKey(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid bundle configuration "timesheet" found, skipping');

        $bundleConfig = [
            'timesheet' => ['test'],
        ];
        $container = $this->getContainer();
        $container->setParameter('kimai.bundles.config', $bundleConfig);

        $this->extension->load($this->getMinConfig(), $container);
    }

    public function testWithBundleConfigurationFailsOnNonArray(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid bundle configuration found, skipping all bundle configuration');

        $container = $this->getContainer();
        $container->setParameter('kimai.bundles.config', 'asdasd');

        $this->extension->load($this->getMinConfig(), $container);
    }

    // TODO test permissions
}
