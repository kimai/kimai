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
        $configuration = new Configuration();

        $node = $configuration->getConfigTreeBuilder()->buildTree();
        $normalizedConfig = $node->normalize($inputConfig);
        $finalizedConfig = $node->finalize($normalizedConfig);

        self::assertEquals($expectedConfig, $finalizedConfig);
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
            'active' => true,
            'connection' => [
                'host' => 'foo'
            ],
        ];

        $this->assertConfig($config, []);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "kimai.ldap": The "ldap.connection.host" config must be set if LDAP is activated.
     */
    public function testValidateLdapConfigConnectionHost()
    {
        $config = $this->getMinConfig();
        $config['ldap'] = [
            'active' => true,
            'connection' => [
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
            'active' => false,
            'connection' => [
                'useSsl' => true,
                'useStartTls' => true,
            ],
        ];

        $this->assertConfig($config, []);
    }
}
