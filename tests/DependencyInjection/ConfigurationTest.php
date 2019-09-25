<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\DependencyInjection\Configuration
 */
class ConfigurationTest extends TestCase
{
    protected function getMinConfig($dataDir = '/tmp/', $pluginDir = '/tmp/')
    {
        return [
            'data_dir' => $dataDir,
            'plugin_dir' => $pluginDir,
            'timesheet' => [],
        ];
    }

    protected function assertConfig($inputConfig, $expectedConfig)
    {
        $finalizedConfig = $this->getCompiledConfig($inputConfig);

        self::assertEquals($expectedConfig, $finalizedConfig);
    }

    protected function getCompiledConfig($inputConfig)
    {
        $configuration = new Configuration();

        $node = $configuration->getConfigTreeBuilder()->buildTree();
        $normalizedConfig = $node->normalize($inputConfig);

        return $node->finalize($normalizedConfig);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.data_dir": Data directory does not exist
     */
    public function testValidateDataDir()
    {
        $this->assertConfig($this->getMinConfig('sdfsdfsdfds'), []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.plugin_dir": Plugin directory does not exist
     */
    public function testValidatePluginDir()
    {
        $this->assertConfig($this->getMinConfig('/tmp/', 'sdfsdfs'), []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.ldap": The "ldap.user.baseDn" config must be set if LDAP is activated.
     */
    public function testValidateLdapConfigUserBaseDn()
    {
        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'host' => 'foo'
            ],
        ];

        $this->assertConfig($config, []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.ldap.connection": The ldap.connection.useSsl and ldap.connection.useStartTls options are mutually exclusive.
     */
    public function testValidateLdapConfig()
    {
        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'useSsl' => true,
                'useStartTls' => true,
            ],
        ];

        $this->assertConfig($config, []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.ldap.user.filter": The ldap.user.filter must be enclosed by a matching number of parentheses "()" and must NOT contain a "%s" replacer
     */
    public function testValidateLdapFilterIncludingReplacer()
    {
        $config = $this->getMinConfig();
        $config['ldap'] = [
            'user' => [
                'filter' => '(sdfsdfsdf)(uid=%s)',
            ],
        ];

        $this->assertConfig($config, []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.ldap.user.filter": The ldap.user.filter must be enclosed by a matching number of parentheses "()" and must NOT contain a "%s" replacer
     */
    public function testValidateLdapFilterMissingStartingParenthesis()
    {
        $config = $this->getMinConfig();
        $config['ldap'] = [
            'user' => [
                'filter' => 's(dfsdfsdf)',
            ],
        ];

        $this->assertConfig($config, []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.ldap.user.filter": The ldap.user.filter must be enclosed by a matching number of parentheses "()" and must NOT contain a "%s" replacer
     */
    public function testValidateLdapFilterInvalidParenthesisCounter()
    {
        $config = $this->getMinConfig();
        $config['ldap'] = [
            'user' => [
                'filter' => '(dfsdfsdf))',
            ],
        ];

        $this->assertConfig($config, []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.ldap.connection.accountFilterFormat": The accountFilterFormat must be enclosed by a matching number of parentheses "()" and contain one "%s" replacer for the username
     */
    public function testValidateLdapAccountFilterFormatMissingUserAttributeReplacer()
    {
        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'accountFilterFormat' => '(sdfsdfsdf)(uid=xx)',
            ],
        ];

        $this->assertConfig($config, []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.ldap.connection.accountFilterFormat": The accountFilterFormat must be enclosed by a matching number of parentheses "()" and contain one "%s" replacer for the username
     */
    public function testValidateLdapAccountFilterFormatMissingStartingParenthesis()
    {
        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'accountFilterFormat' => 's(dfsdfsdf)',
            ],
        ];

        $this->assertConfig($config, []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.ldap.connection.accountFilterFormat": The accountFilterFormat must be enclosed by a matching number of parentheses "()" and contain one "%s" replacer for the username
     */
    public function testValidateLdapAccountFilterFormatInvalidParenthesisCounter()
    {
        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'accountFilterFormat' => '(dfsdfsdf))',
            ],
        ];

        $this->assertConfig($config, []);
    }

    public function testDefaultLdapSettings()
    {
        $finalizedConfig = $this->getCompiledConfig($this->getMinConfig());
        $expected = [
            'user' => [
                'baseDn' => '',
                'filter' => '',
                'usernameAttribute' => 'uid',
                'attributesFilter' => '(objectClass=*)',
                'attributes' => [],
            ],
            'role' => [
                'baseDn' => null,
                'usernameAttribute' => 'dn',
                'nameAttribute' => 'cn',
                'userDnAttribute' => 'member',
                'groups' => [],
            ],
            'connection' => [
                'host' => null,
                'port' => 389,
                'useStartTls' => false,
                'useSsl' => false,
                'bindRequiresDn' => true,
                'accountFilterFormat' => '',
            ]
        ];
        self::assertEquals($expected, $finalizedConfig['ldap']);
    }

    public function testFullDefaultConfig()
    {
        $fullDefaultConfig = [
            'data_dir' => '/tmp/',
            'plugin_dir' => '/tmp/',
            'timesheet' => [
                'default_begin' => 'now',
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
            'user' => [
                'registration' => true,
                'password_reset' => true,
            ],
            'invoice' => [
                'documents' => [
                    0 => 'var/invoices/',
                    1 => 'templates/invoice/renderer/',
                ],
            ],
            'languages' => [],
            'calendar' => [
                'week_numbers' => true,
                'day_limit' => 4,
                'slot_duration' => '00:30:00',
                'businessHours' => [
                    'days' => [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        3 => 4,
                        4 => 5,
                    ],
                    'begin' => '08:00',
                    'end' => '20:00',
                ],
                'visibleHours' => [
                    'begin' => '00:00',
                    'end' => '23:59',
                ],
                'google' => [
                    'api_key' => null,
                    'sources' => [
                    ],
                ],
                'weekends' => true,
            ],
            'theme' => [
                'active_warning' => 3,
                'box_color' => 'green',
                'select_type' => 'selectpicker',
                'auto_reload_datatable' => false,
                'show_about' => true,
                'chart' => [
                    'background_color' => 'rgba(0,115,183,0.7)',
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
                'autocomplete_chars' => 3,
            ],
            'industry' => [
                'translation' => null,
            ],
            'dashboard' => [],
            'widgets' => [],
            'defaults' => [
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
                ],
            ],
            'permissions' => [
                'sets' => [],
                'maps' => [],
                'roles' => [
                    'ROLE_USER' => [],
                    'ROLE_TEAMLEAD' => [],
                    'ROLE_ADMIN' => [],
                    'ROLE_SUPER_ADMIN' => [],
                ],
            ],
            'ldap' => [
                'connection' => [
                    'host' => null,
                    'port' => 389,
                    'useStartTls' => false,
                    'useSsl' => false,
                    'bindRequiresDn' => true,
                    'accountFilterFormat' => null,
                ],
                'user' => [
                    'baseDn' => null,
                    'filter' => '',
                    'attributesFilter' => '(objectClass=*)',
                    'usernameAttribute' => 'uid',
                    'attributes' => [],
                ],
                'role' => [
                    'baseDn' => null,
                    'usernameAttribute' => 'dn',
                    'nameAttribute' => 'cn',
                    'userDnAttribute' => 'member',
                    'groups' => [],
                ],
            ]
        ];

        $this->assertConfig($this->getMinConfig(), $fullDefaultConfig);
    }
}
