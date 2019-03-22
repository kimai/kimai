<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\SystemConfiguration;
use App\Entity\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\SystemConfiguration
 * @covers \App\Configuration\StringAccessibleConfigTrait
 */
class SystemConfigurationTest extends TestCase
{
    /**
     * @param array $settings
     * @param array $loaderSettings
     * @return SystemConfiguration
     */
    protected function getSut(array $settings, array $loaderSettings = [])
    {
        $loader = new TestConfigLoader($loaderSettings);
        return new SystemConfiguration($loader, $settings);
    }

    protected function getDefaultSettings()
    {
        return [
            'timesheet' => [
                'rules' => [
                    'allow_future_times' => false,
                ],
                'duration_only' => true,
                'markdown_content' => false,
                'active_entries' => [
                    'hard_limit' => 99,
                    'soft_limit' => 15,
                ],
            ],
            'defaults' => [
                'customer' => [
                    'timezone' => 'Europe/London',
                    'currency' => 'GBP',
                    'country' => 'FR',
                ],
            ],
        ];
    }

    protected function getDefaultLoaderSettings()
    {
        return [
            (new Configuration())->setName('defaults.customer.timezone')->setValue('Russia/Moscov'),
            (new Configuration())->setName('defaults.customer.currency')->setValue('RUB'),
            (new Configuration())->setName('timesheet.rules.allow_future_times')->setValue('1'),
            (new Configuration())->setName('timesheet.duration_only')->setValue('0'),
            (new Configuration())->setName('timesheet.markdown_content')->setValue('1'),
            (new Configuration())->setName('timesheet.active_entries.hard_limit')->setValue('7'),
            (new Configuration())->setName('timesheet.active_entries.soft_limit')->setValue('3'),
        ];
    }

    public function testPrefix()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('kimai', $sut->getPrefix());
    }


    public function testDefaultWithoutLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('Europe/London', $sut->find('defaults.customer.timezone'));
        $this->assertEquals('GBP', $sut->find('defaults.customer.currency'));
        $this->assertEquals(false, $sut->find('timesheet.rules.allow_future_times'));
        $this->assertEquals(99, $sut->find('timesheet.active_entries.hard_limit'));
    }

    public function testDefaultWithLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        $this->assertEquals('Russia/Moscov', $sut->find('defaults.customer.timezone'));
        $this->assertEquals('RUB', $sut->find('defaults.customer.currency'));
        $this->assertEquals(true, $sut->find('timesheet.rules.allow_future_times'));
        $this->assertEquals(7, $sut->find('timesheet.active_entries.hard_limit'));
    }

    public function testDefaultWithMixedConfigs()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.duration_only')->setValue(''),
        ]);
        $this->assertEquals(false, $sut->find('timesheet.duration_only'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown config: foo
     */
    public function testUnknownConfigAreNotImportedAndFindingThemThrowsException()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.foo')->setValue('hello'),
        ]);
        $this->assertEquals('hello', $sut->find('foo'));
    }
}
