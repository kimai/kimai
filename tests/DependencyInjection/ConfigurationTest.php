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
}
