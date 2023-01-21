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
    protected function getMinConfig($dataDir = '/tmp/')
    {
        return [
            'data_dir' => $dataDir,
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

    public function testValidateLdapConfigUserBaseDn()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.ldap": The "ldap.user.baseDn" config must be set if LDAP is activated.');

        $config = $this->getMinConfig();
        $config['ldap'] = [
            'activate' => true,
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

    public function testValidateCalendarDragDropMaxEntries()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "kimai.calendar.dragdrop_amount": The dragdrop_amount must be between 0 and 20');

        $config = $this->getMinConfig();
        $config['calendar'] = [
            'dragdrop_amount' => 50,
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
            'activate' => false,
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
                    'hard_limit' => 1,
                ],
                'rules' => [
                    'allow_future_times' => true,
                    'allow_zero_duration' => true,
                    'allow_overlapping_records' => true,
                    'lockdown_period_start' => null,
                    'lockdown_period_end' => null,
                    'lockdown_grace_period' => null,
                    'allow_overbooking_budget' => true,
                    'lockdown_period_timezone' => null,
                    'break_warning_duration' => 0,
                    'long_running_duration' => 480,
                    'require_activity' => true,
                ],
                'duration_increment' => 15,
                'time_increment' => 15,
            ],
            'user' => [
                'registration' => false,
                'password_reset' => true,
                'login' => true,
                'password_reset_retry_ttl' => 7200,
                'password_reset_token_ttl' => 86400,
            ],
            'invoice' => [
                'documents' => [
                ],
                'defaults' => [
                    0 => 'var/invoices/',
                    1 => 'templates/invoice/renderer/',
                ],
                'number_format' => '{Y}/{cy,3}',
                'upload_twig' => true,
            ],
            'export' => [
                'documents' => [
                ],
                'defaults' => [
                    0 => 'var/export/',
                    1 => 'templates/export/renderer/',
                ],
            ],
            'calendar' => [
                'week_numbers' => true,
                'day_limit' => 4,
                'slot_duration' => '00:30:00',
                'businessHours' => [
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
                'dragdrop_amount' => 5,
                'dragdrop_data' => false,
                'title_pattern' => '{activity}',
            ],
            'theme' => [
                'show_about' => true,
                'branding' => [
                    'logo' => null,
                    'mini' => null,
                    'company' => null,
                    'title' => null,
                ],
                'colors_limited' => true,
                'color_choices' => 'Silver|#c0c0c0,Gray|#808080,Black|#000000,Maroon|#800000,Brown|#a52a2a,Red|#ff0000,Orange|#ffa500,Gold|#ffd700,Yellow|#ffff00,Peach|#ffdab9,Khaki|#f0e68c,Olive|#808000,Lime|#00ff00,Jelly|#9acd32,Green|#008000,Teal|#008080,Aqua|#00ffff,LightBlue|#add8e6,DeepSky|#00bfff,Dodger|#1e90ff,Blue|#0000ff,Navy|#000080,Purple|#800080,Fuchsia|#ff00ff,Violet|#ee82ee,Rose|#ffe4e1,Lavender|#E6E6FA',
                'avatar_url' => false,
            ],
            'defaults' => [
                'customer' => [
                    'timezone' => null,
                    'country' => 'DE',
                    'currency' => 'EUR',
                ],
                'user' => [
                    'timezone' => null,
                    'language' => 'en',
                    'theme' => 'default',
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
                'activate' => false,
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
                    'resetOnLogin' => true,
                    'attribute' => null,
                    'mapping' => []
                ],
                'mapping' => [],
                'connection' => [
                    'organization' => []
                ],
                'provider' => null,
            ],
            'company' => [
                'financial_year' => null,
            ],
            'quick_entry' => [
                'recent_activities' => 5,
                'recent_activity_weeks' => null,
                'minimum_rows' => 3,
            ],
            'project' => [
                'copy_teams_on_create' => false,
            ],
            'activity' => [
                'allow_inline_create' => false,
            ],
            'customer' => [
                'number_format' => '{cc,4}',
                'rules' => [
                    'allow_duplicate_number' => false,
                ],
            ],
        ];

        $this->assertConfig($this->getMinConfig(), $fullDefaultConfig);
    }
}
