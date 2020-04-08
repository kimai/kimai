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
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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

    public function testValidateDataDir()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.data_dir": Data directory does not exist');

        $this->assertConfig($this->getMinConfig('sdfsdfsdfds'), []);
    }

    public function testValidatePluginDir()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.plugin_dir": Plugin directory does not exist');

        $this->assertConfig($this->getMinConfig('/tmp/', 'sdfsdfs'), []);
    }

    public function testValidateLdapConfigUserBaseDn()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.ldap": The "ldap.user.baseDn" config must be set if LDAP is activated.');

        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'host' => 'foo'
            ],
        ];

        $this->assertConfig($config, []);
    }

    public function testValidateLdapConfig()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.ldap.connection": The ldap.connection.useSsl and ldap.connection.useStartTls options are mutually exclusive.');

        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'useSsl' => true,
                'useStartTls' => true,
            ],
        ];

        $this->assertConfig($config, []);
    }

    public function testValidateLdapFilterIncludingReplacer()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.ldap.user.filter": The ldap.user.filter must be enclosed by a matching number of parentheses "()" and must NOT contain a "%s" replacer');

        $config = $this->getMinConfig();
        $config['ldap'] = [
            'user' => [
                'filter' => '(sdfsdfsdf)(uid=%s)',
            ],
        ];

        $this->assertConfig($config, []);
    }

    public function testValidateLdapFilterMissingStartingParenthesis()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.ldap.user.filter": The ldap.user.filter must be enclosed by a matching number of parentheses "()" and must NOT contain a "%s" replacer');

        $config = $this->getMinConfig();
        $config['ldap'] = [
            'user' => [
                'filter' => 's(dfsdfsdf)',
            ],
        ];

        $this->assertConfig($config, []);
    }

    public function testValidateLdapFilterInvalidParenthesisCounter()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.ldap.user.filter": The ldap.user.filter must be enclosed by a matching number of parentheses "()" and must NOT contain a "%s" replacer');

        $config = $this->getMinConfig();
        $config['ldap'] = [
            'user' => [
                'filter' => '(dfsdfsdf))',
            ],
        ];

        $this->assertConfig($config, []);
    }

    public function testValidateLdapAccountFilterFormatMissingUserAttributeReplacer()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.ldap.connection.accountFilterFormat": The accountFilterFormat must be enclosed by a matching number of parentheses "()" and contain one "%s" replacer for the username');

        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'accountFilterFormat' => '(sdfsdfsdf)(uid=xx)',
            ],
        ];

        $this->assertConfig($config, []);
    }

    public function testValidateLdapAccountFilterFormatMissingStartingParenthesis()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.ldap.connection.accountFilterFormat": The accountFilterFormat must be enclosed by a matching number of parentheses "()" and contain one "%s" replacer for the username');

        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'accountFilterFormat' => 's(dfsdfsdf)',
            ],
        ];

        $this->assertConfig($config, []);
    }

    public function testValidateLdapAccountFilterFormatInvalidParenthesisCounter()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.ldap.connection.accountFilterFormat": The accountFilterFormat must be enclosed by a matching number of parentheses "()" and contain one "%s" replacer for the username');

        $config = $this->getMinConfig();
        $config['ldap'] = [
            'connection' => [
                'accountFilterFormat' => '(dfsdfsdf))',
            ],
        ];

        $this->assertConfig($config, []);
    }

    public function testValidateSamlIsMissingMappingForEmail()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.saml": You need to configure a SAML mapping for the email attribute.');

        $config = $this->getMinConfig();
        $config['saml'] = [
            'activate' => true,
            'mapping' => [],
        ];

        $this->assertConfig($config, []);
    }

    public function testValidateSamlDoesNotTriggerOnDeactivatedSaml()
    {
        $finalizedConfig = $this->getCompiledConfig($this->getMinConfig());
        $config = $this->getMinConfig();
        $config['saml'] = [
            'activate' => false,
            'mapping' => [],
        ];

        $this->assertConfig($config, $finalizedConfig);
    }

    public function testValidateSamlDoesNotTriggerWhenEmailMappingExists()
    {
        $config = $this->getMinConfig();
        $config['saml'] = [
            'activate' => true,
            'mapping' => [
                ['saml' => 'email', 'kimai' => 'email']
            ],
        ];
        $finalizedConfig = $this->getCompiledConfig($config);

        $this->assertConfig($config, $finalizedConfig);
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
                'rounding' => [
                    'default' => [
                        'begin' => 1,
                        'end' => 1,
                        'duration' => 0,
                        'mode' => 'default',
                        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
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
            ],
            'user' => [
                'registration' => true,
                'password_reset' => true,
            ],
            'invoice' => [
                'documents' => [
                ],
                'defaults' => [
                    0 => 'var/invoices/',
                    1 => 'templates/invoice/renderer/',
                ],
                'simple_form' => true,
                'number_format' => '{Y}/{cy,3}',
            ],
            'export' => [
                'documents' => [
                ],
                'defaults' => [
                    0 => 'var/export/',
                    1 => 'templates/export/renderer/',
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
                'tags_create' => true,
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
            ],
            'saml' => [
                'activate' => false,
                'title' => 'Login with SAML',
                'roles' => [
                    'attribute' => null,
                    'mapping' => []
                ],
                'mapping' => [],
                'connection' => [
                    'organization' => []
                ],
            ]
        ];

        $this->assertConfig($this->getMinConfig(), $fullDefaultConfig);
    }
}
